<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityModuleAccess extends Model
{
  protected $fillable = [
    'facility_id',
    'module_key',
    'module_label',
    'is_enabled',
    'last_changed_by_user_id',
  ];

  protected $casts = [
    'facility_id' => 'integer',
    'is_enabled' => 'boolean',
    'last_changed_by_user_id' => 'integer',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }
}
