<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
  use HasFactory;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'cl_date',
    'cl_sex',
    'toc',
    'seeking_care',
    'transportation',
    'parity',
    'dodel',
    'cl_phone',
    'mod',
    'partograph',
    'oxytocin',
    'misoprostol',
    'alive',
    'admitted',
    'discharged',
    'referred_out',
    'pac',
    'mother_transportation',
    'dead',
    'MDA_conducted',
    'MDA_not_conducted',
    'abortion',
    'time_of_delivery',
    'pre_term',
    'breathing',
    'weight',
    'still_birth',
    'baby_dead',
    'live_births',
    'baby_sex',
    'took_delivery',
    'doctor',
    'newborn_care',
    'clamped',
    'CKX_gel',
    'breast',
    'temperature',
    'breastfeeding',
    'postpartum',
    'took_del',
    'officer_name',
    'officer_role',
    'officer_designation',
    'blood_loss',
    'gestational_age',
    'complications'
  ];

  protected $casts = [
    'month_year' => 'date',
    'cl_date' => 'date',
    'dodel' => 'date',
    'weight' => 'float',
    'temperature' => 'float',
    'blood_loss' => 'float',
    'gestational_age' => 'integer'
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
  public function getFormattedDeliveryDateAttribute(): string
  {
    return $this->dodel ? $this->dodel->format('d M Y') : '';
  }

  public function getBabySexBadgeColorAttribute(): string
  {
    return $this->baby_sex === 'Male' ? 'primary' : 'success';
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
    return $query->orderBy('dodel', 'desc')->orderBy('created_at', 'desc');
  }
}
