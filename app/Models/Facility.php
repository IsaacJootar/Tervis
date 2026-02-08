<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facility extends Model
{
  use SoftDeletes;

  protected $fillable = [
    'name',
    'code',
    'state_id',
    'lga_id',
    'ward_id',
    'state',
    'lga',
    'ward',
    'address',
    'phone',
    'email',
    'type',
    'ownership',
    'is_active',
  ];

  protected $casts = [
    'state_id' => 'integer',
    'lga_id' => 'integer',
    'ward_id' => 'integer',
    'is_active' => 'boolean',
  ];

  /**
   * Get the state this facility belongs to (relationship)
   */
  public function stateRelation(): BelongsTo
  {
    return $this->belongsTo(State::class, 'state_id');
  }

  /**
   * Get the LGA this facility belongs to (relationship)
   */
  public function lgaRelation(): BelongsTo
  {
    return $this->belongsTo(Lga::class, 'lga_id');
  }

  /**
   * Get the ward this facility belongs to (relationship)
   */
  public function wardRelation(): BelongsTo
  {
    return $this->belongsTo(Ward::class, 'ward_id');
  }

  /**
   * Scope for active facilities
   */
  public function scopeActive($query)
  {
    return $query->where('is_active', true);
  }
}
