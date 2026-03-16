<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitEvent extends Model
{
  use HasFactory;

  protected $fillable = [
    'visit_id',
    'patient_id',
    'facility_id',
    'activity_id',
    'event_time',
    'module',
    'action',
    'description',
    'performed_by',
    'source_type',
    'source_id',
    'meta',
  ];

  protected $casts = [
    'event_time' => 'datetime',
    'meta' => 'array',
  ];

  public function visit(): BelongsTo
  {
    return $this->belongsTo(Visit::class);
  }

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function activity(): BelongsTo
  {
    return $this->belongsTo(Activity::class);
  }
}

