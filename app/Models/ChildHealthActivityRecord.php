<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChildHealthActivityRecord extends Model
{
  use HasFactory, SoftDeletes;

  public const BREASTFEEDING_OPTIONS = ['E', 'P', 'BW', 'NO'];

  protected $fillable = [
    'patient_id',
    'linked_child_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'visit_date',
    'vaccination_dates',
    'vaccination_notes',
    'weight_entries',
    'breastfeeding_entries',
    'aefi_period',
    'aefi_type',
    'aefi_sia_campaign',
    'aefi_cases',
    'comments',
    'summary_map',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'month_year' => 'date',
    'visit_date' => 'date',
    'vaccination_dates' => 'array',
    'vaccination_notes' => 'array',
    'weight_entries' => 'array',
    'breastfeeding_entries' => 'array',
    'aefi_cases' => 'array',
    'summary_map' => 'array',
  ];

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function linkedChild(): BelongsTo
  {
    return $this->belongsTo(LinkedChild::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function getCompletedVaccinesCountAttribute(): int
  {
    return collect((array) $this->vaccination_dates)
      ->filter(fn($value) => !empty($value))
      ->count();
  }

  public function getWeightEntriesCountAttribute(): int
  {
    return count((array) $this->weight_entries);
  }

  public function getBreastfeedingMonthsLoggedAttribute(): int
  {
    return collect((array) $this->breastfeeding_entries)
      ->filter(fn($value) => !empty($value))
      ->count();
  }
}

