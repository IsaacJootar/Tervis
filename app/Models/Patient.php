<?php

namespace App\Models;

use App\Models\Registrations\AntenatalRegistration;
use App\Models\Registrations\FamilyPlanningRegistration;
use App\Models\Registrations\GeneralPatientsRegistration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Patient extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'din',
    'first_name',
    'middle_name',
    'last_name',
    'gender',
    'date_of_birth',
    'phone',
    'email',
    'state_id',
    'lga_id',
    'ward_id',
    'facility_id',
    'registration_date',
    'is_active',
    // NHIS Fields
    'is_nhis_subscriber',
    'nhis_number',
    'nhis_provider',
    'nhis_expiry_date',
    'nhis_plan_type',
    'nhis_principal_name',
    'nhis_principal_number',
  ];

  protected $casts = [
    'date_of_birth' => 'date',
    'registration_date' => 'date',
    'nhis_expiry_date' => 'date',
    'is_active' => 'boolean',
    'is_nhis_subscriber' => 'boolean',
    'state_id' => 'integer',
    'lga_id' => 'integer',
    'ward_id' => 'integer',
    'facility_id' => 'integer',
  ];

  /**
   * Generate unique 8-digit DIN
   * Called manually from registration forms
   */
  public static function generateDIN(): string
  {
    $totalCapacity = 100000000;
    if (self::withTrashed()->count() >= $totalCapacity) {
      throw new \Exception("Maximum DIN capacity reached (100 million records).");
    }
    for ($attempts = 0; $attempts < 100; $attempts++) {
      $randomNumber = random_int(0, 99999999);
      $formattedDIN = str_pad($randomNumber, 8, '0', STR_PAD_LEFT);
      $exists = self::withTrashed()->where('din', $formattedDIN)->exists();
      if (!$exists) {
        return $formattedDIN;
      }
    }
    throw new \Exception("Could not generate a unique DIN.");
  }

  /**
   * Search patients by DIN, phone, name, or email
   */
  public static function search(string $query, int $limit = 10)
  {
    return self::where(function ($q) use ($query) {
      $q->where('din', 'like', "%{$query}%")
        ->orWhere('phone', 'like', "%{$query}%")
        ->orWhere('first_name', 'like', "%{$query}%")
        ->orWhere('last_name', 'like', "%{$query}%")
        ->orWhere('middle_name', 'like', "%{$query}%")
        ->orWhere('email', 'like', "%{$query}%")
        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
        ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
    })
      ->where('is_active', true)
      ->with(['state', 'lga', 'ward', 'generalRegistration', 'antenatalRegistrations'])
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

  public function scopeByFacility($query, int $facilityId)
  {
    return $query->where('facility_id', $facilityId);
  }

  public function scopeByGender($query, string $gender)
  {
    return $query->where('gender', $gender);
  }

  public function scopeRegisteredBetween($query, $startDate, $endDate)
  {
    return $query->whereBetween('registration_date', [$startDate, $endDate]);
  }

  public function scopeFemale($query)
  {
    return $query->where('gender', 'Female');
  }

  public function scopeWithActivePregnancy($query)
  {
    return $query->whereHas('antenatalRegistrations', function ($q) {
      $q->where('is_active', true);
    });
  }

  public function scopeEligibleForAntenatal($query)
  {
    return $query->where('gender', 'Female');
  }

  /**
   * Scope: Patients with linked children
   */
  public function scopeWithLinkedChildren($query)
  {
    return $query->whereHas('linkedChildren');
  }

  // ============================================
  // RELATIONSHIPS
  // ============================================

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  public function state(): BelongsTo
  {
    return $this->belongsTo(State::class);
  }

  public function lga(): BelongsTo
  {
    return $this->belongsTo(Lga::class);
  }

  public function ward(): BelongsTo
  {
    return $this->belongsTo(Ward::class);
  }

  public function generalRegistration(): HasOne
  {
    return $this->hasOne(GeneralPatientsRegistration::class);
  }

  public function antenatalRegistrations(): HasMany
  {
    return $this->hasMany(AntenatalRegistration::class)
      ->orderBy('registration_date', 'desc');
  }

  public function activeAntenatalRegistration(): HasOne
  {
    return $this->hasOne(AntenatalRegistration::class)
      ->where('is_active', true)
      ->latest('registration_date');
  }

  public function familyPlanningRegistration(): HasOne
  {
    return $this->hasOne(FamilyPlanningRegistration::class);
  }

  /**
   * Linked Children - Active only
   * Children linked to this patient for immunization/nutrition tracking
   */
  public function linkedChildren(): HasMany
  {
    return $this->hasMany(LinkedChild::class, 'parent_patient_id')
      ->where('is_active', true)
      ->orderBy('date_of_birth', 'desc');
  }

  /**
   * All Linked Children - Including inactive (graduated/deceased)
   */
  public function allLinkedChildren(): HasMany
  {
    return $this->hasMany(LinkedChild::class, 'parent_patient_id')
      ->orderBy('date_of_birth', 'desc');
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
    return $this->date_of_birth ? $this->date_of_birth->diffInYears(now()) : 0;
  }

  public function getAgeInMonthsAttribute(): int
  {
    return $this->date_of_birth ? $this->date_of_birth->diffInMonths(now()) : 0;
  }

  public function getAgeInDaysAttribute(): int
  {
    return $this->date_of_birth ? $this->date_of_birth->diffInDays(now()) : 0;
  }

  public function getNhmisAgeGroupAttribute(): string
  {
    $ageInDays = $this->age_in_days;
    $ageInMonths = $this->age_in_months;
    $ageInYears = $this->age;

    if ($ageInDays <= 28) return '0-28d';
    elseif ($ageInMonths <= 11) return '29d-11m';
    elseif ($ageInYears >= 1 && $ageInYears <= 4) return '1-4y';
    elseif ($ageInYears >= 5 && $ageInYears <= 9) return '5-9y';
    elseif ($ageInYears >= 10 && $ageInYears <= 14) return '10-14y';
    elseif ($ageInYears >= 15 && $ageInYears <= 19) return '15-19y';
    elseif ($ageInYears >= 20 && $ageInYears <= 39) return '20-39y';
    else return '40+y';
  }

  public function getIsChildAttribute(): bool
  {
    return $this->age < 5;
  }

  public function getIsAdultAttribute(): bool
  {
    return $this->age >= 18;
  }

  public function getIsNeonateAttribute(): bool
  {
    return $this->age_in_days <= 28;
  }

  public function getIsInfantAttribute(): bool
  {
    return $this->age_in_months < 12;
  }

  public function getFormattedDateOfBirthAttribute(): string
  {
    return $this->date_of_birth ? $this->date_of_birth->format('d M Y') : '';
  }

  public function getFormattedRegistrationDateAttribute(): string
  {
    return $this->registration_date ? $this->registration_date->format('d M Y') : '';
  }

  public function getNhisStatusAttribute(): string
  {
    if (!$this->is_nhis_subscriber) {
      return 'Non-NHIS';
    }

    if ($this->nhis_expiry_date && $this->nhis_expiry_date->isPast()) {
      return 'NHIS Expired';
    }

    return 'NHIS Active';
  }

  public function getTotalPregnanciesAttribute(): int
  {
    return $this->antenatalRegistrations()->count();
  }

  public function getLastAntenatalRegistrationAttribute()
  {
    return $this->antenatalRegistrations()->first();
  }

  public function getAntenatalRiskLevelAttribute(): string
  {
    if (!$this->hasActivePregnancy()) {
      return 'N/A';
    }

    $activeRegistration = $this->activeAntenatalRegistration;

    if (!$activeRegistration) {
      return 'N/A';
    }

    // High risk factors
    if (
      $activeRegistration->gravida >= 5
      || $this->age < 18
      || $this->age > 35
      || $activeRegistration->heart_disease
      || $activeRegistration->kidney_disease
      || $activeRegistration->hasAnyCsareanHistory()
    ) {
      return 'High';
    }

    // Medium risk factors
    if (
      $activeRegistration->gravida >= 3
      || $this->age > 30
      || $activeRegistration->chest_disease
      || $activeRegistration->current_gestational_age['weeks'] > 20
    ) {
      return 'Medium';
    }

    return 'Low';
  }

  public function getEntryPointsAttribute(): array
  {
    $entryPoints = [];

    if ($this->hasGeneralRegistration()) $entryPoints[] = 'OPD';
    if ($this->hasAntenatalRegistration()) $entryPoints[] = 'ANC';
    if ($this->hasFamilyPlanningRegistration()) $entryPoints[] = 'FP';

    return $entryPoints;
  }

  /**
   * Get total count of active linked children
   */
  public function getTotalLinkedChildrenAttribute(): int
  {
    return $this->linkedChildren()->count();
  }

  /**
   * Get youngest linked child
   */
  public function getYoungestChildAttribute()
  {
    return $this->linkedChildren()->orderBy('date_of_birth', 'desc')->first();
  }

  /**
   * Get oldest linked child
   */
  public function getOldestChildAttribute()
  {
    return $this->linkedChildren()->orderBy('date_of_birth', 'asc')->first();
  }

  // ============================================
  // HELPER METHODS - EXISTING
  // ============================================

  public function hasGeneralRegistration(): bool
  {
    return $this->generalRegistration()->exists();
  }

  public function hasAntenatalRegistration(): bool
  {
    return $this->antenatalRegistrations()->exists();
  }

  public function hasActiveAntenatalRegistration(): bool
  {
    return $this->activeAntenatalRegistration()->exists();
  }

  public function hasFamilyPlanningRegistration(): bool
  {
    return $this->familyPlanningRegistration()->exists();
  }

  public function hasActivePregnancy(): bool
  {
    return $this->antenatalRegistrations()
      ->where('is_active', true)
      ->exists();
  }

  public function getCurrentPregnancyNumber(): int
  {
    return $this->antenatalRegistrations()->count() + 1;
  }

  public function isEligibleForAntenatal(): bool
  {
    return strtolower($this->gender) === 'female';
  }

  // ============================================
  // HELPER METHODS - LINKED CHILDREN (SAFE METHODS ONLY)
  // ============================================

  /**
   * Check if patient has any linked children
   */
  public function hasLinkedChildren(): bool
  {
    return $this->linkedChildren()->exists();
  }

  /**
   * Check if patient can link children
   * Any patient (mother, father, guardian) can link children
   */
  public function canLinkChildren(): bool
  {
    return true;
  }

  /**
   * Get children by age range (in months)
   *
   * @param int $minMonths Minimum age in months
   * @param int $maxMonths Maximum age in months
   * @return \Illuminate\Support\Collection
   */
  public function getChildrenByAgeRange(int $minMonths, int $maxMonths)
  {
    return $this->linkedChildren()->get()->filter(function ($child) use ($minMonths, $maxMonths) {
      $ageInMonths = $child->age_in_months;
      return $ageInMonths >= $minMonths && $ageInMonths <= $maxMonths;
    });
  }

  /**
   * Get infants (0-11 months)
   */
  public function getInfants()
  {
    return $this->getChildrenByAgeRange(0, 11);
  }

  /**
   * Get toddlers (12-35 months / 1-2 years)
   */
  public function getToddlers()
  {
    return $this->getChildrenByAgeRange(12, 35);
  }

  /**
   * Get preschool children (36-59 months / 3-4 years)
   */
  public function getPreschoolChildren()
  {
    return $this->getChildrenByAgeRange(36, 59);
  }

  /**
   * Get school-age children (60-168 months / 5-14 years)
   */
  public function getSchoolAgeChildren()
  {
    return $this->getChildrenByAgeRange(60, 168);
  }

  /**
   * Get children eligible for graduation (180+ months / 15+ years)
   * These children can be converted to full patients
   */
  public function getChildrenEligibleForGraduation()
  {
    return $this->linkedChildren()->get()->filter(function ($child) {
      return $child->age >= 15;
    });
  }

  /**
   * Get children by gender
   *
   * @param string $gender 'Male' or 'Female'
   * @return \Illuminate\Support\Collection
   */
  public function getChildrenByGender(string $gender)
  {
    return $this->linkedChildren()->where('gender', $gender)->get();
  }

  /**
   * Get male children
   */
  public function getMaleChildren()
  {
    return $this->getChildrenByGender('Male');
  }

  /**
   * Get female children
   */
  public function getFemaleChildren()
  {
    return $this->getChildrenByGender('Female');
  }

  /**
   * Get children born in a specific year
   *
   * @param int $year
   * @return \Illuminate\Support\Collection
   */
  public function getChildrenBornInYear(int $year)
  {
    return $this->linkedChildren()->get()->filter(function ($child) use ($year) {
      return $child->date_of_birth && $child->date_of_birth->year === $year;
    });
  }

  /**
   * Get children summary statistics
   *
   * @return array
   */
  public function getChildrenSummary(): array
  {
    $children = $this->linkedChildren;

    return [
      'total_children' => $children->count(),
      'male_children' => $children->where('gender', 'Male')->count(),
      'female_children' => $children->where('gender', 'Female')->count(),
      'infants' => $this->getInfants()->count(),
      'toddlers' => $this->getToddlers()->count(),
      'preschool' => $this->getPreschoolChildren()->count(),
      'school_age' => $this->getSchoolAgeChildren()->count(),
      'eligible_for_graduation' => $this->getChildrenEligibleForGraduation()->count(),
      'youngest_child_age_months' => $this->youngest_child?->age_in_months,
      'oldest_child_age_months' => $this->oldest_child?->age_in_months,
    ];
  }

  // ============================================
  // PLACEHOLDER METHODS - TO BE IMPLEMENTED AFTER REGISTER FORMS PROVIDED
  // ============================================

  /**
   * Get children due for immunization
   * TODO: Implement after immunization_records table structure is confirmed
   *
   * @return \Illuminate\Support\Collection
   */
  public function getChildrenDueForImmunization()
  {
    // Placeholder - will be implemented after register forms provided
    // This will check immunization_records and return children with overdue vaccines
    return collect();
  }

  /**
   * Get children with nutritional issues (SAM/MAM)
   * TODO: Implement after nutrition_records table structure is confirmed
   *
   * @return \Illuminate\Support\Collection
   */
  public function getChildrenWithNutritionalIssues()
  {
    // Placeholder - will be implemented after register forms provided
    // This will check nutrition_records for SAM/MAM status
    return collect();
  }

  /**
   * Get immunization summary for all children
   * TODO: Implement after immunization_records table structure is confirmed
   *
   * @return array
   */
  public function getChildrenImmunizationSummary(): array
  {
    // Placeholder - will be implemented after register forms provided
    // This will aggregate immunization data across all children
    return [
      'total_children' => $this->total_linked_children,
      'fully_immunized' => 0, // TODO: Calculate from immunization_records
      'partially_immunized' => 0, // TODO: Calculate from immunization_records
      'not_immunized' => 0, // TODO: Calculate from immunization_records
      'overdue_vaccines' => 0, // TODO: Calculate from immunization_records
    ];
  }

  /**
   * Get nutrition summary for all children
   * TODO: Implement after nutrition_records table structure is confirmed
   *
   * @return array
   */
  public function getChildrenNutritionSummary(): array
  {
    // Placeholder - will be implemented after register forms provided
    // This will aggregate nutrition data across all children
    return [
      'total_children' => $this->total_linked_children,
      'normal_nutrition' => 0, // TODO: Calculate from nutrition_records
      'mam_cases' => 0, // TODO: Calculate from nutrition_records
      'sam_cases' => 0, // TODO: Calculate from nutrition_records
      'last_screening_date' => null, // TODO: Get from nutrition_records
    ];
  }
}
