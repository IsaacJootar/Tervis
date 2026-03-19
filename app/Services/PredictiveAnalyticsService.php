<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\HealthTrend;
use App\Models\RiskPrediction;
use App\Models\Antenatal;
use App\Models\Delivery;
use App\Models\PostnatalRecord;
use App\Models\Facility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PredictiveAnalyticsService
{
  private const RISK_LEVELS = ['critical', 'high', 'moderate', 'low'];
  private const RISK_HISTORY_DAYS = 90;
  private const SERVICE_HISTORY_DAYS = 60;
  private const OUTCOME_HISTORY_DAYS = 30;
  private const TREND_LOOKBACK_DAYS = 14;
  private const MIN_POINTS_FOR_PREDICTION = 7;
  private const TREND_SLOPE_THRESHOLD = 0.1;
  private const HIGH_RISK_ALERT_CASES = 5;
  private const LOW_CONFIDENCE_ALERT_THRESHOLD = 70;
  private const POSTNATAL_COVERAGE_THRESHOLD = 60;
  private const HIGH_PREDICTED_CASES_THRESHOLD = 10;
  private const STAFF_PER_HIGH_RISK_CASES = 10;
  private const SPECIALIST_HOURS_PER_HIGH_RISK_CASE = 2;
  private const MONITORING_EQUIPMENT_CAPACITY = 20;
  private const DELIVERY_BED_MULTIPLIER = 1.2;
  private const POSTNATAL_BED_MULTIPLIER = 2.5;
  private const OCCUPANCY_MULTIPLIER = 3;
  private const TOTAL_BED_CAPACITY = 20;
  private const EMERGENCY_KIT_PER_CRITICAL_CASES = 5;
  private const RATE_HIGH_MULTIPLIER = 1.2;
  private const RATE_IMPROVING_MULTIPLIER = 0.8;
  private const RISK_INCREASE_INFLUENCE = 0.3;

  protected $scopeService;

  public function __construct(DataScopeService $scopeService)
  {
    $this->scopeService = $scopeService;
  }

  /**
   * Generate comprehensive facility health predictions
   */
  public function generateFacilityPredictions($facilityId = null, $predictionHorizon = 30)
  {
    if ($facilityId) {
      $facilityIds = [$facilityId];
    } else {
      $scope = $this->scopeService->getUserScope();
      $facilityIds = $scope['facility_ids'];
    }

    if (empty($facilityIds)) {
      return ['error' => 'No facilities found in your scope'];
    }

    try {
      $predictions = [
        'risk_predictions' => $this->predictRiskTrends($facilityIds, $predictionHorizon),
        'service_utilization' => $this->predictServiceUtilization($facilityIds, $predictionHorizon),
        'resource_requirements' => $this->predictResourceNeeds($facilityIds, $predictionHorizon),
        'outcome_forecasts' => $this->predictHealthOutcomes($facilityIds, $predictionHorizon),
        'seasonal_patterns' => $this->identifySeasonalPatterns($facilityIds),
        'intervention_opportunities' => $this->identifyInterventionOpportunities($facilityIds),
        'facility_count' => count($facilityIds)
      ];

      $this->storePredictions($facilityIds, $predictions, $predictionHorizon);

      return $predictions;
    } catch (\Exception $e) {
      Log::error('Predictive analytics generation failed: ' . $e->getMessage());
      throw $e;
    }
  }

  /**
   * Predict risk level trends based on historical patterns
   */
  private function predictRiskTrends($facilityIds, $days)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    // Get historical risk data
    $historicalData = RiskPrediction::whereIn('facility_id', $facilityIds)
      ->where('assessment_date', '>=', Carbon::now()->subDays(self::RISK_HISTORY_DAYS))
      ->select(
        DB::raw('DATE(assessment_date) as date'),
        DB::raw('COUNT(*) as total_assessments'),
        DB::raw('SUM(CASE WHEN risk_level = "critical" THEN 1 ELSE 0 END) as critical_count'),
        DB::raw('SUM(CASE WHEN risk_level = "high" THEN 1 ELSE 0 END) as high_count'),
        DB::raw('SUM(CASE WHEN risk_level = "moderate" THEN 1 ELSE 0 END) as moderate_count'),
        DB::raw('SUM(CASE WHEN risk_level = "low" THEN 1 ELSE 0 END) as low_count')
      )
      ->groupBy(DB::raw('DATE(assessment_date)'))
      ->orderBy('date')
      ->get();

    if ($historicalData->count() < self::MIN_POINTS_FOR_PREDICTION) {
      return ['error' => 'Insufficient historical data for prediction'];
    }

    // Calculate trends for each risk level
    $predictions = [];
    $riskLevels = self::RISK_LEVELS;

    foreach ($riskLevels as $level) {
      $values = $historicalData->pluck($level . '_count')->toArray();
      $trend = $this->calculateLinearTrend($values);

      $windowValues = array_slice($values, -1 * self::MIN_POINTS_FOR_PREDICTION);
      $currentAverage = count($windowValues) > 0 ? array_sum($windowValues) / count($windowValues) : 0;
      $predictedValue = $currentAverage + ($trend['slope'] * $days);

      $predictions[$level] = [
        'current_average' => round($currentAverage, 1),
        'predicted_value' => max(0, round($predictedValue, 1)),
        'trend_direction' => $this->resolveTrendDirection((float)$trend['slope']),
        'confidence' => $this->calculatePredictionConfidence($trend['r_squared']),
        'change_percentage' => $currentAverage > 0 ? round((($predictedValue - $currentAverage) / $currentAverage) * 100, 1) : 0
      ];
    }

    $totalPredicted = array_sum(array_column($predictions, 'predicted_value'));

    return [
      'prediction_horizon_days' => $days,
      'risk_levels' => $predictions,
      'total_predicted_assessments' => $totalPredicted,
      'high_risk_percentage' => $totalPredicted > 0
        ? round((($predictions['critical']['predicted_value'] + $predictions['high']['predicted_value']) / $totalPredicted) * 100, 1)
        : 0,
      'recommendations' => $this->generateRiskPredictionRecommendations($predictions)
    ];
  }

  /**
   * Predict service utilization patterns
   */
  private function predictServiceUtilization($facilityIds, $days)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    $services = [
      'antenatal' => Antenatal::class,
      'delivery' => Delivery::class,
      'postnatal' => PostnatalRecord::class
    ];

    $predictions = [];

    foreach ($services as $serviceName => $model) {
      $dateField = $serviceName === 'antenatal' ? 'date_of_booking' : ($serviceName === 'delivery' ? 'dodel' : 'visit_date');
      $facilityField = 'facility_id';

      $historicalData = $model::whereIn($facilityField, $facilityIds)
        ->where($dateField, '>=', Carbon::now()->subDays(self::SERVICE_HISTORY_DAYS))
        ->select(
          DB::raw("DATE($dateField) as date"),
          DB::raw('COUNT(*) as count')
        )
        ->groupBy(DB::raw("DATE($dateField)"))
        ->orderBy('date')
        ->get();

      if ($historicalData->count() >= self::MIN_POINTS_FOR_PREDICTION) {
        $values = $historicalData->pluck('count')->toArray();
        $trend = $this->calculateLinearTrend($values);
        $windowValues = array_slice($values, -1 * self::MIN_POINTS_FOR_PREDICTION);
        $currentAverage = count($windowValues) > 0 ? array_sum($windowValues) / count($windowValues) : 0;
        $predictedDaily = max(0, $currentAverage + ($trend['slope'] * ($days / 7)));

        $predictions[$serviceName] = [
          'current_daily_average' => round($currentAverage, 1),
          'predicted_daily_average' => round($predictedDaily, 1),
          'predicted_total' => round($predictedDaily * $days),
          'trend_direction' => $this->resolveTrendDirection((float)$trend['slope']),
          'confidence' => $this->calculatePredictionConfidence($trend['r_squared'])
        ];
      }
    }

    return $predictions;
  }

  /**
   * Predict resource requirements based on trends
   */
  private function predictResourceNeeds($facilityIds, $days)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    $riskPredictions = $this->predictRiskTrends($facilityIds, $days);
    $servicePredictions = $this->predictServiceUtilization($facilityIds, $days);

    $predictions = [];

    // Staff requirements based on high-risk cases
    if (isset($riskPredictions['risk_levels'])) {
      $highRiskCases = $riskPredictions['risk_levels']['critical']['predicted_value'] +
        $riskPredictions['risk_levels']['high']['predicted_value'];

      $predictions['staffing'] = [
        'additional_nurses_needed' => ceil($highRiskCases / self::STAFF_PER_HIGH_RISK_CASES),
        'specialist_hours_needed' => ceil($highRiskCases * self::SPECIALIST_HOURS_PER_HIGH_RISK_CASE),
        'monitoring_equipment_utilization' => min(100, ($highRiskCases / self::MONITORING_EQUIPMENT_CAPACITY) * 100)
      ];
    }

    // Bed capacity requirements
    if (isset($servicePredictions['delivery'])) {
      $predictions['bed_capacity'] = [
        'delivery_beds_needed' => ceil($servicePredictions['delivery']['predicted_daily_average'] * self::DELIVERY_BED_MULTIPLIER),
        'postnatal_beds_needed' => ceil($servicePredictions['delivery']['predicted_daily_average'] * self::POSTNATAL_BED_MULTIPLIER),
        'occupancy_rate_prediction' => min(100, ($servicePredictions['delivery']['predicted_daily_average'] * self::OCCUPANCY_MULTIPLIER) / self::TOTAL_BED_CAPACITY * 100)
      ];
    }

    // Supply requirements
    $predictions['supplies'] = [
      'emergency_kits_needed' => isset($riskPredictions['risk_levels']) ?
        ceil($riskPredictions['risk_levels']['critical']['predicted_value'] / self::EMERGENCY_KIT_PER_CRITICAL_CASES) : 0,
      'medication_stock_multiplier' => $this->calculateMedicationMultiplier($facilityIds),
      'equipment_maintenance_priority' => $this->getEquipmentMaintenancePriority($facilityIds)
    ];

    return $predictions;
  }

  /**
   * Predict health outcomes based on current trends
   */
  private function predictHealthOutcomes($facilityIds, $days)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    // Analyze recent outcomes
    $recentDeliveries = Delivery::whereIn('facility_id', $facilityIds)
      ->where('dodel', '>=', Carbon::now()->subDays(self::OUTCOME_HISTORY_DAYS))
      ->get();

    if ($recentDeliveries->count() < 5) {
      return ['error' => 'Insufficient delivery data for outcome prediction'];
    }

    $outcomes = [
      'cesarean_rate' => $recentDeliveries->where('mod', 'CS')->count() / $recentDeliveries->count() * 100,
      'complication_rate' => $recentDeliveries->where('complications', '!=', '')->count() / $recentDeliveries->count() * 100,
      'preterm_rate' => $recentDeliveries->where('pre_term', 'yes')->count() / $recentDeliveries->count() * 100
    ];

    // Predict changes based on current risk trends
    $riskTrends = $this->predictRiskTrends($facilityIds, $days);
    $predictions = [];

    foreach ($outcomes as $outcome => $currentRate) {
      $riskMultiplier = 1.0;
      if (isset($riskTrends['risk_levels'])) {
        $highRiskIncrease = $riskTrends['risk_levels']['high']['change_percentage'] ?? 0;
        $riskMultiplier = 1 + ($highRiskIncrease / 100 * self::RISK_INCREASE_INFLUENCE);
      }

      $predictedRate = $currentRate * $riskMultiplier;
      $predictions[$outcome] = [
        'current_rate' => round($currentRate, 1),
        'predicted_rate' => round($predictedRate, 1),
        'change_percentage' => $currentRate > 0 ? round((($predictedRate - $currentRate) / $currentRate) * 100, 1) : 0,
        'risk_level' => $predictedRate > $currentRate * self::RATE_HIGH_MULTIPLIER
          ? 'high'
          : ($predictedRate < $currentRate * self::RATE_IMPROVING_MULTIPLIER ? 'improving' : 'stable')
      ];
    }

    return $predictions;
  }

  /**
   * Identify seasonal patterns in health data
   */
  private function identifySeasonalPatterns($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    //
    $monthlyData = RiskPrediction::whereIn('facility_id', $facilityIds)
      ->where('assessment_date', '>=', Carbon::now()->subYear())
      ->select(
        DB::raw('MONTH(assessment_date) as month'),
        DB::raw('COUNT(*) as total_assessments'),
        DB::raw('AVG(total_risk_score) as avg_risk_score'),
        DB::raw('SUM(CASE WHEN risk_level IN ("high", "critical") THEN 1 ELSE 0 END) as high_risk_count')
      )
      ->groupBy(DB::raw('MONTH(assessment_date)'))
      ->orderBy('month')
      ->get();

    if ($monthlyData->count() < 6) {
      return ['error' => 'Insufficient data for seasonal analysis'];
    }

    $patterns = [];
    $currentMonth = Carbon::now()->month;

    foreach ($monthlyData as $data) {
      $patterns[$data->month] = [
        'total_assessments' => $data->total_assessments,
        'avg_risk_score' => round($data->avg_risk_score, 1),
        'high_risk_percentage' => round(($data->high_risk_count / $data->total_assessments) * 100, 1)
      ];
    }

    // Identify peak months
    $highRiskPercentages = array_column($patterns, 'high_risk_percentage');
    $peakMonth = array_keys($patterns)[array_search(max($highRiskPercentages), $highRiskPercentages)];
    $lowMonth = array_keys($patterns)[array_search(min($highRiskPercentages), $highRiskPercentages)];

    return [
      'monthly_patterns' => $patterns,
      'peak_risk_month' => $peakMonth,
      'lowest_risk_month' => $lowMonth,
      'seasonal_variance' => round((max($highRiskPercentages) - min($highRiskPercentages)), 1),
      'current_month_prediction' => $patterns[$currentMonth] ?? null,
      'recommendations' => $this->generateSeasonalRecommendations($peakMonth, $lowMonth, $currentMonth)
    ];
  }

  /**
   * Identify intervention opportunities
   */
  private function identifyInterventionOpportunities($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);
    $opportunities = [];

    // Check for increasing trends that could be addressed
    $recentTrends = HealthTrend::whereIn('facility_id', $facilityIds)
      ->where('period_start', '>=', Carbon::now()->subDays(self::TREND_LOOKBACK_DAYS))
      ->where('trend_direction', 'increasing')
      ->where('trend_severity', '!=', 'minimal')
      ->get();

    foreach ($recentTrends as $trend) {
      if (str_contains($trend->metric_name, 'critical') && $trend->current_value > self::HIGH_RISK_ALERT_CASES) {
        $opportunities[] = [
          'type' => 'immediate_intervention',
          'priority' => 'high',
          'title' => 'Critical Risk Cases Spike',
          'description' => 'Critical risk cases have increased to ' . $trend->current_value . ' cases',
          'facility_id' => $trend->facility_id,
          'intervention' => 'Implement emergency response protocol',
          'expected_impact' => 'Reduce critical cases by 30-40%',
          'timeline' => 'Immediate (24-48 hours)'
        ];
      }

      if (str_contains($trend->metric_name, 'confidence') && $trend->current_value < self::LOW_CONFIDENCE_ALERT_THRESHOLD) {
        $opportunities[] = [
          'type' => 'system_improvement',
          'priority' => 'medium',
          'title' => 'AI Assessment Accuracy Declining',
          'description' => 'AI confidence has dropped to ' . $trend->current_value . '%',
          'facility_id' => $trend->facility_id,
          'intervention' => 'Data quality improvement and model retraining',
          'expected_impact' => 'Increase accuracy by 15-20%',
          'timeline' => 'Short-term (1-2 weeks)'
        ];
      }
    }

    // Check for underutilized services
    $serviceUtilization = $this->analyzeServiceUtilization($facilityIds);
    if ($serviceUtilization['postnatal_coverage'] < self::POSTNATAL_COVERAGE_THRESHOLD) {
      $opportunities[] = [
        'type' => 'service_enhancement',
        'priority' => 'medium',
        'title' => 'Low Postnatal Follow-up Coverage',
        'description' => 'Only ' . $serviceUtilization['postnatal_coverage'] . '% of mothers receive postnatal care',
        'intervention' => 'Implement postnatal reminder system and community outreach',
        'expected_impact' => 'Increase coverage to 80%+',
        'timeline' => 'Medium-term (1-3 months)'
      ];
    }

    return $opportunities;
  }

  /**
   * Calculate linear trend from array of values
   */
  private function calculateLinearTrend($values)
  {
    $n = count($values);
    if ($n < 2) return ['slope' => 0, 'r_squared' => 0];

    $x = range(1, $n);
    $sumX = array_sum($x);
    $sumY = array_sum($values);
    $sumXY = 0;
    $sumXX = 0;

    for ($i = 0; $i < $n; $i++) {
      $sumXY += $x[$i] * $values[$i];
      $sumXX += $x[$i] * $x[$i];
    }

    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;

    // Calculate R-squared
    $meanY = $sumY / $n;
    $ssTotal = 0;
    $ssRes = 0;

    for ($i = 0; $i < $n; $i++) {
      $predicted = $intercept + $slope * $x[$i];
      $ssTotal += pow($values[$i] - $meanY, 2);
      $ssRes += pow($values[$i] - $predicted, 2);
    }

    $rSquared = $ssTotal > 0 ? 1 - ($ssRes / $ssTotal) : 0;

    return [
      'slope' => $slope,
      'intercept' => $intercept,
      'r_squared' => max(0, min(1, $rSquared))
    ];
  }

  /**
   * Calculate prediction confidence based on R-squared
   */
  private function calculatePredictionConfidence($rSquared)
  {
    if ($rSquared >= 0.8) return 'High';
    if ($rSquared >= 0.6) return 'Medium';
    if ($rSquared >= 0.3) return 'Low';
    return 'Very Low';
  }

  private function resolveTrendDirection(float $slope): string
  {
    if ($slope > self::TREND_SLOPE_THRESHOLD) {
      return 'increasing';
    }

    if ($slope < (-1 * self::TREND_SLOPE_THRESHOLD)) {
      return 'decreasing';
    }

    return 'stable';
  }

  // Helper methods
  private function generateRiskPredictionRecommendations($predictions)
  {
    $recommendations = [];

    if ($predictions['critical']['trend_direction'] === 'increasing') {
      $recommendations[] = 'Prepare emergency protocols for increasing critical cases';
    }

    if ($predictions['high']['predicted_value'] > self::HIGH_PREDICTED_CASES_THRESHOLD) {
      $recommendations[] = 'Consider additional specialist consultations';
    }

    return $recommendations;
  }

  private function calculateMedicationMultiplier($facilityIds)
  {
    return 1.2;
  }

  private function getEquipmentMaintenancePriority($facilityIds)
  {
    return ['monitoring_equipment', 'delivery_beds', 'emergency_kits'];
  }

  private function generateSeasonalRecommendations($peakMonth, $lowMonth, $currentMonth)
  {
    $recommendations = [];

    if ($currentMonth === $peakMonth) {
      $recommendations[] = 'Peak season: Increase staffing and emergency preparedness';
    }

    if (abs($currentMonth - $peakMonth) <= 1) {
      $recommendations[] = 'Approaching peak season: Prepare additional resources';
    }

    return $recommendations;
  }

  private function analyzeServiceUtilization($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);


    $totalPatients = Antenatal::whereIn('facility_id', $facilityIds)->count();
    $postnatalPatients = PostnatalRecord::whereIn('facility_id', $facilityIds)->distinct('patient_id')->count('patient_id');

    return [
      'postnatal_coverage' => $totalPatients > 0 ? ($postnatalPatients / $totalPatients) * 100 : 0
    ];
  }

  private function storePredictions($facilityIds, $predictions, $horizon)
  {
    Log::info("Predictions generated for facilities: " . implode(',', $facilityIds) . " with {$horizon} day horizon");
  }
}
