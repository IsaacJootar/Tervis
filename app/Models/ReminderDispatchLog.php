<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderDispatchLog extends Model
{
  use HasFactory;

  protected $fillable = [
    'reminder_id',
    'patient_id',
    'facility_id',
    'channel',
    'status',
    'provider',
    'recipient',
    'subject',
    'message',
    'provider_message',
    'provider_message_id',
    'provider_http_code',
    'delivery_status',
    'delivery_message',
    'delivery_payload',
    'delivery_updated_at',
    'provider_payload',
    'sent_at',
    'failed_at',
  ];

  protected $casts = [
    'provider_payload' => 'array',
    'delivery_payload' => 'array',
    'delivery_updated_at' => 'datetime',
    'sent_at' => 'datetime',
    'failed_at' => 'datetime',
  ];

  public function reminder(): BelongsTo
  {
    return $this->belongsTo(Reminder::class);
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
