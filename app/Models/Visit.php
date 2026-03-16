<?php

namespace App\Models;

use App\Models\Registrations\DinActivation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
  use HasFactory;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'activation_id',
    'visit_date',
    'check_in_time',
    'check_out_time',
    'status',
    'total_events',
    'modules_summary',
    'notes',
    'recorded_by',
  ];

  protected $casts = [
    'visit_date' => 'date',
    'check_out_time' => 'datetime',
    'total_events' => 'integer',
    'modules_summary' => 'array',
  ];

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function activation(): BelongsTo
  {
    return $this->belongsTo(DinActivation::class, 'activation_id');
  }

  public function events(): HasMany
  {
    return $this->hasMany(VisitEvent::class)->orderByDesc('event_time');
  }

  public function scopeForPatientFacility($query, int $patientId, int $facilityId)
  {
    return $query
      ->where('patient_id', $patientId)
      ->where('facility_id', $facilityId);
  }

  public function getCheckInDisplayAttribute(): ?string
  {
    if (!$this->check_in_time) {
      return null;
    }

    try {
      return Carbon::createFromFormat('H:i:s', (string) $this->check_in_time)->format('h:i A');
    } catch (\Throwable $e) {
      return (string) $this->check_in_time;
    }
  }
}

