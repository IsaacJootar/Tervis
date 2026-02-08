<?php

namespace App\Services;

use App\Models\Antenatal;
use App\Models\Delivery;
use App\Models\PostnatalRecord;
use App\Models\TetanusVaccination;
use App\Models\DailyAttendance;
use App\Models\ClinicalNote;
use App\Models\Facility;
use App\Models\User;
use Carbon\Carbon;
use App\Models\RiskPrediction;


class DashboardMetricsService
{
  protected $scopeService;

  public function __construct(DataScopeService $scopeService)
  {
    $this->scopeService = $scopeService;
  }

  public function getRealTimeMetrics($facilityId = null)
  {
    // If facilityId is provided, use it for specific facility view
    // Otherwise, use user's scope (facility/lga/state)
    if ($facilityId) {
      $facilityIds = [$facilityId];
      $scope = ['scope_type' => 'facility', 'facility_ids' => $facilityIds];
    } else {
      $scope = $this->scopeService->getUserScope();
      $facilityIds = $scope['facility_ids'];
    }

    if (empty($facilityIds)) {
      return $this->getEmptyMetrics($scope);
    }

    $cacheKey = $this->getCacheKey($scope, $facilityId);

    return cache()->remember($cacheKey, 3600, function () use ($facilityIds, $scope) {
      return [
        'total_patients' => $this->getTotalUniquePatients($facilityIds),
        'today_visits' => $this->getTodayVisits($facilityIds),
        'high_risk_pregnancies' => $this->getHighRiskPregnancies($facilityIds),
        'upcoming_deliveries' => $this->getUpcomingDeliveries($facilityIds),
        'monthly_trends' => $this->getMonthlyTrends($facilityIds),
        'risk_alerts' => $this->getRiskAlerts($facilityIds),
        'facility_performance' => $this->getFacilityPerformance($facilityIds),
        'facility_info' => $this->getFacilityInfo($scope),
        'service_coverage' => $this->getServiceCoverageMetrics($facilityIds),
        'patient_journey' => $this->getPatientJourneyMetrics($facilityIds),
        'clinical_outcomes' => $this->getClinicalOutcomeMetrics($facilityIds),
        'vaccination_coverage' => $this->getVaccinationCoverageMetrics($facilityIds),
        'ai_predictions' => $this->getAIPredictionsMetrics($facilityIds),
        'scope_info' => $scope,
      ];
    });
  }

  private function getCacheKey($scope, $specificFacilityId = null)
  {
    if ($specificFacilityId) {
      return "dashboard_metrics_facility_{$specificFacilityId}_" . now()->format('Y-m-d-H');
    }

    $type = $scope['scope_type'];
    $identifier = implode('_', $scope['facility_ids']);
    return "dashboard_metrics_{$type}_{$identifier}_" . now()->format('Y-m-d-H');
  }

  private function getEmptyMetrics($scope)
  {
    return [
      'total_patients' => 0,
      'today_visits' => ['antenatal' => 0, 'postnatal' => 0, 'delivery' => 0, 'tetanus' => 0, 'attendance' => 0, 'clinical_notes' => 0],
      'high_risk_pregnancies' => collect(),
      'upcoming_deliveries' => collect(),
      'monthly_trends' => collect(),
      'risk_alerts' => collect(),
      'facility_performance' => ['current_month' => 0, 'previous_month' => 0, 'growth_rate' => 0, 'trend' => 'stable'],
      'facility_info' => $this->getFacilityInfo($scope),
      'service_coverage' => ['antenatal_coverage' => 0, 'delivery_coverage' => 0, 'postnatal_coverage' => 0, 'tetanus_coverage' => 0, 'clinical_notes_count' => 0, 'total_unique_patients' => 0, 'coverage_percentage' => 0],
      'patient_journey' => ['complete_journey' => 0, 'antenatal_only' => 0, 'antenatal_delivery' => 0, 'antenatal_postnatal' => 0, 'missing_delivery' => 0, 'missing_postnatal' => 0, 'total_patients' => 0],
      'clinical_outcomes' => ['total_deliveries' => 0, 'cesarean_rate' => 0, 'normal_delivery_rate' => 0, 'complication_rate' => 0, 'maternal_mortality_rate' => 0, 'stillbirth_rate' => 0],
      'vaccination_coverage' => ['tt1_coverage' => 0, 'tt2_coverage' => 0, 'tt3_coverage' => 0, 'tt4_coverage' => 0, 'tt5_coverage' => 0, 'full_protection_rate' => 0, 'total_vaccinated' => 0],
      'ai_predictions' => ['total_predictions' => 0, 'latest_predictions' => collect(), 'average_confidence' => 0, 'overdue_assessments' => 0, 'risk_distribution' => []],
      'scope_info' => $scope,
      'note' => 'No facilities found in your scope or no data available.'
    ];
  }

