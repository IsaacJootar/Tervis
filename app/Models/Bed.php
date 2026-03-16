<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bed extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'facility_id',
    'bed_code',
    'ward_section',
    'room_label',
    'bed_type',
    'status',
    'is_active',
    'occupied_by_patient_id',
    'occupied_since',
    'last_status_changed_at',
    'notes',
  ];

  protected $casts = [
    'is_active' => 'boolean',
    'occupied_since' => 'datetime',
    'last_status_changed_at' => 'datetime',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function occupiedByPatient(): BelongsTo
  {
    return $this->belongsTo(Patient::class, 'occupied_by_patient_id');
  }

  public function scopeForFacility($query, int $facilityId)
  {
    return $query->where('facility_id', $facilityId);
  }
}

