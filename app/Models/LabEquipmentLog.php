<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabEquipmentLog extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'facility_id',
    'equipment_name',
    'equipment_code',
    'log_type',
    'performed_date',
    'next_due_date',
    'result_status',
    'performed_by',
    'notes',
  ];

  protected $casts = [
    'performed_date' => 'date',
    'next_due_date' => 'date',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }
}

