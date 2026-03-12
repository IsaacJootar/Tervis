<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'invoice_code',
    'invoice_date',
    'total_amount',
    'amount_paid',
    'outstanding_amount',
    'status',
    'notes',
    'created_by',
  ];

  protected $casts = [
    'month_year' => 'date',
    'invoice_date' => 'date',
    'total_amount' => 'decimal:2',
    'amount_paid' => 'decimal:2',
    'outstanding_amount' => 'decimal:2',
  ];

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function lines(): HasMany
  {
    return $this->hasMany(InvoiceLine::class);
  }

  public function allocations(): HasMany
  {
    return $this->hasMany(PaymentAllocation::class);
  }
}

