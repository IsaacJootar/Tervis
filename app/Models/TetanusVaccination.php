<?php

namespace App\Models;

use App\Models\Registrations\AntenatalRegistration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class TetanusVaccination extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    // Relationships
    'patient_id',
    'antenatal_registration_id',
    'facility_id',

    // Visit Information
    'visit_date',

    // Tetanus Vaccination Information
    'current_tt_dose',
    'dose_date',
    'dose_number',

    // Protection and Scheduling
    'protection_status',
    'dose_interval',
    'next_appointment_date',

    // Vaccine Information
    'vaccination_site',
    'batch_number',
    'expiry_date',

    // Safety Monitoring
    'adverse_event',
    'adverse_event_details',
    'notes',

    // Patient Snapshot (Copied at record creation)
    'patient_din',
    'patient_first_name',
    'patient_middle_name',
    'patient_last_name',
    'patient_phone',
    'patient_age',
    'patient_gender',

    // Officer Information
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'visit_date' => 'date',
    'dose_date' => 'date',
    'next_appointment_date' => 'date',
    'expiry_date' => 'date',
    'dose_interval' => 'integer',
    'dose_number' => 'integer',
    'patient_id' => 'integer',
    'antenatal_registration_id' => 'integer',
    'facility_id' => 'integer',
    'patient_age' => 'integer',
  ];

  // ============================================
  // RELATIONSHIPS
  // ============================================

  /**
   * Get the patient this vaccination belongs to
   */
  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  /**
   * Get the antenatal registration (pregnancy) this vaccination belongs to
   */
  public function antenatalRegistration(): BelongsTo
  {
    return $this->belongsTo(AntenatalRegistration::class);
  }

  /**
   * Get the facility where this vaccination was administered
   */
  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }

  // ============================================
  // ACCESSORS
  // ============================================

  /**
   * Get patient's full name
   */
  public function getPatientFullNameAttribute(): string
  {
    return trim("{$this->patient_first_name} {$this->patient_middle_name} {$this->patient_last_name}");
  }

  /**
   * Get formatted visit date
   */
  public function getFormattedVisitDateAttribute(): string
  {
    return $this->visit_date ? $this->visit_date->format('d M Y') : '';
  }

  /**
   * Get formatted dose date
   */
  public function getFormattedDoseDateAttribute(): string
  {
    return $this->dose_date ? $this->dose_date->format('d M Y') : '';
  }

  /**
   * Get formatted next appointment date
   */
  public function getFormattedNextAppointmentDateAttribute(): string
  {
    return $this->next_appointment_date ? $this->next_appointment_date->format('d M Y') : '';
  }

  /**
   * Get formatted expiry date
   */
  public function getFormattedExpiryDateAttribute(): string
  {
    return $this->expiry_date ? $this->expiry_date->format('d M Y') : '';
  }

  /**
   * Check if vaccine is expired
   */
  public function getIsExpiredAttribute(): bool
  {
    return $this->expiry_date && $this->expiry_date->isPast();
  }

  /**
   * Check if next appointment is overdue
   */
  public function getIsOverdueAttribute(): bool
  {
    return $this->next_appointment_date && $this->next_appointment_date->isPast();
  }

  /**
   * Get days until next appointment
   */
  public function getDaysUntilNextAppointmentAttribute(): ?int
  {
    if (!$this->next_appointment_date) return null;
    return now()->diffInDays($this->next_appointment_date, false);
  }

  /**
   * Get protection status badge color
   */
  public function getProtectionStatusColorAttribute(): string
  {
    return match ($this->protection_status) {
      'Fully Protected' => 'success',
      'Protected' => 'info',
      'Partially Protected' => 'warning',
      'Not Protected' => 'danger',
      default => 'secondary',
    };
  }

  /**
   * Get dose badge color
   */
  public function getDoseBadgeColorAttribute(): string
  {
    return match ($this->current_tt_dose) {
      'TT1' => 'primary',
      'TT2' => 'info',
      'TT3' => 'success',
      'TT4' => 'warning',
      'TT5' => 'danger',
      default => 'secondary',
    };
  }

  // ============================================
  // SCOPES
  // ============================================

  /**
   * Scope to filter by facility
   */
  public function scopeByFacility($query, int $facilityId)
  {
    return $query->where('facility_id', $facilityId);
  }

  /**
   * Scope to filter by patient
   */
  public function scopeByPatient($query, int $patientId)
  {
    return $query->where('patient_id', $patientId);
  }

  /**
   * Scope to filter by antenatal registration (pregnancy)
   */
  public function scopeByPregnancy($query, int $antenatalRegistrationId)
  {
    return $query->where('antenatal_registration_id', $antenatalRegistrationId);
  }

  /**
   * Scope to filter by dose type
   */
  public function scopeByDose($query, string $dose)
  {
    return $query->where('current_tt_dose', $dose);
  }

  /**
   * Scope to filter by protection status
   */
  public function scopeByProtectionStatus($query, string $status)
  {
    return $query->where('protection_status', $status);
  }

  /**
   * Scope to filter by date range
   */
  public function scopeBetweenDates($query, $startDate, $endDate)
  {
    return $query->whereBetween('visit_date', [$startDate, $endDate]);
  }

  /**
   * Scope to filter by specific date
   */
  public function scopeByDate($query, $date)
  {
    return $query->whereDate('visit_date', $date);
  }

  /**
   * Scope to get today's vaccinations
   */
  public function scopeToday($query)
  {
    return $query->whereDate('visit_date', today());
  }

  /**
   * Scope to order by most recent first
   */
  public function scopeLatestFirst($query)
  {
    return $query->orderBy('visit_date', 'desc')
      ->orderBy('created_at', 'desc');
  }

  /**
   * Scope to get overdue appointments
   */
  public function scopeOverdue($query)
  {
    return $query->whereNotNull('next_appointment_date')
      ->where('next_appointment_date', '<', today());
  }

  /**
   * Scope to get upcoming appointments
   */
  public function scopeUpcoming($query, int $days = 7)
  {
    return $query->whereNotNull('next_appointment_date')
      ->whereBetween('next_appointment_date', [today(), now()->addDays($days)]);
  }

  /**
   * Scope to get fully protected patients
   */
  public function scopeFullyProtected($query)
  {
    return $query->where('protection_status', 'Fully Protected');
  }

  /**
   * Scope to get recent vaccinations
   */
  public function scopeRecent($query, int $days = 30)
  {
    return $query->where('visit_date', '>=', now()->subDays($days));
  }

  // ============================================
  // HELPER METHODS
  // ============================================

  /**
   * Get vaccination history for a patient in a specific pregnancy
   */
  public static function getPregnancyHistory(int $patientId, int $antenatalRegistrationId)
  {
    return self::where('patient_id', $patientId)
      ->where('antenatal_registration_id', $antenatalRegistrationId)
      ->orderBy('dose_number', 'asc')
      ->get();
  }

  /**
   * Get the last dose given for a pregnancy
   */
  public static function getLastDoseForPregnancy(int $antenatalRegistrationId)
  {
    return self::where('antenatal_registration_id', $antenatalRegistrationId)
      ->orderBy('dose_number', 'desc')
      ->first();
  }

  /**
   * Get the next dose number for a pregnancy
   */
  public static function getNextDoseNumber(int $antenatalRegistrationId): int
  {
    $lastDose = self::getLastDoseForPregnancy($antenatalRegistrationId);
    return $lastDose ? min($lastDose->dose_number + 1, 5) : 1;
  }

  /**
   * Get the next TT dose label for a pregnancy
   */
  public static function getNextDoseLabel(int $antenatalRegistrationId): string
  {
    $nextNumber = self::getNextDoseNumber($antenatalRegistrationId);
    return "TT{$nextNumber}";
  }

  /**
   * Check if patient has completed all 5 TT doses for a pregnancy
   */
  public static function hasCompletedAllDoses(int $antenatalRegistrationId): bool
  {
    return self::where('antenatal_registration_id', $antenatalRegistrationId)
      ->where('dose_number', 5)
      ->exists();
  }

  /**
   * Calculate protection status based on dose number
   */
  public static function calculateProtectionStatus(int $doseNumber): string
  {
    return match ($doseNumber) {
      1 => 'Not Protected',
      2 => 'Partially Protected',
      3 => 'Protected',
      4, 5 => 'Fully Protected',
      default => 'Not Protected',
    };
  }

  /**
   * Calculate recommended interval to next dose (in days)
   */
  public static function getRecommendedInterval(int $currentDoseNumber): ?int
  {
    return match ($currentDoseNumber) {
      1 => 28,      // TT1 to TT2: 4 weeks (28 days)
      2 => 182,     // TT2 to TT3: 6 months (182 days)
      3 => 365,     // TT3 to TT4: 1 year (365 days)
      4 => 365,     // TT4 to TT5: 1 year (365 days)
      5 => null,    // No more doses needed
      default => null,
    };
  }

  /**
   * Calculate next appointment date based on current dose
   */
  public static function calculateNextAppointmentDate(int $currentDoseNumber, $currentDoseDate): ?Carbon
  {
    $interval = self::getRecommendedInterval($currentDoseNumber);
    if (!$interval) return null;

    $doseDate = $currentDoseDate instanceof Carbon ? $currentDoseDate : Carbon::parse($currentDoseDate);
    return $doseDate->addDays($interval);
  }

  /**
   * Get vaccination statistics for a facility within a date range
   */
  public static function getStatistics(int $facilityId, $startDate, $endDate): array
  {
    $vaccinations = self::where('facility_id', $facilityId)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get();

    return [
      'total_vaccinations' => $vaccinations->count(),
      'unique_patients' => $vaccinations->unique('patient_id')->count(),
      'tt1_count' => $vaccinations->where('current_tt_dose', 'TT1')->count(),
      'tt2_count' => $vaccinations->where('current_tt_dose', 'TT2')->count(),
      'tt3_count' => $vaccinations->where('current_tt_dose', 'TT3')->count(),
      'tt4_count' => $vaccinations->where('current_tt_dose', 'TT4')->count(),
      'tt5_count' => $vaccinations->where('current_tt_dose', 'TT5')->count(),
      'fully_protected' => $vaccinations->where('protection_status', 'Fully Protected')->count(),
      'adverse_events' => $vaccinations->where('adverse_event', '!=', 'None')->count(),
    ];
  }

  /**
   * Get NHMIS monthly statistics for TT vaccinations
   * Fields 36-40 in NHMIS report
   */
  public static function getNhmisStatistics(int $facilityId, int $month, int $year): array
  {
    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = Carbon::create($year, $month, 1)->endOfMonth();

    $vaccinations = self::where('facility_id', $facilityId)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get();

    return [
      // NHMIS Field 36: TT1 doses given
      'nhmis_36_tt1' => $vaccinations->where('current_tt_dose', 'TT1')->count(),
      // NHMIS Field 37: TT2 doses given
      'nhmis_37_tt2' => $vaccinations->where('current_tt_dose', 'TT2')->count(),
      // NHMIS Field 38: TT3 doses given
      'nhmis_38_tt3' => $vaccinations->where('current_tt_dose', 'TT3')->count(),
      // NHMIS Field 39: TT4 doses given
      'nhmis_39_tt4' => $vaccinations->where('current_tt_dose', 'TT4')->count(),
      // NHMIS Field 40: TT5 doses given
      'nhmis_40_tt5' => $vaccinations->where('current_tt_dose', 'TT5')->count(),
    ];
  }
}
