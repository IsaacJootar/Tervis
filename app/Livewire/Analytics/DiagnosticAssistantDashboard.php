<?php

namespace App\Livewire\Analytics;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\RiskPrediction;
use Livewire\Component;
use App\Services\DataScopeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\DashboardMetricsService;
use App\Services\DiagnosticAssistantService;

class DiagnosticAssistantDashboard extends Component
{
  public $selectedPatientId = null;
  public $diagnosticSummary = null;
  public $showDiagnosticModal = false;
  public $highRiskPatients = [];

  public $scopeInfo = [];
  public $selectedFacilityId = null;
  public $availableFacilities = [];

  protected $dashboardService;
  protected $diagnosticService;
  protected $scopeService;

  public function boot(
    DashboardMetricsService $dashboardService,
    DiagnosticAssistantService $diagnosticService,
    DataScopeService $scopeService
  ) {
    $this->dashboardService = $dashboardService;
    $this->diagnosticService = $diagnosticService;
    $this->scopeService = $scopeService;
  }

  public function mount()
  {
    $this->scopeInfo = $this->scopeService->getUserScope();

    if (count($this->scopeInfo['facility_ids']) > 1) {
      $this->availableFacilities = Facility::whereIn('id', $this->scopeInfo['facility_ids'])
        ->orderBy('name')
        ->get()
        ->map(function ($facility) {
          return [
            'id' => $facility->id,
            'name' => $facility->name,
            'lga' => $facility->lga,
            'ward' => $facility->ward,
          ];
        })->toArray();
    }

    $this->loadDiagnosticData();
  }

  public function loadDiagnosticData()
  {
    try {
      $metricsData = $this->dashboardService->getRealTimeMetrics($this->selectedFacilityId);
      $this->highRiskPatients = $this->normalizeQueueRows($metricsData['high_risk_pregnancies'] ?? collect());
    } catch (\Exception $e) {
      Log::error('Diagnostic dashboard loading failed: ' . $e->getMessage());
      $this->highRiskPatients = [];
      toastr()->error('Failed to load diagnostic queue');
    }
  }

  public function selectFacility($facilityId)
  {
    $this->selectedFacilityId = $facilityId;
    $this->loadDiagnosticData();

    $facilityName = Facility::find($facilityId)->name ?? 'Unknown';
    toastr()->info("Viewing queue for {$facilityName}");
  }

  public function updatedSelectedFacilityId()
  {
    $this->loadDiagnosticData();
  }

  public function resetToScope()
  {
    $this->selectedFacilityId = null;
    $this->loadDiagnosticData();
    toastr()->info('Viewing queue for all facilities in scope');
  }

  public function refreshData()
  {
    $this->loadDiagnosticData();
    toastr()->success('Diagnostic queue refreshed');
  }

  public function getAIMetrics()
  {
    $facilityId = $this->selectedFacilityId;
    $facilityIds = $facilityId ? [$facilityId] : ($this->scopeInfo['facility_ids'] ?? []);

    if (empty($facilityIds)) {
      return [
        'total_assessments' => 0,
        'this_week' => 0,
        'high_risk_detected' => 0,
        'average_confidence' => 0,
      ];
    }

    $totalAssessments = RiskPrediction::whereIn('facility_id', $facilityIds)->count();
    $thisWeek = RiskPrediction::whereIn('facility_id', $facilityIds)
      ->where('assessment_date', '>=', now()->startOfWeek())
      ->count();
    $highRisk = RiskPrediction::whereIn('facility_id', $facilityIds)
      ->whereIn('risk_level', ['high', 'critical'])
      ->count();

    $averageConfidence = round(
      RiskPrediction::whereIn('facility_id', $facilityIds)
        ->whereNotNull('prediction_confidence')
        ->get()
        ->avg(function ($prediction) {
          $confidence = $prediction->prediction_confidence;
          return is_array($confidence) && isset($confidence['overall_confidence'])
            ? (float) $confidence['overall_confidence']
            : 0;
        }),
      1
    );

    return [
      'total_assessments' => $totalAssessments,
      'this_week' => $thisWeek,
      'high_risk_detected' => $highRisk,
      'average_confidence' => $averageConfidence,
    ];
  }

