<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabProcessingBatch extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'facility_id',
    'batch_code',
    'test_profile',
    'analyzer_name',
    'run_date',
    'status',
    'sample_count',
    'notes',
    'created_by',
    'completed_by',
    'completed_at',
  ];

  protected $casts = [
    'run_date' => 'date',
    'completed_at' => 'datetime',
    'sample_count' => 'integer',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function samples(): HasMany
  {
    return $this->hasMany(LabSample::class, 'processing_batch_id');
  }
}

