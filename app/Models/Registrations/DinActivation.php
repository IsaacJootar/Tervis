<?php

namespace App\Models\Registrations;

use App\Models\Patient;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DinActivation extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'patient_id',
    'facility_id',
    'visit_date',
    'check_in_time',
    'patient_din',
    'patient_first_name',
    'patient_middle_name',
    'patient_last_name',
    'patient_phone',
    'patient_age',
    'patient_gender',
    'officer_name',
    'officer_role',
    'officer_designation',
  ];

  protected $casts = [
    'visit_date' => 'date',
    'check_in_time' => 'datetime',
    'patient_age' => 'integer',
    'patient_id' => 'integer',
    'facility_id' => 'integer',
  ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

  /**
   * Get the patient this activation belongs to
   */
  public function patient(): BelongsTo
  {
    return $this->belongsTo(Patient::class);
  }

  /**
   * Get the facility where this activation occurred
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
   * Get formatted check-in time
   */
  public function getFormattedCheckInTimeAttribute(): string
  {
    return $this->check_in_time ? $this->check_in_time->format('h:i A') : '';
  }

  /**
   * Get time since check-in
   */
  public function getTimeSinceCheckInAttribute(): string
  {
    if (!$this->check_in_time) return '';

    return $this->check_in_time->diffForHumans();
  }

  /**
   * Check if activation is today
   */
  public function getIsTodayAttribute(): bool
  {
    return $this->visit_date && $this->visit_date->isToday();
  }

    // ============================================
    // SCOPES
    // ============================================

  /**
   * Scope to filter activations for today
   */
  public function scopeToday($query)
  {
    return $query->whereDate('visit_date', today());
  }

  /**
   * Scope to filter by facility
   */
  public function scopeByFacility($query, int $facilityId)
  {
    return $query->where('facility_id', $facilityId);
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
   * Scope to order by most recent first
   */
  public function scopeLatestFirst($query)
  {
    return $query->orderBy('visit_date', 'desc')
      ->orderBy('check_in_time', 'desc');
  }

  /**
   * Scope to filter by patient gender
   */
  public function scopeByGender($query, string $gender)
  {
    return $query->where('patient_gender', $gender);
  }

  /**
   * Scope to get recent activations
   */
  public function scopeRecent($query, int $days = 7)
  {
    return $query->where('visit_date', '>=', now()->subDays($days));
  }

    // ============================================
    // HELPER METHODS
    // ============================================

  /**
   * Check if patient has already been activated today
   */
  public static function hasActivationToday(int $patientId, int $facilityId): bool
  {
    return self::where('patient_id', $patientId)
      ->where('facility_id', $facilityId)
      ->whereDate('visit_date', today())
      ->exists();
  }

  /**
   * Get today's activation for a patient
   */
  public static function getTodaysActivation(int $patientId, int $facilityId)
  {
    return self::where('patient_id', $patientId)
      ->where('facility_id', $facilityId)
      ->whereDate('visit_date', today())
      ->first();
  }

  /**
   * Count today's activations for facility
   */
  public static function countTodaysByFacility(int $facilityId): int
  {
    return self::where('facility_id', $facilityId)
      ->whereDate('visit_date', today())
      ->count();
  }

  /**
   * Get activation statistics for a date range
   */
  public static function getStatistics(int $facilityId, $startDate, $endDate): array
  {
    $activations = self::where('facility_id', $facilityId)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get();

    return [
      'total_activations' => $activations->count(),
      'unique_patients' => $activations->unique('patient_id')->count(),
      'male_patients' => $activations->where('patient_gender', 'Male')->count(),
      'female_patients' => $activations->where('patient_gender', 'Female')->count(),
      'average_age' => $activations->avg('patient_age'),
      'daily_average' => $activations->groupBy(function ($item) {
        return $item->visit_date->format('Y-m-d');
      })->avg(function ($group) {
        return $group->count();
      }),
    ];
  }
}
