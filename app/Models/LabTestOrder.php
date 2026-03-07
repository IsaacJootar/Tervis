<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabTestOrder extends Model
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
    'visit_date',
    'test_name',
    'specimen',
    'priority',
    'instructions',
    'status',
    'requested_by',
    'requested_at',
    'completed_lab_test_id',
    'completed_at',
    'completed_by',
    'completion_notes',
  ];

  protected $casts = [
    'month_year' => 'date',
    'visit_date' => 'date',
    'requested_at' => 'datetime',
    'completed_at' => 'datetime',
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

  public function labTest(): BelongsTo
  {
    return $this->belongsTo(LabTest::class, 'completed_lab_test_id');
  }
}