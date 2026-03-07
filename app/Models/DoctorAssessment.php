<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorAssessment extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'doctor_user_id',
    'month_year',
    'visit_date',
    'chief_complaints',
    'history_of_present_illness',
    'vital_signs',
    'physical_examination',
    'clinical_findings',
    'provisional_diagnosis',
    'final_diagnosis',
    'assessment_note',
    'management_plan',
    'follow_up_instructions',
    'referral_note',
    'advice_to_patient',
    'requires_lab_tests',
    'requires_drugs',
    'summary_map',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'month_year' => 'date',
    'visit_date' => 'date',
    'requires_lab_tests' => 'boolean',
    'requires_drugs' => 'boolean',
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

  public function labTestOrders(): HasMany
  {
    return $this->hasMany(LabTestOrder::class);
  }

  public function prescriptions(): HasMany
  {
    return $this->hasMany(Prescription::class);
  }
}