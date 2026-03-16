<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabReagentMovement extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'facility_id',
    'lab_reagent_stock_id',
    'movement_type',
    'quantity',
    'balance_after',
    'moved_at',
    'moved_by',
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

  public function stock(): BelongsTo
  {
    return $this->belongsTo(LabReagentStock::class, 'lab_reagent_stock_id');
  }
}

