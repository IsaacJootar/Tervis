<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'doctor_assessment_id',
    'patient_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'prescribed_date',
    'drug_name',
    'dosage',
    'frequency',
    'duration',
    'route',
    'instructions',
    'quantity_prescribed',
    'quantity_dispensed',
    'status',
    'prescribed_by',
    'dispensed_by',
    'dispensed_date',
    'dispense_notes',
  ];

  protected $casts = [
    'month_year' => 'date',
    'prescribed_date' => 'date',
    'dispensed_date' => 'date',
    'quantity_prescribed' => 'decimal:2',
    'quantity_dispensed' => 'decimal:2',
  ];

  public function assessment(): BelongsTo
  {
    return $this->belongsTo(DoctorAssessment::class, 'doctor_assessment_id');
  }

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }
}