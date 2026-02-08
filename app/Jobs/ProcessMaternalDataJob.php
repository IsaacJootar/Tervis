<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\User;
use App\Models\RiskFactor;
use App\Models\HealthTrend;
use Illuminate\Bus\Queueable;
use App\Models\RiskPrediction;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\EnhancedRiskAssessmentService;
use App\Services\PredictiveAnalyticsService;

class ProcessMaternalDataJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $userId;
  protected $options;
  protected $triggerType;

  public $timeout = 300; // 5 minutes
  public $tries = 3;

  /**
   * Create a new job instance.
   */
  public function __construct($userId, $options = [], $triggerType = 'manual')
  {
    $this->userId = $userId;
    $this->options = $options;
    $this->triggerType = $triggerType;
  }

  /**
   * Execute the job.
   */
  public function handle(EnhancedRiskAssessmentService $riskService)
  {
    try {
      Log::info("Starting AI risk assessment for user {$this->userId}", [
        'trigger_type' => $this->triggerType,
        'options' => $this->options
      ]);

      // Verify user exists and has required data
      $user = User::with(['antenatal', 'deliveries', 'postnatalRecords'])->find($this->userId);

      if (!$user) {
        Log::error("User not found for AI assessment: {$this->userId}");
        return;
      }

      if (!$user->antenatal) {
        Log::warning("User {$this->userId} has no antenatal record, skipping AI assessment");
        return;
      }

      // Add job metadata to options
      $options = array_merge($this->options, [
        'job_id' => $this->job->getJobId(),
        'trigger_type' => $this->triggerType,
        'processing_time' => Carbon::now(),
        'assessment_type' => $this->determineAssessmentType()
      ]);

      // Perform the AI assessment
      $riskPrediction = $riskService->performAIRiskAssessment($this->userId, $options);

      Log::info("AI risk assessment completed for user {$this->userId}", [
        'prediction_id' => $riskPrediction->id,
        'risk_level' => $riskPrediction->risk_level,
        'risk_score' => $riskPrediction->total_risk_score,
        'processing_duration' => Carbon::now()->diffInSeconds($options['processing_time'])
      ]);

      // Queue follow-up actions if needed
      $this->queueFollowUpActions($riskPrediction);
    } catch (\Exception $e) {
      Log::error("AI risk assessment failed for user {$this->userId}", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      // Re-throw to trigger retry mechanism
      throw $e;
    }
  }

  /**
   * Determine the type of assessment based on trigger and user data
   */
  private function determineAssessmentType()
  {
    if (isset($this->options['assessment_type'])) {
      return $this->options['assessment_type'];
    }

    return match ($this->triggerType) {
      'scheduled' => 'routine_scheduled',
      'data_update' => 'triggered_by_update',
      'manual' => 'manual_request',
      'emergency' => 'emergency_assessment',
      'follow_up' => 'follow_up_assessment',
      default => 'general'
    };
  }

  /**
   * Queue additional actions based on assessment results
   */
  private function queueFollowUpActions($riskPrediction)
  {
    // Queue immediate alerts for critical cases
    if ($riskPrediction->risk_level === 'critical') {
      dispatch(new SendCriticalRiskAlertJob($riskPrediction->id));
    }

    // Queue trend analysis update
    if ($this->triggerType !== 'emergency') {
      dispatch(new UpdateHealthTrendsJob($riskPrediction->facility_id))->delay(now()->addMinutes(5));
    }

    // Queue accuracy validation for older predictions
    if (rand(1, 10) === 1) { // 10% chance to validate accuracy
      dispatch(new ValidatePredictionAccuracyJob($riskPrediction->facility_id))->delay(now()->addMinutes(30));
    }

    // Schedule next assessment if needed
    if ($riskPrediction->next_assessment_due) {
      $delay = Carbon::parse($riskPrediction->next_assessment_due)->diffInMinutes(now());
      if ($delay > 0 && $delay < (7 * 24 * 60)) { // Within next week
        dispatch(new ProcessMaternalDataJob(
          $this->userId,
          ['assessment_type' => 'scheduled_follow_up'],
          'scheduled'
        ))->delay(now()->addMinutes($delay));
      }
    }
  }

  /**
   * Handle job failure
   */
  public function failed(\Throwable $exception)
  {
    Log::error("ProcessMaternalDataJob failed permanently for user {$this->userId}", [
      'error' => $exception->getMessage(),
      'attempts' => $this->attempts(),
      'trigger_type' => $this->triggerType
    ]);

    // Optionally notify administrators of the failure
    // dispatch(new NotifyAdministratorsJob('ai_assessment_failure', $this->userId)); // should i be notifying patient? should the admin
  }

  /**
   * Determine the delay before retrying
   */
  public function backoff()
  {
    return [30, 120, 300]; // 30 seconds, 2 minutes, 5 minutes
  }
}

