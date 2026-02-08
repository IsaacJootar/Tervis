<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Facility;
use App\Models\RiskPrediction;
use Illuminate\Support\Facades\Log;

class DiagnosticAssistantService
{
  private $riskService;
  private $enhancedRiskService;
  protected $scopeService;

  public function __construct(
    RiskAssessmentService $riskService,
    EnhancedRiskAssessmentService $enhancedRiskService,
    DataScopeService $scopeService
  ) {
    $this->riskService = $riskService;
    $this->enhancedRiskService = $enhancedRiskService;
    $this->scopeService = $scopeService;
  }
  /**
   * Generate comprehensive diagnostic summary for a patient
   */
  public function generateDiagnosticSummary($userId)
  {
    try {
      $user = User::with([
        'antenatal',
        'deliveries',
        'postnatalRecords',
        'clinicalNotes',
        'tetanusVaccinations'
      ])->find($userId);

      if (!$user || !$user->antenatal) {
        throw new \Exception('Patient data not found or incomplete');
      }

      // Get latest AI risk assessment or create new one
      $latestPrediction = RiskPrediction::where('user_id', $userId)
        ->latest('assessment_date')
        ->first();

      if (!$latestPrediction || $latestPrediction->assessment_date < Carbon::now()->subDays(7)) {
        // Generate fresh assessment if older than 7 days
        $latestPrediction = $this->enhancedRiskService->performAIRiskAssessment($userId);
      }

      return [
        'patient_info' => $this->buildPatientSnapshot($user),
        'clinical_snapshot' => $this->buildClinicalSnapshot($user, $latestPrediction),
        'primary_concerns' => $this->identifyPrimaryConcerns($latestPrediction),
        'clinical_reasoning' => $this->generateClinicalReasoning($latestPrediction, $user),
        'risk_trajectory' => $this->analyzeRiskTrajectory($userId),
        'immediate_actions' => $this->prioritizeImmediateActions($latestPrediction),
        'monitoring_plan' => $this->createMonitoringSchedule($latestPrediction, $user),
        'consultation_triggers' => $this->identifyEscalationCriteria($latestPrediction),
        'care_gaps' => $this->identifyGapsInCare($user),
        'metadata' => [
          'generated_at' => now(),
          'assessment_date' => $latestPrediction->assessment_date,
          'model_version' => $latestPrediction->model_version,
          'confidence' => $latestPrediction->prediction_confidence['overall_confidence'] ?? 0
        ]
      ];
    } catch (\Exception $e) {
      Log::error('Diagnostic summary generation failed: ' . $e->getMessage());
      throw $e;
    }
  }

  /**
   * Build patient snapshot
   */
  private function buildPatientSnapshot($user)
  {
    $antenatal = $user->antenatal;

    return [
      'name' => $user->first_name . ' ' . $user->last_name,
      'din' => $user->DIN,
      'age' => $antenatal->age ?? 'N/A',
      'phone' => $user->phone,
      'registration_date' => $antenatal->date_of_booking ?? null,
      'total_visits' => $user->dailyAttendances->count()
    ];
  }

  /**
   * Build clinical snapshot with key vitals and status
   */
  private function buildClinicalSnapshot($user, $prediction)
  {
    $antenatal = $user->antenatal;

    // Calculate gestational age
    $gestationalAge = 'N/A';
    $gestationalWeeks = 0;
    if ($antenatal->lmp) {
      $lmp = Carbon::parse($antenatal->lmp);
      $gestationalWeeks = $lmp->diffInWeeks(Carbon::now());
      $days = $lmp->diffInDays(Carbon::now()) % 7;
      $gestationalAge = "{$gestationalWeeks}w {$days}d";
    }

    // Calculate BMI
    $bmi = null;
    if ($antenatal->weight && $antenatal->height) {
      $heightInMeters = $antenatal->height / 100;
      $bmi = round($antenatal->weight / ($heightInMeters * $heightInMeters), 1);
    }

    // Parse blood pressure
    $bp = $this->parseBloodPressure($antenatal->blood_pressure);

    return [
      'gestational_age' => $gestationalAge,
      'gestational_weeks' => $gestationalWeeks,
      'edd' => $antenatal->edd,
      'days_until_edd' => $antenatal->edd ? Carbon::parse($antenatal->edd)->diffInDays(Carbon::now()) : null,
      'trimester' => $this->determineTrimester($gestationalWeeks),
      'vitals' => [
        'blood_pressure' => $antenatal->blood_pressure,
        'bp_systolic' => $bp['systolic'] ?? null,
        'bp_diastolic' => $bp['diastolic'] ?? null,
        'bp_status' => $this->getBPStatus($bp),
        'hemoglobin' => $antenatal->hemoglobin,
        'hb_status' => $this->getHemoglobinStatus($antenatal->hemoglobin),
        'weight' => $antenatal->weight,
        'height' => $antenatal->height,
        'bmi' => $bmi,
        'bmi_category' => $this->getBMICategory($bmi)
      ],
      'blood_work' => [
        'blood_group' => $antenatal->blood_group_rhesus,
        'genotype' => $antenatal->genotype,
        'genotype_risk' => strpos($antenatal->genotype, 'S') !== false ? 'Sickle cell trait/disease' : 'Normal'
      ],
      'overall_risk' => [
        'level' => $prediction->risk_level,
        'score' => $prediction->total_risk_score,
        'percentage' => $prediction->risk_percentage
      ]
    ];
  }

