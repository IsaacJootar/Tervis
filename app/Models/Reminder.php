<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reminder extends Model
{
  use HasFactory;
  use SoftDeletes;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'source_module',
    'source_record_id',
    'title',
    'message',
    'reminder_date',
    'reminder_time',
    'status',
    'channels',
    'recipient_phone',
    'recipient_email',
    'created_by',
    'created_by_role',
    'meta',
    'queued_at',
    'sent_at',
    'failed_at',
  ];

  protected $casts = [
    'reminder_date' => 'date',
    'channels' => 'array',
    'meta' => 'array',
    'queued_at' => 'datetime',
    'sent_at' => 'datetime',
    'failed_at' => 'datetime',
  ];

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function dispatchLogs(): HasMany
  {
    return $this->hasMany(ReminderDispatchLog::class);
  }
}