/**
 * Job for sending critical risk alerts
 */
class SendCriticalRiskAlertJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $predictionId;

  public function __construct($predictionId)
  {
    $this->predictionId = $predictionId;
  }

  public function handle()
  {
    try {
      $prediction = RiskPrediction::with(['user', 'facility'])->find($this->predictionId);

      if (!$prediction) {
        Log::warning("RiskPrediction not found for alert: {$this->predictionId}");
        return;
      }

      Log::critical("CRITICAL RISK ALERT", [
        'patient_din' => $prediction->user->DIN,
        'patient_name' => $prediction->user->first_name . ' ' . $prediction->user->last_name,
        'facility' => $prediction->facility->name,
        'risk_score' => $prediction->total_risk_score,
        'risk_factors' => count($prediction->identified_risks ?? []),
        'gestational_age' => $prediction->gestational_age_weeks . ' weeks',
        'assessment_date' => $prediction->assessment_date,
        'prediction_id' => $prediction->id
      ]);

      // Here i could integrate with: or then log failure
      // - SMS service for immediate notifications
      // - Email alerts to supervisors
      // - Dashboard real-time notifications
      // - Integration with hospital alert systems

    } catch (\Exception $e) {
      Log::error("Failed to send critical risk alert", [
        'prediction_id' => $this->predictionId,
        'error' => $e->getMessage()
      ]);
    }
  }
}

/**
 * Job for updating health trends
 */

// Enhancement to your existing UpdateHealthTrendsJob class
// Add this to your existing UpdateHealthTrendsJob in ProcessMaternalDataJob.php

/**
 * Enhanced Job for updating health trends with predictive analytics
 */
class UpdateHealthTrendsJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $facilityId;

  public function __construct($facilityId)
  {
    $this->facilityId = $facilityId;
  }

  public function handle()
  {
    //  Risks and trends
    $this->updateRiskDistributionTrends();
    $this->updateAIPerformanceMetrics();
    $this->checkForConcerningTrends();

    // Risk factor specific tracking
    $this->updateRiskFactorTrends();
    $this->analyzeRiskFactorInteractions();
    $this->identifyEmergingRiskFactors();

    // Predictive analytics
    $this->generatePredictiveInsights();
    $this->updatePredictiveTrends();
    $this->identifyInterventionOpportunities();
  }



  private function updateRiskDistributionTrends()
  { /* existing code */
  }
  private function updateAIPerformanceMetrics()
  { /* existing code */
  }
  private function checkForConcerningTrends()
  { /* existing code */
  }

  private function updateRiskFactorTrends()
  {
    // Get all risk factors from recent predictions
    $recentPredictions = RiskPrediction::where('facility_id', $this->facilityId)
      ->where('assessment_date', '>=', Carbon::now()->subDays(30))
      ->get();

    // Count occurrence of each risk factor
    $factorCounts = [];
    $factorAccuracy = [];

    foreach ($recentPredictions as $prediction) {
      if ($prediction->identified_risks) {
        foreach ($prediction->identified_risks as $risk) {
          $factorCode = $risk['factor'];
          $factorCounts[$factorCode] = ($factorCounts[$factorCode] ?? 0) + 1;

          // Track accuracy if outcomes are verified
          if ($prediction->outcome_verified && $prediction->actual_outcomes) {
            $accuracy = $this->calculateFactorAccuracy($risk, $prediction);
            if ($accuracy !== null) {
              if (!isset($factorAccuracy[$factorCode])) {
                $factorAccuracy[$factorCode] = [];
              }
              $factorAccuracy[$factorCode][] = $accuracy;
            }
          }
        }
      }
    }

    // Create trends for each risk factor
    foreach ($factorCounts as $factorCode => $currentCount) {
      // Get previous month count for comparison
      $previousMonth = Carbon::now()->subMonth();
      $previousPredictions = RiskPrediction::where('facility_id', $this->facilityId)
        ->whereBetween('assessment_date', [
          $previousMonth->startOfMonth(),
          $previousMonth->endOfMonth()
        ])
        ->get();

      $previousCount = 0;
      foreach ($previousPredictions as $prediction) {
        if ($prediction->identified_risks) {
          foreach ($prediction->identified_risks as $risk) {
            if ($risk['factor'] === $factorCode) {
              $previousCount++;
            }
          }
        }
      }

      // Calculate trend
      $percentageChange = $previousCount > 0 ?
        (($currentCount - $previousCount) / $previousCount) * 100 : 0;

      $trendDirection = $percentageChange > 5 ? 'increasing' : ($percentageChange < -5 ? 'decreasing' : 'stable');

      // Update or create risk factor trend
      HealthTrend::updateOrCreate([
        'facility_id' => $this->facilityId,
        'trend_type' => 'risk_factor_frequency',
        'metric_name' => $factorCode . '_frequency',
        'period_start' => Carbon::now()->startOfMonth(),
        'period_end' => Carbon::now()->endOfMonth()
      ], [
        'trend_category' => 'clinical',
        'period_type' => 'monthly',
        'current_value' => $currentCount,
        'previous_value' => $previousCount,
        'percentage_change' => round($percentageChange, 1),
        'trend_direction' => $trendDirection,
        'trend_severity' => $this->determineRiskFactorSeverity($factorCode, $percentageChange),
        'sample_size' => $recentPredictions->count(),
        'alert_level' => $this->determineRiskFactorAlertLevel($factorCode, $percentageChange),
        'ai_interpretation' => $this->generateRiskFactorInterpretation($factorCode, $currentCount, $percentageChange),
        'geographic_scope' => 'facility',
        'contributing_factors' => json_encode([
          'factor_code' => $factorCode,
          'total_patients_affected' => $currentCount,
          'facility_prevalence' => round(($currentCount / $recentPredictions->count()) * 100, 1)
        ])
      ]);

      // Update risk factor accuracy if we have data
      if (isset($factorAccuracy[$factorCode])) {
        $avgAccuracy = array_sum($factorAccuracy[$factorCode]) / count($factorAccuracy[$factorCode]);

        HealthTrend::updateOrCreate([
          'facility_id' => $this->facilityId,
          'trend_type' => 'risk_factor_accuracy',
          'metric_name' => $factorCode . '_accuracy',
          'period_start' => Carbon::now()->startOfMonth(),
          'period_end' => Carbon::now()->endOfMonth()
        ], [
          'trend_category' => 'operational',
          'period_type' => 'monthly',
          'current_value' => round($avgAccuracy, 1),
          'trend_direction' => $avgAccuracy > 75 ? 'stable' : 'decreasing',
          'trend_severity' => $avgAccuracy > 80 ? 'minimal' : 'moderate',
          'sample_size' => count($factorAccuracy[$factorCode]),
          'alert_level' => $avgAccuracy < 60 ? 'warning' : 'none',
          'ai_interpretation' => "Risk factor '{$factorCode}' prediction accuracy: {$avgAccuracy}%",
          'geographic_scope' => 'facility'
        ]);
      }

      // Update the actual RiskFactor model statistics
      $this->updateRiskFactorModel($factorCode, $currentCount, $factorAccuracy[$factorCode] ?? []);
    }
  }

  /**
   * NEW: Update RiskFactor model with current statistics
   */
  private function updateRiskFactorModel($factorCode, $detectionCount, $accuracyScores)
  {

    dd('risk');
    $riskFactor = RiskFactor::firstOrCreate([
      'factor_code' => $factorCode
    ], [
      'factor_name' => ucwords(str_replace('_', ' ', $factorCode)),
      'description' => "AI-detected risk factor: " . $factorCode,
      'category' => $this->determineFactorCategory($factorCode),
      'base_weight' => $this->determineFactorWeight($factorCode),
      'severity_impact' => 'moderate',
      'gestational_relevance' => 'any',
      'ai_detectable' => true,
      'is_active' => true
    ]);

    // Update statistics
    $riskFactor->update([
      'times_detected' => $riskFactor->times_detected + $detectionCount,
      'prediction_accuracy' => count($accuracyScores) > 0 ?
        round(array_sum($accuracyScores) / count($accuracyScores), 2) : null,
      'last_updated_weights' => Carbon::now()
    ]);
  }

  /**
   * NEW: Track risk factor combinations and interactions
   */
  private function analyzeRiskFactorInteractions()
  {
    $recentPredictions = RiskPrediction::where('facility_id', $this->facilityId)
      ->where('assessment_date', '>=', Carbon::now()->subDays(30))
      ->get();

    $interactions = [];

    foreach ($recentPredictions as $prediction) {
      if ($prediction->identified_risks && count($prediction->identified_risks) > 1) {
        $factors = array_column($prediction->identified_risks, 'factor');
        sort($factors);

        // Create combination key
        $combinationKey = implode('+', $factors);
        $interactions[$combinationKey] = ($interactions[$combinationKey] ?? 0) + 1;
      }
    }

    // Store significant interactions (occurring in >5% of cases)
    $totalPredictions = $recentPredictions->count();
    foreach ($interactions as $combination => $count) {
      $prevalence = ($count / $totalPredictions) * 100;

      if ($prevalence > 5) { // 5% threshold
        HealthTrend::updateOrCreate([
          'facility_id' => $this->facilityId,
          'trend_type' => 'risk_factor_interaction',
          'metric_name' => 'interaction_' . str_replace('+', '_', $combination),
          'period_start' => Carbon::now()->startOfMonth(),
          'period_end' => Carbon::now()->endOfMonth()
        ], [
          'trend_category' => 'clinical',
          'period_type' => 'monthly',
          'current_value' => $count,
          'trend_direction' => 'stable',
          'trend_severity' => $prevalence > 15 ? 'significant' : 'moderate',
          'sample_size' => $totalPredictions,
          'alert_level' => $prevalence > 20 ? 'warning' : 'none',
          'ai_interpretation' => "Risk factor combination '{$combination}' occurs in {$prevalence}% of cases",
          'geographic_scope' => 'facility',
          'contributing_factors' => json_encode([
            'factor_combination' => explode('+', $combination),
            'prevalence_percentage' => round($prevalence, 1),
            'cases_affected' => $count
          ])
        ]);
      }
    }
  }

  /**
   * NEW: Predict emerging risk factors
   */
  private function identifyEmergingRiskFactors()
  {
    // Compare current month to previous 3 months
    $currentMonth = Carbon::now()->startOfMonth();
    $threeMonthsAgo = Carbon::now()->subMonths(3)->startOfMonth();

    $currentFactors = $this->getRiskFactorCounts($currentMonth, $currentMonth->copy()->endOfMonth());
    $historicalFactors = $this->getRiskFactorCounts($threeMonthsAgo, $currentMonth->copy()->subMonth()->endOfMonth());

    foreach ($currentFactors as $factorCode => $currentCount) {
      $historicalAvg = ($historicalFactors[$factorCode] ?? 0) / 3;

      if ($historicalAvg > 0) {
        $increase = (($currentCount - $historicalAvg) / $historicalAvg) * 100;

        // Flag as emerging if 50%+ increase
        if ($increase > 50) {
          HealthTrend::updateOrCreate([
            'facility_id' => $this->facilityId,
            'trend_type' => 'emerging_risk_factor',
            'metric_name' => $factorCode . '_emergence',
            'period_start' => $currentMonth,
            'period_end' => $currentMonth->copy()->endOfMonth()
          ], [
            'trend_category' => 'clinical',
            'period_type' => 'monthly',
            'current_value' => $currentCount,
            'previous_value' => $historicalAvg,
            'percentage_change' => round($increase, 1),
            'trend_direction' => 'increasing',
            'trend_severity' => 'significant',
            'alert_level' => 'urgent',
            'requires_intervention' => true,
            'ai_interpretation' => "Emerging risk factor: '{$factorCode}' increased by {$increase}% - requires investigation",
            'recommended_actions' => [
              "Investigate causes of increased {$factorCode} occurrences",
              "Review recent policy or environmental changes",
              "Consider targeted prevention strategies"
            ],
            'geographic_scope' => 'facility'
          ]);
        }
      }
    }
  }

  // Helper methods
  private function getRiskFactorCounts($startDate, $endDate)
  {
    $predictions = RiskPrediction::where('facility_id', $this->facilityId)
      ->whereBetween('assessment_date', [$startDate, $endDate])
      ->get();

    $factorCounts = [];
    foreach ($predictions as $prediction) {
      if ($prediction->identified_risks) {
        foreach ($prediction->identified_risks as $risk) {
          $factorCode = $risk['factor'];
          $factorCounts[$factorCode] = ($factorCounts[$factorCode] ?? 0) + 1;
        }
      }
    }

    return $factorCounts;
  }

  private function calculateFactorAccuracy($risk, $prediction)
  {
    // Implementation depends on your specific accuracy calculation logic
    // This is a simplified version
    $actualOutcomes = $prediction->actual_outcomes;
    if (!$actualOutcomes) return null;

    // Basic accuracy check - can be enhanced based on specific risk factor
    return rand(60, 95); // Placeholder - implement actual accuracy calculation
  }

  private function determineRiskFactorSeverity($factorCode, $percentageChange)
  {
    $absChange = abs($percentageChange);

    if ($absChange > 100) return 'critical';
    if ($absChange > 50) return 'significant';
    if ($absChange > 20) return 'moderate';
    return 'minimal';
  }

  private function determineRiskFactorAlertLevel($factorCode, $percentageChange)
  {
    // Critical factors need urgent attention
    $criticalFactors = ['bleeding', 'heart_disease', 'kidney_disease'];

    if (in_array($factorCode, $criticalFactors) && $percentageChange > 25) {
      return 'urgent';
    }

    if ($percentageChange > 50) return 'warning';
    return 'none';
  }

  private function generateRiskFactorInterpretation($factorCode, $count, $change)
  {
    $factorName = ucwords(str_replace('_', ' ', $factorCode));

    if ($change > 20) { // significant change? //
      return "Significant increase in {$factorName}: {$count} cases ({$change}% increase)";
    } elseif ($change < -20) {
      return "Positive trend: {$factorName} decreased by {$change}%";
    } else {
      return "Stable pattern for {$factorName}: {$count} cases";
    }
  }

  private function determineFactorCategory($factorCode)
  {
    $categories = [
      'age' => 'demographic',
      'hypertension' => 'medical_history',
      'bleeding' => 'current_pregnancy',
      'hemoglobin' => 'clinical_measurements',
      'cesarean' => 'obstetric_history'
    ];

    foreach ($categories as $keyword => $category) {
      if (str_contains($factorCode, $keyword)) {
        return $category;
      }
    }

    return 'medical_history';
  }

  private function determineFactorWeight($factorCode)
  {
    $weights = [
      'heart_disease' => 25,
      'bleeding' => 20,
      'hypertension' => 18,
      'teen_pregnancy' => 15,
      'anemia' => 12,
      'sickle_cell' => 15
    ];

    return $weights[$factorCode] ?? 10;
  }

  /**
   * Generate predictive insights for the facility
   */



  private function generatePredictiveInsights()
  {
    $predictiveService = app(PredictiveAnalyticsService::class);

    try {
      $predictions = $predictiveService->generateFacilityPredictions($this->facilityId, 30);

      // Store risk prediction trends
      if (isset($predictions['risk_predictions']['risk_levels'])) {
        foreach ($predictions['risk_predictions']['risk_levels'] as $riskLevel => $prediction) {
          HealthTrend::updateOrCreate([
            'facility_id' => $this->facilityId,
            'trend_type' => 'predictive_risk',
            'metric_name' => $riskLevel . '_risk_prediction',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->addDays(30)->endOfDay()
          ], [
            'trend_category' => 'predictive',
            'period_type' => 'monthly',
            'current_value' => $prediction['predicted_value'],
            'previous_value' => $prediction['current_average'],
            'percentage_change' => $prediction['change_percentage'],
            'trend_direction' => $prediction['trend_direction'],
            'trend_severity' => $this->determinePredictiveSeverity($prediction),
            'sample_size' => 1,
            'confidence_level' => $this->mapConfidenceToNumber($prediction['confidence']),
            'ai_interpretation' => $this->generatePredictiveInterpretation($riskLevel, $prediction),
            'alert_level' => $this->determineAlertLevel($prediction),
            'geographic_scope' => 'facility',
            'predictions' => json_encode($prediction)
          ]);
        }
      }

      // Store service utilization predictions
      if (isset($predictions['service_utilization'])) {
        foreach ($predictions['service_utilization'] as $service => $prediction) {
          HealthTrend::updateOrCreate([
            'facility_id' => $this->facilityId,
            'trend_type' => 'service_prediction',
            'metric_name' => $service . '_utilization_forecast',
            'period_start' => Carbon::now()->startOfDay(),
            'period_end' => Carbon::now()->addDays(30)->endOfDay()
          ], [
            'trend_category' => 'operational',
            'period_type' => 'monthly',
            'current_value' => $prediction['predicted_total'],
            'previous_value' => $prediction['current_daily_average'] * 30,
            'trend_direction' => $prediction['trend_direction'],
            'trend_severity' => 'moderate',
            'sample_size' => 1,
            'confidence_level' => $this->mapConfidenceToNumber($prediction['confidence']),
            'ai_interpretation' => "Predicted {$service} utilization: {$prediction['predicted_total']} cases over 30 days",
            'alert_level' => 'none',
            'geographic_scope' => 'facility',
            'predictions' => json_encode($prediction)
          ]);
        }
      }

      Log::info("Predictive insights generated for facility {$this->facilityId}");
    } catch (\Exception $e) {
      Log::error("Failed to generate predictive insights for facility {$this->facilityId}: " . $e->getMessage());
    }
  }

  /**
   * Update predictive trend patterns
   */
  private function updatePredictiveTrends()
  {
    // Analyze prediction accuracy from past predictions
    $pastPredictions = HealthTrend::where('facility_id', $this->facilityId)
      ->where('trend_type', 'predictive_risk')
      ->where('period_start', '<=', Carbon::now()->subDays(30))
      ->where('period_end', '<=', Carbon::now())
      ->get();

    if ($pastPredictions->count() > 0) {
      $accuracyScores = [];

      foreach ($pastPredictions as $prediction) {
        $accuracy = $this->calculatePredictionAccuracy($prediction);
        if ($accuracy !== null) {
          $accuracyScores[] = $accuracy;
        }
      }

      if (count($accuracyScores) > 0) {
        $avgAccuracy = array_sum($accuracyScores) / count($accuracyScores);

        HealthTrend::updateOrCreate([
          'facility_id' => $this->facilityId,
          'trend_type' => 'prediction_accuracy',
          'metric_name' => 'forecast_accuracy',
          'period_start' => Carbon::now()->startOfMonth(),
          'period_end' => Carbon::now()->endOfMonth()
        ], [
          'trend_category' => 'operational',
          'period_type' => 'monthly',
          'current_value' => round($avgAccuracy, 1),
          'trend_direction' => $avgAccuracy > 70 ? 'stable' : 'decreasing',
          'trend_severity' => $avgAccuracy > 80 ? 'minimal' : ($avgAccuracy > 60 ? 'moderate' : 'significant'),
          'sample_size' => count($accuracyScores),
          'alert_level' => $avgAccuracy < 60 ? 'warning' : 'none',
          'ai_interpretation' => "Prediction model accuracy: {$avgAccuracy}%",
          'geographic_scope' => 'facility'
        ]);
      }
    }
  }

  /**
   * Identify intervention opportunities based on predictions
   */
  private function identifyInterventionOpportunities()
  {
    $predictiveService = app(PredictiveAnalyticsService::class);

    try {
      $predictions = $predictiveService->generateFacilityPredictions($this->facilityId, 14);

      if (isset($predictions['intervention_opportunities'])) {
        foreach ($predictions['intervention_opportunities'] as $opportunity) {
          HealthTrend::updateOrCreate([
            'facility_id' => $this->facilityId,
            'trend_type' => 'intervention_opportunity',
            'metric_name' => $opportunity['type'],
            'period_start' => Carbon::now(),
            'period_end' => Carbon::now()->addDays(14)
          ], [
            'trend_category' => 'clinical',
            'period_type' => 'weekly',
            'current_value' => 1, // Opportunity exists
            'trend_direction' => 'increasing',
            'trend_severity' => $opportunity['priority'] === 'high' ? 'critical' : 'moderate',
            'sample_size' => 1,
            'alert_level' => $opportunity['priority'] === 'high' ? 'urgent' : 'warning',
            'requires_intervention' => true,
            'ai_interpretation' => $opportunity['description'],
            'recommended_actions' => [$opportunity['intervention']],
            'geographic_scope' => 'facility',
            'predictions' => json_encode([
              'expected_impact' => $opportunity['expected_impact'],
              'timeline' => $opportunity['timeline']
            ])
          ]);
        }
      }
    } catch (\Exception $e) {
      Log::error("Failed to identify intervention opportunities for facility {$this->facilityId}: " . $e->getMessage());
    }
  }

  /**
   * Helper method to determine predictive severity
   */
  private function determinePredictiveSeverity($prediction)
  {
    $changePercent = abs($prediction['change_percentage']);

    if ($changePercent > 50) return 'critical';
    if ($changePercent > 25) return 'significant';
    if ($changePercent > 10) return 'moderate';
    return 'minimal';
  }

  /**
   * Helper method to map confidence levels to numbers
   */
  private function mapConfidenceToNumber($confidence)
  {
    return match ($confidence) {
      'High' => 90,
      'Medium' => 70,
      'Low' => 50,
      'Very Low' => 30,
      default => 60
    };
  }

  /**
   * Generate interpretation for predictive data
   */
  private function generatePredictiveInterpretation($riskLevel, $prediction)
  {
    $direction = $prediction['trend_direction'];
    $change = $prediction['change_percentage'];

    if ($direction === 'increasing' && $change > 20) {
      return "Significant increase predicted in {$riskLevel} risk cases ({$change}% increase expected)";
    } elseif ($direction === 'decreasing' && $change < -10) {
      return "Positive trend predicted: {$riskLevel} risk cases expected to decrease by {$change}%";
    } else {
      return "Stable pattern predicted for {$riskLevel} risk cases with {$change}% change";
    }
  }

  /**
   * Determine alert level based on prediction
   */
  private function determineAlertLevel($prediction)
  {
    if ($prediction['trend_direction'] === 'increasing' && abs($prediction['change_percentage']) > 30) {
      return 'urgent';
    } elseif (abs($prediction['change_percentage']) > 15) {
      return 'warning';
    }
    return 'none';
  }

  /**
   * Calculate accuracy of past predictions
   */
  private function calculatePredictionAccuracy($prediction)
  {
    // Compare predicted values with actual outcomes
    $predictionData = json_decode($prediction->predictions, true);
    if (!$predictionData) return null;

    $predictedValue = $predictionData['predicted_value'] ?? 0;
    $actualValue = $prediction->current_value ?? 0;

    if ($predictedValue == 0) return null;

    $accuracy = 100 - (abs($predictedValue - $actualValue) / $predictedValue * 100);
    return max(0, $accuracy);
  }
}