  /**
   * Identify primary concerns from risk assessment
   */
  private function identifyPrimaryConcerns($prediction)
  {
    $concerns = [];
    $risks = $prediction->identified_risks;

    // Group risks by severity
    $criticalRisks = collect($risks)->filter(function ($risk) {
      return ($risk['weight'] ?? 0) >= 20;
    })->sortByDesc('weight')->take(3);

    foreach ($criticalRisks as $risk) {
      $concerns[] = [
        'concern' => $risk['description'] ?? $risk['factor'],
        'severity' => $this->getSeverityLevel($risk['weight'] ?? 0),
        'category' => $risk['category'] ?? 'clinical_assessment',
        'confidence' => $risk['confidence'] ?? 1.0,
        'clinical_impact' => $this->describeImpact($risk)
      ];
    }

    return $concerns;
  }

  /**
   * Generate clinical reasoning for detected risks
   */
  private function generateClinicalReasoning($prediction, $user)
  {
    $reasoning = [];

    foreach ($prediction->identified_risks as $risk) {
      $reasoning[] = [
        'risk_factor' => $risk['factor'],
        'description' => $risk['description'] ?? $risk['factor'],
        'why_flagged' => $this->explainWhyFlagged($risk, $user),
        'clinical_significance' => $this->explainSignificance($risk),
        'potential_complications' => $this->identifyPotentialComplications($risk),
        'evidence_level' => $risk['confidence'] ?? 1.0
      ];
    }

    return $reasoning;
  }

  /**
   * Explain why a risk was flagged
   */
  private function explainWhyFlagged($risk, $user)
  {
    $factor = $risk['factor'];
    $antenatal = $user->antenatal;

    $explanations = [
      'teen_pregnancy' => "Patient age ({$antenatal->age} years) is below 18, associated with increased obstetric risks",
      'advanced_maternal_age' => "Patient age ({$antenatal->age} years) is above 35, requiring enhanced monitoring",
      'hypertension' => "Blood pressure ({$antenatal->blood_pressure}) exceeds normal range (≥140/90 mmHg)",
      'anemia' => "Hemoglobin level ({$antenatal->hemoglobin} g/dL) is below 11 g/dL",
      'bleeding' => "Active bleeding during pregnancy detected in clinical records",
      'sickle_cell' => "Genotype ({$antenatal->genotype}) indicates sickle cell trait or disease",
      'heart_disease' => "Pre-existing cardiac condition documented in medical history",
      'kidney_disease' => "Renal condition documented, requiring close monitoring during pregnancy",
    ];

    return $explanations[$factor] ?? "Clinical criteria met for {$risk['description']}";
  }

  /**
   * Explain clinical significance
   */
  private function explainSignificance($risk)
  {
    $significanceMap = [
      'teen_pregnancy' => 'Increased risk of preterm labor, low birth weight, and postpartum complications',
      'advanced_maternal_age' => 'Elevated risk of gestational diabetes, preeclampsia, and chromosomal abnormalities',
      'hypertension' => 'May progress to preeclampsia; risks include placental abruption and fetal growth restriction',
      'anemia' => 'Increases maternal fatigue and risk of postpartum hemorrhage; affects fetal development',
      'bleeding' => 'May indicate placental complications, threatened abortion, or cervical issues',
      'sickle_cell' => 'Risk of sickle cell crises, increased infections, and pregnancy complications',
      'heart_disease' => 'Cardiac decompensation risk; requires specialist care and delivery planning',
      'kidney_disease' => 'Risk of preeclampsia, preterm delivery, and worsening renal function',
    ];

    return $significanceMap[$risk['factor']] ?? 'Requires clinical monitoring and appropriate intervention';
  }

