<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacilityServiceCatalogItem extends Model
{
  use SoftDeletes;

  protected $fillable = [
    'facility_id',
    'service_code',
    'service_name',
    'service_category',
    'description',
    'base_fee',
    'is_active',
    'created_by_user_id',
    'updated_by_user_id',
  ];

  protected $casts = [
    'facility_id' => 'integer',
    'base_fee' => 'decimal:2',
    'is_active' => 'boolean',
    'created_by_user_id' => 'integer',
    'updated_by_user_id' => 'integer',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function feeSchedules(): HasMany
  {
    return $this->hasMany(FacilityFeeSchedule::class, 'facility_service_catalog_item_id');
  }
}
