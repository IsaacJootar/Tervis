<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityDepartment extends Model
{
  use HasFactory;

  protected $fillable = [
    'facility_id',
    'name',
    'details',
    'is_active',
  ];

  protected $casts = [
    'is_active' => 'boolean',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * Get the facility that owns the department.
   */
  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  /**
   * Scope a query to only include active departments.
   */
  public function scopeActive($query)
  {
    return $query->where('is_active', true);
  }

  /**
   * Scope a query to only include departments for a specific facility.
   */
  public function scopeForFacility($query, $facilityId)
  {
    return $query->where('facility_id', $facilityId);
  }

  /**
   * Get formatted created date.
   */
  public function getFormattedCreatedAtAttribute()
  {
    return $this->created_at->format('M d, Y');
  }

  /**
   * Get status
   */
  public function getStatusTextAttribute()
  {
    return $this->is_active ? 'Active' : 'Inactive';
  }

  /**
   * Get status badge
   */
  public function getStatusBadgeAttribute()
  {
    return $this->is_active ? 'bg-label-success' : 'bg-label-danger';
  }
}