  public function viewDiagnosticSummary($patientId)
  {
    try {
      $patient = Patient::find($patientId);
      if (!$patient) {
        toastr()->error('Patient not found');
        return;
      }

      $summary = $this->diagnosticService->generateDiagnosticSummary($patientId);
      $this->diagnosticSummary = $this->sanitizeDiagnosticSummary($summary);
      $this->selectedPatientId = $patientId;
      $this->showDiagnosticModal = true;

      toastr()->success('Diagnostic summary generated');
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

  private function normalizeQueueRows($rows): array
  {
    $items = collect($rows);

    return $items->map(function ($row) {
      $patientRelation = $row->patient ?? $row->user ?? null;

      $patientId = (int) ($row->patient_id ?? $row->user_id ?? $patientRelation->id ?? 0);
      $riskFactorCount = (int) ($row->risk_factor_count ?? (is_array($row->risk_factors ?? null) ? count($row->risk_factors) : 0));

      return [
        'patient_id' => $patientId,
        'patient_name' => $this->text($row->patient_name ?? trim(($patientRelation->first_name ?? '') . ' ' . ($patientRelation->last_name ?? '')), 'Unknown Patient'),
        'patient_din' => $this->text($row->patient_din ?? $patientRelation->din ?? $patientRelation->DIN ?? null, 'N/A'),
        'patient_age' => $this->text($row->patient_age ?? $row->age ?? $patientRelation->age ?? null, 'N/A'),
        'gestational_age' => $this->gestationalLabel($row),
        'risk_factor_count' => $riskFactorCount,
      ];
    })->filter(fn($item) => $item['patient_id'] > 0)->values()->toArray();
  }

  private function sanitizeDiagnosticSummary(array $summary): array
  {
    $summary['patient_info']['name'] = $this->text($summary['patient_info']['name'] ?? null, 'Unknown Patient');
    $summary['patient_info']['din'] = $this->text($summary['patient_info']['din'] ?? null, 'N/A');
    $summary['patient_info']['age'] = $this->text($summary['patient_info']['age'] ?? null, 'N/A');
    $summary['patient_info']['phone'] = $this->text($summary['patient_info']['phone'] ?? null, 'N/A');

    $summary['clinical_snapshot']['gestational_age'] = $this->text($summary['clinical_snapshot']['gestational_age'] ?? null, 'N/A');
    $summary['clinical_snapshot']['trimester'] = $this->text($summary['clinical_snapshot']['trimester'] ?? null, 'N/A');
    $summary['clinical_snapshot']['edd'] = $this->text($summary['clinical_snapshot']['edd'] ?? null, 'N/A');
    $summary['clinical_snapshot']['days_until_edd'] = $this->text($summary['clinical_snapshot']['days_until_edd'] ?? null, 'N/A');

    $summary['metadata']['model_version'] = $this->text($summary['metadata']['model_version'] ?? null, 'N/A');
    $summary['metadata']['confidence'] = (float) ($summary['metadata']['confidence'] ?? 0);

    $summary['primary_concerns'] = collect($summary['primary_concerns'] ?? [])->map(function ($concern) {
      return [
        'concern' => $this->text($concern['concern'] ?? null, 'N/A'),
        'severity' => $this->text($concern['severity'] ?? null, 'N/A'),
        'category' => $this->text($concern['category'] ?? null, 'N/A'),
        'clinical_impact' => $this->text($concern['clinical_impact'] ?? null, 'N/A'),
        'confidence' => (float) ($concern['confidence'] ?? 0),
      ];
    })->toArray();

    $summary['clinical_reasoning'] = collect($summary['clinical_reasoning'] ?? [])->map(function ($reasoning) {
      return [
        'description' => $this->text($reasoning['description'] ?? null, 'N/A'),
        'why_flagged' => $this->text($reasoning['why_flagged'] ?? null, 'N/A'),
        'clinical_significance' => $this->text($reasoning['clinical_significance'] ?? null, 'N/A'),
        'potential_complications' => array_values(array_filter((array) ($reasoning['potential_complications'] ?? []), fn($v) => is_scalar($v) && trim((string) $v) !== '')),
      ];
    })->toArray();

    return $summary;
  }

  private function gestationalLabel($row): string
  {
    $label = $row->gestational_age_label ?? $row->current_gestational_age ?? null;

    if (is_array($label)) {
      $weeks = $label['weeks'] ?? null;
      $days = $label['days'] ?? null;
      if (is_numeric($weeks) || is_numeric($days)) {
        return trim(((string) ($weeks ?? 0)) . 'w ' . ((string) ($days ?? 0)) . 'd');
      }
    }

    return $this->text($label, 'N/A');
  }

  private function text($value, string $default = 'N/A'): string
  {
    if (is_array($value)) {
      $scalarValues = array_values(array_filter($value, fn($item) => is_scalar($item) && trim((string) $item) !== ''));
      $joined = implode(', ', array_map(fn($item) => (string) $item, $scalarValues));
      return $joined !== '' ? $joined : $default;
    }

    if (is_object($value)) {
      return method_exists($value, '__toString') ? (string) $value : $default;
    }

    $text = trim((string) ($value ?? ''));
    return $text !== '' ? $text : $default;
  }

  public function render()
  {
    $user = Auth::user();
    $layout = match (true) {
      in_array($user->role, ['State Data Administrator']) => 'layouts.stateOfficerLayout',
      in_array($user->role, ['LGA Officer']) => 'layouts.lgaOfficerLayout',
      in_array($user->role, ['Facility Administrator']) => 'layouts.facilityAdminLayout',
      default => 'layouts.lgaOfficerLayout',
    };

    return view('livewire.analytics.diagnostic-assistant-dashboard', [
      'user' => $user,
    ])->layout($layout);
  }
}
