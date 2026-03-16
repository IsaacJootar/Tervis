<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DrugStockBatch extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'facility_id',
    'drug_catalog_item_id',
    'batch_number',
    'received_date',
    'expiry_date',
    'quantity_received',
    'quantity_available',
    'unit_cost',
    'supplier_name',
    'notes',
    'is_active',
  ];

  protected $casts = [
    'received_date' => 'date',
    'expiry_date' => 'date',
    'quantity_received' => 'decimal:2',
    'quantity_available' => 'decimal:2',
    'unit_cost' => 'decimal:2',
    'is_active' => 'boolean',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function catalogItem(): BelongsTo
  {
    return $this->belongsTo(DrugCatalogItem::class, 'drug_catalog_item_id');
  }

  public function movements(): HasMany
  {
    return $this->hasMany(DrugStockMovement::class, 'drug_stock_batch_id');
  }
}