  private function getAIPredictionsMetrics($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    $predictions = RiskPrediction::whereIn('facility_id', $facilityIds)
      ->where('assessment_date', '>=', now()->subDays(30))
      ->with('user')
      ->orderBy('assessment_date', 'desc')
      ->get();

    $overdue = $predictions->filter(function ($prediction) {
      return $prediction->next_assessment_due &&
        Carbon::parse($prediction->next_assessment_due)->isPast();
    });

    return [
      'total_predictions' => $predictions->count(),
      'latest_predictions' => $predictions->take(10)->map(function ($prediction) {
        return (object)[
          'user' => $prediction->user,
          'risk_level' => $prediction->risk_level,
          'total_risk_score' => $prediction->total_risk_score,
          'assessment_date' => Carbon::parse($prediction->assessment_date),
          'facility_name' => $prediction->facility->name ?? 'Unknown',
          'is_overdue' => $prediction->next_assessment_due &&
            Carbon::parse($prediction->next_assessment_due)->isPast()
        ];
      }),
      'average_confidence' => $predictions->avg(function ($pred) {
        $conf = $pred->prediction_confidence;
        return is_array($conf) && isset($conf['overall_confidence']) ? $conf['overall_confidence'] : 0;
      }) ?? 0,
      'overdue_assessments' => $overdue->count(),
      'risk_distribution' => $predictions->groupBy('risk_level')->map->count()
    ];
  }

  private function getFacilityInfo($scope)
  {
    if ($scope['scope_type'] === 'facility' && count($scope['facility_ids']) === 1) {
      $facility = Facility::find($scope['facility_ids'][0]);
      return [
        'scope' => 'Single Facility',
        'name' => $facility->name ?? 'N/A',
        'state' => $facility->state ?? 'N/A',
        'lga' => $facility->lga ?? 'N/A',
        'ward' => $facility->ward ?? 'N/A',
        'facility_count' => 1
      ];
    }

    $facilities = Facility::whereIn('id', $scope['facility_ids'])->get();
    $scopeLabel = $scope['scope_type'] === 'lga' ? 'LGA-wide' : 'State-wide';

    return [
      'scope' => $scopeLabel,
      'facility_count' => $facilities->count(),
      'facilities' => $facilities->pluck('name')->toArray(),
      'state' => $scope['user']->state->name ?? 'N/A',
      'lga' => $scope['scope_type'] === 'lga' ? ($scope['user']->lga->name ?? 'N/A') : 'Multiple LGAs'
    ];
  }

  private function getTotalUniquePatients($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    try {
      $antenatalPatients = Antenatal::whereIn('registration_facility_id', $facilityIds)
        ->pluck('user_id')->toArray();

      $deliveryPatients = Delivery::whereIn('facility_id', $facilityIds)
        ->pluck('user_id')->toArray();

      $postnatalPatients = PostnatalRecord::whereIn('facility_id', $facilityIds)
        ->pluck('user_id')->toArray();

      $tetanusPatients = TetanusVaccination::whereIn('facility_id', $facilityIds)
        ->pluck('user_id')->toArray();

      $attendancePatients = DailyAttendance::whereIn('facility_id', $facilityIds)
        ->pluck('user_id')->toArray();

      $allPatients = array_unique(array_merge(
        $antenatalPatients,
        $deliveryPatients,
        $postnatalPatients,
        $tetanusPatients,
        $attendancePatients
      ));

      return count($allPatients);
    } catch (\Exception $e) {
      \Log::error("Error calculating total patients: " . $e->getMessage());
      return 0;
    }
  }

