<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class LinkedChild extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'linked_child_id',
    'parent_patient_id',
    'first_name',
    'last_name',
    'middle_name',
    'gender',
    'date_of_birth',
    'relationship',
    'birth_weight',
    'birth_length',
    'birth_order',
    'is_active',
    'deceased_date',
    'graduated_patient_id',
    'graduated_at',
    'facility_id',
    'created_by',
    'updated_by',
    'notes',
  ];

  protected $casts = [
    'date_of_birth' => 'date',
    'deceased_date' => 'date',
    'graduated_at' => 'datetime',
    'is_active' => 'boolean',
    'birth_weight' => 'decimal:2',
    'birth_length' => 'decimal:2',
    'birth_order' => 'integer',
    'parent_patient_id' => 'integer',
    'graduated_patient_id' => 'integer',
    'facility_id' => 'integer',
    'created_by' => 'integer',
    'updated_by' => 'integer',
  ];

  /**
   * Generate unique Linked Child ID (LC-XXXXX)
   * Format: LC-00001, LC-00002, etc.
   */
  /**
   * Generate unique Linked Child ID (LC-XXXXXXXX)
   * Format: LC-00000001, LC-00000002, etc.
   */
  public static function generateLinkedChildID(): string
  {
    $totalCapacity = 99999999; // LC-00000001 to LC-99999999 (99 million+)
    $currentCount = self::withTrashed()->count();

    if ($currentCount >= $totalCapacity) {
      throw new \Exception("Maximum Linked Child ID capacity reached (99,999,999 records).");
    }

    for ($attempts = 0; $attempts < 100; $attempts++) {
      $nextNumber = $currentCount + 1 + $attempts;
      $formattedID = 'LC-' . str_pad($nextNumber, 8, '0', STR_PAD_LEFT); // 8 digits

      $exists = self::withTrashed()->where('linked_child_id', $formattedID)->exists();

      if (!$exists) {
        return $formattedID;
      }
    }

    throw new \Exception("Could not generate a unique Linked Child ID.");
  }
  /**
   * Search linked children by name, ID, or parent info
   */
  public static function search(string $query, int $limit = 10)
  {
    return self::where(function ($q) use ($query) {
      $q->where('linked_child_id', 'like', "%{$query}%")
        ->orWhere('first_name', 'like', "%{$query}%")
        ->orWhere('last_name', 'like', "%{$query}%")
        ->orWhere('middle_name', 'like', "%{$query}%")
        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
        ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$query}%"])
        ->orWhereHas('parent', function ($parentQuery) use ($query) {
          $parentQuery->where('din', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
        });
    })
      ->where('is_active', true)
      ->with(['parent', 'facility'])
      ->limit($limit)
      ->get();
  }

  // ============================================
  // SCOPES
  // ============================================

  public function scopeActive($query)
  {
    return $query->where('is_active', true);
  }

  public function scopeInactive($query)
  {
    return $query->where('is_active', false);
  }

  public function scopeGraduated($query)
  {
    return $query->whereNotNull('graduated_patient_id');
  }

  public function scopeDeceased($query)
  {
    return $query->whereNotNull('deceased_date');
  }

  public function scopeByFacility($query, int $facilityId)
  {
    return $query->where('facility_id', $facilityId);
  }

  public function scopeByGender($query, string $gender)
  {
    return $query->where('gender', $gender);
  }

  public function scopeByParent($query, int $parentPatientId)
  {
    return $query->where('parent_patient_id', $parentPatientId);
  }

  public function scopeInfants($query)
  {
    return $query->whereRaw('TIMESTAMPDIFF(MONTH, date_of_birth, NOW()) < 12');
  }

  public function scopeToddlers($query)
  {
    return $query->whereRaw('TIMESTAMPDIFF(MONTH, date_of_birth, NOW()) BETWEEN 12 AND 35');
  }

  public function scopePreschool($query)
  {
    return $query->whereRaw('TIMESTAMPDIFF(MONTH, date_of_birth, NOW()) BETWEEN 36 AND 59');
  }

  public function scopeSchoolAge($query)
  {
    return $query->whereRaw('TIMESTAMPDIFF(MONTH, date_of_birth, NOW()) BETWEEN 60 AND 168');
  }

  public function scopeEligibleForGraduation($query)
  {
    return $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) >= 15')
      ->where('is_active', true);
  }

    // ============================================
    // RELATIONSHIPS
    // ============================================

  /**
   * Parent (Mother/Guardian)
   * THE LINK: Child belongs to one parent patient
   */
  public function parent(): BelongsTo
  {
    return $this->belongsTo(Patient::class, 'parent_patient_id');
  }

  /**
   * Graduated Patient Record (if child became full patient)
   */
  public function graduatedPatient(): BelongsTo
  {
    return $this->belongsTo(Patient::class, 'graduated_patient_id');
  }

  /**
   * Facility where child is registered
   */
  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  /**
   * User who created this record
   */
  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  /**
   * User who last updated this record
   */
  public function updater(): BelongsTo
  {
    return $this->belongsTo(User::class, 'updated_by');
  }

  /**
   * Immunization Records
   * TODO: Implement after immunization_records table is created
   */
  public function immunizationRecords(): HasMany
  {
    // Will be implemented when immunization_records table is ready
    // return $this->hasMany(ImmunizationRecord::class, 'linked_child_id');

    // Placeholder for now
    return $this->hasMany(ImmunizationRecord::class, 'linked_child_id');
  }

  /**
   * Nutrition Records
   * TODO: Implement after nutrition_records table is created
   */
  public function nutritionRecords(): HasMany
  {
    // Will be implemented when nutrition_records table is ready
    // return $this->hasMany(NutritionRecord::class, 'linked_child_id');

    // Placeholder for now
    return $this->hasMany(NutritionRecord::class, 'linked_child_id');
  }

  /**
   * Latest Nutrition Record
   */
  public function latestNutritionRecord()
  {
    return $this->hasOne(NutritionRecord::class, 'linked_child_id')
      ->latest('visit_date');
  }

  /**
   * Latest Immunization Record
   */
  public function latestImmunizationRecord()
  {
    return $this->hasOne(ImmunizationRecord::class, 'linked_child_id')
      ->latest('visit_date');
  }

  // ============================================
  // ACCESSORS
  // ============================================

  public function getFullNameAttribute(): string
  {
    return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
  }

  public function getAgeAttribute(): int
  {
    return $this->date_of_birth ? $this->date_of_birth->age : 0;
  }

  public function getAgeInMonthsAttribute(): int
  {
    return $this->date_of_birth ? $this->date_of_birth->diffInMonths(now()) : 0;
  }

  public function getAgeInDaysAttribute(): int
  {
    return $this->date_of_birth ? $this->date_of_birth->diffInDays(now()) : 0;
  }

  public function getAgeInWeeksAttribute(): int
  {
    return $this->date_of_birth ? $this->date_of_birth->diffInWeeks(now()) : 0;
  }

  public function getFormattedDateOfBirthAttribute(): string
  {
    return $this->date_of_birth ? $this->date_of_birth->format('d M Y') : '';
  }

  public function getAgeDisplayAttribute(): string
  {
    if (!$this->date_of_birth) {
      return 'Unknown';
    }

    $months = $this->age_in_months;
    $years = $this->age;

    if ($years >= 1) {
      return $years . ($years === 1 ? ' year' : ' years');
    } elseif ($months >= 1) {
      return $months . ($months === 1 ? ' month' : ' months');
    } else {
      $days = $this->age_in_days;
      return $days . ($days === 1 ? ' day' : ' days');
    }
  }

  public function getAgeGroupAttribute(): string
  {
    $months = $this->age_in_months;

    if ($months < 12) {
      return 'Infant (0-11 months)';
    } elseif ($months < 36) {
      return 'Toddler (1-2 years)';
    } elseif ($months < 60) {
      return 'Preschool (3-4 years)';
    } elseif ($months < 169) {
      return 'School Age (5-14 years)';
    } else {
      return 'Adolescent (15+ years)';
    }
  }

  public function getIsInfantAttribute(): bool
  {
    return $this->age_in_months < 12;
  }

  public function getIsToddlerAttribute(): bool
  {
    $months = $this->age_in_months;
    return $months >= 12 && $months < 36;
  }

  public function getIsPreschoolAttribute(): bool
  {
    $months = $this->age_in_months;
    return $months >= 36 && $months < 60;
  }

  public function getIsSchoolAgeAttribute(): bool
  {
    $months = $this->age_in_months;
    return $months >= 60 && $months < 169;
  }

  public function getIsEligibleForGraduationAttribute(): bool
  {
    return $this->age >= 15 && $this->is_active;
  }

  public function getIsDeceasedAttribute(): bool
  {
    return !is_null($this->deceased_date);
  }

  public function getIsGraduatedAttribute(): bool
  {
    return !is_null($this->graduated_patient_id);
  }

  public function getStatusAttribute(): string
  {
    if ($this->is_deceased) {
      return 'Deceased';
    } elseif ($this->is_graduated) {
      return 'Graduated';
    } elseif ($this->is_active) {
      return 'Active';
    } else {
      return 'Inactive';
    }
  }

  public function getParentNameAttribute(): string
  {
    return $this->parent ? $this->parent->full_name : 'Unknown';
  }

  public function getParentDinAttribute(): ?string
  {
    return $this->parent?->din;
  }

    // ============================================
    // HELPER METHODS
    // ============================================

  /**
   * Check if child has parent linked
   */
  public function hasParent(): bool
  {
    return !is_null($this->parent_patient_id);
  }

  /**
   * Check if child is active
   */
  public function isActive(): bool
  {
    return $this->is_active === true;
  }

  /**
   * Check if child is deceased
   */
  public function isDeceased(): bool
  {
    return !is_null($this->deceased_date);
  }

  /**
   * Check if child has graduated to full patient
   */
  public function hasGraduated(): bool
  {
    return !is_null($this->graduated_patient_id);
  }

  /**
   * Mark child as deceased
   */
  public function markAsDeceased(Carbon $date = null): void
  {
    $this->update([
      'deceased_date' => $date ?? now(),
      'is_active' => false,
    ]);
  }

  /**
   * Graduate child to full patient
   * Creates new patient record and links it
   *
   * @param array $patientData Additional patient data for full registration
   * @return Patient
   */
  public function graduateToFullPatient(array $patientData = []): Patient
  {
    // Create new patient record
    $newPatient = Patient::create(array_merge([
      'din' => Patient::generateDIN(),
      'first_name' => $this->first_name,
      'last_name' => $this->last_name,
      'middle_name' => $this->middle_name,
      'gender' => $this->gender,
      'date_of_birth' => $this->date_of_birth,
      'facility_id' => $this->facility_id,
      'registration_date' => now(),
      'is_active' => true,
    ], $patientData));

    // Update linked child record
    $this->update([
      'graduated_patient_id' => $newPatient->id,
      'graduated_at' => now(),
      'is_active' => false,
    ]);

    return $newPatient;
  }

  /**
   * Reactivate child (undo graduation or inactive status)
   */
  public function reactivate(): void
  {
    $this->update([
      'is_active' => true,
      'graduated_patient_id' => null,
      'graduated_at' => null,
    ]);
  }

  /**
   * Transfer to different parent/guardian
   */
  public function transferToParent(int $newParentPatientId): void
  {
    $this->update([
      'parent_patient_id' => $newParentPatientId,
    ]);
  }

  /**
   * Get immunization summary
   * TODO: Implement after immunization_records table structure is confirmed
   */
  public function getImmunizationSummary(): array
  {
    // Placeholder - will be implemented after register forms provided
    return [
      'total_vaccines' => 0,
      'last_vaccine_date' => null,
      'next_vaccine_due' => null,
      'is_up_to_date' => false,
    ];
  }

  /**
   * Get nutrition summary
   * TODO: Implement after nutrition_records table structure is confirmed
   */
  public function getNutritionSummary(): array
  {
    // Placeholder - will be implemented after register forms provided
    return [
      'last_weight' => null,
      'last_height' => null,
      'nutritional_status' => 'Unknown',
      'last_screening_date' => null,
    ];
  }

  /**
   * Get formatted child card display
   */
  public function getChildCardData(): array
  {
    return [
      'id' => $this->linked_child_id,
      'name' => $this->full_name,
      'gender' => $this->gender,
      'age' => $this->age_display,
      'age_group' => $this->age_group,
      'dob' => $this->formatted_date_of_birth,
      'parent_name' => $this->parent_name,
      'parent_din' => $this->parent_din,
      'status' => $this->status,
      'birth_weight' => $this->birth_weight,
      'birth_order' => $this->birth_order,
      'relationship' => $this->relationship,
      'is_eligible_for_graduation' => $this->is_eligible_for_graduation,
    ];
  }
}
