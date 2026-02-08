<?php

namespace App\Livewire\Analytics;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use App\Models\Facility;
use App\Models\RiskPrediction;
use App\Models\DailyAttendance;
use App\Services\DataScopeService;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessMaternalDataJob;
use Illuminate\Support\Facades\Auth;
use App\Services\RiskAssessmentService;
use App\Services\DashboardMetricsService;
use App\Services\DiagnosticAssistantService;
use App\Services\EnhancedRiskAssessmentService;

class RiskDashboard extends Component
{
  public $selectedPatientId = null;
  public $riskAssessment = null;
  public $showAssessmentModal = false;
  public $facilityRiskSummary = [];
  public $highRiskPatients = [];
  public $showDiagnosticModal = false;
  public $diagnosticSummary = null;

  // New scope-related properties
  public $scopeInfo = [];
  public $selectedFacilityId = null;
  public $availableFacilities = [];

  protected $riskService;
  protected $dashboardService;
  protected $enhancedRiskService;
  protected $scopeService;

  public function boot(
    RiskAssessmentService $riskService,
    DashboardMetricsService $dashboardService,
    EnhancedRiskAssessmentService $enhancedRiskService,
    DataScopeService $scopeService
  ) {
    $this->riskService = $riskService;
    $this->dashboardService = $dashboardService;
    $this->enhancedRiskService = $enhancedRiskService;
    $this->scopeService = $scopeService;
  }

  public function mount()
  {
    $this->scopeInfo = $this->scopeService->getUserScope();

    // Load available facilities for dropdown (if multi-facility scope)
    if (count($this->scopeInfo['facility_ids']) > 1) {
      $this->availableFacilities = Facility::whereIn('id', $this->scopeInfo['facility_ids'])
        ->orderBy('name')
        ->get()
        ->map(function ($facility) {
          return [
            'id' => $facility->id,
            'name' => $facility->name,
            'lga' => $facility->lga,
            'ward' => $facility->ward
          ];
        })->toArray();
    }

    $this->loadRiskData();
  }

  public function loadRiskData()
  {
    try {
      // Use selected facility if drilling down, otherwise use full scope
      $facilityId = $this->selectedFacilityId;

      $this->facilityRiskSummary = $this->riskService->getFacilityRiskSummary($facilityId);

      $metricsData = $this->dashboardService->getRealTimeMetrics($facilityId);
      $this->highRiskPatients = $metricsData['high_risk_pregnancies'] ?? collect();
    } catch (\Exception $e) {
      Log::error('Risk data loading failed: ' . $e->getMessage());
      $this->facilityRiskSummary = [];
      $this->highRiskPatients = collect();
    }
  }

  public function selectFacility($facilityId)
  {
    $this->selectedFacilityId = $facilityId;
    $this->loadRiskData();

    $facilityName = Facility::find($facilityId)->name ?? 'Unknown';
    toastr()->info("Viewing data for {$facilityName}");
  }

  public function resetToScope()
  {
    $this->selectedFacilityId = null;
    $this->loadRiskData();

    $scopeLabel = $this->scopeInfo['scope_type'] === 'lga' ? 'LGA' : ($this->scopeInfo['scope_type'] === 'state' ? 'State' : 'Facility');
    toastr()->info("Viewing data for entire {$scopeLabel}");
  }

  public function assessPatientRisk($patientId)
  {
    $this->selectedPatientId = $patientId;
    $this->riskAssessment = $this->riskService->assessRisk($patientId);
    $this->showAssessmentModal = true;
  }


  public function scheduleAIAssessment($patientId)
  {
    try {
      // Queue the AI assessment as a background job
      ProcessMaternalDataJob::dispatch($patientId, [
        'assessment_type' => 'manual_request',
        'officer_name' => Auth::user()->first_name . ' ' . Auth::user()->last_name
      ], 'manual');

      toastr()->info('AI assessment queued - results will appear in dashboard shortly');
    } catch (\Exception $e) {
      Log::error('Failed to queue AI assessment: ' . $e->getMessage());
      toastr()->error('Failed to queue assessment');
    }
  }


  public function performAIAssessment($patientId)
  {
    try {
      $user = User::with(['antenatal', 'deliveries', 'postnatalRecords', 'clinicalNotes'])
        ->find($patientId);

      if (!$user) {
        toastr()->error('Patient not found.');
        return;
      }

      if (!$user->antenatal) {
        toastr()->warning('Patient lacks antenatal data required for AI assessment.');
        return;
      }

      $prediction = $this->enhancedRiskService->performAIRiskAssessment($user->id);
      $this->buildAssessmentData($user, $prediction);

      $this->selectedPatientId = $patientId;
      $this->showAssessmentModal = true;

      toastr()->info('AI assessment completed! Risk Level: ' . ucfirst($prediction->risk_level));
      $this->loadRiskData();
    } catch (\Exception $e) {
      Log::error('AI Assessment Error: ' . $e->getMessage());
      toastr()->error('AI assessment failed: ' . $e->getMessage());
    }
  }

