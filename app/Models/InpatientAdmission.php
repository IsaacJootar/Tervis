<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InpatientAdmission extends Model
{
  use HasFactory, SoftDeletes;

  public const STATUS_ADMITTED = 'admitted';
  public const STATUS_DISCHARGED = 'discharged';
  public const STATUS_REFERRED = 'referred';

  protected $fillable = [
    'facility_id',
    'patient_id',
    'bed_section_id',
    'bed_id',
    'admission_code',
    'admitted_at',
    'admitted_by',
    'admission_reason',
    'status',
    'is_active',
    'discharged_at',
    'discharged_by',
    'discharge_note',
    'referral_destination',
  ];

  protected $casts = [
    'admitted_at' => 'datetime',
    'discharged_at' => 'datetime',
    'is_active' => 'boolean',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function section(): BelongsTo
  {
    return $this->belongsTo(BedSection::class, 'bed_section_id');
  }

  public function bed(): BelongsTo
  {
    return $this->belongsTo(Bed::class);
  }

  public function scopeForFacility($query, int $facilityId)
  {
    return $query->where('facility_id', $facilityId);
  }
}

