<?php

namespace App\Models;

use App\Models\Registrations\FamilyPlanningRegistration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyPlanningFollowUp extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'family_planning_registration_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'visit_date',
    'next_appointment_date',
    'method_change',
    'method_supplied',
    'brand_size_quality',
    'blood_pressure',
    'weight',
    'pelvic_exam_performed',
    'observation_notes',
    'summary_map',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'month_year' => 'date',
    'visit_date' => 'date',
    'next_appointment_date' => 'date',
    'weight' => 'decimal:2',
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

  public function registration(): BelongsTo
  {
    return $this->belongsTo(FamilyPlanningRegistration::class, 'family_planning_registration_id');
  }
}