  private function getTodayVisits($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    try {
      $today = Carbon::today();

      return [
        'antenatal' => Antenatal::whereIn('registration_facility_id', $facilityIds)
          ->whereDate('date_of_booking', $today)->count(),
        'postnatal' => PostnatalRecord::whereIn('facility_id', $facilityIds)
          ->whereDate('visit_date', $today)->count(),
        'delivery' => Delivery::whereIn('facility_id', $facilityIds)
          ->whereDate('dodel', $today)->count(),
        'tetanus' => TetanusVaccination::whereIn('facility_id', $facilityIds)
          ->whereDate('visit_date', $today)->count(),
        'attendance' => DailyAttendance::whereIn('facility_id', $facilityIds)
          ->whereDate('visit_date', $today)->count(),
        'clinical_notes' => ClinicalNote::whereIn('facility_id', $facilityIds)
          ->whereDate('date_of_visit', $today)->count()
      ];
    } catch (\Exception $e) {
      \Log::error("Error calculating today's visits: " . $e->getMessage());
      return [
        'antenatal' => 0,
        'postnatal' => 0,
        'delivery' => 0,
        'tetanus' => 0,
        'attendance' => 0,
        'clinical_notes' => 0
      ];
    }
  }

  private function getHighRiskPregnancies($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    return Antenatal::whereIn('registration_facility_id', $facilityIds)
      ->where(function ($query) {
        $query->where('age', '<', 18)
          ->orWhere('age', '>', 35)
          ->orWhere('heart_disease', 1)
          ->orWhere('kidney_disease', 1)
          ->orWhere('family_hypertension', 1)
          ->orWhere('bleeding', 1)
          ->orWhere('hemoglobin', '<', 11)
          ->orWhere(function ($q) {
            $q->whereRaw("SUBSTRING_INDEX(blood_pressure, '/', 1) >= 140")
              ->orWhereRaw("SUBSTRING_INDEX(blood_pressure, '/', -1) >= 90");
          })
          ->orWhere('genotype', 'LIKE', '%S%');
      })
      ->with(['user', 'registrationFacility'])
      ->get();
  }

  private function getUpcomingDeliveries($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);
    $nextWeek = Carbon::now()->addWeek();

