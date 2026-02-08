<?php

namespace App\Services;

use App\Models\User;
use App\Models\RiskPrediction;
use App\Models\RiskFactor;
use App\Models\HealthTrend;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnhancedRiskAssessmentService
{
  private $originalRiskService;
  protected $scopeService;

  public function __construct(RiskAssessmentService $originalRiskService, DataScopeService $scopeService)
  {
    $this->originalRiskService = $originalRiskService;
    $this->scopeService = $scopeService;
  }

  /**
   * Perform comprehensive AI-powered risk assessment
   */
  public function performAIRiskAssessment($userId, $options = []): RiskPrediction
  {
    $user = User::with(['antenatal', 'deliveries', 'postnatalRecords', 'clinicalNotes'])->find($userId);

    if (!$user) {
      throw new \Exception("User not found");
    }
    $officer = Auth::user();

    // 1. Get standard clinical assessment as baseline
    $standardAssessment = $this->originalRiskService->assessRisk($userId);

    if (!$standardAssessment) {
      throw new \Exception("Cannot perform AI assessment without clinical baseline data");
    }

    // 2. Get AI-optimized patient data
    $patientData = $user->getPatientDataForAI();

    // 3. Let AI analyze and enhance the standard assessment
    $aiAnalysis = $this->performAIAnalysis($standardAssessment, $patientData);

    // 4. Generate AI recommendations (enhanced from both sources)
    $recommendations = $this->generateEnhancedRecommendations($aiAnalysis, $patientData, $standardAssessment);

    // 5. Generate outcome predictions
    $predictions = $this->generateOutcomePredictions($aiAnalysis, $patientData);

    // 6. Store the AI-enhanced prediction
    $riskPrediction = $this->storePrediction($user, $aiAnalysis, $recommendations, $predictions, $options);

    // 7. Update trends and alerts
    $this->updateHealthTrends($officer->facility_id, $aiAnalysis);
    $this->checkForAlerts($riskPrediction);

    return $riskPrediction;
  }

  private function determineAIRiskLevel($finalScore, $clinicalScore, $aiEnhancement)
  {
    $baseLevel = match (true) {
      $finalScore >= 80 => 'critical',
      $finalScore >= 50 => 'high',
      $finalScore >= 25 => 'moderate',
      default => 'low'
    };

    if ($aiEnhancement > 20 && $baseLevel === 'moderate') {
      return 'high';
    }

    if ($aiEnhancement > 30 && $baseLevel === 'high') {
      return 'critical';
    }

    if ($aiEnhancement < 5 && $clinicalScore < 30 && $baseLevel === 'high') {
      return 'moderate';
    }

    return $baseLevel;
  }

  private function performAIAnalysis($standardAssessment, $patientData)
  {
    $baseScore = $standardAssessment['total_risk_score'];
    $clinicalRisks = $standardAssessment['identified_risks'];

    $detectedFactors = $this->detectRiskFactors($patientData);
    $additionalPatterns = $this->detectComplexPatterns($patientData);
    $allAIFactors = $detectedFactors->merge($additionalPatterns);

    $aiEnhancementScore = 0;
    $aiRiskDetails = [];
    $confidenceScores = [];

    foreach ($allAIFactors as $detection) {
      $factor = $detection['factor'];
      $confidence = $detection['confidence'];
      $weight = $detection['weight'];

      $alreadyDetected = collect($clinicalRisks)->contains(function ($risk) use ($factor) {
        return $risk['factor'] === ($factor->factor_code ?? $factor->factor_name);
      });

      if (!$alreadyDetected) {
        $adjustedWeight = $weight * $confidence;
        $aiEnhancementScore += $adjustedWeight;

        $aiRiskDetails[] = [
          'factor' => $factor->factor_code ?? $factor->factor_name,
          'description' => $factor->factor_name ?? $factor->factor_code,
          'weight' => $weight,
          'confidence' => $confidence,
          'adjusted_weight' => $adjustedWeight,
          'category' => 'ai_detected',
          'source' => 'ai_pattern_analysis'
        ];
      }

      $confidenceScores[] = $confidence;
    }

    $gestationalWeeks = $patientData['gestational_age_weeks'] ?? 0;
    $gestationalModifier = $this->getGestationalRiskModifier($gestationalWeeks);
    $finalScore = ($baseScore + $aiEnhancementScore) * $gestationalModifier;

    $standardizedClinicalRisks = collect($clinicalRisks)->map(function ($risk) {
      return array_merge($risk, [
        'category' => $risk['category'] ?? 'clinical_assessment',
        'source' => 'standard_clinical_assessment',
        'confidence' => 1.0
      ]);
    })->toArray();

    $allRisks = array_merge($standardizedClinicalRisks, $aiRiskDetails);
    $aiRiskLevel = $this->determineAIRiskLevel($finalScore, $baseScore, $aiEnhancementScore);
    $overallConfidence = count($confidenceScores) > 0 ? array_sum($confidenceScores) / count($confidenceScores) : 0.9;

    return [
      'total_risk_score' => round($finalScore),
      'risk_level' => $aiRiskLevel,
      'risk_percentage' => min(100, ($finalScore / 200) * 100),
      'identified_risks' => $allRisks,
      'baseline_clinical_score' => $baseScore,
      'ai_enhancement_score' => round($aiEnhancementScore),
      'overall_confidence' => round($overallConfidence * 100, 2),
      'gestational_modifier' => $gestationalModifier,
      'factors_count' => count($allRisks),
      'ai_analysis_summary' => [
        'clinical_factors_detected' => count($standardizedClinicalRisks),
        'ai_patterns_detected' => count($aiRiskDetails),
        'total_factors_analyzed' => count($allRisks),
        'confidence_level' => round($overallConfidence * 100, 2)
      ]
    ];
  }

  private function detectRiskFactors($patientData)
  {
    $gestationalWeeks = $patientData['gestational_age_weeks'];
    $detectedFactors = RiskFactor::detectFactorsInPatient($patientData, $gestationalWeeks);
    $additionalFactors = $this->detectComplexPatterns($patientData);
    return $detectedFactors->merge($additionalFactors);
  }

  private function detectComplexPatterns($patientData)
  {
    $patterns = [];

    if ($this->detectPregnancyProgressionRisk($patientData)) {
      $patterns[] = [
        'factor' => $this->createVirtualFactor('pregnancy_progression_risk', 'Abnormal Pregnancy Progression', 15),
        'confidence' => 0.9,
        'weight' => 15
      ];
    }

    if ($this->detectServiceUtilizationRisk($patientData)) {
      $patterns[] = [
        'factor' => $this->createVirtualFactor('poor_service_utilization', 'Poor Service Utilization', 10),
        'confidence' => 0.85,
        'weight' => 10
      ];
    }

    if ($this->detectRiskFactorInteractions($patientData)) {
      $patterns[] = [
        'factor' => $this->createVirtualFactor('risk_factor_interaction', 'Multiple Risk Factor Interaction', 20),
        'confidence' => 0.95,
        'weight' => 20
      ];
    }

    if ($this->detectHighRiskDeliveryPattern($patientData)) {
      $patterns[] = [
        'factor' => $this->createVirtualFactor('high_risk_delivery_pattern', 'High-Risk Delivery Pattern Detected', 25),
        'confidence' => 0.95,
        'weight' => 25
      ];
    }

    if ($this->detectEmergencyDeliveryPattern($patientData)) {
      $patterns[] = [
        'factor' => $this->createVirtualFactor('emergency_delivery_pattern', 'Emergency Delivery Pattern', 20),
        'confidence' => 0.9,
        'weight' => 20
      ];
    }

    if ($this->detectNeonatalComplicationsPattern($patientData)) {
      $patterns[] = [
        'factor' => $this->createVirtualFactor('neonatal_complications_pattern', 'Neonatal Complications Pattern', 30),
        'confidence' => 0.92,
        'weight' => 30
      ];
    }

    if ($this->detectBirthWeightConcernPattern($patientData)) {
      $patterns[] = [
        'factor' => $this->createVirtualFactor('birth_weight_concern_pattern', 'Birth Weight Concern Pattern', 18),
        'confidence' => 0.88,
        'weight' => 18
      ];
    }

    if ($this->detectInterventionEscalationPattern($patientData)) {
      $patterns[] = [
        'factor' => $this->createVirtualFactor('intervention_escalation_pattern', 'Multiple Medical Interventions', 22),
        'confidence' => 0.85,
        'weight' => 22
      ];
    }

    if ($this->detectMaternalOutcomeComplicationsPattern($patientData)) {
      $patterns[] = [
        'factor' => $this->createVirtualFactor('maternal_outcome_complications', 'Maternal Outcome Complications', 28),
        'confidence' => 0.93,
        'weight' => 28
      ];
    }

    return collect($patterns);
  }

  private function detectPregnancyProgressionRisk($data)
  {
    $gestationalWeeks = $data['gestational_age_weeks'] ?? 0;
    $visitCount = $data['antenatal_visits_count'] ?? 0;

    $expectedVisits = match (true) {
      $gestationalWeeks < 12 => 1,
      $gestationalWeeks < 20 => 2,
      $gestationalWeeks < 28 => 3,
      $gestationalWeeks < 36 => 5,
      default => 7
    };

    return $visitCount < ($expectedVisits * 0.7);
  }

  private function detectServiceUtilizationRisk($data)
  {
    $gestationalWeeks = $data['gestational_age_weeks'] ?? 0;
    $lastVisit = $data['last_antenatal_visit'];

    if (!$lastVisit || $gestationalWeeks < 12) {
      return false;
    }

    $daysSinceLastVisit = Carbon::parse($lastVisit)->diffInDays(Carbon::now());

    $riskThreshold = match (true) {
      $gestationalWeeks < 28 => 42,
      $gestationalWeeks < 36 => 28,
      default => 14
    };

    return $daysSinceLastVisit > $riskThreshold;
  }

  private function detectRiskFactorInteractions($data)
  {
    $riskCount = 0;

    if ($data['is_high_risk_age']) $riskCount++;
    if ($data['has_hypertension']) $riskCount++;
    if ($data['is_anemic']) $riskCount++;
    if ($data['has_sickle_cell']) $riskCount++;
    if ($data['heart_disease']) $riskCount++;
    if ($data['kidney_disease']) $riskCount++;
    if ($data['bleeding']) $riskCount++;

    return $riskCount >= 3;
  }

  private function detectHighRiskDeliveryPattern($data)
  {
    if (!isset($data['latest_delivery'])) {
      return false;
    }

    $delivery = $data['latest_delivery'];
    $riskFactors = 0;

    if ($delivery['mod'] === 'CS') $riskFactors++;
    if (isset($delivery['weight']) && $delivery['weight'] < 2.5) $riskFactors++;
    if (!empty($delivery['still_birth'])) $riskFactors++;
    if ($delivery['breathing'] === 'yes') $riskFactors++;
    if ($delivery['pre_term'] === 'yes') $riskFactors++;
    if ($delivery['toc'] === 'Unbooked') $riskFactors++;

    return $riskFactors >= 2;
  }

  private function detectEmergencyDeliveryPattern($data)
  {
    if (!isset($data['latest_delivery'])) {
      return false;
    }

    $delivery = $data['latest_delivery'];

    return ($delivery['seeking_care'] === 'less24' &&
      ($delivery['transportation'] === 'ambulance' || $delivery['mother_transportation'] === 'ambulance') &&
      $delivery['toc'] === 'Unbooked');
  }

  private function detectNeonatalComplicationsPattern($data)
  {
    if (!isset($data['latest_delivery'])) {
      return false;
    }

    $delivery = $data['latest_delivery'];
    $complications = 0;

    if ($delivery['breathing'] === 'yes') $complications++;
    if (isset($delivery['temperature']) && ($delivery['temperature'] < 36.0 || $delivery['temperature'] > 37.5)) $complications++;
    if (isset($delivery['weight']) && $delivery['weight'] < 1.5) $complications++;
    if ($delivery['newborn_care'] === 'no') $complications++;
    if ($delivery['baby_dead'] === 'yes') $complications++;

    return $complications >= 2;
  }

  private function detectBirthWeightConcernPattern($data)
  {
    if (!isset($data['latest_delivery']) || !isset($data['latest_delivery']['weight'])) {
      return false;
    }

    $weight = $data['latest_delivery']['weight'];
    $gestationalWeeks = $data['gestational_age_weeks'] ?? 0;

    if ($weight < 1.5) {
      return true;
    }

    if ($gestationalWeeks >= 37 && $weight < 2.5) {
      return true;
    }

    if ($gestationalWeeks < 32 && $weight > 3.0) {
      return true;
    }

    return false;
  }

  private function detectInterventionEscalationPattern($data)
  {
    if (!isset($data['latest_delivery'])) {
      return false;
    }

    $delivery = $data['latest_delivery'];
    $interventions = 0;

    if ($delivery['oxytocin'] === 'yes') $interventions++;
    if ($delivery['misoprostol'] === 'yes') $interventions++;
    if ($delivery['partograph'] === 'yes') $interventions++;
    if ($delivery['mod'] === 'CS' || $delivery['mod'] === 'AD') $interventions++;

    return $interventions >= 3;
  }

  private function detectMaternalOutcomeComplicationsPattern($data)
  {
    if (!isset($data['latest_delivery'])) {
      return false;
    }

    $delivery = $data['latest_delivery'];
    $complications = 0;

    if ($delivery['dead'] === 'yes') $complications += 3;
    if ($delivery['admitted'] === 'yes') $complications++;
    if ($delivery['referred_out'] === 'yes') $complications++;
    if ($delivery['pac'] === 'yes') $complications++;
    if ($delivery['abortion'] === 'yes') $complications++;

    return $complications >= 2;
  }

  private function createVirtualFactor($code, $name, $weight)
  {
    return (object)[
      'factor_code' => $code,
      'factor_name' => $name,
      'base_weight' => $weight,
      'severity_impact' => $weight >= 20 ? 'critical' : ($weight >= 15 ? 'high' : 'moderate'),
      'category' => 'ai_detected'
    ];
  }

  private function getGestationalRiskModifier($weeks)
  {
    return match (true) {
      $weeks < 12 => 1.1,
      $weeks < 28 => 1.0,
      $weeks < 37 => 1.2,
      $weeks >= 37 => 1.3,
      default => 1.0
    };
  }

  private function determineRiskLevel($score)
  {
    return match (true) {
      $score >= 80 => 'critical',
      $score >= 50 => 'high',
      $score >= 25 => 'moderate',
      default => 'low'
    };
  }

  private function generateEnhancedRecommendations($aiAnalysis, $patientData, $standardAssessment)
  {
    $recommendations = [];
    $riskLevel = $aiAnalysis['risk_level'];

    $clinicalRecommendations = $standardAssessment['recommendations'] ?? [];
    $recommendations = array_merge($recommendations, $clinicalRecommendations);

    $aiRecommendations = $this->getAISpecificRecommendations($aiAnalysis, $patientData);
    $recommendations = array_merge($recommendations, $aiRecommendations);

    $contextualRecommendations = $this->generateContextualRecommendations($aiAnalysis, $patientData);
    $recommendations = array_merge($recommendations, $contextualRecommendations);

    return array_unique($recommendations);
  }

  private function getAISpecificRecommendations($aiAnalysis, $patientData)
  {
    $recommendations = [];
    $enhancementScore = $aiAnalysis['ai_enhancement_score'];

    if ($enhancementScore > 20) {
      $recommendations[] = 'AI detected additional risk patterns - enhanced monitoring recommended';
      $recommendations[] = 'Consider multidisciplinary team consultation based on AI analysis';
    }

    if ($enhancementScore > 30) {
      $recommendations[] = 'AI analysis indicates high-complexity case - specialist referral advised';
      $recommendations[] = 'Implement intensive monitoring protocol based on AI risk assessment';
    }

    foreach ($aiAnalysis['identified_risks'] as $risk) {
      if ($risk['category'] === 'ai_detected') {
        $factor = $risk['factor'];
        $specificRecommendations = $this->getFactorSpecificRecommendations($risk, $patientData);
        $recommendations = array_merge($recommendations, $specificRecommendations);
      }
    }

    return $recommendations;
  }

  private function getFactorSpecificRecommendations($risk, $patientData)
  {
    $recommendations = [];
    $factor = $risk['factor'];

    $specificRecommendations = match ($factor) {
      'teen_pregnancy' => [
        'Enhanced nutritional support and counseling',
        'Social work referral for support services',
        'Close monitoring for growth and development',
        'Education on pregnancy and parenting'
      ],
      'advanced_maternal_age' => [
        'Genetic counseling and screening',
        'Enhanced fetal monitoring',
        'Consider additional ultrasounds',
        'Monitor for gestational diabetes'
      ],
      'hypertension' => [
        'Blood pressure monitoring twice weekly',
        'Low-sodium diet counseling',
        'Monitor for preeclampsia symptoms',
        'Consider antihypertensive medication'
      ],
      'anemia' => [
        'Iron supplementation',
        'Dietary counseling for iron-rich foods',
        'Monitor hemoglobin levels monthly',
        'Investigate underlying causes'
      ],
      'sickle_cell' => [
        'Hematology consultation',
        'Crisis prevention education',
        'Increased folic acid supplementation',
        'Pain management planning'
      ],
      'high_risk_delivery_pattern' => [
        'Prepare for high-risk delivery protocols',
        'Ensure pediatric team availability',
        'Consider delivery at higher-level facility',
        'Enhanced postpartum monitoring required'
      ],
      'emergency_delivery_pattern' => [
        'Immediate obstetric evaluation required',
        'Prepare emergency delivery team',
        'Consider urgent transfer if needed',
        'Continuous monitoring essential'
      ],
      'neonatal_complications_pattern' => [
        'NICU team on standby for delivery',
        'Immediate neonatal resuscitation preparedness',
        'Enhanced newborn monitoring protocols',
        'Pediatric consultation required'
      ],
      default => []
    };

    return $specificRecommendations;
  }

  private function generateContextualRecommendations($riskAnalysis, $patientData)
  {
    $recommendations = [];
    $gestationalWeeks = $patientData['gestational_age_weeks'] ?? 0;

    if ($gestationalWeeks >= 28 && $riskAnalysis['risk_level'] !== 'low') {
      $recommendations[] = 'Consider antenatal corticosteroids for fetal lung maturity';
    }

    if ($gestationalWeeks >= 34 && $riskAnalysis['risk_level'] === 'critical') {
      $recommendations[] = 'Prepare for potential early delivery';
      $recommendations[] = 'NICU consultation and preparation';
    }

    $visitCount = $patientData['antenatal_visits_count'] ?? 0;
    if ($visitCount < 4 && $gestationalWeeks > 20) {
      $recommendations[] = 'Improve antenatal visit attendance';
      $recommendations[] = 'Social support assessment needed';
    }

    if ($riskAnalysis['factors_count'] >= 3) {
      $recommendations[] = 'Multidisciplinary team approach required';
      $recommendations[] = 'Consider high-risk pregnancy clinic referral';
    }

    return $recommendations;
  }

  private function generateOutcomePredictions($riskAnalysis, $patientData)
  {
    $predictions = [];
    $riskLevel = $riskAnalysis['risk_level'];
    $gestationalWeeks = $patientData['gestational_age_weeks'] ?? 0;

    $predictions['delivery_outcomes'] = $this->predictDeliveryOutcomes($riskAnalysis, $patientData);
    $predictions['maternal_outcomes'] = $this->predictMaternalOutcomes($riskAnalysis, $patientData);
    $predictions['fetal_outcomes'] = $this->predictFetalOutcomes($riskAnalysis, $patientData);
    $predictions['timeline'] = $this->predictTimeline($riskAnalysis, $patientData);

    return $predictions;
  }

  private function predictDeliveryOutcomes($riskAnalysis, $patientData)
  {
    $riskLevel = $riskAnalysis['risk_level'];

    $probabilities = match ($riskLevel) {
      'critical' => [
        'cesarean_delivery' => 75,
        'preterm_delivery' => 40,
        'assisted_delivery' => 60,
        'normal_delivery' => 25
      ],
      'high' => [
        'cesarean_delivery' => 45,
        'preterm_delivery' => 25,
        'assisted_delivery' => 35,
        'normal_delivery' => 55
      ],
      'moderate' => [
        'cesarean_delivery' => 25,
        'preterm_delivery' => 15,
        'assisted_delivery' => 20,
        'normal_delivery' => 75
      ],
      'low' => [
        'cesarean_delivery' => 15,
        'preterm_delivery' => 8,
        'assisted_delivery' => 10,
        'normal_delivery' => 85
      ]
    };

    foreach ($riskAnalysis['identified_risks'] as $risk) {
      $probabilities = $this->adjustProbabilitiesForRisk($probabilities, $risk['factor']);
    }

    return $probabilities;
  }

  private function predictMaternalOutcomes($riskAnalysis, $patientData)
  {
    $riskLevel = $riskAnalysis['risk_level'];

    return match ($riskLevel) {
      'critical' => [
        'postpartum_hemorrhage' => 25,
        'preeclampsia' => 35,
        'infection' => 20,
        'prolonged_stay' => 60,
        'maternal_mortality' => 0.5
      ],
      'high' => [
        'postpartum_hemorrhage' => 15,
        'preeclampsia' => 20,
        'infection' => 12,
        'prolonged_stay' => 35,
        'maternal_mortality' => 0.2
      ],
      'moderate' => [
        'postpartum_hemorrhage' => 8,
        'preeclampsia' => 10,
        'infection' => 7,
        'prolonged_stay' => 20,
        'maternal_mortality' => 0.1
      ],
      'low' => [
        'postpartum_hemorrhage' => 5,
        'preeclampsia' => 4,
        'infection' => 3,
        'prolonged_stay' => 10,
        'maternal_mortality' => 0.02
      ]
    };
  }

  private function predictFetalOutcomes($riskAnalysis, $patientData)
  {
    $riskLevel = $riskAnalysis['risk_level'];

    return match ($riskLevel) {
      'critical' => [
        'low_birth_weight' => 40,
        'fetal_distress' => 35,
        'nicu_admission' => 50,
        'stillbirth' => 3,
        'neonatal_mortality' => 2
      ],
      'high' => [
        'low_birth_weight' => 25,
        'fetal_distress' => 20,
        'nicu_admission' => 30,
        'stillbirth' => 1.5,
        'neonatal_mortality' => 1
      ],
      'moderate' => [
        'low_birth_weight' => 15,
        'fetal_distress' => 12,
        'nicu_admission' => 15,
        'stillbirth' => 0.8,
        'neonatal_mortality' => 0.5
      ],
      'low' => [
        'low_birth_weight' => 8,
        'fetal_distress' => 5,
        'nicu_admission' => 8,
        'stillbirth' => 0.3,
        'neonatal_mortality' => 0.2
      ]
    };
  }

  private function predictTimeline($riskAnalysis, $patientData)
  {
    $gestationalWeeks = $patientData['gestational_age_weeks'] ?? 0;
    $riskLevel = $riskAnalysis['risk_level'];

    $expectedDeliveryWeek = match ($riskLevel) {
      'critical' => min(37, $gestationalWeeks + 2),
      'high' => min(39, $gestationalWeeks + 4),
      'moderate' => min(40, $gestationalWeeks + 6),
      'low' => 40
    };

    return [
      'expected_delivery_week' => $expectedDeliveryWeek,
      'next_assessment_due' => $this->calculateNextAssessmentDate($riskLevel),
      'critical_monitoring_period' => $this->getCriticalMonitoringPeriod($riskLevel, $gestationalWeeks)
    ];
  }

  private function adjustProbabilitiesForRisk($probabilities, $riskFactor)
  {
    $adjustments = match ($riskFactor) {
      'hypertension' => ['cesarean_delivery' => 15, 'preterm_delivery' => 10],
      'advanced_maternal_age' => ['cesarean_delivery' => 20, 'assisted_delivery' => 15],
      'sickle_cell' => ['cesarean_delivery' => 10, 'preterm_delivery' => 20],
      'teen_pregnancy' => ['preterm_delivery' => 15, 'low_birth_weight' => 25],
      'high_risk_delivery_pattern' => ['cesarean_delivery' => 25, 'nicu_admission' => 30],
      'emergency_delivery_pattern' => ['cesarean_delivery' => 35, 'preterm_delivery' => 25],
      'neonatal_complications_pattern' => ['nicu_admission' => 40, 'neonatal_mortality' => 15],
      default => []
    };

    foreach ($adjustments as $outcome => $adjustment) {
      if (isset($probabilities[$outcome])) {
        $probabilities[$outcome] = min(95, $probabilities[$outcome] + $adjustment);
      }
    }

    return $probabilities;
  }

  private function calculateNextAssessmentDate($riskLevel)
  {
    $days = match ($riskLevel) {
      'critical' => 1,
      'high' => 3,
      'moderate' => 7,
      'low' => 14
    };

    return Carbon::now()->addDays($days);
  }

  private function getCriticalMonitoringPeriod($riskLevel, $gestationalWeeks)
  {
    if ($riskLevel === 'critical') {
      return "Continuous monitoring required";
    }

    if ($gestationalWeeks >= 34 && $riskLevel === 'high') {
      return "Weekly monitoring until delivery";
    }

    return "Monitor according to risk level protocols";
  }

  private function storePrediction($user, $riskAnalysis, $recommendations, $predictions, $options)
  {
    $officer = Auth::user();

    return RiskPrediction::create([
      'user_id' => $user->id,
      'facility_id' => $user->facility_id ?? $officer->facility_id,
      'antenatal_id' => $user->antenatal?->id,
      'total_risk_score' => $riskAnalysis['total_risk_score'],
      'risk_level' => $riskAnalysis['risk_level'],
      'risk_percentage' => $riskAnalysis['risk_percentage'],
      'identified_risks' => $riskAnalysis['identified_risks'],
      'ai_recommendations' => $recommendations,
      'prediction_confidence' => [
        'overall_confidence' => $riskAnalysis['overall_confidence'],
        'gestational_modifier' => $riskAnalysis['gestational_modifier'],
        'factors_analyzed' => $riskAnalysis['factors_count'],
        'baseline_clinical_score' => $riskAnalysis['baseline_clinical_score'],
        'ai_enhancement_score' => $riskAnalysis['ai_enhancement_score']
      ],
      'gestational_age_weeks' => $user->getPatientDataForAI()['gestational_age_weeks'],
      'assessment_date' => Carbon::today(),
      'next_assessment_due' => $predictions['timeline']['next_assessment_due'],
      'predicted_outcomes' => $predictions,
      'prediction_timestamp' => Carbon::now(),
      'assessment_type' => $options['assessment_type'] ?? 'ai_routine',
      'model_version' => '1.0', //out first model, we will improve to better versions later
      'officer_name' => $officer->first_name . ' ' . $officer->last_name,
      'officer_role' => $officer->role,
      'officer_designation' => $officer->designation,
      'clinical_notes' => $options['clinical_notes'] ?? null
    ]);
  }

  private function updateHealthTrends($facilityId, $riskAnalysis)
  {
    try {
      $this->updateRiskDistributionTrend($facilityId, $riskAnalysis['risk_level']);
      $this->updateAIAccuracyTrend($facilityId, $riskAnalysis['overall_confidence']);
    } catch (\Exception $e) {
      Log::warning('Failed to update health trends: ' . $e->getMessage());
    }
  }

  private function updateRiskDistributionTrend($facilityId, $riskLevel)
  {
    $currentMonth = Carbon::now()->startOfMonth();

    $trend = HealthTrend::firstOrCreate([
      'facility_id' => $facilityId,
      'trend_type' => 'risk_distribution',
      'metric_name' => $riskLevel . '_risk_assessments',
      'period_start' => $currentMonth,
      'period_end' => $currentMonth->copy()->endOfMonth()
    ], [
      'trend_category' => 'clinical',
      'period_type' => 'monthly',
      'current_value' => 0,
      'sample_size' => 0,
      'alert_level' => 'none',
      'geographic_scope' => 'facility'
    ]);

    $trend->increment('current_value');
    $trend->increment('sample_size');

    $this->evaluateTrendAlert($trend);
  }

  private function updateAIAccuracyTrend($facilityId, $confidence)
  {
    $currentWeek = Carbon::now()->startOfWeek();

    $trend = HealthTrend::firstOrCreate([
      'facility_id' => $facilityId,
      'trend_type' => 'ai_performance',
      'metric_name' => 'assessment_confidence',
      'period_start' => $currentWeek,
      'period_end' => $currentWeek->copy()->endOfWeek()
    ], [
      'trend_category' => 'operational',
      'period_type' => 'weekly',
      'current_value' => $confidence,
      'sample_size' => 1,
      'alert_level' => 'none',
      'geographic_scope' => 'facility'
    ]);

    $newAverage = (($trend->current_value * ($trend->sample_size - 1)) + $confidence) / $trend->sample_size;
    $trend->update(['current_value' => round($newAverage, 2)]);

    if ($confidence < 70) {
      $trend->update(['alert_level' => 'warning']);
    }
  }

  private function evaluateTrendAlert($trend)
  {
    if ($trend->metric_name === 'critical_risk_assessments' && $trend->current_value > 10) {
      $trend->update([
        'alert_level' => 'urgent',
        'requires_intervention' => true,
        'recommended_actions' => [
          'Review critical risk cases',
          'Assess resource allocation',
          'Consider additional specialist support'
        ]
      ]);
    }
  }

  private function checkForAlerts($riskPrediction)
  {
    if ($riskPrediction->risk_level === 'critical') {
      $this->sendCriticalRiskAlert($riskPrediction);
    }

    if ($riskPrediction->risk_level === 'high' && $riskPrediction->gestational_age_weeks >= 34) {
      $this->sendHighRiskLatePregnancyAlert($riskPrediction);
    }
  }

  private function sendCriticalRiskAlert($riskPrediction)
  {
    Log::info("Critical risk alert for patient {$riskPrediction->user->DIN} at facility {$riskPrediction->facility_id}");
  }

  private function sendHighRiskLatePregnancyAlert($riskPrediction)
  {
    Log::info("High risk late pregnancy alert for patient {$riskPrediction->user->DIN}");
  }

  /**
   * Get comprehensive facility risk analytics - NOW WITH SCOPE SUPPORT
   */
  public function getFacilityRiskAnalytics($facilityId = null, $period = 30)
  {
    if ($facilityId) {
      $facilityIds = [$facilityId];
    } else {
      $scope = $this->scopeService->getUserScope();
      $facilityIds = $scope['facility_ids'];
    }

    if (empty($facilityIds)) {
      return $this->getEmptyAnalytics();
    }

    $predictions = RiskPrediction::whereIn('facility_id', $facilityIds)
      ->where('assessment_date', '>=', Carbon::now()->subDays($period))
      ->with('user')
      ->get();

    return [
      'summary' => $this->calculateRiskSummary($predictions),
      'trends' => $this->calculateRiskTrends($predictions),
      'ai_performance' => $this->calculateAIPerformance($predictions),
      'top_risk_factors' => $this->getTopRiskFactors($predictions),
      'outcome_accuracy' => $this->calculateOutcomeAccuracy($predictions),
      'facility_breakdown' => $this->getFacilityBreakdown($predictions, $facilityIds),
      'facility_count' => count($facilityIds)
    ];
  }

  private function getEmptyAnalytics()
  {
    return [
      'summary' => ['total_assessments' => 0, 'risk_distribution' => [], 'average_risk_score' => 0, 'average_confidence' => 0, 'pending_reassessments' => 0],
      'trends' => [],
      'ai_performance' => ['total_predictions' => 0, 'verified_outcomes' => 0, 'average_confidence' => 0, 'model_versions' => []],
      'top_risk_factors' => [],
      'outcome_accuracy' => ['accuracy' => null, 'total_verified' => 0],
      'facility_breakdown' => [],
      'facility_count' => 0
    ];
  }

  private function getFacilityBreakdown($predictions, $facilityIds)
  {
    if (count($facilityIds) === 1) {
      return null;
    }

    $breakdown = [];
    foreach ($facilityIds as $facilityId) {
      $facilityPredictions = $predictions->where('facility_id', $facilityId);
      $facility = \App\Models\Facility::find($facilityId);

      if ($facilityPredictions->count() > 0) {
        $breakdown[] = [
          'facility_id' => $facilityId,
          'facility_name' => $facility->name ?? 'Unknown',
          'total_assessments' => $facilityPredictions->count(),
          'high_risk_count' => $facilityPredictions->whereIn('risk_level', ['high', 'critical'])->count(),
          'average_risk_score' => round($facilityPredictions->avg('total_risk_score'), 1),
          'latest_assessment' => $facilityPredictions->max('assessment_date')
        ];
      }
    }

    return $breakdown;
  }

  private function calculateRiskSummary($predictions)
  {
    $total = $predictions->count();

    return [
      'total_assessments' => $total,
      'risk_distribution' => $predictions->groupBy('risk_level')->map->count(),
      'average_risk_score' => round($predictions->avg('total_risk_score'), 1),
      'average_confidence' => round($predictions->avg('prediction_confidence.overall_confidence'), 1),
      'pending_reassessments' => $predictions->where('next_assessment_due', '<=', Carbon::today())->count()
    ];
  }

  private function calculateRiskTrends($predictions)
  {
    $weeklyData = $predictions->groupBy(function ($prediction) {
      return Carbon::parse($prediction->assessment_date)->startOfWeek()->format('Y-m-d');
    });

    $trends = [];
    foreach ($weeklyData as $week => $weekPredictions) {
      $trends[] = [
        'week' => $week,
        'total' => $weekPredictions->count(),
        'high_risk' => $weekPredictions->whereIn('risk_level', ['high', 'critical'])->count(),
        'average_score' => round($weekPredictions->avg('total_risk_score'), 1)
      ];
    }

    return $trends;
  }

  private function calculateAIPerformance($predictions)
  {
    return [
      'total_predictions' => $predictions->count(),
      'verified_outcomes' => $predictions->where('outcome_verified', true)->count(),
      'average_confidence' => round($predictions->avg('prediction_confidence.overall_confidence'), 1),
      'model_versions' => $predictions->groupBy('model_version')->map->count()
    ];
  }

  private function getTopRiskFactors($predictions)
  {
    $allFactors = [];

    foreach ($predictions as $prediction) {
      if ($prediction->identified_risks) {
        foreach ($prediction->identified_risks as $risk) {
          $factor = $risk['factor'];
          $allFactors[$factor] = ($allFactors[$factor] ?? 0) + 1;
        }
      }
    }

    arsort($allFactors);
    return array_slice($allFactors, 0, 10, true);
  }

  private function calculateOutcomeAccuracy($predictions)
  {
    $verifiedPredictions = $predictions->where('outcome_verified', true);

    if ($verifiedPredictions->isEmpty()) {
      return ['accuracy' => null, 'total_verified' => 0];
    }

    $accurateCount = 0;
    foreach ($verifiedPredictions as $prediction) {
      if ($prediction->calculateAccuracy() > 70) {
        $accurateCount++;
      }
    }

    return [
      'accuracy' => round(($accurateCount / $verifiedPredictions->count()) * 100, 1),
      'total_verified' => $verifiedPredictions->count()
    ];
  }
}
