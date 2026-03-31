<?php

namespace App\Models;

use App\Models\Patient;
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
    'account_status',
    'facility_id',
    'department_id',
    'lga_id',
    'state_id',
    'patient_id',
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
    'account_status' => 'string',
    'facility_id' => 'integer',
    'department_id' => 'integer',
    'lga_id' => 'integer',
    'state_id' => 'integer',
    'patient_id' => 'integer',
  ];

  /**
   * Get the facility this user belongs to
   */
  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  /**
   * Get the facility department this user belongs to.
   */
  public function department(): BelongsTo
  {
    return $this->belongsTo(FacilityDepartment::class, 'department_id');
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
   * Get the linked patient record for patient-portal accounts.
   */
  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
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

  /**
   * Expose DIN on patient portal accounts.
   * For the current rollout, patient usernames are DIN values.
   */
  public function getDinAttribute(): ?string
  {
    if ($this->patient?->din) {
      return (string) $this->patient->din;
    }

    if ($this->role === 'Patient' && preg_match('/^\d{8}$/', (string) $this->username)) {
      return (string) $this->username;
    }

    $patient = Patient::query()
      ->where('facility_id', $this->facility_id)
      ->where('first_name', $this->first_name)
      ->where('last_name', $this->last_name)
      ->orderBy('id')
      ->first();

    return $patient?->din;
  }

  /**
   * Backward compatibility for legacy uppercase DIN references in views/services.
   */
  public function getAttribute($key)
  {
    if ($key === 'DIN') {
      return $this->getDinAttribute();
    }

    return parent::getAttribute($key);
  }
}
