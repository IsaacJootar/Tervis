<?php

namespace App\Models\Registrations;

use App\Models\Facility;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class AntenatalRegistration extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'pregnancy_number',
    'is_active',
    'pregnancy_status',
    'previous_registration_id',
    'registration_date',
    'date_of_booking',
    'indication_for_booking',
    'xray_no',
    'unit_no',
    'ethnic_group',
    'occupation',
    'speaks_english',
    'literate',
    'special_points',
    'consultant',
    'husband_name',
    'husband_occupation',
    'husband_employer',
    'lmp',
    'edd',
    'gestational_age_weeks',
    'gestational_age_days',
    'booking_trimester',
    'gravida',
    'parity',
    'total_births',
    'living_children',
    'abortions',
    'preg_0_dob',
    'preg_0_dur',
    'preg_0_outcome',
    'preg_0_weight',
    'preg_0_nndd',
    'preg_1_dob',
    'preg_1_dur',
    'preg_1_outcome',
    'preg_1_weight',
    'preg_1_nndd',
    'preg_2_dob',
    'preg_2_dur',
    'preg_2_outcome',
    'preg_2_weight',
    'preg_2_nndd',
    'preg_3_dob',
    'preg_3_dur',
    'preg_3_outcome',
    'preg_3_weight',
    'preg_3_nndd',
    'preg_4_dob',
    'preg_4_dur',
    'preg_4_outcome',
    'preg_4_weight',
    'preg_4_nndd',
    'heart_disease',
    'chest_disease',
    'kidney_disease',
    'blood_transfusion',
    'other_medical_history',
    'family_multiple_pregnancy',
    'family_tuberculosis',
    'family_hypertension',
    'family_heart_disease',
    'other_family_history',
    'bleeding',
    'discharge',
    'urinary_symptoms',
    'swelling_ankles',
    'other_symptoms',
    'height',
    'weight',
    'blood_pressure',
    'hemoglobin',
    'genotype',
    'blood_group_rhesus',
    'kahn_test',
    'oedema',
    'anaemia',
    'breast_nipple',
    'chest_xray',
    'urine_analysis',
    'respiratory_system',
    'comments',
    'examiner',
    'special_instructions',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'speaks_english' => 'boolean',
    'literate' => 'boolean',
    'heart_disease' => 'boolean',
    'chest_disease' => 'boolean',
    'kidney_disease' => 'boolean',
    'blood_transfusion' => 'boolean',
    'family_multiple_pregnancy' => 'boolean',
    'family_tuberculosis' => 'boolean',
    'family_hypertension' => 'boolean',
    'family_heart_disease' => 'boolean',
    'bleeding' => 'boolean',
    'discharge' => 'boolean',
    'urinary_symptoms' => 'boolean',
    'swelling_ankles' => 'boolean',
    'oedema' => 'boolean',
    'anaemia' => 'boolean',
    'is_active' => 'boolean',
    'registration_date' => 'date',
    'date_of_booking' => 'date',
    'lmp' => 'date',
    'edd' => 'date',
    'preg_0_dob' => 'date',
    'preg_1_dob' => 'date',
    'preg_2_dob' => 'date',
    'preg_3_dob' => 'date',
    'preg_4_dob' => 'date',
    'height' => 'float',
    'weight' => 'float',
    'hemoglobin' => 'float',
    'gravida' => 'integer',
    'parity' => 'integer',
    'total_births' => 'integer',
    'living_children' => 'integer',
    'abortions' => 'integer',
    'pregnancy_number' => 'integer',
    'gestational_age_weeks' => 'integer',
    'gestational_age_days' => 'integer',
    'patient_id' => 'integer',
    'facility_id' => 'integer',
    'previous_registration_id' => 'integer',
  ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

  /**
   * Get the patient this registration belongs to
   */
  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  /**
   * Get the facility where this registration occurred
   */
  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  /**
   * Get the previous pregnancy registration
   */
  public function previousRegistration(): BelongsTo
  {
    return $this->belongsTo(AntenatalRegistration::class, 'previous_registration_id');
  }

  /**
   * Get the next pregnancy registration
   */
  public function nextRegistration(): HasOne
  {
    return $this->hasOne(AntenatalRegistration::class, 'previous_registration_id');
  }

    // ============================================
    // ACCESSORS
    // ============================================

  /**
   * Get formatted LMP date
   */
  public function getFormattedLmpAttribute(): string
  {
    return $this->lmp ? $this->lmp->format('d M Y') : '';
  }

  /**
   * Get formatted EDD date
   */
  public function getFormattedEddAttribute(): string
  {
    return $this->edd ? $this->edd->format('d M Y') : '';
  }

  /**
   * Get formatted registration date
   */
  public function getFormattedRegistrationDateAttribute(): string
  {
    return $this->registration_date ? $this->registration_date->format('d M Y') : '';
  }

  /**
   * Calculate current gestational age in real-time
   */
  public function getCurrentGestationalAgeAttribute(): array
  {
    if (!$this->lmp) {
      return ['weeks' => 0, 'days' => 0, 'total_days' => 0];
    }

    $today = Carbon::today();
    $daysSinceLmp = $this->lmp->diffInDays($today);

    return [
      'weeks' => floor($daysSinceLmp / 7),
      'days' => $daysSinceLmp % 7,
      'total_days' => $daysSinceLmp
    ];
  }

  /**
   * Get gestational age as formatted string
   */
  public function getGestationalAgeStringAttribute(): string
  {
    $ga = $this->current_gestational_age;
    return "{$ga['weeks']} weeks, {$ga['days']} days";
  }

  /**
   * Get Gravida-Parity notation (e.g., G3P2)
   */
  public function getGravidaParityNotationAttribute(): string
  {
    $g = $this->gravida ?? 0;
    $p = $this->parity ?? 0;
    return "G{$g}P{$p}";
  }

  /**
   * Get patient's full name through relationship
   */
  public function getPatientFullNameAttribute(): string
  {
    return $this->patient ? $this->patient->full_name : '';
  }

  /**
   * Check if pregnancy is high risk
   */
  public function getIsHighRiskAttribute(): bool
  {
    return $this->gravida >= 5
      || $this->patient->age < 18
      || $this->patient->age > 35
      || $this->hasAnyCsareanHistory()
      || $this->heart_disease
      || $this->kidney_disease;
  }

  /**
   * Get risk factors as array
   */
  public function getRiskFactorsAttribute(): array
  {
    $risks = [];

    if ($this->gravida >= 5) $risks[] = 'Grand Multipara (≥5 pregnancies)';
    if ($this->patient->age < 18) $risks[] = 'Teenage pregnancy';
    if ($this->patient->age > 35) $risks[] = 'Advanced maternal age (>35 years)';
    if ($this->hasAnyCsareanHistory()) $risks[] = 'Previous Cesarean section';
    if ($this->heart_disease) $risks[] = 'Heart disease';
    if ($this->chest_disease) $risks[] = 'Chest disease';
    if ($this->kidney_disease) $risks[] = 'Kidney disease';
    if ($this->current_gestational_age['weeks'] > 20 && $this->pregnancy_number == 1) {
      $risks[] = 'Late booking (>20 weeks)';
    }

    return $risks;
  }

    // ============================================
    // SCOPES
    // ============================================

  /**
   * Scope to filter active pregnancies
   */
  public function scopeActive($query)
  {
    return $query->where('is_active', true);
  }

  /**
   * Scope to filter by facility
   */
  public function scopeByFacility($query, int $facilityId)
  {
    return $query->where('facility_id', $facilityId);
  }

  /**
   * Scope to filter by registration date range
   */
  public function scopeRegisteredBetween($query, $startDate, $endDate)
  {
    return $query->whereBetween('registration_date', [$startDate, $endDate]);
  }

  /**
   * Scope to get recent registrations
   */
  public function scopeRecent($query, int $days = 7)
  {
    return $query->where('registration_date', '>=', now()->subDays($days));
  }

  /**
   * Scope to order by most recent first
   */
  public function scopeLatestFirst($query)
  {
    return $query->orderBy('registration_date', 'desc');
  }

  /**
   * Scope to filter high-risk pregnancies
   */
  public function scopeHighRisk($query)
  {
    return $query->where(function ($q) {
      $q->where('gravida', '>=', 5)
        ->orWhere('heart_disease', true)
        ->orWhere('kidney_disease', true);
    });
  }

  /**
   * Scope to filter by pregnancy status
   */
  public function scopeByStatus($query, string $status)
  {
    return $query->where('pregnancy_status', $status);
  }

    // ============================================
    // HELPER METHODS
    // ============================================

  /**
   * Calculate EDD from LMP using Naegele's Rule
   */
  public function calculateEDD(): ?Carbon
  {
    if (!$this->lmp) return null;

    // Naegele's Rule: LMP + 280 days (40 weeks)
    return $this->lmp->copy()->addDays(280);
  }

  /**
   * Update gestational age based on current date
   */
  public function updateGestationalAge(): void
  {
    $ga = $this->current_gestational_age;
    $this->gestational_age_weeks = $ga['weeks'];
    $this->gestational_age_days = $ga['days'];
    $this->save();
  }

  /**
   * Determine trimester based on gestational age
   */
  public function determineTrimester(): string
  {
    $weeks = $this->current_gestational_age['weeks'];

    if ($weeks <= 13) return 'First';
    if ($weeks <= 26) return 'Second';
    return 'Third';
  }

  /**
   * Check if patient has any previous Cesarean history
   */
  public function hasAnyCsareanHistory(): bool
  {
    $outcomes = [
      $this->preg_0_outcome,
      $this->preg_1_outcome,
      $this->preg_2_outcome,
      $this->preg_3_outcome,
      $this->preg_4_outcome,
    ];

    return in_array('Cesarean', $outcomes);
  }

  /**
   * Get all previous pregnancies for this patient
   */
  public function getAllPreviousRegistrations()
  {
    return AntenatalRegistration::where('patient_id', $this->patient_id)
      ->where('id', '!=', $this->id)
      ->orderBy('registration_date', 'desc')
      ->get();
  }

  /**
   * Suggest gravida and parity based on previous registrations
   */
  public static function suggestGravidaParity(int $patientId): array
  {
    $lastRegistration = AntenatalRegistration::where('patient_id', $patientId)
      ->orderBy('registration_date', 'desc')
      ->first();

    if (!$lastRegistration) {
      return ['gravida' => 1, 'parity' => 0];
    }

    $suggestedGravida = ($lastRegistration->gravida ?? 0) + 1;

    // Increment parity only if last pregnancy was delivered
    $suggestedParity = $lastRegistration->parity ?? 0;
    if (in_array($lastRegistration->pregnancy_status, ['delivered'])) {
      $suggestedParity++;
    }

    return [
      'gravida' => $suggestedGravida,
      'parity' => $suggestedParity
    ];
  }

  /**
   * Calculate next pregnancy number for a patient
   */
  public static function getNextPregnancyNumber(int $patientId): int
  {
    $count = AntenatalRegistration::where('patient_id', $patientId)->count();
    return $count + 1;
  }
}