  private function buildAssessmentData($user, $prediction)
  {
    $antenatal = $user->antenatal;

    $gestationalAge = 'N/A';
    if ($antenatal && $antenatal->lmp) {
      try {
        $lmp = Carbon::parse($antenatal->lmp);
        $now = Carbon::now();
        $weeks = $lmp->diffInWeeks($now);
        $days = $lmp->diffInDays($now) % 7;
        $gestationalAge = "{$weeks}w {$days}d";
      } catch (\Exception $e) {
        $gestationalAge = 'Invalid date';
      }
    }

    $bmi = 'N/A';
    if ($antenatal && $antenatal->weight && $antenatal->height) {
      try {
        $heightInMeters = $antenatal->height / 100;
        if ($heightInMeters > 0) {
          $bmi = round($antenatal->weight / ($heightInMeters * $heightInMeters), 1);
        }
      } catch (\Exception $e) {
        $bmi = 'Calculation error';
      }
    }

    $this->riskAssessment = [
      'patient_name' => $user->first_name . ' ' . $user->last_name,
      'din' => $user->DIN,
      'gestational_age' => $gestationalAge,
      'edd' => $antenatal->edd ?? 'N/A',
      'bmi' => $bmi,
      'model_version' => $prediction->model_version,
      'total_risk_score' => $prediction->total_risk_score,
      'risk_level' => $prediction->risk_level,
      'risk_percentage' => $prediction->risk_percentage,
      'assessment_date' => $prediction->assessment_date,
      'identified_risks' => $prediction->identified_risks,
      'ai_recommendations' => $prediction->ai_recommendations,
      'prediction_confidence' => $prediction->prediction_confidence,
      'predicted_outcomes' => $prediction->predicted_outcomes,
      'recommendations' => $prediction->ai_recommendations,
      'next_visit_recommendation' => $this->generateNextVisitRecommendation($prediction->risk_level),
      'service_history' => [
        'antenatal_visits' => DailyAttendance::where('user_id', $user->id)->count(),
        'delivery_count' => $user->deliveries?->count() ?? 0,
        'postnatal_visits' => $user->postnatalRecords?->count() ?? 0,
        'clinical_notes' => $user->clinicalNotes?->count() ?? 0,
      ]
    ];
  }

  private function generateNextVisitRecommendation($riskLevel)
  {
    return match ($riskLevel) {
      'critical' => 'Immediate follow-up within 24 hours',
      'high' => 'Follow-up within 3 days',
      'moderate' => 'Follow-up within 1 week',
      'low' => 'Next routine visit in 2 weeks',
      default => 'Follow routine antenatal schedule'
    };
  }

  public function getAIMetrics()
  {
    $facilityId = $this->selectedFacilityId;
    $facilityIds = $facilityId ? [$facilityId] : $this->scopeInfo['facility_ids'];

    $totalAssessments = RiskPrediction::whereIn('facility_id', $facilityIds)->count();
    $thisWeek = RiskPrediction::whereIn('facility_id', $facilityIds)
      ->where('assessment_date', '>=', now()->startOfWeek())->count();
    $highRisk = RiskPrediction::whereIn('facility_id', $facilityIds)
      ->whereIn('risk_level', ['high', 'critical'])->count();

    return [
      'total_assessments' => $totalAssessments,
      'this_week' => $thisWeek,
      'high_risk_detected' => $highRisk,
      'average_confidence' => round(RiskPrediction::whereIn('facility_id', $facilityIds)
        ->whereNotNull('prediction_confidence')
        ->get()
        ->avg(function ($pred) {
          $conf = $pred->prediction_confidence;
          return is_array($conf) && isset($conf['overall_confidence']) ? $conf['overall_confidence'] : 0;
        }), 1)
    ];
  }

  public function viewDiagnosticSummary($patientId)
  {
    try {
      $diagnosticService = app(DiagnosticAssistantService::class);
      $this->diagnosticSummary = $diagnosticService->generateDiagnosticSummary($patientId);
      $this->selectedPatientId = $patientId;
      $this->showDiagnosticModal = true;

      toastr()->info('Diagnostic summary generated successfully');
    } catch (\Exception $e) {
      Log::error('Diagnostic summary failed: ' . $e->getMessage());
      toastr()->error('Failed to generate diagnostic summary: ' . $e->getMessage());
    }
  }

  public function closeDiagnosticModal()
  {
    $this->showDiagnosticModal = false;
    $this->diagnosticSummary = null;
    $this->selectedPatientId = null;
  }

  public function closeModal()
  {
    $this->showAssessmentModal = false;
    $this->riskAssessment = null;
    $this->selectedPatientId = null;
  }

  public function refreshData()
  {
    $this->loadRiskData();
    toastr()->info('Risk data refreshed successfully');
  }

  public function render()
  {
    $user = Auth::user();
    $layout = match (true) {
      in_array($user->role, ['State Data Administrator']) => 'layouts.stateOfficerLayout',
      in_array($user->role, ['LGA Officer']) => 'layouts.lgaOfficerLayout',
      in_array($user->role, ['Facility Administrator']) => 'layouts.facilityAdminLayout',
      default => 'lgaOfficerLayout'
    };
    return view('livewire.analytics.risk-dashboard', [
      'user' => $user
    ])->layout($layout);
  }
}
