<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffManagementAudit extends Model
{
  use HasFactory;

  protected $fillable = [
    'facility_id',
    'target_user_id',
    'action',
    'old_values',
    'new_values',
    'changed_by_user_id',
    'changed_by_name',
    'notes',
  ];

  protected $casts = [
    'old_values' => 'array',
    'new_values' => 'array',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function targetUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'target_user_id');
  }

  public function changedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'changed_by_user_id');
  }
}

