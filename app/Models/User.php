<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
  use HasFactory, Notifiable, SoftDeletes;

  protected $fillable = [
    'first_name',
    'last_name',
    'username',
    'email',
    'phone',
    'password',
    'role',
    'designation',
    'facility_id',
    'lga_id',
    'state_id',
    'is_active',
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'is_active' => 'boolean',
    'facility_id' => 'integer',
    'lga_id' => 'integer',
    'state_id' => 'integer',
  ];

  /**
   * Get the facility this user belongs to
   */
  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  /**
   * Get the LGA this user belongs to
   */
  public function lga(): BelongsTo
  {
    return $this->belongsTo(Lga::class);
  }

  /**
   * Get the state this user belongs to
   */
  public function state(): BelongsTo
  {
    return $this->belongsTo(State::class);
  }

  /**
   * Check if user has a specific role
   */
  public function hasRole(string $role): bool
  {
    return $this->role === $role;
  }

  /**
   * Check if user has any of the given roles
   */
  public function hasAnyRole(array $roles): bool
  {
    return in_array($this->role, $roles);
  }

  /**
   * Scope for active users
   */
  public function scopeActive($query)
  {
    return $query->where('is_active', true);
  }

  /**
   * Scope for specific role
   */
  public function scopeRole($query, string $role)
  {
    return $query->where('role', $role);
  }

  /**
   * Get full name
   */
  public function getFullNameAttribute(): string
  {
    return "{$this->first_name} {$this->last_name}";
  }
}
