<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientPayment extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'state_id',
    'lga_id',
    'ward_id',
    'month_year',
    'payment_code',
    'payment_date',
    'amount_received',
    'payment_method',
    'notes',
    'received_by',
  ];

  protected $casts = [
    'month_year' => 'date',
    'payment_date' => 'date',
    'amount_received' => 'decimal:2',
  ];

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function allocations(): HasMany
  {
    return $this->hasMany(PaymentAllocation::class);
  }
}

