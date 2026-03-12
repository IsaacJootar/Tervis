<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
  use HasFactory;

  protected $fillable = [
    'patient_payment_id',
    'invoice_id',
    'amount_allocated',
  ];

  protected $casts = [
    'amount_allocated' => 'decimal:2',
  ];

  public function payment(): BelongsTo
  {
    return $this->belongsTo(PatientPayment::class, 'patient_payment_id');
  }

  public function invoice(): BelongsTo
  {
    return $this->belongsTo(Invoice::class);
  }
}

