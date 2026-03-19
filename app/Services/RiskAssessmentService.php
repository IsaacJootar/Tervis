<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Delivery;
use App\Models\Antenatal;
use App\Models\ClinicalNote;
use App\Models\RiskPrediction;
use App\Models\DailyAttendance;
use App\Models\PostnatalRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RiskAssessmentService
{
  private const AGE_TEEN_THRESHOLD = 18;
  private const AGE_ADVANCED_THRESHOLD = 35;
  private const HEMOGLOBIN_ANEMIA_THRESHOLD = 11.0;
  private const BP_SYSTOLIC_HYPERTENSION = 140;
  private const BP_DIASTOLIC_HYPERTENSION = 90;
  private const BMI_LOW_THRESHOLD = 18.5;
  private const BMI_HIGH_THRESHOLD = 30.0;
  private const BIRTH_WEIGHT_EXTREMELY_LOW = 1.0;
  private const BIRTH_WEIGHT_VERY_LOW = 1.5;
  private const BIRTH_WEIGHT_LOW = 2.5;
  private const NEONATAL_TEMP_LOW = 36.0;
  private const NEONATAL_TEMP_HIGH = 37.5;
  private const MULTI_FACTOR_THRESHOLD = 2;
  private const CLINICAL_NOTES_FREQUENT_VISITS_THRESHOLD = 5;
  private const CLINICAL_NOTES_RECURRING_INFECTION_THRESHOLD = 3;
  private const RISK_SCORE_CRITICAL_MIN = 70;
  private const RISK_SCORE_HIGH_MIN = 40;
  private const RISK_SCORE_MODERATE_MIN = 20;
  private const BP_PATTERN = '/(\d{2,3})\s*[\/\-]\s*(\d{2,3})/';
  private const BP_SYSTOLIC_MIN = 70;
  private const BP_SYSTOLIC_MAX = 250;
  private const BP_DIASTOLIC_MIN = 40;
  private const BP_DIASTOLIC_MAX = 150;
  private const BMI_WEIGHT_MIN = 20;
  private const BMI_WEIGHT_MAX = 200;
  private const BMI_HEIGHT_CM_MIN = 100;
  private const BMI_HEIGHT_CM_MAX = 220;
  private const INFECTION_KEYWORDS = ['infection', 'fever', 'UTI', 'sepsis', 'antibiotics'];

  protected $scopeService;

  public function __construct(DataScopeService $scopeService)
  {
    $this->scopeService = $scopeService;
  }

  private $riskFactors = [
    // Age-related risks
    'teen_pregnancy' => ['weight' => 15, 'description' => 'Age below 18 years'],
    'advanced_maternal_age' => ['weight' => 12, 'description' => 'Age above 35 years'],

    // Medical history risks
    'heart_disease' => ['weight' => 25, 'description' => 'Pre-existing heart condition'],
    'kidney_disease' => ['weight' => 20, 'description' => 'Pre-existing kidney disease'],
    'chest_disease' => ['weight' => 15, 'description' => 'Respiratory conditions'],
    'diabetes' => ['weight' => 18, 'description' => 'Diabetes mellitus'],

    // Family history risks
    'family_hypertension' => ['weight' => 10, 'description' => 'Family history of hypertension'],
    'family_heart_disease' => ['weight' => 8, 'description' => 'Family history of heart disease'],
    'family_multiple_pregnancy' => ['weight' => 5, 'description' => 'Family history of multiple pregnancies'],

    // Current pregnancy risks
    'bleeding' => ['weight' => 20, 'description' => 'Bleeding during pregnancy'],
    'discharge' => ['weight' => 8, 'description' => 'Abnormal vaginal discharge'],
    'swelling_ankles' => ['weight' => 10, 'description' => 'Ankle swelling'],
    'urinary_symptoms' => ['weight' => 7, 'description' => 'Urinary tract symptoms'],

    // Clinical measurements
    'anemia' => ['weight' => 12, 'description' => 'Low hemoglobin levels'],
    'hypertension' => ['weight' => 18, 'description' => 'High blood pressure'],
    'sickle_cell' => ['weight' => 15, 'description' => 'Sickle cell trait/disease'],
    'low_bmi' => ['weight' => 8, 'description' => 'Underweight BMI'],
    'high_bmi' => ['weight' => 10, 'description' => 'Overweight/Obese BMI'],

    // Obstetric history
    'previous_cesarean' => ['weight' => 12, 'description' => 'Previous cesarean delivery'],
    'previous_complications' => ['weight' => 15, 'description' => 'Previous pregnancy complications'],
    'grand_multiparity' => ['weight' => 10, 'description' => 'More than 4 previous deliveries'],

    // Delivery risks
    'cesarean_delivery' => ['weight' => 10, 'description' => 'Current cesarean delivery'],
    'excessive_bleeding' => ['weight' => 20, 'description' => 'Excessive blood loss during delivery'],
    'delivery_complications' => ['weight' => 15, 'description' => 'Delivery complications noted'],
    'stillbirth' => ['weight' => 25, 'description' => 'Stillbirth occurrence'],
    'preterm_delivery' => ['weight' => 12, 'description' => 'Delivery before 37 weeks'],
    'emergency_cesarean' => ['weight' => 25, 'description' => 'Emergency cesarean delivery'],
    'very_low_birth_weight' => ['weight' => 30, 'description' => 'Birth weight below 1.5kg'],
    'extremely_low_birth_weight' => ['weight' => 35, 'description' => 'Birth weight below 1kg'],
    'fresh_stillbirth' => ['weight' => 35, 'description' => 'Fresh stillbirth'],
    'macerated_stillbirth' => ['weight' => 30, 'description' => 'Macerated stillbirth'],
    'neonatal_asphyxia' => ['weight' => 25, 'description' => 'Baby not breathing at birth'],
    'neonatal_hypothermia' => ['weight' => 15, 'description' => 'Low body temperature'],
    'neonatal_hyperthermia' => ['weight' => 12, 'description' => 'High body temperature'],
    'unbooked_delivery' => ['weight' => 18, 'description' => 'No antenatal care'],
    'delayed_care_seeking' => ['weight' => 12, 'description' => 'Sought care after 24 hours'],
    'emergency_transport' => ['weight' => 15, 'description' => 'Ambulance transport required'],
    'multiple_interventions' => ['weight' => 20, 'description' => 'Multiple medical interventions'],
    'delivery_complications_pattern' => ['weight' => 25, 'description' => 'Multiple delivery complications'],

    // Postnatal risks
    'postpartum_hypertension' => ['weight' => 15, 'description' => 'High blood pressure after delivery'],
    'postpartum_complications' => ['weight' => 12, 'description' => 'Postnatal health complications'],
    'breastfeeding_problems' => ['weight' => 5, 'description' => 'Difficulty with breastfeeding'],
    'postpartum_depression' => ['weight' => 10, 'description' => 'Mental health concerns postpartum'],

    // Clinical notes indicators
    'recurring_infections' => ['weight' => 8, 'description' => 'Multiple infection episodes'],
    'medication_complications' => ['weight' => 7, 'description' => 'Adverse drug reactions'],
    'frequent_hospital_visits' => ['weight' => 6, 'description' => 'Multiple emergency visits']
  ];

  public function assessRisk($userId)
  {
    $antenatal = Antenatal::where('user_id', $userId)->latest()->first();
    if (!$antenatal) return null;

    return $this->assessComprehensiveRisk($antenatal->user_id);
  }

  public function assessComprehensiveRisk($userId): array|null
  {
    $antenatal = Antenatal::where('user_id', $userId)->latest()->first();
    $deliveries = Delivery::where('patient_id', $userId)->get();
    $postnatalRecords = PostnatalRecord::where('patient_id', $userId)->get();
    $clinicalNotes = ClinicalNote::where('user_id', $userId)->get();
    $user = User::find($userId);

    if (!$antenatal || !$user) return null;

    $riskScore = 0;
    $identifiedRisks = [];
    $recommendations = [];

    // Antenatal risk assessment
    $antenatalRisk = $this->assessAntenatalRisk($antenatal);
    $riskScore += $antenatalRisk['score'];
    $identifiedRisks = array_merge($identifiedRisks, $antenatalRisk['risks']);
    $recommendations = array_merge($recommendations, $antenatalRisk['recommendations']);

    // Delivery risk assessment
    foreach ($deliveries as $delivery) {
      $deliveryRisk = $this->assessDeliveryRisk($delivery);
      $riskScore += $deliveryRisk['score'];
      $identifiedRisks = array_merge($identifiedRisks, $deliveryRisk['risks']);
      $recommendations = array_merge($recommendations, $deliveryRisk['recommendations']);
    }

    // Postnatal risk assessment
    foreach ($postnatalRecords as $postnatal) {
      $postnatalRisk = $this->assessPostnatalRisk($postnatal);
      $riskScore += $postnatalRisk['score'];
      $identifiedRisks = array_merge($identifiedRisks, $postnatalRisk['risks']);
      $recommendations = array_merge($recommendations, $postnatalRisk['recommendations']);
    }

    // Clinical notes risk assessment
    $clinicalRisk = $this->assessClinicalNotesRisk($clinicalNotes);
    $riskScore += $clinicalRisk['score'];
    $identifiedRisks = array_merge($identifiedRisks, $clinicalRisk['risks']);
    $recommendations = array_merge($recommendations, $clinicalRisk['recommendations']);

    $riskLevel = $this->determineRiskLevel($riskScore);
    $nextVisitRecommendation = $this->getNextVisitRecommendation($riskLevel, $antenatal->edd);

    return [
      'patient_id' => $antenatal->id,
      'patient_name' => $user->first_name . ' ' . $user->last_name,
      'din' => $user->DIN,
      'total_risk_score' => $riskScore,
      'risk_level' => $riskLevel,
      'risk_percentage' => min(100, ($riskScore / 300) * 100),
      'identified_risks' => $identifiedRisks,
      'recommendations' => array_unique($recommendations),
      'next_visit_recommendation' => $nextVisitRecommendation,
      'assessment_date' => Carbon::now(),
      'bmi' => $this->calculateBMI($antenatal->weight, $antenatal->height),
      'gestational_age' => $this->calculateGestationalAge($antenatal->lmp),
      'edd' => $antenatal->edd,
      'service_history' => [ // // i will add tt vacc. to this service category here in the array later, DONT forget
        'antenatal_visits' => DailyAttendance::where('user_id', $user->id)->count(),
        'delivery_count' => $deliveries->count(),
        'postnatal_visits' => $postnatalRecords->count(),
        'clinical_notes' => $clinicalNotes->count()
      ]
    ];
  }

  public function getFacilityRiskSummary($facilityId = null)
  {
    if ($facilityId) {
      $facilityIds = [$facilityId];
    } else {
      $scope = $this->scopeService->getUserScope();

      // Safety check - ensure facility_ids key exists
      if (!isset($scope['facility_ids']) || !is_array($scope['facility_ids'])) {
        Log::error('Invalid scope returned from getUserScope', ['scope' => $scope]);
        return $this->getEmptyRiskSummary();
      }

      $facilityIds = $scope['facility_ids'];
    }

    if (empty($facilityIds)) {
      Log::warning('No facility IDs found for user scope');
      return $this->getEmptyRiskSummary();
    }

    // Store count before any normalization
    $facilityCount = count($facilityIds);

    $predictions = RiskPrediction::whereIn('facility_id', $facilityIds)
      ->where('assessment_date', '>=', Carbon::now()->subDays(30))
      ->with('user')
      ->get();

    if ($predictions->isEmpty()) {
      return [
        'total_patients' => $this->getTotalPatientsCount($facilityIds),
        'assessed_patients' => 0,
        'low_risk' => 0,
        'moderate_risk' => 0,
        'high_risk' => 0,
        'critical_risk' => 0,
        'risk_distribution' => [
          'low' => 0,
          'moderate' => 0,
          'high' => 0,
          'critical' => 0
        ],
        'common_risk_factors' => [],
        'service_utilization' => $this->getServiceUtilizationSummary($facilityIds),
        'facility_count' => $facilityCount,
        'note' => 'No recent AI risk assessments found. Use "Assess with AI" buttons to generate predictions.'
      ];
    }

    return $this->calculateSummaryFromPredictions($predictions, $facilityIds, $facilityCount);
  }

  private function getEmptyRiskSummary()
  {
    return [
      'total_patients' => 0,
      'assessed_patients' => 0,
      'low_risk' => 0,
      'moderate_risk' => 0,
      'high_risk' => 0,
      'critical_risk' => 0,
      'risk_distribution' => [
        'low' => 0,
        'moderate' => 0,
        'high' => 0,
        'critical' => 0
      ],
      'common_risk_factors' => [],
      'service_utilization' => [
        'total_patients' => 0,
        'with_deliveries' => 0,
        'with_postnatal' => 0,
        'with_tetanus' => 0
      ],
      'facility_count' => 0,
      'note' => 'No data available for your scope'
    ];
  }

  private function calculateSummaryFromPredictions($predictions, $facilityIds, $facilityCount)
  {
    $riskCounts = $predictions->groupBy('risk_level')->map->count();
    $total = $predictions->count();
    $uniquePatients = $predictions->pluck('user_id')->unique()->count();

    // Count UNIQUE PATIENTS per risk factor
    $patientRiskFactors = [];

    foreach ($predictions->groupBy('user_id') as $userId => $userPredictions) {
      $latestPrediction = $userPredictions->sortByDesc('assessment_date')->first();

      if ($latestPrediction->identified_risks) {
        foreach ($latestPrediction->identified_risks as $risk) {
          $factor = $risk['factor'] ?? 'unknown';
          if (!isset($patientRiskFactors[$factor])) {
            $patientRiskFactors[$factor] = [];
          }
          $patientRiskFactors[$factor][$userId] = true;
        }
      }
    }

    // Convert to counts of unique patients
    $factorCounts = [];
    foreach ($patientRiskFactors as $factor => $patients) {
      $factorCounts[$factor] = count($patients);
    }
    arsort($factorCounts);

    return [
      'total_patients' => $uniquePatients,
      'assessed_patients' => $total,
      'low_risk' => $riskCounts->get('low', 0),
      'moderate_risk' => $riskCounts->get('moderate', 0),
      'high_risk' => $riskCounts->get('high', 0),
      'critical_risk' => $riskCounts->get('critical', 0),
      'risk_distribution' => [
        'low' => $total > 0 ? round(($riskCounts->get('low', 0) / $total) * 100, 1) : 0,
        'moderate' => $total > 0 ? round(($riskCounts->get('moderate', 0) / $total) * 100, 1) : 0,
        'high' => $total > 0 ? round(($riskCounts->get('high', 0) / $total) * 100, 1) : 0,
        'critical' => $total > 0 ? round(($riskCounts->get('critical', 0) / $total) * 100, 1) : 0,
      ],
      'common_risk_factors' => array_slice($factorCounts, 0, 5, true),
      'service_utilization' => $this->getServiceUtilizationSummary($facilityIds),
      'last_assessment_date' => $predictions->max('assessment_date'),
      'average_confidence' => $this->calculateAverageConfidence($predictions),
      'facility_count' => $facilityCount
    ];
  }

  private function getTotalPatientsCount($facilityIds)
  {
    return Antenatal::whereIn('registration_facility_id', $facilityIds)
      ->distinct('user_id')
      ->count('user_id');
  }

  private function calculateAverageConfidence($predictions)
  {
    $confidenceValues = [];

    foreach ($predictions as $prediction) {
      $confidence = $prediction->prediction_confidence;
      if (is_array($confidence) && isset($confidence['overall_confidence'])) {
        $confidenceValues[] = $confidence['overall_confidence'];
      }
    }

    return count($confidenceValues) > 0 ? round(array_sum($confidenceValues) / count($confidenceValues), 1) : 0;
  }

  private function getServiceUtilizationSummary($facilityIds)
  {
    $totalPatients = User::where('role', 'Patient')
      ->whereHas('antenatal', function ($q) use ($facilityIds) {
        $q->whereIn('registration_facility_id', $facilityIds);
      })->count();

    return [
      'total_patients' => $totalPatients,
      'with_deliveries' => User::where('role', 'Patient')
        ->whereHas('deliveries', function ($q) use ($facilityIds) {
          $q->whereIn('facility_id', $facilityIds);
        })->count(),
      'with_postnatal' => User::where('role', 'Patient')
        ->whereHas('postnatalRecords', function ($q) use ($facilityIds) {
          $q->whereIn('facility_id', $facilityIds);
        })->count(),
      'with_tetanus' => User::where('role', 'Patient')
        ->whereHas('tetanusVaccinations', function ($q) use ($facilityIds) {
          $q->whereIn('facility_id', $facilityIds);
        })->count()
    ];
  }

  private function assessAntenatalRisk($antenatal)
  {
    $riskScore = 0;
    $identifiedRisks = [];
    $recommendations = [];

    // Age-based assessment
    if ($antenatal->age < self::AGE_TEEN_THRESHOLD) {
      $riskScore += $this->riskFactors['teen_pregnancy']['weight'];
      $identifiedRisks[] = [
        'factor' => 'teen_pregnancy',
        'description' => $this->riskFactors['teen_pregnancy']['description'],
        'weight' => $this->riskFactors['teen_pregnancy']['weight']
      ];
      $recommendations[] = 'Close monitoring for growth and development';
      $recommendations[] = 'Nutritional counseling and support';
    }

    if ($antenatal->age > self::AGE_ADVANCED_THRESHOLD) {
      $riskScore += $this->riskFactors['advanced_maternal_age']['weight'];
      $identifiedRisks[] = [
        'factor' => 'advanced_maternal_age',
        'description' => $this->riskFactors['advanced_maternal_age']['description'],
        'weight' => $this->riskFactors['advanced_maternal_age']['weight']
      ];
      $recommendations[] = 'Genetic counseling and screening';
      $recommendations[] = 'Enhanced fetal monitoring';
    }

    // Medical history assessment
    if ($antenatal->heart_disease) {
      $riskScore += $this->riskFactors['heart_disease']['weight'];
      $identifiedRisks[] = [
        'factor' => 'heart_disease',
        'description' => $this->riskFactors['heart_disease']['description'],
        'weight' => $this->riskFactors['heart_disease']['weight']
      ];
      $recommendations[] = 'Cardiology consultation required';
      $recommendations[] = 'Cardiac monitoring throughout pregnancy';
    }

    if ($antenatal->kidney_disease) {
      $riskScore += $this->riskFactors['kidney_disease']['weight'];
      $identifiedRisks[] = [
        'factor' => 'kidney_disease',
        'description' => $this->riskFactors['kidney_disease']['description'],
        'weight' => $this->riskFactors['kidney_disease']['weight']
      ];
      $recommendations[] = 'Nephrology consultation';
      $recommendations[] = 'Regular kidney function monitoring';
    }

    // Family history assessment
    if ($antenatal->family_hypertension) {
      $riskScore += $this->riskFactors['family_hypertension']['weight'];
      $identifiedRisks[] = [
        'factor' => 'family_hypertension',
        'description' => $this->riskFactors['family_hypertension']['description'],
        'weight' => $this->riskFactors['family_hypertension']['weight']
      ];
      $recommendations[] = 'Regular blood pressure monitoring';
    }

    // Current pregnancy symptoms
    if ($antenatal->bleeding) {
      $riskScore += $this->riskFactors['bleeding']['weight'];
      $identifiedRisks[] = [
        'factor' => 'bleeding',
        'description' => $this->riskFactors['bleeding']['description'],
        'weight' => $this->riskFactors['bleeding']['weight']
      ];
      $recommendations[] = 'Immediate medical evaluation required';
      $recommendations[] = 'Bed rest may be necessary';
    }

    if ($antenatal->swelling_ankles) {
      $riskScore += $this->riskFactors['swelling_ankles']['weight'];
      $identifiedRisks[] = [
        'factor' => 'swelling_ankles',
        'description' => $this->riskFactors['swelling_ankles']['description'],
        'weight' => $this->riskFactors['swelling_ankles']['weight']
      ];
      $recommendations[] = 'Monitor for preeclampsia';
    }

    // Clinical measurements
    if ($antenatal->hemoglobin < self::HEMOGLOBIN_ANEMIA_THRESHOLD) {
      $riskScore += $this->riskFactors['anemia']['weight'];
      $identifiedRisks[] = [
        'factor' => 'anemia',
        'description' => $this->riskFactors['anemia']['description'],
        'weight' => $this->riskFactors['anemia']['weight']
      ];
      $recommendations[] = 'Iron supplementation';
      $recommendations[] = 'Nutritional counseling';
    }

    // Blood pressure assessment
    $bp = $this->parseBloodPressure($antenatal->blood_pressure);
    if ($bp && ($bp['systolic'] >= self::BP_SYSTOLIC_HYPERTENSION || $bp['diastolic'] >= self::BP_DIASTOLIC_HYPERTENSION)) {
      $riskScore += $this->riskFactors['hypertension']['weight'];
      $identifiedRisks[] = [
        'factor' => 'hypertension',
        'description' => $this->riskFactors['hypertension']['description'],
        'weight' => $this->riskFactors['hypertension']['weight']
      ];
      $recommendations[] = 'Blood pressure management';
      $recommendations[] = 'Monitor for preeclampsia';
    }

    // Genotype assessment
    if (strpos($antenatal->genotype, 'S') !== false) {
      $riskScore += $this->riskFactors['sickle_cell']['weight'];
      $identifiedRisks[] = [
        'factor' => 'sickle_cell',
        'description' => $this->riskFactors['sickle_cell']['description'],
        'weight' => $this->riskFactors['sickle_cell']['weight']
      ];
      $recommendations[] = 'Hematology consultation';
      $recommendations[] = 'Crisis prevention measures';
    }

    // BMI assessment
    $bmi = $this->calculateBMI($antenatal->weight, $antenatal->height);
    if ($bmi < self::BMI_LOW_THRESHOLD) {
      $riskScore += $this->riskFactors['low_bmi']['weight'];
      $identifiedRisks[] = [
        'factor' => 'low_bmi',
        'description' => $this->riskFactors['low_bmi']['description'],
        'weight' => $this->riskFactors['low_bmi']['weight']
      ];
      $recommendations[] = 'Nutritional support and weight gain monitoring';
    } elseif ($bmi >= self::BMI_HIGH_THRESHOLD) {
      $riskScore += $this->riskFactors['high_bmi']['weight'];
      $identifiedRisks[] = [
        'factor' => 'high_bmi',
        'description' => $this->riskFactors['high_bmi']['description'],
        'weight' => $this->riskFactors['high_bmi']['weight']
      ];
      $recommendations[] = 'Weight management counseling';
      $recommendations[] = 'Diabetes screening';
    }

    return ['score' => $riskScore, 'risks' => $identifiedRisks, 'recommendations' => $recommendations];
  }

  private function assessDeliveryRisk($delivery)
  {
    $riskScore = 0;
    $identifiedRisks = [];
    $recommendations = [];

    // Emergency cesarean (higher risk than planned)
    if ($delivery->mod === 'CS') {
      $weight = ($delivery->seeking_care === 'less24') ?
        $this->riskFactors['emergency_cesarean']['weight'] :
        $this->riskFactors['cesarean_delivery']['weight'];

      $riskScore += $weight;
      $identifiedRisks[] = [
        'factor' => $delivery->seeking_care === 'less24' ? 'emergency_cesarean' : 'cesarean_delivery',
        'description' => $delivery->seeking_care === 'less24' ?
          $this->riskFactors['emergency_cesarean']['description'] :
          $this->riskFactors['cesarean_delivery']['description'],
        'weight' => $weight
      ];
    }

    // Birth weight assessment
    if ($delivery->weight) {
      if ($delivery->weight < self::BIRTH_WEIGHT_EXTREMELY_LOW) {
        $riskScore += $this->riskFactors['extremely_low_birth_weight']['weight'];
        $identifiedRisks[] = [
          'factor' => 'extremely_low_birth_weight',
          'description' => $this->riskFactors['extremely_low_birth_weight']['description'],
          'weight' => $this->riskFactors['extremely_low_birth_weight']['weight']
        ];
        $recommendations[] = 'Immediate NICU admission required';
      } elseif ($delivery->weight < self::BIRTH_WEIGHT_VERY_LOW) {
        $riskScore += $this->riskFactors['very_low_birth_weight']['weight'];
        $identifiedRisks[] = [
          'factor' => 'very_low_birth_weight',
          'description' => $this->riskFactors['very_low_birth_weight']['description'],
          'weight' => $this->riskFactors['very_low_birth_weight']['weight']
        ];
        $recommendations[] = 'NICU consultation required';
      } elseif ($delivery->weight < self::BIRTH_WEIGHT_LOW) {
        $riskScore += $this->riskFactors['preterm_delivery']['weight'];
        $identifiedRisks[] = [
          'factor' => 'low_birth_weight',
          'description' => 'Low birth weight (below 2.5kg)',
          'weight' => $this->riskFactors['preterm_delivery']['weight']
        ];
      }
    }

    // Stillbirth assessment
    if ($delivery->still_birth) {
      $factor = $delivery->still_birth === 'fresh' ? 'fresh_stillbirth' : 'macerated_stillbirth';
      $riskScore += $this->riskFactors[$factor]['weight'];
      $identifiedRisks[] = [
        'factor' => $factor,
        'description' => $this->riskFactors[$factor]['description'],
        'weight' => $this->riskFactors[$factor]['weight']
      ];
      $recommendations[] = 'Immediate psychological support required';
      $recommendations[] = 'Investigation of cause needed';
    }

    // Neonatal breathing issues
    if ($delivery->breathing === 'yes') {
      $riskScore += $this->riskFactors['neonatal_asphyxia']['weight'];
      $identifiedRisks[] = [
        'factor' => 'neonatal_asphyxia',
        'description' => $this->riskFactors['neonatal_asphyxia']['description'],
        'weight' => $this->riskFactors['neonatal_asphyxia']['weight']
      ];
      $recommendations[] = 'Immediate neonatal resuscitation required';
    }

    // Temperature assessment
    if ($delivery->temperature) {
      if ($delivery->temperature < self::NEONATAL_TEMP_LOW) {
        $riskScore += $this->riskFactors['neonatal_hypothermia']['weight'];
        $identifiedRisks[] = [
          'factor' => 'neonatal_hypothermia',
          'description' => $this->riskFactors['neonatal_hypothermia']['description'],
          'weight' => $this->riskFactors['neonatal_hypothermia']['weight']
        ];
      } elseif ($delivery->temperature > self::NEONATAL_TEMP_HIGH) {
        $riskScore += $this->riskFactors['neonatal_hyperthermia']['weight'];
        $identifiedRisks[] = [
          'factor' => 'neonatal_hyperthermia',
          'description' => $this->riskFactors['neonatal_hyperthermia']['description'],
          'weight' => $this->riskFactors['neonatal_hyperthermia']['weight']
        ];
      }
    }

    // Unbooked patient-i wont even need this , doesn make sense.
    if ($delivery->toc === 'Unbooked') {
      $riskScore += $this->riskFactors['unbooked_delivery']['weight'];
      $identifiedRisks[] = [
        'factor' => 'unbooked_delivery',
        'description' => $this->riskFactors['unbooked_delivery']['description'],
        'weight' => $this->riskFactors['unbooked_delivery']['weight']
      ];
      $recommendations[] = 'Enhanced postpartum monitoring required';
    }

    // Delayed care seeking
    if ($delivery->seeking_care === 'more24') {
      $riskScore += $this->riskFactors['delayed_care_seeking']['weight'];
      $identifiedRisks[] = [
        'factor' => 'delayed_care_seeking',
        'description' => $this->riskFactors['delayed_care_seeking']['description'],
        'weight' => $this->riskFactors['delayed_care_seeking']['weight']
      ];
    }

    // Emergency transport
    if ($delivery->transportation === 'ambulance' || $delivery->mother_transportation === 'ambulance') {
      $riskScore += $this->riskFactors['emergency_transport']['weight'];
      $identifiedRisks[] = [
        'factor' => 'emergency_transport',
        'description' => $this->riskFactors['emergency_transport']['description'],
        'weight' => $this->riskFactors['emergency_transport']['weight']
      ];
    }

    // Multiple interventions pattern
    $interventionCount = 0;
    if ($delivery->oxytocin === 'yes') $interventionCount++;
    if ($delivery->misoprostol === 'yes') $interventionCount++;
    if ($delivery->partograph === 'yes') $interventionCount++;

    if ($interventionCount >= self::MULTI_FACTOR_THRESHOLD) {
      $riskScore += $this->riskFactors['multiple_interventions']['weight'];
      $identifiedRisks[] = [
        'factor' => 'multiple_interventions',
        'description' => $this->riskFactors['multiple_interventions']['description'],
        'weight' => $this->riskFactors['multiple_interventions']['weight']
      ];
      $recommendations[] = 'Complex delivery - enhanced monitoring required';
    }

    // Multiple complications pattern
    $complicationCount = 0;
    if ($delivery->dead === 'yes') $complicationCount++;
    if ($delivery->admitted === 'yes') $complicationCount++;
    if ($delivery->referred_out === 'yes') $complicationCount++;
    if ($delivery->pac === 'yes') $complicationCount++;

    if ($complicationCount >= self::MULTI_FACTOR_THRESHOLD) {
      $riskScore += $this->riskFactors['delivery_complications_pattern']['weight'];
      $identifiedRisks[] = [
        'factor' => 'delivery_complications_pattern',
        'description' => $this->riskFactors['delivery_complications_pattern']['description'],
        'weight' => $this->riskFactors['delivery_complications_pattern']['weight']
      ];
      $recommendations[] = 'Multiple complications detected - urgent care needed';
    }

    return ['score' => $riskScore, 'risks' => $identifiedRisks, 'recommendations' => $recommendations];
  }


  private function assessPostnatalRisk($postnatal)
  {
    $riskScore = 0;
    $identifiedRisks = [];
    $recommendations = [];

    // High blood pressure postpartum
    if ($postnatal->systolic_bp > self::BP_SYSTOLIC_HYPERTENSION || $postnatal->diastolic_bp > self::BP_DIASTOLIC_HYPERTENSION) {
      $riskScore += $this->riskFactors['postpartum_hypertension']['weight'];
      $identifiedRisks[] = [
        'factor' => 'postpartum_hypertension',
        'description' => $this->riskFactors['postpartum_hypertension']['description'],
        'weight' => $this->riskFactors['postpartum_hypertension']['weight']
      ];
      $recommendations[] = 'Blood pressure management';
      $recommendations[] = 'Monitor for postpartum preeclampsia';
    }

    // Associated problems
    if (!empty($postnatal->associated_problems)) {
      $riskScore += $this->riskFactors['postpartum_complications']['weight'];
      $identifiedRisks[] = [
        'factor' => 'postpartum_complications',
        'description' => $this->riskFactors['postpartum_complications']['description'],
        'weight' => $this->riskFactors['postpartum_complications']['weight']
      ];
      $recommendations[] = 'Address specific health concerns';
      $recommendations[] = 'Follow-up care as needed';
    }

    // Breastfeeding issues
    if (
      $postnatal->breastfeeding_status === 'Not Breastfeeding' ||
      $postnatal->breastfeeding_status === 'Mixed'
    ) {
      $riskScore += $this->riskFactors['breastfeeding_problems']['weight'];
      $identifiedRisks[] = [
        'factor' => 'breastfeeding_problems',
        'description' => $this->riskFactors['breastfeeding_problems']['description'],
        'weight' => $this->riskFactors['breastfeeding_problems']['weight']
      ];
      $recommendations[] = 'Breastfeeding counseling and support';
      $recommendations[] = 'Lactation consultant referral';
    }

    return ['score' => $riskScore, 'risks' => $identifiedRisks, 'recommendations' => $recommendations];
  }
  private function assessClinicalNotesRisk($clinicalNotes)
  {
    $riskScore = 0;
    $identifiedRisks = [];
    $recommendations = [];

    if ($clinicalNotes->count() > self::CLINICAL_NOTES_FREQUENT_VISITS_THRESHOLD) {
      $riskScore += $this->riskFactors['frequent_hospital_visits']['weight'];
      $identifiedRisks[] = [
        'factor' => 'frequent_hospital_visits',
        'description' => $this->riskFactors['frequent_hospital_visits']['description'],
        'weight' => $this->riskFactors['frequent_hospital_visits']['weight']
      ];
      $recommendations[] = 'Investigate underlying health issues';
      $recommendations[] = 'Comprehensive health assessment needed';
    }

    // Check for infection patterns in notes
    $infectionCount = 0;

    foreach ($clinicalNotes as $note) {
      if ($this->detectClinicalSignals((string)($note->note ?? ''), self::INFECTION_KEYWORDS)) {
        $infectionCount++;
      }
    }

    if ($infectionCount >= self::CLINICAL_NOTES_RECURRING_INFECTION_THRESHOLD) {
      $riskScore += $this->riskFactors['recurring_infections']['weight'];
      $identifiedRisks[] = [
        'factor' => 'recurring_infections',
        'description' => $this->riskFactors['recurring_infections']['description'],
        'weight' => $this->riskFactors['recurring_infections']['weight']
      ];
      $recommendations[] = 'Investigate immune system function';
      $recommendations[] = 'Infection prevention counseling';
    }

    return ['score' => $riskScore, 'risks' => $identifiedRisks, 'recommendations' => $recommendations];
  }


  private function parseBloodPressure($bpString)
  {
    if (empty($bpString) || !is_string($bpString)) {
      return null;
    }

    $bpString = trim($bpString);

    // Handle different formats: 120/80, 120 / 80, 120-80
    if (preg_match(self::BP_PATTERN, $bpString, $matches)) {
      $systolic = (int)$matches[1];
      $diastolic = (int)$matches[2];

      // Validate reasonable ranges, i can add later maybe
      if (
        $systolic >= self::BP_SYSTOLIC_MIN
        && $systolic <= self::BP_SYSTOLIC_MAX
        && $diastolic >= self::BP_DIASTOLIC_MIN
        && $diastolic <= self::BP_DIASTOLIC_MAX
      ) {
        return [
          'systolic' => $systolic,
          'diastolic' => $diastolic
        ];
      }
    }

    return null;
  }
  private function calculateBMI($weight, $height)
  {
    if (!is_numeric($weight) || !is_numeric($height) || $weight <= 0 || $height <= 0) {
      return null;
    }

    // Validate reasonable ranges- will play with some values later also to see
    if (
      $weight < self::BMI_WEIGHT_MIN
      || $weight > self::BMI_WEIGHT_MAX
      || $height < self::BMI_HEIGHT_CM_MIN
      || $height > self::BMI_HEIGHT_CM_MAX
    ) {
      return null;
    }

    $heightInMeters = $height / 100;
    return round($weight / ($heightInMeters * $heightInMeters), 1);
  }

  private function calculateGestationalAge($lmp)
  {
    if (!$lmp) {
      return null;
    }

    try {
      $lmpDate = Carbon::parse($lmp);
      $now = Carbon::now();

      // Validate reasonable range
      if ($lmpDate->isFuture() || $lmpDate->diffInWeeks($now) > 45) {
        return null;
      }

      $weeks = $lmpDate->diffInWeeks($now);
      $days = $lmpDate->diffInDays($now) % 7;
      return "{$weeks}w {$days}d";
    } catch (\Exception $e) {
      return null;
    }
  }
  private function determineRiskLevel($score)
  {
    if ($score >= self::RISK_SCORE_CRITICAL_MIN) return 'critical';
    if ($score >= self::RISK_SCORE_HIGH_MIN) return 'high';
    if ($score >= self::RISK_SCORE_MODERATE_MIN) return 'moderate';
    return 'low';
  }

  private function detectClinicalSignals(string $text, array $keywords): bool
  {
    $cleanText = trim($text);
    if ($cleanText === '' || empty($keywords)) {
      return false;
    }

    $escapedKeywords = array_map(
      static fn(string $keyword) => preg_quote(strtolower($keyword), '/'),
      $keywords
    );
    $pattern = '/\b(' . implode('|', $escapedKeywords) . ')\b/i';

    return preg_match($pattern, strtolower($cleanText)) === 1;
  }

  private function getNextVisitRecommendation($riskLevel, $edd)
  {
    $gestationalAge = $this->calculateGestationalAge($edd);

    switch ($riskLevel) {
      case 'critical':
        return 'Immediate medical attention required - Schedule within 24-48 hours';
      case 'high':
        return 'Weekly visits required - Next visit within 1 week';
      case 'moderate':
        return 'Bi-weekly visits - Next visit within 2 weeks';
      default:
        return 'Monthly visits - Next visit within 4 weeks';
    }
  }
}
