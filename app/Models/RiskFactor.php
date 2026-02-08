<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class RiskFactor extends Model
{
  protected $fillable = [
    'factor_code',
    'factor_name',
    'description',
    'category',
    'base_weight',
    'weight_modifiers',
    'severity_impact',
    'gestational_relevance',
    'trigger_conditions',
    'exclusion_conditions',
    'clinical_evidence',
    'evidence_strength',
    'reference_studies',
    'ai_detectable',
    'detection_rules',
    'related_factors',
    'ai_confidence_threshold',
    'recommended_actions',
    'monitoring_requirements',
    'patient_education',
    'is_active',
    'display_order',
    'auto_detect',
    'effective_from',
    'effective_until',
    'times_detected',
    'prediction_accuracy',
    'last_updated_weights',
    'created_by',
    'updated_by',
    'update_notes'
  ];

  protected $casts = [
    'weight_modifiers' => 'array',
    'trigger_conditions' => 'array',
    'exclusion_conditions' => 'array',
    'reference_studies' => 'array',
    'detection_rules' => 'array',
    'related_factors' => 'array',
    'recommended_actions' => 'array',
    'monitoring_requirements' => 'array',
    'ai_detectable' => 'boolean',
    'is_active' => 'boolean',
    'evidence_strength' => 'decimal:2',
    'ai_confidence_threshold' => 'decimal:2', // the farther the worse i guess
    'prediction_accuracy' => 'decimal:2',
    'effective_from' => 'date',
    'effective_until' => 'date',
    'last_updated_weights' => 'datetime'
  ];

  // Scopes
  public function scopeActive($query): Builder
  {
    return $query->where('is_active', true)
      ->where(function ($q) {
        $q->whereNull('effective_until')
          ->orWhere('effective_until', '>=', Carbon::today());
      })
      ->where(function ($q) {
        $q->whereNull('effective_from')
          ->orWhere('effective_from', '<=', Carbon::today());
      });
  }

  public function scopeByCategory($query, $category): Builder
  {
    return $query->where('category', $category);
  }

  public function scopeAiDetectable($query): Builder
  {
    return $query->where('ai_detectable', true);
  }

  public function scopeByGestationalAge($query, $weeks): Builder
  {
    return $query->where(function ($q) use ($weeks) {
      $q->where('gestational_relevance', 'any')
        ->orWhere(function ($subQ) use ($weeks) {
          if ($weeks <= 12) {
            $subQ->where('gestational_relevance', 'first_trimester');
          } elseif ($weeks <= 24) {
            $subQ->where('gestational_relevance', 'second_trimester');
          } elseif ($weeks <= 37) {
            $subQ->where('gestational_relevance', 'third_trimester');
          } else {
            $subQ->where('gestational_relevance', 'delivery');
          }
        });
    });
  }

  public function scopeHighImpact($query): Builder
  {
    return $query->whereIn('severity_impact', ['high', 'critical']);
  }

  public function scopeOrderedForDisplay($query): Builder
  {
    return $query->orderBy('display_order')->orderBy('factor_name');
  }

  // Accessors
  public function getSeverityColorAttribute(): string
  {
    return match ($this->severity_impact) {
      'minimal' => 'secondary',
      'low' => 'info',
      'moderate' => 'warning',
      'high' => 'danger',
      'critical' => 'dark',
      default => 'secondary'
    };
  }

  public function getCategoryIconAttribute(): string
  {
    return match ($this->category) {
      'demographic' => 'bx-user',
      'medical_history' => 'bx-heart',
      'current_pregnancy' => 'bx-child',
      'clinical_measurements' => 'bx-test-tube',
      'obstetric_history' => 'bx-calendar',
      'family_history' => 'bx-group',
      default => 'bx-help-circle'
    };
  }

  public function getIsCurrentlyActiveAttribute(): bool
  {
    if (!$this->is_active) return false;

    $now = Carbon::today();

    if ($this->effective_from && $now->lt($this->effective_from)) {
      return false;
    }

    if ($this->effective_until && $now->gt($this->effective_until)) {
      return false;
    }

    return true;
  }

  // Helper Methods
  public function calculateWeight($patientData = [], $otherFactors = []): int
  {
    $weight = $this->base_weight;

    if (!$this->weight_modifiers) {
      return $weight;
    }

    foreach ($this->weight_modifiers as $modifier) {
      if ($this->modifierApplies($modifier, $patientData, $otherFactors)) {
        $weight += $modifier['weight_change'] ?? 0;
      }
    }

    return max(0, $weight); // Ensure non-negative weight
  }

  private function modifierApplies($modifier, $patientData, $otherFactors): bool
  {
    if (!isset($modifier['condition'])) {
      return false;
    }

    $condition = $modifier['condition'];

    // Check patient data conditions
    if (isset($condition['patient_field'])) {
      $field = $condition['patient_field'];
      $operator = $condition['operator'] ?? '=';
      $value = $condition['value'];
      $patientValue = $patientData[$field] ?? null;

      return $this->evaluateCondition($patientValue, $operator, $value);
    }

    // Check other risk factors
    if (isset($condition['has_factor'])) {
      $requiredFactor = $condition['has_factor'];
      return in_array($requiredFactor, $otherFactors);
    }

    return false;
  }

  private function evaluateCondition($value, $operator, $expected): bool
  {
    return match ($operator) {
      '=' => $value == $expected,
      '!=' => $value != $expected,
      '>' => $value > $expected,
      '<' => $value < $expected,
      '>=' => $value >= $expected,
      '<=' => $value <= $expected,
      'in' => in_array($value, (array)$expected),
      'not_in' => !in_array($value, (array)$expected),
      default => false
    };
  }

  public function canDetectInPatient($patientData): bool
  {
    if (!$this->ai_detectable || !$this->detection_rules) {
      return false;
    }

    foreach ($this->detection_rules as $rule) {
      if ($this->ruleMatches($rule, $patientData)) {
        return true;
      }
    }

    return false;
  }

  private function ruleMatches($rule, $patientData): bool
  {
    if (!isset($rule['conditions'])) {
      return false;
    }

    $logic = $rule['logic'] ?? 'and'; // 'and' or 'or'
    $conditions = $rule['conditions'];

    $results = [];
    foreach ($conditions as $condition) {
      $field = $condition['field'];
      $operator = $condition['operator'];
      $value = $condition['value'];
      $patientValue = $patientData[$field] ?? null;

      $results[] = $this->evaluateCondition($patientValue, $operator, $value);
    }

    return $logic === 'and' ? !in_array(false, $results) : in_array(true, $results);
  }

  public function incrementDetectionCount(): void
  {
    $this->increment('times_detected');
  }

  public function updateAccuracy($wasCorrect): void
  {
    $currentAccuracy = $this->prediction_accuracy ?? 0;
    $detectionCount = $this->times_detected ?? 1;

    // Simple moving average for accuracy
    $newAccuracy = (($currentAccuracy * ($detectionCount - 1)) + ($wasCorrect ? 100 : 0)) / $detectionCount;

    $this->update(['prediction_accuracy' => round($newAccuracy, 2)]);
  }

  public function getRecommendationsFor($patientData): array
  {
    $recommendations = $this->recommended_actions ?? [];

    // Add monitoring requirements
    if ($this->monitoring_requirements) {
      $monitoring = $this->monitoring_requirements;
      $recommendations[] = [
        'type' => 'monitoring',
        'title' => 'Required Monitoring',
        'details' => $monitoring
      ];
    }

    // Add patient education- even this is important, but normal users with the manual way may not care.
    if ($this->patient_education) {
      $recommendations[] = [
        'type' => 'education',
        'title' => 'Patient Education',
        'details' => $this->patient_education
      ];
    }

    return $recommendations;
  }

  // Static Methods
  public static function getActiveFactorsForCategory($category)
  {
    return static::active()
      ->byCategory($category)
      ->orderedForDisplay()
      ->get();
  }

  public static function detectFactorsInPatient($patientData, $gestationalWeeks = null)
  {
    $query = static::active()->aiDetectable();

    if ($gestationalWeeks) {
      $query->byGestationalAge($gestationalWeeks);
    }

    $factors = $query->get();
    $detectedFactors = [];

    foreach ($factors as $factor) {
      if ($factor->canDetectInPatient($patientData)) {
        $detectedFactors[] = [
          'factor' => $factor,
          'confidence' => $factor->calculateConfidence($patientData),
          'weight' => $factor->calculateWeight($patientData)
        ];
      }
    }

    return collect($detectedFactors);
  }

  public function calculateConfidence($patientData): float
  {
    // Simple confidence calculation - can be enhanced with ML-use this as default for now.
    $baseConfidence = $this->evidence_strength ?? 0.8;

    // Adjust based on data completeness- may not do very accurate on incomplete data
    $requiredFields = $this->getRequiredFields();
    $availableFields = array_intersect($requiredFields, array_keys($patientData));
    $completeness = count($availableFields) / max(1, count($requiredFields));

    return min(1.0, $baseConfidence * $completeness);
  }

  private function getRequiredFields(): array
  {
    $fields = [];

    if ($this->detection_rules) {
      foreach ($this->detection_rules as $rule) {
        if (isset($rule['conditions'])) {
          foreach ($rule['conditions'] as $condition) {
            $fields[] = $condition['field'];
          }
        }
      }
    }

    return array_unique($fields);
  }

  public static function getSystemStatistics()
  {
    return [
      'total_factors' => static::count(),
      'active_factors' => static::active()->count(),
      'ai_detectable' => static::aiDetectable()->count(),
      'by_category' => static::selectRaw('category, COUNT(*) as count')
        ->groupBy('category')
        ->pluck('count', 'category'),
      'by_severity' => static::selectRaw('severity_impact, COUNT(*) as count')
        ->groupBy('severity_impact')
        ->pluck('count', 'severity_impact'),
      'average_accuracy' => static::whereNotNull('prediction_accuracy')
        ->avg('prediction_accuracy'),
      'most_detected' => static::orderBy('times_detected', 'desc')
        ->limit(5)
        ->get(['factor_name', 'times_detected'])
    ];
  }
}
