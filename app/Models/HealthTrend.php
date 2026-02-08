<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class HealthTrend extends Model
{
  protected $fillable = [
    'facility_id',
    'state_id',
    'lga_id',
    'trend_type',
    'metric_name',
    'trend_category',
    'period_start',
    'period_end',
    'period_type',
    'current_value',
    'previous_value',
    'percentage_change',
    'trend_direction',
    'trend_severity',
    'sample_size',
    'confidence_level',
    'trend_data_points',
    'statistical_metadata',
    'ai_interpretation',
    'contributing_factors',
    'predictions',
    'alert_level',
    'geographic_scope',
    'affected_demographics',
    'requires_intervention',
    'recommended_actions',
    'intervention_notes',
    'last_reviewed',
    'reviewed_by'
  ];

  protected $casts = [
    'period_start' => 'date',
    'period_end' => 'date',
    'current_value' => 'decimal:2',
    'previous_value' => 'decimal:2',
    'percentage_change' => 'decimal:2',
    'confidence_level' => 'decimal:2',
    'trend_data_points' => 'array',
    'statistical_metadata' => 'array',
    'contributing_factors' => 'array',
    'predictions' => 'array',
    'affected_demographics' => 'array',
    'recommended_actions' => 'array',
    'requires_intervention' => 'boolean',
    'last_reviewed' => 'date'
  ];

  // Relationships
  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function state(): BelongsTo
  {
    return $this->belongsTo(State::class);
  }

  public function lga(): BelongsTo
  {
    return $this->belongsTo(Lga::class);
  }

  // Scopes
  public function scopeByFacility($query, $facilityId)
  {
    return $query->where('facility_id', $facilityId);
  }

  public function scopeRequiringAction($query)
  {
    return $query->where('requires_intervention', true)
      ->whereIn('alert_level', ['warning', 'urgent']);
  }

  public function scopeBySeverity($query, $severity)
  {
    return $query->where('trend_severity', $severity);
  }

  public function scopeByCategory($query, $category)
  {
    return $query->where('trend_category', $category);
  }

  public function scopeCurrentPeriod($query)
  {
    $now = Carbon::now();
    return $query->where('period_start', '<=', $now)
      ->where('period_end', '>=', $now);
  }

  public function scopeRecentTrends($query, $days = 90)
  {
    return $query->where('period_end', '>=', Carbon::now()->subDays($days));
  }

  // Accessors
  public function getTrendDirectionIconAttribute()
  {
    return match ($this->trend_direction) {
      'increasing' => 'bx-trending-up',
      'decreasing' => 'bx-trending-down',
      'stable' => 'bx-minus',
      'fluctuating' => 'bx-line-chart',
      default => 'bx-help-circle'
    };
  }

  public function getTrendDirectionColorAttribute()
  {
    // Color depends on whether increase is good or bad for this metric
    $goodWhenIncreasing = [
      'antenatal_coverage',
      'skilled_birth_attendance',
      'postnatal_visits',
      'vaccination_coverage'
    ];

    $goodWhenDecreasing = [
      'maternal_mortality',
      'cesarean_rate',
      'stillbirth_rate',
      'complication_rate'
    ];

    if (in_array($this->metric_name, $goodWhenIncreasing)) {
      return match ($this->trend_direction) {
        'increasing' => 'success',
        'decreasing' => 'danger',
        'stable' => 'info',
        'fluctuating' => 'warning',
        default => 'secondary'
      };
    } elseif (in_array($this->metric_name, $goodWhenDecreasing)) {
      return match ($this->trend_direction) {
        'increasing' => 'danger',
        'decreasing' => 'success',
        'stable' => 'info',
        'fluctuating' => 'warning',
        default => 'secondary'
      };
    }

    return 'info'; // Neutral for unknown metrics
  }

  public function getAlertLevelBadgeAttribute()
  {
    return match ($this->alert_level) {
      'none' => 'secondary',
      'watch' => 'info',
      'warning' => 'warning',
      'urgent' => 'danger',
      default => 'secondary'
    };
  }

  public function getPeriodLengthAttribute()
  {
    return Carbon::parse($this->period_start)->diffInDays($this->period_end);
  }

  public function getIsCurrentPeriodAttribute()
  {
    $now = Carbon::now();
    return $now->between($this->period_start, $this->period_end);
  }

  // Helper Methods
  public function calculateSignificance()
  {
    if (!$this->statistical_metadata) {
      return 'unknown';
    }

    $metadata = $this->statistical_metadata;
    $pValue = $metadata['p_value'] ?? null;

    if ($pValue === null) {
      return 'unknown';
    }

    if ($pValue < 0.001) return 'highly_significant';
    if ($pValue < 0.01) return 'very_significant';
    if ($pValue < 0.05) return 'significant';
    return 'not_significant';
  }

  public function generateAISummary()
  {
    $direction = $this->trend_direction;
    $change = $this->percentage_change;
    $metric = str_replace('_', ' ', $this->metric_name);

    $summary = "The {$metric} has been {$direction}";

    if ($change) {
      $changeText = $change > 0 ? "increased" : "decreased";
      $summary .= " and has {$changeText} by " . abs($change) . "%";
    }

    $summary .= " over the {$this->period_type} period from {$this->period_start->format('M d')} to {$this->period_end->format('M d, Y')}.";

    if ($this->sample_size) {
      $summary .= " This analysis is based on {$this->sample_size} data points.";
    }

    if ($this->requires_intervention) {
      $summary .= " This trend requires immediate attention and intervention.";
    }

    return $summary;
  }

  public function predictNextPeriod()
  {
    if (!$this->trend_data_points || count($this->trend_data_points) < 3) {
      return null;
    }

    $points = $this->trend_data_points;
    $n = count($points);

    // Simple linear regression for prediction
    $sumX = 0;
    $sumY = 0;
    $sumXY = 0;
    $sumX2 = 0;

    foreach ($points as $i => $point) {
      $x = $i + 1; // Time period
      $y = $point['value'];
      $sumX += $x;
      $sumY += $y;
      $sumXY += $x * $y;
      $sumX2 += $x * $x;
    }

    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;

    // Predict next value
    $nextPeriod = $n + 1;
    $prediction = $slope * $nextPeriod + $intercept;

    return [
      'predicted_value' => round($prediction, 2),
      'confidence' => $this->confidence_level,
      'method' => 'linear_regression',
      'trend_strength' => abs($slope)
    ];
  }

  // Static Methods for Analytics
  public static function getActiveTrends($facilityId)
  {
    return static::byFacility($facilityId)
      ->currentPeriod()
      ->orderBy('trend_severity', 'desc')
      ->orderBy('alert_level', 'desc')
      ->get();
  }

  public static function getCriticalAlerts($facilityId)
  {
    return static::byFacility($facilityId)
      ->where('alert_level', 'urgent')
      ->where('requires_intervention', true)
      ->get();
  }

  public static function getTrendSummary($facilityId, $category = null)
  {
    $query = static::byFacility($facilityId)->recentTrends();

    if ($category) {
      $query->byCategory($category);
    }

    return [
      'total_trends' => $query->count(),
      'requiring_action' => $query->clone()->where('requires_intervention', true)->count(),
      'by_severity' => $query->clone()->selectRaw('trend_severity, COUNT(*) as count')
        ->groupBy('trend_severity')->pluck('count', 'trend_severity'),
      'by_direction' => $query->clone()->selectRaw('trend_direction, COUNT(*) as count')
        ->groupBy('trend_direction')->pluck('count', 'trend_direction'),
      'alert_distribution' => $query->clone()->selectRaw('alert_level, COUNT(*) as count')
        ->groupBy('alert_level')->pluck('count', 'alert_level')
    ];
  }
}