  /**
   * Identify potential complications
   */
  private function identifyPotentialComplications($risk)
  {
    $complicationsMap = [
      'hypertension' => ['Preeclampsia', 'Eclampsia', 'Placental abruption', 'Stroke'],
      'anemia' => ['Maternal fatigue', 'Postpartum hemorrhage', 'Low birth weight', 'Preterm delivery'],
      'heart_disease' => ['Cardiac failure', 'Arrhythmias', 'Maternal mortality'],
      'kidney_disease' => ['Preeclampsia', 'Chronic hypertension', 'Preterm delivery'],
      'bleeding' => ['Placental abruption', 'Placenta previa', 'Miscarriage'],
      'sickle_cell' => ['Vaso-occlusive crises', 'Acute chest syndrome', 'Infections'],
    ];

    return $complicationsMap[$risk['factor']] ?? ['Pregnancy complications requiring monitoring'];
  }

  /**
   * Analyze risk trajectory over time
   */
  private function analyzeRiskTrajectory($userId)
  {
    $predictions = RiskPrediction::where('user_id', $userId)
      ->orderBy('assessment_date', 'asc')
      ->get();

    if ($predictions->count() < 2) {
      return [
        'trend' => 'insufficient_data',
        'message' => 'Requires multiple assessments to establish trend',
        'assessments_count' => $predictions->count()
      ];
    }

    $latest = $predictions->last();
    $previous = $predictions->slice(-2, 1)->first();

    $scoreChange = $latest->total_risk_score - $previous->total_risk_score;
    $trend = $scoreChange > 5 ? 'increasing' : ($scoreChange < -5 ? 'decreasing' : 'stable');

    return [
      'trend' => $trend,
      'score_change' => $scoreChange,
      'current_score' => $latest->total_risk_score,
      'previous_score' => $previous->total_risk_score,
      'assessments_count' => $predictions->count(),
      'first_assessment' => $predictions->first()->assessment_date,
      'latest_assessment' => $latest->assessment_date,
      'interpretation' => $this->interpretTrend($trend, $scoreChange)
    ];
  }

  /**
   * Prioritize immediate actions
   */
  private function prioritizeImmediateActions($prediction)
  {
    $actions = [];
    $riskLevel = $prediction->risk_level;

    // Critical actions based on risk level
    if ($riskLevel === 'critical') {
      $actions[] = [
        'priority' => 'urgent',
        'action' => 'Immediate obstetric evaluation required',
        'timeframe' => 'Within 24 hours',
        'reason' => 'Critical risk level detected'
      ];
    }

    // Actions based on specific risks
    foreach ($prediction->identified_risks as $risk) {
      $specificActions = $this->getSpecificActions($risk);
      $actions = array_merge($actions, $specificActions);
    }

    // Sort by priority
    usort($actions, function ($a, $b) {
      $priorityOrder = ['urgent' => 0, 'high' => 1, 'medium' => 2, 'routine' => 3];
      return ($priorityOrder[$a['priority']] ?? 99) - ($priorityOrder[$b['priority']] ?? 99);
    });

    return array_slice($actions, 0, 5); // Return top 5 actions
  }

  /**
   * Get specific actions for a risk
   */
  private function getSpecificActions($risk)
  {
    $actionsMap = [
      'hypertension' => [
        ['priority' => 'high', 'action' => 'Blood pressure monitoring twice weekly', 'timeframe' => 'Ongoing'],
        ['priority' => 'high', 'action' => 'Urine protein test', 'timeframe' => 'Within 48 hours'],
      ],
      'anemia' => [
        ['priority' => 'medium', 'action' => 'Start iron supplementation', 'timeframe' => 'Immediate'],
        ['priority' => 'medium', 'action' => 'Repeat hemoglobin in 4 weeks', 'timeframe' => '4 weeks'],
      ],
      'bleeding' => [
        ['priority' => 'urgent', 'action' => 'Ultrasound examination', 'timeframe' => 'Within 24 hours'],
        ['priority' => 'urgent', 'action' => 'Assess for placental complications', 'timeframe' => 'Immediate'],
      ],
    ];

    $actions = $actionsMap[$risk['factor']] ?? [];

    // Add reason to each action
    foreach ($actions as &$action) {
      $action['reason'] = $risk['description'] ?? $risk['factor'];
    }

    return $actions;
  }

