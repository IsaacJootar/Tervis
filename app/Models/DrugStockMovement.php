<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DrugStockMovement extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'facility_id',
    'drug_catalog_item_id',
    'drug_stock_batch_id',
    'patient_id',
    'movement_type',
    'quantity',
    'balance_after',
    'moved_at',
    'moved_by',
    'reference_type',
    'reference_id',
    'reference_code',
    'notes',
  ];

  protected $casts = [
    'quantity' => 'decimal:2',
    'balance_after' => 'decimal:2',
    'moved_at' => 'datetime',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function catalogItem(): BelongsTo
  {
    return $this->belongsTo(DrugCatalogItem::class, 'drug_catalog_item_id');
  }

  public function batch(): BelongsTo
  {
    return $this->belongsTo(DrugStockBatch::class, 'drug_stock_batch_id');
  }

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }
}

