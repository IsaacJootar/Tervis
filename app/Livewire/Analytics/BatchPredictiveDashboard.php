<?php

namespace App\Livewire\Analytics;

use Livewire\Component;
use App\Models\Facility;
use App\Services\DataScopeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Services\PredictiveAnalyticsService;

class BatchPredictiveDashboard extends Component
{
  protected $analyticsService;
  protected $scopeService;

  public $user;
  public $facilityCount = 0;
  public $predictionHorizon = 30;
  public $predictions = [];
  public $isGenerating = false;
  public $selectedFacilityId = null;
  public $facilities = [];

  // View toggles
  public $showRiskPredictions = true;
  public $showServiceUtilization = true;
  public $showResourceNeeds = true;
  public $showOutcomes = true;
  public $showSeasonalPatterns = true;
  public $showInterventions = true;

  public function boot(
    PredictiveAnalyticsService $analyticsService,
    DataScopeService $scopeService
  ) {
    $this->analyticsService = $analyticsService;
    $this->scopeService = $scopeService;
  }

  public function mount()
  {
    $this->user = Auth::user();

    if (!$this->user || !in_array($this->user->role, [
      'State Data Administrator',
      'LGA Officer',
    ])) {
      abort(403, 'Unauthorized: Only administrators can access predictive analytics.');
    }

    $scope = $this->scopeService->getUserScope();
    $this->facilityCount = count($scope['facility_ids'] ?? []);
    $this->facilities = Facility::whereIn('id', $scope['facility_ids'] ?? [])
      ->select('id', 'name', 'lga')
      ->get();

    Log::info('Predictive Analytics Dashboard Initialized', [
      'user_id' => $this->user->id,
      'facility_count' => $this->facilityCount
    ]);
  }

  public function generatePredictions()
  {
    $this->isGenerating = true;
    $this->dispatch('generating');

    // Use the property directly - empty string becomes null
    $facilityId = !empty($this->selectedFacilityId) ? $this->selectedFacilityId : null;

    try {
      $cacheKey = "predictions_" . ($facilityId ?? 'all') . "_{$this->predictionHorizon}_" . $this->user->id;

      $this->predictions = Cache::remember($cacheKey, 1800, function () use ($facilityId) {
        return $this->analyticsService->generateFacilityPredictions(
          $facilityId,
          $this->predictionHorizon
        );
      });

      if (isset($this->predictions['error'])) {
        toastr()->warning($this->predictions['error']);
      } else {
        $facilityName = $facilityId
          ? Facility::find($facilityId)->name
          : "all facilities ({$this->facilityCount})";

        toastr()->info("Predictions generated for {$facilityName} with {$this->predictionHorizon}-day horizon.");
      }

      Log::info('Predictive analytics generated', [
        'facility_id' => $facilityId,
        'horizon' => $this->predictionHorizon,
        'user_id' => $this->user->id
      ]);
    } catch (\Exception $e) {
      Log::error('Predictive analytics failed: ' . $e->getMessage());
      toastr()->error('Failed to generate predictions: ' . $e->getMessage());
      $this->predictions = [];
    }

    $this->isGenerating = false;
    $this->dispatch('generation-complete');
  }
  public function updatedPredictionHorizon()
  {
    if (!empty($this->predictions)) {
      $this->generatePredictions();
    }
  }

  public function clearPredictions()
  {
    $this->predictions = [];
    $this->selectedFacilityId = null;
    toastr()->info('Predictions cleared.');
  }

  public function refreshPredictions()
  {
    $facilityId = !empty($this->selectedFacilityId) ? $this->selectedFacilityId : null;
    $cacheKey = "predictions_" . ($facilityId ?? 'all') . "_{$this->predictionHorizon}_" . $this->user->id;
    Cache::forget($cacheKey);
    $this->generatePredictions();
  }

  public function render()
  {
    $user = Auth::user();
    $layout = match (true) {
      in_array($user->role, ['State Data Administrator']) => 'layouts.stateOfficerLayout',
      in_array($user->role, ['LGA Officer']) => 'layouts.lgaOfficerLayout',
      default => 'lgaOfficerLayout'
    };
    return view('livewire.analytics.batch-predictive-dashboard')
      ->layout($layout);
  }
}