  /**
   * Create monitoring schedule
   */
  private function createMonitoringSchedule($prediction, $user)
  {
    $gestationalWeeks = 0;
    if ($user->antenatal && $user->antenatal->lmp) {
      $gestationalWeeks = Carbon::parse($user->antenatal->lmp)->diffInWeeks(Carbon::now());
    }

    $riskLevel = $prediction->risk_level;

    $frequency = match ($riskLevel) {
      'critical' => 'Every 3-7 days',
      'high' => 'Weekly',
      'moderate' => 'Every 2 weeks',
      'low' => 'Every 4 weeks'
    };

    return [
      'visit_frequency' => $frequency,
      'next_visit_due' => $prediction->next_assessment_due,
      'monitoring_parameters' => $this->getMonitoringParameters($prediction, $gestationalWeeks),
      'specialist_referrals' => $this->getRequiredReferrals($prediction),
      'lab_schedule' => $this->getLabSchedule($prediction, $gestationalWeeks)
    ];
  }

  /**
   * Get monitoring parameters
   */
  private function getMonitoringParameters($prediction, $gestationalWeeks)
  {
    $baseParameters = [
      'Blood pressure',
      'Weight',
      'Fetal heart rate',
      'Fundal height'
    ];

    // Add specific parameters based on risks
    $risks = collect($prediction->identified_risks)->pluck('factor')->toArray();

    if (in_array('hypertension', $risks)) {
      $baseParameters[] = 'Urine protein';
      $baseParameters[] = 'Edema assessment';
    }

    if (in_array('anemia', $risks)) {
      $baseParameters[] = 'Hemoglobin level';
    }

    if ($gestationalWeeks >= 28) {
      $baseParameters[] = 'Fetal movement count';
    }

    return array_unique($baseParameters);
  }

  /**
   * Get required specialist referrals
   */
  private function getRequiredReferrals($prediction)
  {
    $referrals = [];
    $risks = collect($prediction->identified_risks)->pluck('factor')->toArray();

    if (in_array('heart_disease', $risks)) {
      $referrals[] = ['specialist' => 'Cardiologist', 'urgency' => 'high', 'reason' => 'Cardiac disease management'];
    }

    if (in_array('kidney_disease', $risks)) {
      $referrals[] = ['specialist' => 'Nephrologist', 'urgency' => 'high', 'reason' => 'Renal function monitoring'];
    }

    if (in_array('sickle_cell', $risks)) {
      $referrals[] = ['specialist' => 'Hematologist', 'urgency' => 'medium', 'reason' => 'Sickle cell disease management'];
    }

    if ($prediction->risk_level === 'critical') {
      $referrals[] = ['specialist' => 'Maternal-Fetal Medicine', 'urgency' => 'high', 'reason' => 'High-risk pregnancy management'];
    }

    return $referrals;
  }

  /**
   * Get lab schedule
   */
  private function getLabSchedule($prediction, $gestationalWeeks)
  {
    $labs = [];

    // Standard labs based on gestational age
    if ($gestationalWeeks >= 24 && $gestationalWeeks < 28) {
      $labs[] = ['test' => 'Glucose tolerance test', 'timing' => '24-28 weeks'];
    }

    if ($gestationalWeeks >= 28) {
      $labs[] = ['test' => 'Complete blood count', 'timing' => '28-32 weeks'];
    }

    // Risk-specific labs
    $risks = collect($prediction->identified_risks)->pluck('factor')->toArray();

    if (in_array('hypertension', $risks)) {
      $labs[] = ['test' => 'Renal function tests', 'timing' => 'Monthly'];
      $labs[] = ['test' => 'Liver function tests', 'timing' => 'Monthly'];
    }

    if (in_array('anemia', $risks)) {
      $labs[] = ['test' => 'Hemoglobin', 'timing' => 'Every 4 weeks'];
    }

    return $labs;
  }

