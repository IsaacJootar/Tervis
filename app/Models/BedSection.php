<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BedSection extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'facility_id',
    'name',
    'details',
    'is_active',
  ];

  protected $casts = [
    'is_active' => 'boolean',
  ];

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function beds(): HasMany
  {
    return $this->hasMany(Bed::class, 'bed_section_id');
  }

  public function scopeForFacility($query, int $facilityId)
  {
    return $query->where('facility_id', $facilityId);
  }
}

