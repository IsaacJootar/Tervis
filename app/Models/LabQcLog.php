<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabQcLog extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'facility_id',
    'qc_date',
    'qc_type',
    'test_profile',
    'control_level',
    'expected_range',
    'observed_value',
    'status',
    'reviewed_by',
    'remarks',
  ];

  protected $casts = [
    'qc_date' => 'date',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }
}