  /**
   * Identify escalation criteria- will can add to this array later
   */
  private function identifyEscalationCriteria($prediction)
  {
    $criteria = [
      'immediate_emergency' => [
        'Severe headache with visual changes',
        'Sudden severe abdominal pain',
        'Vaginal bleeding (moderate to heavy)',
        'Sudden decrease in fetal movement',
        'Seizures or altered consciousness',
        'Blood pressure ≥160/110 mmHg'
      ],
      'urgent_within_24hrs' => [
        'Blood pressure 140-159/90-109 mmHg on two readings',
        'Reduced fetal movement',
        'Mild to moderate vaginal bleeding',
        'Persistent vomiting',
        'Fever >38°C'
      ],
      'schedule_soon' => [
        'New onset edema',
        'Persistent headache',
        'Abdominal discomfort',
        'Unusual discharge',
        'Concerns about fetal wellbeing'
      ]
    ];

    return $criteria;
  }

  /**
   * Identify gaps in care
   */
  private function identifyGapsInCare($user)
  {
    $gaps = [];

    // Check tetanus vaccination
    $tetanusCount = $user->tetanusVaccinations->count();
    if ($tetanusCount < 2) {
      $gaps[] = [
        'category' => 'Immunization',
        'gap' => 'Incomplete tetanus toxoid series',
        'current_status' => "{$tetanusCount} dose(s) received",
        'required' => 'Minimum 2 doses for protection',
        'action' => 'Schedule next TT dose'
      ];
    }

    // Check visit frequency
    $gestationalWeeks = 0;
    if ($user->antenatal && $user->antenatal->lmp) {
      $gestationalWeeks = Carbon::parse($user->antenatal->lmp)->diffInWeeks(Carbon::now());
    }

    $visitCount = $user->dailyAttendances()->where('visit_date', '>=', Carbon::now()->subMonths(1))->count();
    $expectedVisits = $gestationalWeeks >= 36 ? 4 : ($gestationalWeeks >= 28 ? 2 : 1);

    if ($visitCount < $expectedVisits) {
      $gaps[] = [
        'category' => 'Attendance',
        'gap' => 'Suboptimal visit frequency',
        'current_status' => "{$visitCount} visit(s) in last month",
        'expected' => "{$expectedVisits} visits expected",
        'action' => 'Encourage regular attendance'
      ];
    }

    // Check for missing vital signs
    if (!$user->antenatal->blood_pressure || !$user->antenatal->hemoglobin) {
      $gaps[] = [
        'category' => 'Clinical Data',
        'gap' => 'Incomplete vital signs',
        'current_status' => 'Missing key measurements',
        'required' => 'Blood pressure and hemoglobin required',
        'action' => 'Schedule assessment'
      ];
    }

    return $gaps;
  }

  // Helper methods
  private function parseBloodPressure($bpString)
  {
    if (empty($bpString)) return [];

    if (preg_match('/(\d{2,3})\s*[\/\-]\s*(\d{2,3})/', $bpString, $matches)) {
      return [
        'systolic' => (int)$matches[1],
        'diastolic' => (int)$matches[2]
      ];
    }

    return [];
  }

  private function determineTrimester($weeks)
  {
    if ($weeks < 13) return '1st Trimester';
    if ($weeks < 27) return '2nd Trimester';
    return '3rd Trimester';
  }

  private function getBPStatus($bp)
  {
    if (empty($bp)) return 'Unknown';

    $systolic = $bp['systolic'];
    $diastolic = $bp['diastolic'];

    if ($systolic >= 160 || $diastolic >= 110) return 'Severe Hypertension';
    if ($systolic >= 140 || $diastolic >= 90) return 'Hypertension';
    if ($systolic >= 120 || $diastolic >= 80) return 'Elevated';
    return 'Normal';
  }

  private function getHemoglobinStatus($hb)
  {
    if (!$hb) return 'Unknown';
    if ($hb < 7) return 'Severe Anemia';
    if ($hb < 10) return 'Moderate Anemia';
    if ($hb < 11) return 'Mild Anemia';
    return 'Normal';
  }

  private function getBMICategory($bmi)
  {
    if (!$bmi) return 'Unknown';
    if ($bmi < 18.5) return 'Underweight';
    if ($bmi < 25) return 'Normal';
    if ($bmi < 30) return 'Overweight';
    return 'Obese';
  }

  private function getSeverityLevel($weight)
  {
    if ($weight >= 25) return 'Critical';
    if ($weight >= 15) return 'High';
    if ($weight >= 10) return 'Moderate';
    return 'Low';
  }

  private function describeImpact($risk)
  {
    $weight = $risk['weight'] ?? 0;

    if ($weight >= 25) return 'Major impact on pregnancy outcome';
    if ($weight >= 15) return 'Significant risk requiring intervention';
    if ($weight >= 10) return 'Moderate risk requiring monitoring';
    return 'Minor risk factor';
  }

