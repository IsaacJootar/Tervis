<?php

namespace App\Livewire\Analytics;

use Livewire\Component;
use App\Models\Facility;
use App\Services\DataScopeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\DiagnosticAssistantService;

class BatchDiagnosticDashboard extends Component
{
  protected $diagnosticService;
  protected $scopeService;

  public $user;
  public $facilityStats = [];
  public $batchResults = [];
  public $selectedFacilityId = null;
  public $selectedRiskLevel = ['high', 'critical'];
  public $daysBack = 30;
  public $isProcessing = false;
  public $facilityCount = 0;

  // For viewing individual summaries
  public $viewingSummary = false;
  public $currentSummary = null;

  public function boot(DiagnosticAssistantService $diagnosticService, DataScopeService $scopeService)
  {
    $this->diagnosticService = $diagnosticService;
    $this->scopeService = $scopeService;
  }

  public function mount()
  {
    $this->user = Auth::user();

    if (!$this->user || !in_array($this->user->role, [
      'State Data Administrator',
      'LGA Officer',

    ])) {
      abort(403, 'Unauthorized: Only administrators can access batch diagnostics.');
    }

    $this->loadFacilityStats();
  }

  public function loadFacilityStats()
  {
    try {
      $this->facilityStats = $this->diagnosticService->getFacilityDiagnosticStats();
      $this->facilityCount = count($this->facilityStats);

      Log::info('Facility diagnostic stats loaded', [
        'user_id' => $this->user->id,
        'facility_count' => $this->facilityCount
      ]);
    } catch (\Exception $e) {
      Log::error('Failed to load facility stats: ' . $e->getMessage());
      toastr()->error('Failed to load facility statistics.');
      $this->facilityStats = [];
    }
  }

  public function runBatchDiagnostics($facilityId = null)
  {
    $this->isProcessing = true;
    $this->selectedFacilityId = $facilityId;
    $this->dispatch('processing');

    try {
      $options = [
        'risk_level' => $this->selectedRiskLevel,
        'days' => $this->daysBack
      ];

      $results = $this->diagnosticService->generateBatchDiagnosticSummaries($facilityId, $options);

      $this->batchResults = $results;

      if ($results['success']) {
        $facilityName = $facilityId
          ? Facility::find($facilityId)->name
          : 'all facilities in your scope';

        toastr()->info(
          "Generated {$results['success_count']} diagnostic summaries for {$facilityName}."
        );
      } else {
        toastr()->error($results['message']);
      }

      Log::info('Batch diagnostics completed', [
        'facility_id' => $facilityId,
        'total_patients' => $results['total_patients'] ?? 0,
        'user_id' => $this->user->id
      ]);
    } catch (\Exception $e) {
      Log::error('Batch diagnostics failed: ' . $e->getMessage());
      toastr()->error('Failed to generate batch diagnostics: ' . $e->getMessage());
      $this->batchResults = [];
    }

    $this->isProcessing = false;
    $this->dispatch('processing-complete');
  }

  public function viewSummary($index)
  {
    if (isset($this->batchResults['summaries'][$index])) {
      $this->currentSummary = $this->batchResults['summaries'][$index];
      $this->viewingSummary = true;
    }
  }

  public function closeSummary()
  {
    $this->viewingSummary = false;
    $this->currentSummary = null;
  }

  public function clearResults()
  {
    $this->batchResults = [];
    $this->selectedFacilityId = null;
    toastr()->info('Results cleared.');
  }

  public function updatedSelectedRiskLevel()
  {
    // Auto-refresh stats when risk level filter changes
    $this->loadFacilityStats();
  }

  public function updatedDaysBack()
  {
    // Auto-refresh stats when days filter changes
    $this->loadFacilityStats();
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

    return view('livewire.analytics.batch-diagnostic-dashboard')
      ->layout($layout);
  }
}
