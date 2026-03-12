<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceLine extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'invoice_id',
    'patient_id',
    'facility_id',
    'module',
    'reference_type',
    'reference_id',
    'reference_code',
    'description',
    'quantity',
    'unit_price',
    'line_amount',
    'service_date',
    'created_by',
  ];

  protected $casts = [
    'quantity' => 'decimal:2',
    'unit_price' => 'decimal:2',
    'line_amount' => 'decimal:2',
    'service_date' => 'date',
  ];

  public function invoice(): BelongsTo
  {
    return $this->belongsTo(Invoice::class);
  }
}

