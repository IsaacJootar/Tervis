<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntenatalFollowUpAssessment extends Model
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
    'bp',
    'pcv',
    'weight',
    'fundal_height',
    'presentation_position',
    'relation_to_brim',
    'fetal_heart_rate',
    'urine_test',
    'oedema',
    'clinical_remarks',
    'special_delivery_instructions',
    'next_return_date',
    'xray_pelvimetry',
    'pelvic_inlet',
    'pelvic_cavity',
    'pelvic_outlet',
    'hb_genotype',
    'rhesus',
    'kahn_vdrl',
    'antimalarials_therapy',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'month_year' => 'date',
    'visit_date' => 'date',
    'next_return_date' => 'date',
    'xray_pelvimetry' => 'boolean',
    'pcv' => 'float',
    'weight' => 'float',
    'fundal_height' => 'float',
    'fetal_heart_rate' => 'integer',
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
}
