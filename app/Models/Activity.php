<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
  use HasFactory;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'module',
    'action',
    'description',
    'performed_by',
    'meta',
  ];

  protected $casts = [
    'meta' => 'array',
  ];

  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }
}
