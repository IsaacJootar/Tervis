<?php

namespace App\Livewire\Analytics;

use Livewire\Component;
use App\Models\Facility;
use App\Services\DataScopeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Services\RiskAssessmentService;
use App\Services\DashboardMetricsService;

class RealTimeDashboard extends Component
{
  public $metrics = [];
  public $selectedTimeframe = 'today';
  public $autoRefresh = true;
  public $showRiskModal = false;
  public $showServiceModal = false;
  public $selectedRiskPatient = null;
  public $selectedService = null;
  public $lastRefresh;
  public $alertFilter = 'all';
  public $selectedFacilityId = null;
  public $facilities = [];

  protected $dashboardService;
  protected $riskService;


  public function __construct()
  {
    $this->facilities = collect([]);
  }
  public function boot(DashboardMetricsService $dashboardService, RiskAssessmentService $riskService)
  {
    $this->dashboardService = $dashboardService;
    $this->riskService = $riskService;
  }

  public function mount()
  {
    $user = Auth::user();

    // Load available facilities based on scope
    $scopeService = app(DataScopeService::class);
    $scope = $scopeService->getUserScope();

    // Always initialize as a collection
    if (count($scope['facility_ids'] ?? []) > 1) {
      $this->facilities = Facility::whereIn('id', $scope['facility_ids'])
        ->select('id', 'name', 'lga', 'state')
        ->orderBy('name')
        ->get();
    } else {
      $this->facilities = collect([]); // Empty collection, not array
    }

    $this->loadMetrics();
    $this->lastRefresh = now()->format('g:i A');

    // Load auto-refresh preference from session
    $this->autoRefresh = session('dashboard_auto_refresh', false);

    Log::info('Real-Time Dashboard Initialized', [
      'user_id' => $user->id,
      'role' => $user->role,
      'facility_count' => $this->facilities->count()
    ]);
  }
  public function toggleAutoRefresh()
  {
    $this->autoRefresh = !$this->autoRefresh;
    // Save preference
    session(['dashboard_auto_refresh' => $this->autoRefresh]);
    toastr()->info('Auto-refresh ' . ($this->autoRefresh ? 'enabled' : 'disabled'));
  }

  public function loadMetrics()
  {
    try {
      $this->metrics = $this->dashboardService->getRealTimeMetrics($this->selectedFacilityId);
      $this->lastRefresh = now()->format('g:i A');

      Log::info('Dashboard metrics loaded', [
        'user_id' => Auth::id(),
        'facility_id' => $this->selectedFacilityId,
        'scope' => $this->metrics['scope_info']['scope_type'] ?? 'unknown'
      ]);
    } catch (\Exception $e) {
      Log::error('Dashboard metrics loading failed: ' . $e->getMessage());
      $this->metrics = [];
      toastr()->error('Failed to load dashboard data');
    }
  }

  public function updatedSelectedFacilityId()
  {
    $this->loadMetrics();
  }

  public function refreshData()
  {
    // Clear cache
    Cache::flush();
    $this->loadMetrics();
    $this->dispatch('metrics-updated');
    toastr()->info('Data refreshed successfully at ' . $this->lastRefresh);
  }

  public function viewRiskDetails($antenatalId)
  {
    try {
      $this->selectedRiskPatient = $this->dashboardService->getHighRiskDetails($antenatalId);
      $this->showRiskModal = true;
    } catch (\Exception $e) {
      Log::error('Failed to load risk details: ' . $e->getMessage());
      toastr()->error('Failed to load patient details.');
    }
  }

  public function closeRiskModal()
  {
    $this->showRiskModal = false;
    $this->selectedRiskPatient = null;
  }

  public function viewServiceDetails($serviceName)
  {
    $this->selectedService = [
      'name' => $serviceName,
      'data' => $this->getServiceDetails($serviceName)
    ];
    $this->showServiceModal = true;
  }

  public function closeServiceModal()
  {
    $this->showServiceModal = false;
    $this->selectedService = null;
  }

  public function filterAlerts($type)
  {
    $this->alertFilter = $type;
  }

  private function getServiceDetails($serviceName)
  {
    switch ($serviceName) {
      case 'antenatal':
        return [
          'total_count' => $this->metrics['service_coverage']['antenatal_coverage'] ?? 0,
          'today_count' => $this->metrics['today_visits']['antenatal'] ?? 0,
          'description' => 'Antenatal care registrations and follow-up visits'
        ];
      case 'delivery':
        return [
          'total_count' => $this->metrics['service_coverage']['delivery_coverage'] ?? 0,
          'today_count' => $this->metrics['today_visits']['delivery'] ?? 0,
          'cesarean_rate' => $this->metrics['clinical_outcomes']['cesarean_rate'] ?? 0,
          'description' => 'Delivery services and outcomes'
        ];
      case 'postnatal':
        return [
          'total_count' => $this->metrics['service_coverage']['postnatal_coverage'] ?? 0,
          'today_count' => $this->metrics['today_visits']['postnatal'] ?? 0,
          'description' => 'Postnatal care visits and follow-up'
        ];
      case 'tetanus':
        return [
          'total_count' => $this->metrics['vaccination_coverage']['total_vaccinated'] ?? 0,
          'today_count' => $this->metrics['today_visits']['tetanus'] ?? 0,
          'protection_rate' => $this->metrics['vaccination_coverage']['full_protection_rate'] ?? 0,
          'description' => 'Tetanus toxoid vaccination program'
        ];
      default:
        return [];
    }
  }

  public function getFilteredAlerts()
  {
    $alerts = $this->metrics['risk_alerts'] ?? collect([]);

    if ($this->alertFilter === 'all') {
      return $alerts;
    }

    return $alerts->filter(function ($alert) {
      return $alert['type'] === $this->alertFilter;
    });
  }

  public function render()
  {
    $user = Auth::user();

    // Smart layout detection based on user role
    $layout = match (true) {
      in_array($user->role, ['State Data Administrator']) => 'layouts.stateOfficerLayout',
      in_array($user->role, ['LGA Officer']) => 'layouts.lgaOfficerLayout',
      in_array($user->role, ['Facility Administrator']) => 'layouts.facilityAdminLayout',
      default => 'layouts.stateOfficerLayout'
    };

    return view('livewire.analytics.real-time-dashboard', [
      'user' => $user,
      'filteredAlerts' => $this->getFilteredAlerts()
    ])->layout($layout);
  }
}