    return Antenatal::whereIn('registration_facility_id', $facilityIds)
      ->whereDate('edd', '<=', $nextWeek)
      ->whereDate('edd', '>=', Carbon::today())
      ->with(['user', 'registrationFacility'])
      ->get()
      ->map(function ($antenatal) {
        return [
          'patient_name' => $antenatal->user->first_name . ' ' . $antenatal->user->last_name,
          'din' => $antenatal->user->DIN,
          'facility_name' => $antenatal->registrationFacility->name ?? 'Unknown',
          'edd' => $antenatal->edd,
          'days_until_edd' => Carbon::parse($antenatal->edd)->diffInDays(Carbon::today()),
          'gestational_age' => $this->calculateGestationalAge($antenatal->lmp)
        ];
      });
  }

  private function getMonthlyTrends($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);
    $months = collect();

    for ($i = 5; $i >= 0; $i--) {
      $date = Carbon::now()->subMonths($i);
      $months->push([
        'month' => $date->format('M Y'),
        'antenatal_registrations' => Antenatal::whereIn('registration_facility_id', $facilityIds)
          ->whereYear('date_of_booking', $date->year)
          ->whereMonth('date_of_booking', $date->month)
          ->count(),
        'deliveries' => Delivery::whereIn('facility_id', $facilityIds)
          ->whereYear('dodel', $date->year)
          ->whereMonth('dodel', $date->month)
          ->count(),
        'postnatal_visits' => PostnatalRecord::whereIn('facility_id', $facilityIds)
          ->whereYear('visit_date', $date->year)
          ->whereMonth('visit_date', $date->month)
          ->count(),
        'tetanus_doses' => TetanusVaccination::whereIn('facility_id', $facilityIds)
          ->whereYear('visit_date', $date->year)
          ->whereMonth('visit_date', $date->month)
          ->count(),
        'daily_attendances' => DailyAttendance::whereIn('facility_id', $facilityIds)
          ->whereYear('visit_date', $date->year)
          ->whereMonth('visit_date', $date->month)
          ->count(),
        'clinical_notes' => ClinicalNote::whereIn('facility_id', $facilityIds)
          ->whereYear('date_of_visit', $date->year)
          ->whereMonth('date_of_visit', $date->month)
          ->count()
      ]);
    }
    return $months;
  }

  private function getRiskAlerts($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);
    $alerts = collect();

    // Overdue antenatal visits
    $overdueAntenatal = Antenatal::whereIn('registration_facility_id', $facilityIds)
      ->where('follow_up_next_visit', '<', Carbon::today())
      ->whereNotNull('follow_up_next_visit')
      ->with(['user', 'registrationFacility'])
      ->get();

    foreach ($overdueAntenatal as $antenatal) {
      $alerts->push([
        'type' => 'overdue_antenatal',
        'priority' => 'high',
        'message' => "Overdue antenatal visit for {$antenatal->user->first_name} {$antenatal->user->last_name}",
        'patient_din' => $antenatal->user->DIN,
        'facility_name' => $antenatal->registrationFacility->name ?? 'Unknown',
        'due_date' => $antenatal->follow_up_next_visit,
        'service' => 'Antenatal Care'
      ]);
    }

    // Overdue tetanus vaccinations
    $overdueTetanus = TetanusVaccination::whereIn('facility_id', $facilityIds)
      ->where('next_appointment_date', '<', Carbon::today())
      ->whereNotNull('next_appointment_date')
      ->with(['user', 'facility'])
      ->get();

    foreach ($overdueTetanus as $tetanus) {
      $alerts->push([
        'type' => 'overdue_tetanus',
        'priority' => 'medium',
        'message' => "Overdue tetanus vaccination for {$tetanus->user->first_name} {$tetanus->user->last_name}",
        'patient_din' => $tetanus->user->DIN,
        'facility_name' => $tetanus->facility->name ?? 'Unknown',
        'due_date' => $tetanus->next_appointment_date,
        'service' => 'Tetanus Vaccination'
      ]);
    }

    // Critical delivery dates (within 3 days)
    $criticalDeliveries = Antenatal::whereIn('registration_facility_id', $facilityIds)
      ->whereDate('edd', '<=', Carbon::now()->addDays(3))
      ->whereDate('edd', '>=', Carbon::today())
      ->with(['user', 'registrationFacility'])
      ->get();

    foreach ($criticalDeliveries as $antenatal) {
      $alerts->push([
        'type' => 'critical_delivery',
        'priority' => 'critical',
        'message' => "Expected delivery within 3 days for {$antenatal->user->first_name} {$antenatal->user->last_name}",
        'patient_din' => $antenatal->user->DIN,
        'facility_name' => $antenatal->registrationFacility->name ?? 'Unknown',
        'due_date' => $antenatal->edd,
        'service' => 'Delivery'
      ]);
    }

    return $alerts->sortByDesc('priority');
  }

  private function getFacilityPerformance($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);
    $thisMonth = Carbon::now();
    $lastMonth = Carbon::now()->subMonth();

    $thisMonthServices = $this->getMonthServiceCount($facilityIds, $thisMonth);
    $lastMonthServices = $this->getMonthServiceCount($facilityIds, $lastMonth);

    $growthRate = $lastMonthServices > 0 ?
      (($thisMonthServices - $lastMonthServices) / $lastMonthServices) * 100 : 0;

    return [
      'current_month' => $thisMonthServices,
      'previous_month' => $lastMonthServices,
      'growth_rate' => round($growthRate, 1),
      'trend' => $growthRate > 0 ? 'increasing' : ($growthRate < 0 ? 'decreasing' : 'stable')
    ];
  }

  private function getMonthServiceCount($facilityIds, $date)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    $antenatal = Antenatal::whereIn('registration_facility_id', $facilityIds)
      ->whereYear('date_of_booking', $date->year)
      ->whereMonth('date_of_booking', $date->month)
      ->count();

    $delivery = Delivery::whereIn('facility_id', $facilityIds)
      ->whereYear('dodel', $date->year)
      ->whereMonth('dodel', $date->month)
      ->count();

    $postnatal = PostnatalRecord::whereIn('facility_id', $facilityIds)
      ->whereYear('visit_date', $date->year)
      ->whereMonth('visit_date', $date->month)
      ->count();

    $tetanus = TetanusVaccination::whereIn('facility_id', $facilityIds)
      ->whereYear('visit_date', $date->year)
      ->whereMonth('visit_date', $date->month)
      ->count();

    return $antenatal + $delivery + $postnatal + $tetanus;
  }

  private function getServiceCoverageMetrics($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);
    $totalPatients = $this->getTotalUniquePatients($facilityIds);

    return [
      'antenatal_coverage' => Antenatal::whereIn('registration_facility_id', $facilityIds)->count(),
      'delivery_coverage' => Delivery::whereIn('facility_id', $facilityIds)->count(),
      'postnatal_coverage' => PostnatalRecord::whereIn('facility_id', $facilityIds)->count(),
      'tetanus_coverage' => TetanusVaccination::whereIn('facility_id', $facilityIds)->count(),
      'clinical_notes_count' => ClinicalNote::whereIn('facility_id', $facilityIds)->count(),
      'total_unique_patients' => $totalPatients,
      'coverage_percentage' => $totalPatients > 0 ? round(($this->getPatientsWithCompleteJourney($facilityIds) / $totalPatients) * 100, 1) : 0
    ];
  }

  private function getPatientJourneyMetrics($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    $patients = User::where('role', 'Patient')
      ->whereHas('antenatal', function ($q) use ($facilityIds) {
        $q->whereIn('registration_facility_id', $facilityIds);
      })->get();

    $journeyStats = [
      'complete_journey' => 0,
      'antenatal_only' => 0,
      'antenatal_delivery' => 0,
      'antenatal_postnatal' => 0,
      'missing_delivery' => 0,
      'missing_postnatal' => 0,
      'total_patients' => $patients->count()
    ];

    foreach ($patients as $patient) {
      $hasAntenatal = $patient->antenatal()->whereIn('registration_facility_id', $facilityIds)->exists();
      $hasDelivery = $patient->deliveries()->whereIn('facility_id', $facilityIds)->exists();
      $hasPostnatal = $patient->postnatalRecords()->whereIn('facility_id', $facilityIds)->exists();

      if ($hasAntenatal && $hasDelivery && $hasPostnatal) {
        $journeyStats['complete_journey']++;
      } elseif ($hasAntenatal && $hasDelivery && !$hasPostnatal) {
        $journeyStats['antenatal_delivery']++;
        $journeyStats['missing_postnatal']++;
      } elseif ($hasAntenatal && !$hasDelivery && $hasPostnatal) {
        $journeyStats['antenatal_postnatal']++;
        $journeyStats['missing_delivery']++;
      } elseif ($hasAntenatal && !$hasDelivery && !$hasPostnatal) {
        $journeyStats['antenatal_only']++;
        $journeyStats['missing_delivery']++;
        $journeyStats['missing_postnatal']++;
      }
    }

    return $journeyStats;
  }

  private function getClinicalOutcomeMetrics($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    $totalDeliveries = Delivery::whereIn('facility_id', $facilityIds)->count();
    $cesareanDeliveries = Delivery::whereIn('facility_id', $facilityIds)->where('mod', 'CS')->count();
    $normalDeliveries = Delivery::whereIn('facility_id', $facilityIds)->where('mod', '!=', 'CS')->count();

    $complicatedDeliveries = Delivery::whereIn('facility_id', $facilityIds)
      ->whereNotNull('complications')
      ->where('complications', '!=', '')
      ->count();

    $maternalDeaths = Delivery::whereIn('facility_id', $facilityIds)->where('dead', 'yes')->count();
    $stillBirths = Delivery::whereIn('facility_id', $facilityIds)->where('still_birth', '!=', '')->count();

    return [
      'total_deliveries' => $totalDeliveries,
      'cesarean_rate' => $totalDeliveries > 0 ? round(($cesareanDeliveries / $totalDeliveries) * 100, 1) : 0,
      'normal_delivery_rate' => $totalDeliveries > 0 ? round(($normalDeliveries / $totalDeliveries) * 100, 1) : 0,
      'complication_rate' => $totalDeliveries > 0 ? round(($complicatedDeliveries / $totalDeliveries) * 100, 1) : 0,
      'maternal_mortality_rate' => $totalDeliveries > 0 ? round(($maternalDeaths / $totalDeliveries) * 100, 2) : 0,
      'stillbirth_rate' => $totalDeliveries > 0 ? round(($stillBirths / $totalDeliveries) * 100, 1) : 0
    ];
  }

  private function getVaccinationCoverageMetrics($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    $totalPatients = User::where('role', 'Patient')
      ->whereHas('antenatal', function ($q) use ($facilityIds) {
        $q->whereIn('registration_facility_id', $facilityIds);
      })->count();

    $tt1Count = TetanusVaccination::whereIn('facility_id', $facilityIds)->where('current_tt_dose', 'TT1')->count();
    $tt2Count = TetanusVaccination::whereIn('facility_id', $facilityIds)->where('current_tt_dose', 'TT2')->count();
    $tt3Count = TetanusVaccination::whereIn('facility_id', $facilityIds)->where('current_tt_dose', 'TT3')->count();
    $tt4Count = TetanusVaccination::whereIn('facility_id', $facilityIds)->where('current_tt_dose', 'TT4')->count();
    $tt5Count = TetanusVaccination::whereIn('facility_id', $facilityIds)->where('current_tt_dose', 'TT5')->count();

    $fullyProtected = TetanusVaccination::whereIn('facility_id', $facilityIds)
      ->where('protection_status', 'Fully Protected')->count();

    return [
      'tt1_coverage' => $totalPatients > 0 ? round(($tt1Count / $totalPatients) * 100, 1) : 0,
      'tt2_coverage' => $totalPatients > 0 ? round(($tt2Count / $totalPatients) * 100, 1) : 0,
      'tt3_coverage' => $totalPatients > 0 ? round(($tt3Count / $totalPatients) * 100, 1) : 0,
      'tt4_coverage' => $totalPatients > 0 ? round(($tt4Count / $totalPatients) * 100, 1) : 0,
      'tt5_coverage' => $totalPatients > 0 ? round(($tt5Count / $totalPatients) * 100, 1) : 0,
      'full_protection_rate' => $totalPatients > 0 ? round(($fullyProtected / $totalPatients) * 100, 1) : 0,
      'total_vaccinated' => $tt1Count + $tt2Count + $tt3Count + $tt4Count + $tt5Count
    ];
  }

  private function getPatientsWithCompleteJourney($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);

    return User::where('role', 'Patient')
      ->whereHas('antenatal', function ($q) use ($facilityIds) {
        $q->whereIn('registration_facility_id', $facilityIds);
      })
      ->whereHas('deliveries', function ($q) use ($facilityIds) {
        $q->whereIn('facility_id', $facilityIds);
      })
      ->whereHas('postnatalRecords', function ($q) use ($facilityIds) {
        $q->whereIn('facility_id', $facilityIds);
      })
      ->count();
  }

  public function getHighRiskDetails($userId)
  {
    $antenatal = Antenatal::with('user')->find($userId);
    if (!$antenatal) return null;

    $delivery = Delivery::where('patient_id', $antenatal->user_id)->latest()->first();
    $postnatal = PostnatalRecord::where('patient_id', $antenatal->user_id)->latest()->first();
    $clinicalNotes = ClinicalNote::where('user_id', $antenatal->user_id)->latest()->get();

    return [
      'patient_name' => $antenatal->user->first_name . ' ' . $antenatal->user->last_name,
      'din' => $antenatal->user->DIN,
      'age' => $antenatal->age,
      'phone' => $antenatal->user->phone,
      'address' => $antenatal->address,
      'edd' => $antenatal->edd,
      'lmp' => $antenatal->lmp,
      'gestational_age' => $this->calculateGestationalAge($antenatal->lmp),
      'risk_factors' => $this->identifyComprehensiveRiskFactors($antenatal, $delivery, $postnatal),
      'medical_history' => [
        'heart_disease' => $antenatal->heart_disease,
        'chest_disease' => $antenatal->chest_disease,
        'kidney_disease' => $antenatal->kidney_disease,
        'blood_transfusion' => $antenatal->blood_transfusion,
        'other_medical_history' => $antenatal->other_medical_history
      ],
      'current_pregnancy' => [
        'bleeding' => $antenatal->bleeding,
        'discharge' => $antenatal->discharge,
        'swelling_ankles' => $antenatal->swelling_ankles,
        'other_symptoms' => $antenatal->other_symptoms
      ],
      'vitals' => [
        'height' => $antenatal->height,
        'weight' => $antenatal->weight,
        'blood_pressure' => $antenatal->blood_pressure,
        'hemoglobin' => $antenatal->hemoglobin,
        'genotype' => $antenatal->genotype,
        'blood_group_rhesus' => $antenatal->blood_group_rhesus
      ],
      'delivery_info' => $delivery ? [
        'delivery_date' => $delivery->dodel,
        'mode_of_delivery' => $delivery->mod,
        'complications' => $delivery->complications,
        'blood_loss' => $delivery->blood_loss,
        'baby_weight' => $delivery->weight
      ] : null,
      'postnatal_info' => $postnatal ? [
        'visit_date' => $postnatal->visit_date,
        'associated_problems' => $postnatal->associated_problems,
        'breastfeeding_status' => $postnatal->breastfeeding_status,
        'family_planning' => $postnatal->family_planning
      ] : null,
      'recent_clinical_notes' => $clinicalNotes->take(3),
      'last_visit' => $antenatal->follow_up_date,
      'next_visit' => $antenatal->follow_up_next_visit
    ];
  }

  private function identifyComprehensiveRiskFactors($antenatal, $delivery = null, $postnatal = null)
  {
    $factors = [];

    if ($antenatal->age < 18) $factors[] = 'Teen pregnancy';
    if ($antenatal->age > 35) $factors[] = 'Advanced maternal age';
    if ($antenatal->heart_disease) $factors[] = 'Heart disease';
    if ($antenatal->kidney_disease) $factors[] = 'Kidney disease';
    if ($antenatal->family_hypertension) $factors[] = 'Family history of hypertension';
    if ($antenatal->bleeding) $factors[] = 'Bleeding during pregnancy';
    if ($antenatal->hemoglobin < 11) $factors[] = 'Anemia';
    if (strpos($antenatal->genotype, 'S') !== false) $factors[] = 'Sickle cell trait/disease';

    $bp = $this->parseBloodPressure($antenatal->blood_pressure);
    if ($bp && ($bp['systolic'] >= 140 || $bp['diastolic'] >= 90)) {
      $factors[] = 'Hypertension';
    }

    if ($delivery) {
      if ($delivery->mod === 'CS') $factors[] = 'Cesarean delivery';
      if ($delivery->blood_loss > 500) $factors[] = 'Excessive blood loss';
      if (!empty($delivery->complications)) $factors[] = 'Delivery complications';
      if ($delivery->still_birth) $factors[] = 'Stillbirth history';
    }

    if ($postnatal) {
      if ($postnatal->systolic_bp > 140 || $postnatal->diastolic_bp > 90) {
        $factors[] = 'Postpartum hypertension';
      }
      if (!empty($postnatal->associated_problems)) {
        $factors[] = 'Postnatal complications';
      }
    }

    return $factors;
  }

  private function parseBloodPressure($bpString)
  {
    if (preg_match('/(\d+)\/(\d+)/', $bpString, $matches)) {
      return [
        'systolic' => (int)$matches[1],
        'diastolic' => (int)$matches[2]
      ];
    }
    return null;
  }

  private function calculateGestationalAge($lmp)
  {
    if ($lmp) {
      $lmpDate = Carbon::parse($lmp);
      $now = Carbon::now();
      $weeks = $lmpDate->diffInWeeks($now);
      $days = $lmpDate->diffInDays($now) % 7;
      return "{$weeks}w {$days}d";
    }
    return null;
  }
}
