<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabSample extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'facility_id',
    'patient_id',
    'lab_test_order_id',
    'processing_batch_id',
    'accession_no',
    'test_name',
    'specimen_type',
    'sample_status',
    'collected_at',
    'received_at',
    'received_by',
    'remarks',
  ];

  protected $casts = [
    'collected_at' => 'datetime',
    'received_at' => 'datetime',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function order(): BelongsTo
  {
    return $this->belongsTo(LabTestOrder::class, 'lab_test_order_id');
  }

  public function processingBatch(): BelongsTo
  {
    return $this->belongsTo(LabProcessingBatch::class, 'processing_batch_id');
  }
}

