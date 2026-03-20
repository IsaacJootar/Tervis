<?php

namespace App\Services;

use App\Models\Registrations\AntenatalRegistration as Antenatal;
use App\Models\AntenatalFollowUpAssessment;
use App\Models\Delivery;
use App\Models\PostnatalRecord;
use App\Models\TetanusVaccination;
use App\Models\DailyAttendance;
use App\Models\ClinicalNote;
use App\Models\Facility;
use App\Models\Patient;
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

    // Do not cache this payload in DB cache store:
    // high-risk lists can become very large and exceed MySQL max_allowed_packet.
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
        $user = $prediction->user;
        return (object)[
          'user' => (object) [
            'first_name' => $user?->first_name ?? 'Unknown',
            'last_name' => $user?->last_name ?? 'Patient',
            'din' => $user?->din ?? $user?->DIN ?? 'N/A',
          ],
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
      $antenatalPatients = Antenatal::whereIn('facility_id', $facilityIds)
        ->pluck('patient_id')->toArray();

      $deliveryPatients = Delivery::whereIn('facility_id', $facilityIds)
        ->pluck('patient_id')->toArray();

      $postnatalPatients = PostnatalRecord::whereIn('facility_id', $facilityIds)
        ->pluck('patient_id')->toArray();

      $tetanusPatients = TetanusVaccination::whereIn('facility_id', $facilityIds)
        ->pluck('patient_id')->toArray();

      $allPatients = array_unique(array_merge(
        $antenatalPatients,
        $deliveryPatients,
        $postnatalPatients,
        $tetanusPatients
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
        'antenatal' => Antenatal::whereIn('facility_id', $facilityIds)
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

    return Antenatal::whereIn('facility_id', $facilityIds)
      ->where(function ($query) {
        $query->whereHas('patient', function ($patientQuery) {
          $patientQuery->where(function ($ageQuery) {
            $ageQuery->whereDate('date_of_birth', '>', Carbon::today()->subYears(18))
              ->orWhereDate('date_of_birth', '<', Carbon::today()->subYears(35));
          });
        })
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
      ->select([
        'id',
        'patient_id',
        'facility_id',
        'lmp',
        'edd',
        'blood_pressure',
        'hemoglobin',
        'genotype',
        'heart_disease',
        'kidney_disease',
        'family_hypertension',
        'bleeding',
      ])
      ->with([
        'patient:id,din,first_name,middle_name,last_name,date_of_birth,phone',
        'facility:id,name',
      ])
      ->get()
      ->map(function ($antenatal) {
        $patient = $antenatal->patient;
        $delivery = Delivery::query()
          ->where('patient_id', (int) $antenatal->patient_id)
          ->where('facility_id', (int) $antenatal->facility_id)
          ->latest()
          ->first();

        $postnatal = PostnatalRecord::query()
          ->where('patient_id', (int) $antenatal->patient_id)
          ->where('facility_id', (int) $antenatal->facility_id)
          ->latest()
          ->first();

        $riskFactors = $this->identifyComprehensiveRiskFactors($antenatal, $patient, $delivery, $postnatal);

        return (object) [
          'id' => (int) $antenatal->id,
          'patient_id' => (int) $antenatal->patient_id,
          'facility_id' => (int) $antenatal->facility_id,
          'patient_name' => $this->resolvePatientName($patient),
          'patient_din' => $this->resolveDin($patient),
          'patient_age' => $patient?->age,
          'gestational_age_label' => $this->calculateGestationalAge($antenatal->lmp),
          'risk_factors' => $riskFactors,
          'risk_factor_count' => count($riskFactors),
          'edd' => $antenatal->edd,
        ];
      });
  }

  private function getUpcomingDeliveries($facilityIds)
  {
    $facilityIds = $this->scopeService->normalizeFacilityIds($facilityIds);
    $nextWeek = Carbon::now()->addWeek();

    return Antenatal::whereIn('facility_id', $facilityIds)
      ->whereDate('edd', '<=', $nextWeek)
      ->whereDate('edd', '>=', Carbon::today())
      ->with(['patient', 'facility'])
      ->get()
      ->map(function ($antenatal) {
        return [
          'patient_name' => $this->resolvePatientName($antenatal->patient),
          'din' => $this->resolveDin($antenatal->patient),
          'facility_name' => $antenatal->facility->name ?? 'Unknown',
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
        'antenatal_registrations' => Antenatal::whereIn('facility_id', $facilityIds)
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
    $overdueAntenatal = Antenatal::whereIn('facility_id', $facilityIds)
      ->with(['patient', 'facility'])
      ->get()
      ->filter(function ($antenatal) {
        $nextVisit = $this->getNextAntenatalVisitDate($antenatal);
        return $nextVisit && Carbon::parse($nextVisit)->lt(Carbon::today());
      });

    foreach ($overdueAntenatal as $antenatal) {
      $nextVisit = $this->getNextAntenatalVisitDate($antenatal);
      $alerts->push([
        'type' => 'overdue_antenatal',
        'priority' => 'high',
        'message' => "Overdue antenatal visit for {$this->resolvePatientName($antenatal->patient)}",
        'patient_din' => $this->resolveDin($antenatal->patient),
        'facility_name' => $antenatal->facility->name ?? 'Unknown',
        'due_date' => $nextVisit,
        'service' => 'Antenatal Care'
      ]);
    }

    // Overdue tetanus vaccinations
    $overdueTetanus = TetanusVaccination::whereIn('facility_id', $facilityIds)
      ->where('next_appointment_date', '<', Carbon::today())
      ->whereNotNull('next_appointment_date')
      ->with(['patient', 'facility'])
      ->get();

    foreach ($overdueTetanus as $tetanus) {
      $alerts->push([
        'type' => 'overdue_tetanus',
        'priority' => 'medium',
        'message' => "Overdue tetanus vaccination for {$this->resolvePatientName($tetanus->patient)}",
        'patient_din' => $this->resolveDin($tetanus->patient),
        'facility_name' => $tetanus->facility->name ?? 'Unknown',
        'due_date' => $tetanus->next_appointment_date,
        'service' => 'Tetanus Vaccination'
      ]);
    }

    // Critical delivery dates (within 3 days)
    $criticalDeliveries = Antenatal::whereIn('facility_id', $facilityIds)
      ->whereDate('edd', '<=', Carbon::now()->addDays(3))
      ->whereDate('edd', '>=', Carbon::today())
      ->with(['patient', 'facility'])
      ->get();

    foreach ($criticalDeliveries as $antenatal) {
      $alerts->push([
        'type' => 'critical_delivery',
        'priority' => 'critical',
        'message' => "Expected delivery within 3 days for {$this->resolvePatientName($antenatal->patient)}",
        'patient_din' => $this->resolveDin($antenatal->patient),
        'facility_name' => $antenatal->facility->name ?? 'Unknown',
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

    $antenatal = Antenatal::whereIn('facility_id', $facilityIds)
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
      'antenatal_coverage' => Antenatal::whereIn('facility_id', $facilityIds)->count(),
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

    $patients = Patient::query()
      ->whereHas('antenatal', function ($q) use ($facilityIds) {
        $q->whereIn('facility_id', $facilityIds);
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
      $hasAntenatal = $patient->antenatal()->whereIn('facility_id', $facilityIds)->exists();
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

    $totalPatients = Patient::query()
      ->whereHas('antenatal', function ($q) use ($facilityIds) {
        $q->whereIn('facility_id', $facilityIds);
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

    return Patient::query()
      ->whereHas('antenatal', function ($q) use ($facilityIds) {
        $q->whereIn('facility_id', $facilityIds);
      })
      ->whereHas('deliveries', function ($q) use ($facilityIds) {
        $q->whereIn('facility_id', $facilityIds);
      })
      ->whereHas('postnatalRecords', function ($q) use ($facilityIds) {
        $q->whereIn('facility_id', $facilityIds);
      })
      ->count();
  }

  public function getHighRiskDetails($antenatalId)
  {
    $antenatal = Antenatal::with(['patient'])->find($antenatalId);
    if (!$antenatal) return null;

    $patientId = (int) $antenatal->patient_id;
    $patient = $antenatal->patient;

    $delivery = Delivery::where('patient_id', $patientId)->latest()->first();
    $postnatal = PostnatalRecord::where('patient_id', $patientId)->latest()->first();
    $clinicalNotes = ClinicalNote::where('user_id', $patientId)->latest()->get();
    $nextVisit = $this->getNextAntenatalVisitDate($antenatal);

    return [
      'patient_name' => $this->resolvePatientName($patient),
      'din' => $this->resolveDin($patient),
      'age' => $patient?->age ?? null,
      'phone' => $patient?->phone,
      'address' => 'N/A',
      'edd' => $antenatal->edd,
      'lmp' => $antenatal->lmp,
      'gestational_age' => $this->calculateGestationalAge($antenatal->lmp),
      'risk_factors' => $this->identifyComprehensiveRiskFactors($antenatal, $patient, $delivery, $postnatal),
      'medical_history' => [
        'heart_disease' => $antenatal->heart_disease,
        'chest_disease' => $antenatal->chest_disease,
        'kidney_disease' => $antenatal->kidney_disease,
        'family_hypertension' => $antenatal->family_hypertension,
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
      'last_visit' => AntenatalFollowUpAssessment::query()
        ->where('patient_id', $patientId)
        ->where('facility_id', $antenatal->facility_id)
        ->latest('visit_date')
        ->value('visit_date'),
      'next_visit' => $nextVisit
    ];
  }

  private function identifyComprehensiveRiskFactors($antenatal, $patient = null, $delivery = null, $postnatal = null)
  {
    $factors = [];

    if (($patient?->age ?? 0) > 0 && $patient->age < 18) $factors[] = 'Teen pregnancy';
    if (($patient?->age ?? 0) > 35) $factors[] = 'Advanced maternal age';
    if ($antenatal->heart_disease) $factors[] = 'Heart disease';
    if ($antenatal->kidney_disease) $factors[] = 'Kidney disease';
    if ($antenatal->family_hypertension) $factors[] = 'Family history of hypertension';
    if ($antenatal->bleeding) $factors[] = 'Bleeding during pregnancy';
    if ($antenatal->hemoglobin < 11) $factors[] = 'Anemia';
    if (strpos((string) $antenatal->genotype, 'S') !== false) $factors[] = 'Sickle cell trait/disease';

    $bp = $this->parseBloodPressure($antenatal->blood_pressure);
    if ($bp && ($bp['systolic'] >= 140 || $bp['diastolic'] >= 90)) {
      $factors[] = 'Hypertension';
    }

    if ($delivery) {
      if ($delivery->mod === 'CS') $factors[] = 'Cesarean delivery';
      if (is_numeric($delivery->blood_loss) && (float) $delivery->blood_loss > 500) $factors[] = 'Excessive blood loss';
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

  private function resolveDin($patient): string
  {
    if (!$patient) {
      return 'N/A';
    }

    $din = (string) ($patient->din ?? $patient->DIN ?? '');
    return $din !== '' ? $din : 'N/A';
  }

  private function resolvePatientName($patient): string
  {
    if (!$patient) {
      return 'Unknown Patient';
    }

    $fullName = trim((string) ($patient->full_name ?? ''));
    if ($fullName !== '') {
      return $fullName;
    }

    return trim((string) (($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''))) ?: 'Unknown Patient';
  }

  private function getNextAntenatalVisitDate($antenatal)
  {
    return AntenatalFollowUpAssessment::query()
      ->where('patient_id', (int) $antenatal->patient_id)
      ->where('facility_id', (int) $antenatal->facility_id)
      ->whereNotNull('next_return_date')
      ->latest('visit_date')
      ->value('next_return_date');
  }

  private function parseBloodPressure($bpString)
  {
    if (!is_string($bpString) || trim($bpString) === '') {
      return null;
    }

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
