<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSnapshot extends Model
{
  protected $fillable = [
    'snapshot_key',
    'report_key',
    'created_by_user_id',
    'payload',
    'expires_at',
  ];

  protected $casts = [
    'payload' => 'array',
    'expires_at' => 'datetime',
  ];

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by_user_id');
  }
}