/**
 * Job for validating prediction accuracy
 */
class ValidatePredictionAccuracyJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $facilityId;

  public function __construct($facilityId)
  {
    $this->facilityId = $facilityId;
  }

  public function handle()
  {
    try {
      // Find predictions that are ready for accuracy validation
      $predictionsToValidate = RiskPrediction::where('facility_id', $this->facilityId)
        ->where('outcome_verified', false)
        ->where('assessment_date', '<=', Carbon::now()->subDays(30)) // At least 30 days old
        ->whereHas('user.deliveries', function ($query) {
          $query->where('dodel', '>=', Carbon::now()->subDays(30));
        })
        ->with(['user.deliveries', 'user.postnatalRecords'])
        ->get();

      foreach ($predictionsToValidate as $prediction) {
        $this->validatePredictionAccuracy($prediction);
      }

      Log::info("Validated accuracy for {$predictionsToValidate->count()} predictions at facility {$this->facilityId}");
    } catch (\Exception $e) {
      Log::error("Failed to validate prediction accuracy for facility {$this->facilityId}", [
        'error' => $e->getMessage()
      ]);
    }
  }

  private function validatePredictionAccuracy($prediction)
  {
    $actualOutcomes = $this->collectActualOutcomes($prediction);

    if (empty($actualOutcomes)) {
      return; // Not enough data to validate
    }

    $prediction->update([
      'actual_outcomes' => $actualOutcomes,
      'outcome_verified' => true
    ]);

    // Update risk factor accuracy statistics
    $this->updateRiskFactorAccuracy($prediction, $actualOutcomes);
  }

  private function collectActualOutcomes($prediction)
  {
    $outcomes = [];
    $delivery = $prediction->user->deliveries()->latest('dodel')->first();
    $postnatal = $prediction->user->postnatalRecords()->latest('visit_date')->first();

    if ($delivery) {
      $outcomes['delivery'] = [
        'mode' => $delivery->mod,
        'complications' => !empty($delivery->complications),
        'preterm' => $delivery->pre_term === 'yes',
        'blood_loss' => $delivery->blood_loss,
        'baby_weight' => $delivery->weight,
        'stillbirth' => !empty($delivery->still_birth)
      ];
    }

    if ($postnatal) {
      $outcomes['postnatal'] = [
        'complications' => !empty($postnatal->associated_problems),
        'hypertension' => ($postnatal->systolic_bp > 140 || $postnatal->diastolic_bp > 90),
        'breastfeeding_issues' => $postnatal->breastfeeding_status !== 'Exclusive'
      ];
    }

    return $outcomes;
  }

  private function updateRiskFactorAccuracy($prediction, $actualOutcomes)
  {
    $predictedOutcomes = $prediction->predicted_outcomes ?? [];

    foreach ($prediction->identified_risks ?? [] as $risk) {
      $factorCode = $risk['factor'];
      $riskFactor = RiskFactor::where('factor_code', $factorCode)->first();

      if ($riskFactor) {
        $wasAccurate = $this->assessPredictionAccuracy($predictedOutcomes, $actualOutcomes, $risk);
        $riskFactor->updateAccuracy($wasAccurate);
      }
    }
  }

  private function assessPredictionAccuracy($predicted, $actual, $risk)
  {
    // Simple accuracy assessment - can be made more sophisticated
    $accuracyScore = 0;
    $totalChecks = 0;

    // Check delivery predictions
    if (isset($predicted['delivery_outcomes']) && isset($actual['delivery'])) {
      $deliveryPredicted = $predicted['delivery_outcomes'];
      $deliveryActual = $actual['delivery'];

      // Check cesarean prediction
      if (isset($deliveryPredicted['cesarean_delivery'])) {
        $predictedCesarean = $deliveryPredicted['cesarean_delivery'] > 50;
        $actualCesarean = $deliveryActual['mode'] === 'CS';
        if ($predictedCesarean === $actualCesarean) $accuracyScore++;
        $totalChecks++;
      }

      // Check preterm prediction
      if (isset($deliveryPredicted['preterm_delivery'])) {
        $predictedPreterm = $deliveryPredicted['preterm_delivery'] > 30;
        $actualPreterm = $deliveryActual['preterm'];
        if ($predictedPreterm === $actualPreterm) $accuracyScore++;
        $totalChecks++;
      }
    }

    return $totalChecks > 0 ? ($accuracyScore / $totalChecks) >= 0.7 : true;
  }
}
