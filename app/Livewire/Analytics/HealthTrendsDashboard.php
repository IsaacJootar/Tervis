<?php

namespace App\Livewire\Analytics;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\HealthTrend;
use App\Models\RiskPrediction;
use App\Models\Facility;
use App\Services\DataScopeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthTrendsDashboard extends Component
{
  public $selectedTimeRange = '30'; // days
  public $selectedTrendType = 'all';
  public $selectedMetric = 'all';
  public $facilityComparison = false;
  public $selectedFacilityId = null;

  // Scope properties
  public $scopeInfo = [];
  public $availableFacilities = [];

  // Data properties
  public $trendSummary = [];
  public $riskTrends = [];
  public $clinicalTrends = [];
  public $operationalTrends = [];
  public $alertTrends = [];
  public $predictiveInsights = [];

  // Modal properties
  public $showTrendModal = false;
  public $selectedTrend = null;

  protected $scopeService;
  protected $listeners = ['refreshTrends' => 'loadTrendsData'];

  public function boot(DataScopeService $scopeService)
  {
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
            'state' => $facility->state
          ];
        })->toArray();
    }

    $this->loadTrendsData();

    Log::info('Health Trends Dashboard Initialized', [
      'user_id' => Auth::id(),
      'scope_type' => $this->scopeInfo['scope_type'],
      'facility_count' => count($this->scopeInfo['facility_ids'])
    ]);
  }

  public function loadTrendsData()
  {
    try {
      // Use selected facility or full scope
      $facilityIds = $this->selectedFacilityId
        ? [$this->selectedFacilityId]
        : $this->scopeInfo['facility_ids'];

      if (empty($facilityIds)) {
        Log::warning('No facilities in scope for health trends');
        $this->resetToEmpty();
        return;
      }

      $startDate = Carbon::now()->subDays($this->selectedTimeRange);

      $this->trendSummary = $this->getTrendSummary($facilityIds, $startDate);
      $this->riskTrends = $this->getRiskTrends($facilityIds, $startDate);
      $this->clinicalTrends = $this->getClinicalTrends($facilityIds, $startDate);
      $this->operationalTrends = $this->getOperationalTrends($facilityIds, $startDate);
      $this->alertTrends = $this->getAlertTrends($facilityIds, $startDate);
      $this->predictiveInsights = $this->generatePredictiveInsights($facilityIds);

      Log::info('Trends data loaded', [
        'facility_count' => count($facilityIds),
        'total_trends' => $this->trendSummary['total_trends'] ?? 0
      ]);
    } catch (\Exception $e) {
      Log::error('Health trends loading failed: ' . $e->getMessage());
      toastr()->error('Failed to load trends data');
      $this->resetToEmpty();
    }
  }

  public function updatedSelectedTimeRange()
  {
    $this->loadTrendsData();
  }

  public function updatedSelectedTrendType()
  {
    $this->loadTrendsData();
  }

  public function updatedSelectedFacilityId()
  {
    $this->loadTrendsData();

    if ($this->selectedFacilityId) {
      $facilityName = Facility::find($this->selectedFacilityId)->name ?? 'Unknown';
      toastr()->info("Viewing trends for {$facilityName}");
    } else {
      toastr()->info("Viewing trends for all facilities in scope");
    }
  }

  public function resetToScope()
  {
    $this->selectedFacilityId = null;
    $this->loadTrendsData();
  }

  private function getTrendSummary($facilityIds, $startDate)
  {
    $trends = HealthTrend::whereIn('facility_id', $facilityIds)
      ->where('period_start', '>=', $startDate)
      ->get();

    return [
      'total_trends' => $trends->count(),
      'urgent_alerts' => $trends->where('alert_level', 'urgent')->count(),
      'warning_alerts' => $trends->where('alert_level', 'warning')->count(),
      'trending_up' => $trends->where('trend_direction', 'increasing')->count(),
      'trending_down' => $trends->where('trend_direction', 'decreasing')->count(),
      'stable_trends' => $trends->where('trend_direction', 'stable')->count(),
      'critical_trends' => $trends->where('trend_severity', 'critical')->count(),
      'interventions_needed' => $trends->where('requires_intervention', true)->count(),
      'facility_count' => count($facilityIds)
    ];
  }

  private function getRiskTrends($facilityIds, $startDate)
  {
    return HealthTrend::whereIn('facility_id', $facilityIds)
      ->where('trend_type', 'risk_distribution')
      ->where('period_start', '>=', $startDate)
      ->orderBy('period_start', 'desc')
      ->get()
      ->map(function ($trend) {
        return [
          'id' => $trend->id,
          'metric_name' => $trend->metric_name,
          'current_value' => $trend->current_value,
          'previous_value' => $trend->previous_value,
          'percentage_change' => $trend->percentage_change,
          'trend_direction' => $trend->trend_direction,
          'alert_level' => $trend->alert_level,
          'period_start' => $trend->period_start,
          'sample_size' => $trend->sample_size,
          'facility_name' => $trend->facility->name ?? 'Unknown',
          'interpretation' => $this->generateTrendInterpretation($trend)
        ];
      });
  }

  private function getClinicalTrends($facilityIds, $startDate)
  {
    return HealthTrend::whereIn('facility_id', $facilityIds)
      ->where('trend_category', 'clinical')
      ->where('period_start', '>=', $startDate)
      ->orderBy('period_start', 'desc')
      ->get()
      ->groupBy('metric_name')
      ->map(function ($trends, $metricName) {
        $latest = $trends->first();
        $historical = $trends->take(5);

        return [
          'metric_name' => $metricName,
          'current_value' => $latest->current_value,
          'trend_direction' => $latest->trend_direction,
          'alert_level' => $latest->alert_level,
          'historical_data' => $historical->map(function ($trend) {
            return [
              'period' => $trend->period_start->format('M d'),
              'value' => $trend->current_value
            ];
          })->reverse()->values()
        ];
      });
  }

  private function getOperationalTrends($facilityIds, $startDate)
  {
    return HealthTrend::whereIn('facility_id', $facilityIds)
      ->where('trend_category', 'operational')
      ->where('period_start', '>=', $startDate)
      ->orderBy('period_start', 'desc')
      ->get()
      ->map(function ($trend) {
        return [
          'id' => $trend->id,
          'metric_name' => $trend->metric_name,
          'current_value' => $trend->current_value,
          'trend_direction' => $trend->trend_direction,
          'alert_level' => $trend->alert_level,
          'period_start' => $trend->period_start,
          'facility_name' => $trend->facility->name ?? 'Unknown',
          'improvement_suggestion' => $this->getImprovementSuggestion($trend)
        ];
      });
  }

  private function getAlertTrends($facilityIds, $startDate)
  {
    return HealthTrend::whereIn('facility_id', $facilityIds)
      ->where('trend_type', 'alert_pattern')
      ->where('period_start', '>=', $startDate)
      ->orderBy('period_start', 'desc')
      ->get()
      ->map(function ($trend) {
        return [
          'id' => $trend->id,
          'metric_name' => $trend->metric_name,
          'current_value' => $trend->current_value,
          'alert_level' => $trend->alert_level,
          'requires_intervention' => $trend->requires_intervention,
          'ai_interpretation' => $trend->ai_interpretation,
          'recommended_actions' => $trend->recommended_actions,
          'period_start' => $trend->period_start,
          'facility_name' => $trend->facility->name ?? 'Unknown'
        ];
      });
  }

  private function generatePredictiveInsights($facilityIds)
  {
    $recentTrends = HealthTrend::whereIn('facility_id', $facilityIds)
      ->where('period_start', '>=', Carbon::now()->subDays(14))
      ->get();

    $insights = [];

    // Predict risk level changes
    $riskTrends = $recentTrends->where('trend_type', 'risk_distribution');
    if ($riskTrends->count() > 0) {
      $criticalTrend = $riskTrends->where('metric_name', 'like', '%critical%')->first();
      if ($criticalTrend && $criticalTrend->trend_direction === 'increasing') {
        $insights[] = [
          'type' => 'risk_prediction',
          'title' => 'Critical Risk Cases Increasing',
          'prediction' => 'Based on current trends across ' . count($facilityIds) . ' facility(ies), critical risk cases may increase by ' .
            round($criticalTrend->percentage_change, 1) . '% in the next 2 weeks',
          'confidence' => 'Medium',
          'recommendation' => 'Consider increasing staff allocation and preparing emergency protocols'
        ];
      }
    }

    // Predict AI performance trends
    $aiTrends = $recentTrends->where('trend_type', 'ai_performance');
    if ($aiTrends->count() > 0) {
      $confidenceTrend = $aiTrends->where('metric_name', 'assessment_confidence')->first();
      if ($confidenceTrend && $confidenceTrend->current_value < 75) {
        $insights[] = [
          'type' => 'ai_performance',
          'title' => 'AI Confidence Declining',
          'prediction' => 'AI assessment confidence is below optimal levels',
          'confidence' => 'High',
          'recommendation' => 'Review data quality and consider model retraining'
        ];
      }
    }

    // Predict service utilization
    $serviceUtilization = $this->predictServiceUtilization($facilityIds);
    if ($serviceUtilization) {
      $insights[] = $serviceUtilization;
    }

    return $insights;
  }

  private function predictServiceUtilization($facilityIds)
  {
    $recentPredictions = RiskPrediction::whereIn('facility_id', $facilityIds)
      ->where('assessment_date', '>=', Carbon::now()->subDays(7))
      ->get();

    $highRiskCount = $recentPredictions->whereIn('risk_level', ['high', 'critical'])->count();
    $totalCount = $recentPredictions->count();

    if ($totalCount > 0) {
      $highRiskPercentage = ($highRiskCount / $totalCount) * 100;

      if ($highRiskPercentage > 30) {
        return [
          'type' => 'service_utilization',
          'title' => 'High-Risk Patient Load Increasing',
          'prediction' => 'High-risk patients represent ' . round($highRiskPercentage, 1) .
            '% of recent assessments across ' . count($facilityIds) . ' facility(ies). Expect increased demand for specialized care.',
          'confidence' => 'High',
          'recommendation' => 'Prepare additional resources for high-risk pregnancy management'
        ];
      }
    }

    return null;
  }

  private function generateTrendInterpretation($trend)
  {
    $direction = $trend->trend_direction;
    $metricName = $trend->metric_name;
    $change = $trend->percentage_change;

    if (str_contains($metricName, 'critical_risk')) {
      if ($direction === 'increasing') {
        return "Critical risk cases are increasing by {$change}% - immediate attention required";
      } else {
        return "Critical risk cases are decreasing by {$change}% - positive trend";
      }
    }

    if (str_contains($metricName, 'confidence')) {
      if ($direction === 'decreasing') {
        return "AI assessment confidence is declining - data quality review needed";
      } else {
        return "AI assessment confidence is improving - system performing well";
      }
    }

    return "Trend analysis: {$direction} pattern observed";
  }

  private function getImprovementSuggestion($trend)
  {
    $metricName = $trend->metric_name;

    if (str_contains($metricName, 'confidence') && $trend->current_value < 80) {
      return 'Improve data completeness and quality to increase AI confidence';
    }

    if (str_contains($metricName, 'assessment') && $trend->trend_direction === 'decreasing') {
      return 'Consider increasing assessment frequency for better outcomes';
    }

    return 'Monitor trend and maintain current protocols';
  }

  public function viewTrendDetails($trendId)
  {
    $this->selectedTrend = HealthTrend::with('facility')->find($trendId);
    $this->showTrendModal = true;
  }

  public function closeTrendModal()
  {
    $this->showTrendModal = false;
    $this->selectedTrend = null;
  }

  public function refreshTrends()
  {
    $this->loadTrendsData();
    toastr()->success('Trends data refreshed successfully');
  }

  public function toggleFacilityComparison()
  {
    $this->facilityComparison = !$this->facilityComparison;
  }

  private function resetToEmpty()
  {
    $this->trendSummary = [
      'total_trends' => 0,
      'urgent_alerts' => 0,
      'warning_alerts' => 0,
      'trending_up' => 0,
      'trending_down' => 0,
      'stable_trends' => 0,
      'critical_trends' => 0,
      'interventions_needed' => 0
    ];
    $this->riskTrends = [];
    $this->clinicalTrends = [];
    $this->operationalTrends = [];
    $this->alertTrends = [];
    $this->predictiveInsights = [];
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

    return view('livewire.analytics.health-trends-dashboard', [
      'user' => $user,
      'selectedTimeRange' => $this->selectedTimeRange,
      'trendSummary' => $this->trendSummary,
      'riskTrends' => $this->riskTrends,
      'clinicalTrends' => $this->clinicalTrends,
      'operationalTrends' => $this->operationalTrends,
      'alertTrends' => $this->alertTrends,
      'predictiveInsights' => $this->predictiveInsights
    ])->layout($layout);
  }
}
