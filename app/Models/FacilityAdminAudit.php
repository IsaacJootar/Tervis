<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityAdminAudit extends Model
{
  protected $fillable = [
    'facility_id',
    'changed_by_user_id',
    'changed_by_name',
    'action',
    'target_type',
    'target_id',
    'old_values',
    'new_values',
    'notes',
  ];

  protected $casts = [
    'facility_id' => 'integer',
    'changed_by_user_id' => 'integer',
    'target_id' => 'integer',
    'old_values' => 'array',
    'new_values' => 'array',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function changedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'changed_by_user_id');
  }
}
