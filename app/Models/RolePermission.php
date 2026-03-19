<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
  protected $fillable = [
    'role_name',
    'permission_key',
    'permission_label',
    'permission_group',
    'is_allowed',
    'last_changed_by_user_id',
  ];

  protected $casts = [
    'is_allowed' => 'boolean',
    'last_changed_by_user_id' => 'integer',
  ];

  public function changedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'last_changed_by_user_id');
  }
}

