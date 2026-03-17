<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityFeeSchedule extends Model
{
  protected $fillable = [
    'facility_id',
    'facility_service_catalog_item_id',
    'amount',
    'effective_from',
    'effective_to',
    'is_active',
    'notes',
    'created_by_user_id',
    'updated_by_user_id',
  ];

  protected $casts = [
    'facility_id' => 'integer',
    'facility_service_catalog_item_id' => 'integer',
    'amount' => 'decimal:2',
    'effective_from' => 'date',
    'effective_to' => 'date',
    'is_active' => 'boolean',
    'created_by_user_id' => 'integer',
    'updated_by_user_id' => 'integer',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function service(): BelongsTo
  {
    return $this->belongsTo(FacilityServiceCatalogItem::class, 'facility_service_catalog_item_id');
  }
}
