<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabReagentStock extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'facility_id',
    'reagent_name',
    'lot_number',
    'unit',
    'quantity_available',
    'reorder_level',
    'expiry_date',
    'manufacturer',
    'is_active',
    'notes',
  ];

  protected $casts = [
    'quantity_available' => 'decimal:2',
    'reorder_level' => 'decimal:2',
    'expiry_date' => 'date',
    'is_active' => 'boolean',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function movements(): HasMany
  {
    return $this->hasMany(LabReagentMovement::class, 'lab_reagent_stock_id');
  }
}

