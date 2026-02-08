<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostnatalRecord extends Model
{
  use HasFactory;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'visit_date',
    'delivery_date',
    'days_postpartum',
    'age_range',
    'parity_count',
    'attendance',
    'associated_problems',
    'mother_days',
    'child_days',
    'child_sex',
    'nutrition_counseling',
    'breast_examination',
    'breastfeeding_status',
    'family_planning',
    'female_genital_mutilation',
    'vaginal_examination',
    'packed_cell_volume',
    'urine_test_results',
    'newborn_care',
    'kangaroo_mother_care',
    'visit_outcome',
    'systolic_bp',
    'diastolic_bp',
    'newborn_weight',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'month_year' => 'date',
    'visit_date' => 'date',
    'delivery_date' => 'date',
    'days_postpartum' => 'integer',
    'patient_age' => 'integer',
    'parity_count' => 'integer',
    'mother_days' => 'integer',
    'child_days' => 'integer',
    'systolic_bp' => 'integer',
    'diastolic_bp' => 'integer',
    'newborn_weight' => 'decimal:1',
  ];

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

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

  public function ward(): BelongsTo
  {
    return $this->belongsTo(Ward::class);
  }

  // ============================================
  // ACCESSORS
  // ============================================
  public function getFormattedVisitDateAttribute(): string
  {
    return $this->visit_date ? $this->visit_date->format('d M Y') : '';
  }

  public function getFormattedDeliveryDateAttribute(): string
  {
    return $this->delivery_date ? $this->delivery_date->format('d M Y') : '';
  }

  public function getVisitOutcomeColorAttribute(): string
  {
    return match ($this->visit_outcome) {
      'Stable' => 'success',
      'Referred' => 'warning',
      'Admitted' => 'info',
      'Discharged' => 'secondary',
      default => 'secondary',
    };
  }

  // ============================================
  // SCOPES
  // ============================================
  public function scopeByFacility($query, int $facilityId)
  {
    return $query->where('facility_id', $facilityId);
  }

  public function scopeByPatient($query, int $patientId)
  {
    return $query->where('patient_id', $patientId);
  }

  public function scopeLatestFirst($query)
  {
    return $query->orderBy('visit_date', 'desc')->orderBy('created_at', 'desc');
  }
}
