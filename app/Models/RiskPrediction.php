<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class RiskPrediction extends Model
{
  protected $fillable = [
    'user_id',
    'facility_id',
    'antenatal_id',
    'total_risk_score',
    'risk_level',
    'risk_percentage',
    'identified_risks',
    'ai_recommendations',
    'prediction_confidence',
    'gestational_age_weeks',
    'assessment_date',
    'next_assessment_due',
    'clinical_notes',
    'predicted_outcomes',
    'actual_outcomes',
    'outcome_verified',
    'model_version',
    'prediction_timestamp',
    'assessment_type',
    'officer_name',
    'officer_role',
    'officer_designation'
  ];

  protected $casts = [
    'identified_risks' => 'array',
    'ai_recommendations' => 'array',
    'prediction_confidence' => 'array',
    'predicted_outcomes' => 'array',
    'actual_outcomes' => 'array',
    'outcome_verified' => 'boolean',
    'assessment_date' => 'date',
    'next_assessment_due' => 'date',
    'prediction_timestamp' => 'datetime',
    'risk_percentage' => 'decimal:2'
  ];

  // Relationships
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function antenatal(): BelongsTo
  {
    return $this->belongsTo(Antenatal::class);
  }

  // Scopes
  public function scopeHighRisk($query)
  {
    return $query->whereIn('risk_level', ['high', 'critical']);
  }

  public function scopeByFacility($query, $facilityId)
  {
    return $query->where('facility_id', $facilityId);
  }

  public function scopeRecentAssessments($query, $days = 30)
  {
    return $query->where('assessment_date', '>=', Carbon::now()->subDays($days));
  }

  public function scopeDueForReassessment($query)
  {
    return $query->where('next_assessment_due', '<=', Carbon::today())
      ->whereNull('actual_outcomes');
  }

  // Accessors & Mutators
  public function getRiskLevelColorAttribute()
  {
    return match ($this->risk_level) {
      'low' => 'success',
      'moderate' => 'info',
      'high' => 'warning',
      'critical' => 'danger',
      default => 'secondary'
    };
  }

  public function getRiskFactorCountAttribute()
  {
    return count($this->identified_risks ?? []);
  }

  public function getIsOverdueAttribute()
  {
    return $this->next_assessment_due &&
      Carbon::parse($this->next_assessment_due)->isPast();
  }

  // Helper Methods
  public function addActualOutcome(array $outcome)
  {
    $actualOutcomes = $this->actual_outcomes ?? [];
    $actualOutcomes[] = [
      'outcome' => $outcome,
      'recorded_at' => Carbon::now(),
      'recorded_by' => auth()->user()->first_name ?? 'System'

    ];

    $this->update([
      'actual_outcomes' => $actualOutcomes,
      'outcome_verified' => true
    ]);
  }

  public function calculateAccuracy()
  {
    if (!$this->predicted_outcomes || !$this->actual_outcomes) {
      return null;
    }

    // Simple accuracy calculation - can be enhanced, maybe later, i dont have time time. version 2 maybe
    $predicted = $this->predicted_outcomes;
    $actual = $this->actual_outcomes;

    $matches = 0;
    $total = count($predicted);

    foreach ($predicted as $prediction) {
      foreach ($actual as $outcome) {
        if ($this->outcomesMatch($prediction, $outcome['outcome'])) {
          $matches++;
          break;
        }
      }
    }

    return $total > 0 ? ($matches / $total) * 100 : 0;
  }

  private function outcomesMatch($predicted, $actual)
  {
    // Define matching logic based on your outcome structure
    // This is a simplified example
    return isset($predicted['type']) && isset($actual['type']) &&
      $predicted['type'] === $actual['type'];
  }

  public function getNextRecommendedAssessment()
  {
    $baseInterval = match ($this->risk_level) {
      'critical' => 3,  // 3 days
      'high' => 7,      // 1 week
      'moderate' => 14, // 2 weeks
      'low' => 28,      // 4 weeks- about a month is not too far sha, but its well
      default => 28
    };

    return Carbon::parse($this->assessment_date)->addDays($baseInterval);
  }

  // Static methods for analytics
  public static function getFacilityRiskDistribution($facilityId)
  {
    return static::where('facility_id', $facilityId)
      ->selectRaw('risk_level, COUNT(*) as count')
      ->groupBy('risk_level')
      ->pluck('count', 'risk_level');
  }

  public static function getAverageRiskScore($facilityId, $period = 30)
  {
    return static::where('facility_id', $facilityId)
      ->where('assessment_date', '>=', Carbon::now()->subDays($period))
      ->avg('total_risk_score');
  }

  public static function getTrendingRiskFactors($facilityId, $period = 30)
  {
    $predictions = static::where('facility_id', $facilityId)
      ->where('assessment_date', '>=', Carbon::now()->subDays($period))
      ->get();

    $allRisks = [];
    foreach ($predictions as $prediction) {
      if ($prediction->identified_risks) {
        foreach ($prediction->identified_risks as $risk) {
          $factorName = $risk['factor'] ?? 'unknown';
          $allRisks[$factorName] = ($allRisks[$factorName] ?? 0) + 1;
        }
      }
    }

    arsort($allRisks);
    return array_slice($allRisks, 0, 10, true);
  }


  public static function getFacilityRiskSummary($facilityId, $days = 30)
  {
    $predictions = self::where('facility_id', $facilityId)
      ->where('assessment_date', '>=', Carbon::now()->subDays($days))
      ->get();

    if ($predictions->isEmpty()) {
      return [
        'total_patients' => 0,
        'low_risk' => 0,
        'moderate_risk' => 0,
        'high_risk' => 0,
        'critical_risk' => 0,
        'risk_distribution' => [],
        'common_risk_factors' => [],
        'data_quality' => 'insufficient_data',
        'last_updated' => null
      ];
    }

    $riskCounts = $predictions->groupBy('risk_level')->map->count();
    $total = $predictions->count();

    // Get most common risk factors
    $allRiskFactors = [];
    foreach ($predictions as $prediction) {
      if ($prediction->identified_risks) {
        foreach ($prediction->identified_risks as $risk) {
          $factor = $risk['factor'] ?? 'unknown';
          $allRiskFactors[] = $factor;
        }
      }
    }

    $factorCounts = array_count_values($allRiskFactors);
    arsort($factorCounts);

    return [
      'total_patients' => $total,
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
      'data_quality' => self::assessDataQuality($predictions),
      'last_updated' => $predictions->max('updated_at'),
      'average_confidence' => round($predictions->where('prediction_confidence.overall_confidence')->avg(function ($pred) {
        $conf = $pred->prediction_confidence;
        return is_array($conf) ? ($conf['overall_confidence'] ?? 0) : 0;
      }), 1)
    ];
  }

  /**
   * Assess data quality of predictions
   */
  private static function assessDataQuality($predictions)
  {
    $totalPredictions = $predictions->count();
    $withConfidence = $predictions->filter(function ($pred) {
      return !empty($pred->prediction_confidence);
    })->count();

    $withRecommendations = $predictions->filter(function ($pred) {
      return !empty($pred->ai_recommendations);
    })->count();

    $qualityScore = 0;
    if ($totalPredictions > 0) {
      $qualityScore = (($withConfidence / $totalPredictions) * 50) +
        (($withRecommendations / $totalPredictions) * 50);
    }

    if ($qualityScore >= 80) return 'excellent';
    if ($qualityScore >= 60) return 'good';
    if ($qualityScore >= 40) return 'fair';
    return 'poor';
  }

  /**
   * Get overdue assessments for facility
   */
  public static function getOverdueAssessments($facilityId)
  {
    return self::where('facility_id', $facilityId)
      ->where('next_assessment_due', '<', Carbon::today())
      ->whereNotNull('next_assessment_due')
      ->with('user')
      ->get()
      ->map(function ($prediction) {
        $daysOverdue = Carbon::parse($prediction->next_assessment_due)->diffInDays(Carbon::today());
        return [
          'patient_name' => $prediction->user->first_name . ' ' . $prediction->user->last_name,
          'din' => $prediction->user->DIN,
          'risk_level' => $prediction->risk_level,
          'due_date' => $prediction->next_assessment_due,
          'days_overdue' => $daysOverdue,
          'priority' => $daysOverdue > 7 ? 'high' : 'medium'
        ];
      });
  }

  /**
   * Validate prediction data
   */
  public function validatePrediction()
  {
    $issues = [];

    // Check required fields
    if (empty($this->risk_level)) {
      $issues[] = 'Missing risk level';
    }

    if ($this->total_risk_score < 0 || $this->total_risk_score > 300) {
      $issues[] = 'Invalid risk score range';
    }

    // Check risk level consistency
    $scoreRiskMapping = [
      'low' => [0, 19],
      'moderate' => [20, 39],
      'high' => [40, 69],
      'critical' => [70, 300]
    ];

    if (isset($scoreRiskMapping[$this->risk_level])) {
      $range = $scoreRiskMapping[$this->risk_level];
      if ($this->total_risk_score < $range[0] || $this->total_risk_score > $range[1]) {
        $issues[] = 'Risk score and level mismatch';
      }
    }

    // Check confidence data
    if (empty($this->prediction_confidence)) {
      $issues[] = 'Missing confidence data';
    }

    return [
      'is_valid' => empty($issues),
      'issues' => $issues,
      'data_completeness' => $this->calculateCompleteness()
    ];
  }

  /**
   * Calculate data completeness percentage
   */
  private function calculateCompleteness()
  {
    $fields = [
      'risk_level',
      'total_risk_score',
      'identified_risks',
      'ai_recommendations',
      'prediction_confidence',
      'predicted_outcomes'
    ];

    $completedFields = 0;
    foreach ($fields as $field) {
      if (!empty($this->$field)) {
        $completedFields++;
      }
    }

    return round(($completedFields / count($fields)) * 100, 1);
  }
}