  private function interpretTrend($trend, $scoreChange)
  {
    if ($trend === 'increasing') {
      return "Risk level increasing (+" . abs($scoreChange) . " points). Requires enhanced monitoring and possible intervention.";
    } elseif ($trend === 'decreasing') {
      return "Risk level improving (-" . abs($scoreChange) . " points). Continue current management plan.";
    } else {
      return "Risk level stable. Maintain current monitoring schedule.";
    }
  }



  /**
   * Generate batch diagnostic summaries for high-risk patients in scope
   */
  public function generateBatchDiagnosticSummaries($facilityId = null, $options = [])
  {
    if ($facilityId) {
      $facilityIds = [$facilityId];
    } else {
      $scope = $this->scopeService->getUserScope();
      $facilityIds = $scope['facility_ids'] ?? [];
    }

    if (empty($facilityIds)) {
      return [
        'success' => false,
        'message' => 'No facilities found in scope',
        'total_patients' => 0,
        'facility_count' => 0,
        'summaries' => []
      ];
    }

    // Get high-risk patients from recent assessments
    $riskLevel = $options['risk_level'] ?? ['high', 'critical'];
    $days = $options['days'] ?? 30;

    $highRiskPatients = RiskPrediction::whereIn('facility_id', $facilityIds)
      ->whereIn('risk_level', (array)$riskLevel)
      ->where('assessment_date', '>=', Carbon::now()->subDays($days))
      ->with(['user.antenatal'])
      ->latest('assessment_date')
      ->get()
      ->unique('user_id'); // Only get latest assessment per patient

    $summaries = [];
    $successCount = 0;
    $failureCount = 0;

    foreach ($highRiskPatients as $prediction) {
      try {
        if ($prediction->user && $prediction->user->antenatal) {
          $summary = $this->generateDiagnosticSummary($prediction->user_id);
          $summaries[] = [
            'patient_info' => $summary['patient_info'],
            'clinical_snapshot' => $summary['clinical_snapshot'],
            'primary_concerns' => $summary['primary_concerns'],
            'immediate_actions' => $summary['immediate_actions'],
            'risk_level' => $prediction->risk_level,
            'facility_id' => $prediction->facility_id
          ];
          $successCount++;
        }
      } catch (\Exception $e) {
        Log::warning("Failed to generate diagnostic for user {$prediction->user_id}: {$e->getMessage()}");
        $failureCount++;
      }
    }

    // Group by facility
    $facilityBreakdown = collect($summaries)->groupBy('facility_id')->map(function ($items, $facilityId) {
      $facility = Facility::find($facilityId);
      return [
        'facility_name' => $facility->name ?? 'Unknown',
        'patient_count' => $items->count(),
        'critical_count' => $items->where('risk_level', 'critical')->count(),
        'high_count' => $items->where('risk_level', 'high')->count(),
      ];
    });

    return [
      'success' => true,
      'total_patients' => $successCount,
      'facility_count' => count($facilityIds),
      'success_count' => $successCount,
      'failure_count' => $failureCount,
      'summaries' => $summaries,
      'facility_breakdown' => $facilityBreakdown,
      'generated_at' => now(),
      'scope_info' => [
        'facility_ids' => $facilityIds,
        'risk_levels' => (array)$riskLevel,
        'days_back' => $days
      ]
    ];
  }

  /**
   * Get facility-specific diagnostic statistics
   */
  public function getFacilityDiagnosticStats($facilityId = null)
  {
    if ($facilityId) {
      $facilityIds = [$facilityId];
    } else {
      $scope = $this->scopeService->getUserScope();
      $facilityIds = $scope['facility_ids'] ?? [];
    }

    if (empty($facilityIds)) {
      return [];
    }

    $stats = [];

    foreach ($facilityIds as $fId) {
      $facility = Facility::find($fId);

      $highRiskCount = RiskPrediction::where('facility_id', $fId)
        ->whereIn('risk_level', ['high', 'critical'])
        ->where('assessment_date', '>=', Carbon::now()->subDays(30))
        ->distinct('user_id')
        ->count('user_id');

      $stats[] = [
        'facility_id' => $fId,
        'facility_name' => $facility->name ?? 'Unknown',
        'lga' => $facility->lga ?? 'N/A',
        'high_risk_patients' => $highRiskCount,
        'needs_attention' => $highRiskCount > 0
      ];
    }

    return $stats;
  }
}
