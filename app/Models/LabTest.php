<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabTest extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'visit_date',
    'lab_no',
    'specimen',
    'clinician_diagnosis',
    'age_sex',
    'examination',
    'report_values',
    'widal_values',
    'stool_values',
    'mcs_results',
    'urinalysis_results',
    'microscopy_results',
    'sensitivity_results',
    'comment',
    'mlt_sign',
    'sign_date',
    'summary_map',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'month_year' => 'date',
    'visit_date' => 'date',
    'sign_date' => 'date',
    'report_values' => 'array',
    'widal_values' => 'array',
    'stool_values' => 'array',
    'mcs_results' => 'array',
    'urinalysis_results' => 'array',
    'microscopy_results' => 'array',
    'sensitivity_results' => 'array',
    'summary_map' => 'array',
  ];

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function getPositiveMarkersCountAttribute(): int
  {
    return collect((array) $this->mcs_results)
      ->filter(fn($value) => in_array($value, ['Positive', 'Reactive'], true))
      ->count();
  }
}
