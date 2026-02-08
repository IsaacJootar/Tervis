<?php

namespace App\Livewire\Core;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Lga;
use App\Models\Facility;
use App\Models\Antenatal;
use App\Models\Delivery;
use App\Models\DailyAttendance;
use App\Models\PostnatalRecord;
use App\Models\TetanusVaccination;
use App\Services\DataScopeService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LgaOfficerDashboard extends Component
{
  protected $scopeService;

  // LGA-wide scope properties
  public $facilities = [];
  public $facilityIds = [];
  public $facilityCount = 0;
  public $state_name;
  public $lga_name;
  public $lga_id;
  public $user;

  public $selectedTimeframe = '30';
  public $selectedRegister = 'all';

  // Dashboard data properties
  public $totalPatients = 0;
  public $newRegistrations = 0;
  public $totalDeliveries = 0;
  public $activePregnancies = 0;
  public $highRiskCases = 0;
  public $todaysAttendance = 0;

  // Register statistics
  public $antenatalStats = ['total' => 0, 'this_period' => 0, 'trend' => 0];
  public $deliveryStats = ['total' => 0, 'this_period' => 0, 'trend' => 0];
  public $postnatalStats = ['total' => 0, 'this_period' => 0, 'trend' => 0];
  public $tetanusStats = ['total' => 0, 'this_period' => 0, 'trend' => 0];
  public $attendanceStats = ['total' => 0, 'this_period' => 0, 'trend' => 0];

  // Charts data
  public $trendChartData = [];
  public $ageGroupChartData = [];
  public $performanceMetrics = ['antenatal_coverage' => 0, 'avg_daily_attendance' => 0, 'facility_efficiency' => 0];
  public $riskAlerts = [];

  // LGA-specific metrics
  public $wardBreakdown = [];
  public $topFacilities = [];
  public $bottomFacilities = [];

  public function boot(DataScopeService $scopeService)
  {
    $this->scopeService = $scopeService;
  }

  public function mount()
  {
    $user = $this->user = Auth::user();
    if (!$user || !in_array($user->role, ['LGA Officer', 'LGA Data Administrator', 'LGA Administrator'])) {
      abort(403, 'Unauthorized: Only LGA Officers can access this page.');
    }

    // Get LGA-wide scope- i will remove the logs later
    $scope = $this->scopeService->getUserScope();

    if (empty($scope['facility_ids']) || !isset($scope['facility_ids'])) {
      Log::warning('No facilities found for LGA scope', ['user_id' => $user->id, 'lga_id' => $user->lga_id]);
      $this->facilityIds = [];
      $this->facilityCount = 0;
      $this->facilities = collect([]);
      $this->lga_id = $user->lga_id;
      $this->lga_name = Lga::find($user->lga_id)->name ?? 'N/A';
      $this->state_name = $user->state->name ?? 'N/A';

      toastr()->warning('No facilities found for your LGA scope.');
      return;
    }

    $this->facilityIds = $scope['facility_ids'];
    $this->facilityCount = count($this->facilityIds);
    $this->facilities = Facility::whereIn('id', $this->facilityIds)->get();
    $this->lga_id = $user->lga_id;
    $this->lga_name = Lga::find($user->lga_id)->name ?? 'N/A';
    $this->state_name = $user->state->name ?? 'N/A';

    Log::info('LGA Dashboard Initialized', [
      'user_id' => $user->id,
      'lga_id' => $this->lga_id,
      'facility_count' => $this->facilityCount,
      'facility_ids' => $this->facilityIds
    ]);

    $this->loadDashboardData();
  }

  public function updatedSelectedTimeframe()
  {
    $this->loadDashboardData();
  }

  public function updatedSelectedRegister()
  {
    $this->loadDashboardData();
  }

  public function loadDashboardData()
  {
    $this->dispatch('loading');

    try {
      $cacheKey = "lga_dashboard_{$this->lga_id}_{$this->selectedTimeframe}_{$this->selectedRegister}_" . now()->format('YmdH');

      $data = Cache::remember($cacheKey, 1800, function () {
        return $this->calculateDashboardMetrics();
      });

      // Set properties from cached data
      $this->totalPatients = $data['overview']['total_patients'];
      $this->newRegistrations = $data['overview']['new_registrations'];
      $this->totalDeliveries = $data['overview']['total_deliveries'];
      $this->activePregnancies = $data['overview']['active_pregnancies'];
      $this->highRiskCases = $data['overview']['high_risk_cases'];
      $this->todaysAttendance = $data['overview']['todays_attendance'];

      $this->antenatalStats = $data['registers']['antenatal'];
      $this->deliveryStats = $data['registers']['delivery'];
      $this->postnatalStats = $data['registers']['postnatal'];
      $this->tetanusStats = $data['registers']['tetanus'];
      $this->attendanceStats = $data['registers']['attendance'];

      $this->trendChartData = $data['trends'];
      $this->ageGroupChartData = $data['demographics']['age_groups'];
      $this->performanceMetrics = $data['performance'];
      $this->riskAlerts = $data['risks'];

      // LGA-specific data
      $this->wardBreakdown = $data['ward_breakdown'];
      $this->topFacilities = $data['top_facilities'];
      $this->bottomFacilities = $data['bottom_facilities'];

      $this->dispatch('loaded');
    } catch (\Exception $e) {
      Log::error('LGA Dashboard data loading failed: ' . $e->getMessage(), [
        'lga_id' => $this->lga_id,
        'timeframe' => $this->selectedTimeframe
      ]);
      toastr()->error('Error loading dashboard data.');
      $this->resetDashboardData();
      $this->dispatch('loaded');
    }
  }

  private function calculateDashboardMetrics()
  {
    try {
      $startDate = Carbon::now()->subDays($this->selectedTimeframe);

      return [
        'overview' => $this->getOverviewStats($startDate),
        'registers' => $this->getFilteredRegisterStats($startDate),
        'trends' => $this->getFilteredTrendData($startDate),
        'demographics' => $this->getDemographicData(),
        'performance' => $this->getPerformanceMetrics($startDate),
        'risks' => $this->getRiskAlerts(),
        'ward_breakdown' => $this->getWardBreakdown($startDate),
        'top_facilities' => $this->getTopFacilities($startDate),
        'bottom_facilities' => $this->getBottomFacilities($startDate),
      ];
    } catch (\Exception $e) {
      Log::error('LGA Dashboard metrics calculation failed: ' . $e->getMessage());
      return $this->getEmptyDashboardData();
    }
  }

  private function getFilteredRegisterStats($startDate)
  {
    $allStats = $this->getRegisterStats($startDate);

    if ($this->selectedRegister === 'all') {
      return $allStats;
    }

    $filteredStats = [];
    foreach ($allStats as $register => $stats) {
      if ($register === $this->selectedRegister) {
        $filteredStats[$register] = $stats;
      } else {
        $filteredStats[$register] = ['total' => 0, 'this_period' => 0, 'trend' => 0];
      }
    }

    return $filteredStats;
  }

  private function getFilteredTrendData($startDate)
  {
    try {
      $days = [];
      $antenatalData = [];
      $deliveryData = [];
      $attendanceData = [];

      for ($i = $this->selectedTimeframe - 1; $i >= 0; $i--) {
        $date = Carbon::now()->subDays($i);
        $days[] = $date->format('M d');

        if ($this->selectedRegister === 'all' || $this->selectedRegister === 'antenatal') {
          $antenatalCount = Antenatal::whereIn('registration_facility_id', $this->facilityIds)
            ->whereDate('created_at', $date)
            ->count();
          $antenatalData[] = $antenatalCount;
        } else {
          $antenatalData[] = 0;
        }

        if ($this->selectedRegister === 'all' || $this->selectedRegister === 'delivery') {
          $deliveryCount = Delivery::whereIn('facility_id', $this->facilityIds)
            ->whereDate('created_at', $date)
            ->count();
          $deliveryData[] = $deliveryCount;
        } else {
          $deliveryData[] = 0;
        }

        if ($this->selectedRegister === 'all' || $this->selectedRegister === 'attendance') {
          $attendanceCount = DailyAttendance::whereIn('facility_id', $this->facilityIds)
            ->whereDate('visit_date', $date)
            ->count();
          $attendanceData[] = $attendanceCount;
        } else {
          $attendanceData[] = 0;
        }
      }

      return [
        'labels' => $days,
        'antenatal' => $antenatalData,
        'delivery' => $deliveryData,
        'attendance' => $attendanceData,
      ];
    } catch (\Exception $e) {
      Log::error('LGA Trend data calculation failed: ' . $e->getMessage());
      return [
        'labels' => [],
        'antenatal' => [],
        'delivery' => [],
        'attendance' => [],
      ];
    }
  }

  private function getOverviewStats($startDate)
  {
    try {
      if (!$startDate instanceof Carbon) {
        $startDate = Carbon::parse($startDate);
      }

      // Aggregate across all facilities in LGA
      $totalPatients = Antenatal::whereIn('registration_facility_id', $this->facilityIds)
        ->distinct('user_id')
        ->count('user_id');

      $newRegistrations = Antenatal::whereIn('registration_facility_id', $this->facilityIds)
        ->where('created_at', '>=', $startDate)
        ->count();

      $totalDeliveries = Delivery::whereIn('facility_id', $this->facilityIds)->count();

      $activePregnancies = Antenatal::whereIn('registration_facility_id', $this->facilityIds)
        ->whereDate('edd', '>', Carbon::now())
        ->count();

      $highRiskCases = $this->calculateHighRiskCases();

      $todaysAttendance = DailyAttendance::whereIn('facility_id', $this->facilityIds)
        ->whereDate('visit_date', Carbon::today())
        ->count();

      return [
        'total_patients' => $totalPatients,
        'new_registrations' => $newRegistrations,
        'total_deliveries' => $totalDeliveries,
        'active_pregnancies' => $activePregnancies,
        'high_risk_cases' => $highRiskCases,
        'todays_attendance' => $todaysAttendance,
      ];
    } catch (\Exception $e) {
      Log::error('LGA Overview stats calculation failed: ' . $e->getMessage());
      return $this->getEmptyOverviewStats();
    }
  }

  private function getRegisterStats($startDate)
  {
    try {
      return [
        'antenatal' => [
          'total' => Antenatal::whereIn('registration_facility_id', $this->facilityIds)->count(),
          'this_period' => Antenatal::whereIn('registration_facility_id', $this->facilityIds)
            ->where('created_at', '>=', $startDate)->count(),
          'trend' => $this->calculateTrend('antenatal', $startDate),
        ],
        'delivery' => [
          'total' => Delivery::whereIn('facility_id', $this->facilityIds)->count(),
          'this_period' => Delivery::whereIn('facility_id', $this->facilityIds)
            ->where('created_at', '>=', $startDate)->count(),
          'trend' => $this->calculateTrend('delivery', $startDate),
        ],
        'postnatal' => [
          'total' => PostnatalRecord::whereIn('facility_id', $this->facilityIds)->count(),
          'this_period' => PostnatalRecord::whereIn('facility_id', $this->facilityIds)
            ->where('visit_date', '>=', $startDate)->count(),
          'trend' => $this->calculateTrend('postnatal', $startDate),
        ],
        'tetanus' => [
          'total' => TetanusVaccination::whereIn('facility_id', $this->facilityIds)->count(),
          'this_period' => TetanusVaccination::whereIn('facility_id', $this->facilityIds)
            ->where('visit_date', '>=', $startDate)->count(),
          'trend' => $this->calculateTrend('tetanus', $startDate),
        ],
        'attendance' => [
          'total' => DailyAttendance::whereIn('facility_id', $this->facilityIds)->count(),
          'this_period' => DailyAttendance::whereIn('facility_id', $this->facilityIds)
            ->where('visit_date', '>=', $startDate)->count(),
          'trend' => $this->calculateTrend('attendance', $startDate),
        ],
      ];
    } catch (\Exception $e) {
      Log::error('LGA Register stats calculation failed: ' . $e->getMessage());
      return [
        'antenatal' => ['total' => 0, 'this_period' => 0, 'trend' => 0],
        'delivery' => ['total' => 0, 'this_period' => 0, 'trend' => 0],
        'postnatal' => ['total' => 0, 'this_period' => 0, 'trend' => 0],
        'tetanus' => ['total' => 0, 'this_period' => 0, 'trend' => 0],
        'attendance' => ['total' => 0, 'this_period' => 0, 'trend' => 0],
      ];
    }
  }

  private function getDemographicData()
  {
    try {
      $ageGroups = Antenatal::whereIn('registration_facility_id', $this->facilityIds)
        ->selectRaw('
                    CASE
                        WHEN age < 18 THEN "Under 18"
                        WHEN age BETWEEN 18 AND 24 THEN "18-24"
                        WHEN age BETWEEN 25 AND 34 THEN "25-34"
                        WHEN age >= 35 THEN "35+"
                        ELSE "Unknown"
                    END as age_group,
                    COUNT(*) as count
                ')
        ->groupBy('age_group')
        ->get()
        ->pluck('count', 'age_group')
        ->toArray();

      return ['age_groups' => $ageGroups];
    } catch (\Exception $e) {
      Log::error('LGA Demographic data calculation failed: ' . $e->getMessage());
      return ['age_groups' => []];
    }
  }

  private function getPerformanceMetrics($startDate)
  {
    try {
      $facilityPatients = Antenatal::whereIn('registration_facility_id', $this->facilityIds)
        ->distinct('user_id')
        ->count('user_id');

      $activePregnancies = Antenatal::whereIn('registration_facility_id', $this->facilityIds)
        ->whereDate('edd', '>', Carbon::now())
        ->distinct('user_id')
        ->count('user_id');

      $antenatalCoverage = $facilityPatients > 0
        ? round(($activePregnancies / $facilityPatients) * 100, 1)
        : 0;

      $totalAttendanceRecords = DailyAttendance::whereIn('facility_id', $this->facilityIds)
        ->where('visit_date', '>=', $startDate)
        ->count();

      $avgDailyAttendance = round($totalAttendanceRecords / max($this->selectedTimeframe, 1), 1);

      $recentVisits = DailyAttendance::whereIn('facility_id', $this->facilityIds)
        ->where('visit_date', '>=', Carbon::now()->subDays(30))
        ->count();

      if ($activePregnancies > 0) {
        $visitsPerActivePatient = $recentVisits / $activePregnancies;
        $facilityEfficiency = min(100, round(($visitsPerActivePatient / 3) * 100, 1));
      } else {
        $facilityEfficiency = 0;
      }

      return [
        'antenatal_coverage' => $antenatalCoverage,
        'avg_daily_attendance' => $avgDailyAttendance,
        'facility_efficiency' => $facilityEfficiency,
      ];
    } catch (\Exception $e) {
      Log::error('LGA Performance metrics calculation failed: ' . $e->getMessage());
      return [
        'antenatal_coverage' => 0,
        'avg_daily_attendance' => 0,
        'facility_efficiency' => 0,
      ];
    }
  }

  private function getRiskAlerts()
  {
    $alerts = [];

    try {
      $highRiskCount = $this->calculateHighRiskCases();

      if ($highRiskCount > 0) {
        $alerts[] = [
          'type' => 'warning',
          'title' => 'High-Risk Pregnancies (LGA-wide)',
          'message' => "{$highRiskCount} patients across {$this->facilityCount} facilities require immediate attention",
          'count' => $highRiskCount,
          'icon' => 'bx-warning'
        ];
      }

      $overdueCount = Antenatal::whereIn('registration_facility_id', $this->facilityIds)
        ->whereDate('edd', '<', Carbon::now())
        ->whereDoesntHave('user.deliveries', function ($query) {
          $query->whereIn('facility_id', $this->facilityIds);
        })
        ->count();

      if ($overdueCount > 0) {
        $alerts[] = [
          'type' => 'danger',
          'title' => 'Overdue Deliveries (LGA-wide)',
          'message' => "{$overdueCount} patients past expected delivery date across LGA",
          'count' => $overdueCount,
          'icon' => 'bx-calendar-x'
        ];
      }

      $overdueVaccinations = TetanusVaccination::whereIn('facility_id', $this->facilityIds)
        ->where('dose_number', '<', 5)
        ->whereDate('next_appointment_date', '<', Carbon::now())
        ->whereNotNull('next_appointment_date')
        ->count();

      if ($overdueVaccinations > 0) {
        $alerts[] = [
          'type' => 'warning',
          'title' => 'Overdue TT Vaccinations (LGA-wide)',
          'message' => "{$overdueVaccinations} patients have missed TT vaccination appointments",
          'count' => $overdueVaccinations,
          'icon' => 'bx-injection'
        ];
      }

      $highBpPostnatal = PostnatalRecord::whereIn('facility_id', $this->facilityIds)
        ->where(function ($query) {
          $query->where('systolic_bp', '>', 140)
            ->orWhere('diastolic_bp', '>', 90);
        })
        ->whereDate('visit_date', '>=', Carbon::now()->subDays(30))
        ->count();

      if ($highBpPostnatal > 0) {
        $alerts[] = [
          'type' => 'danger',
          'title' => 'High Blood Pressure (Postnatal)',
          'message' => "{$highBpPostnatal} postnatal patients with elevated blood pressure",
          'count' => $highBpPostnatal,
          'icon' => 'bx-heart'
        ];
      }

      $lowBirthWeight = PostnatalRecord::whereIn('facility_id', $this->facilityIds)
        ->where('newborn_weight', '<', 2.5)
        ->whereDate('visit_date', '>=', Carbon::now()->subDays(30))
        ->count();

      if ($lowBirthWeight > 0) {
        $alerts[] = [
          'type' => 'warning',
          'title' => 'Low Birth Weight Babies',
          'message' => "{$lowBirthWeight} newborns with low birth weight need monitoring",
          'count' => $lowBirthWeight,
          'icon' => 'bx-baby-carriage'
        ];
      }

      $emergencyDeliveries = Delivery::whereIn('facility_id', $this->facilityIds)
        ->where('mod', 'CS')
        ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
        ->count();

      if ($emergencyDeliveries > 0) {
        $alerts[] = [
          'type' => 'info',
          'title' => 'Recent Cesarean Deliveries',
          'message' => "{$emergencyDeliveries} cesarean deliveries in the last week",
          'count' => $emergencyDeliveries,
          'icon' => 'bx-plus-medical'
        ];
      }

      $recentAttendees = DailyAttendance::whereIn('facility_id', $this->facilityIds)
        ->whereDate('visit_date', '>=', Carbon::now()->subDays(30))
        ->distinct('user_id')
        ->pluck('user_id');

      $totalActivePatients = Antenatal::whereIn('registration_facility_id', $this->facilityIds)
        ->whereDate('edd', '>', Carbon::now())
        ->distinct('user_id')
        ->count('user_id');

      $inactivePatients = max(0, $totalActivePatients - $recentAttendees->count());

      if ($inactivePatients > 0) {
        $alerts[] = [
          'type' => 'warning',
          'title' => 'Inactive Patients (LGA-wide)',
          'message' => "{$inactivePatients} active patients haven't attended in 30+ days",
          'count' => $inactivePatients,
          'icon' => 'bx-user-x'
        ];
      }
    } catch (\Exception $e) {
      Log::error('LGA Risk alerts calculation failed: ' . $e->getMessage());
    }

    return $alerts;
  }

  private function getWardBreakdown($startDate)
  {
    try {
      $breakdown = [];

      foreach ($this->facilities->groupBy('ward') as $ward => $wardFacilities) {
        $wardFacilityIds = $wardFacilities->pluck('id')->toArray();

        $breakdown[$ward] = [
          'facility_count' => count($wardFacilityIds),
          'total_patients' => Antenatal::whereIn('registration_facility_id', $wardFacilityIds)
            ->distinct('user_id')
            ->count('user_id'),
          'new_registrations' => Antenatal::whereIn('registration_facility_id', $wardFacilityIds)
            ->where('created_at', '>=', $startDate)
            ->count(),
          'total_deliveries' => Delivery::whereIn('facility_id', $wardFacilityIds)->count(),
          'high_risk_cases' => $this->calculateHighRiskCasesForFacilities($wardFacilityIds),
        ];
      }

      uasort($breakdown, function ($a, $b) {
        return $b['total_patients'] <=> $a['total_patients'];
      });

      return $breakdown;
    } catch (\Exception $e) {
      Log::error('Ward breakdown calculation failed: ' . $e->getMessage());
      return [];
    }
  }

  private function getTopFacilities($startDate)
  {
    try {
      $facilityScores = [];

      foreach ($this->facilities as $facility) {
        $patients = Antenatal::where('registration_facility_id', $facility->id)
          ->distinct('user_id')
          ->count('user_id');

        $newReg = Antenatal::where('registration_facility_id', $facility->id)
          ->where('created_at', '>=', $startDate)
          ->count();

        $deliveries = Delivery::where('facility_id', $facility->id)->count();

        $attendance = DailyAttendance::where('facility_id', $facility->id)
          ->where('visit_date', '>=', $startDate)
          ->count();

        $score = ($patients * 2) + ($newReg * 3) + ($deliveries * 2) + $attendance;

        $facilityScores[$facility->id] = [
          'name' => $facility->name,
          'ward' => $facility->ward,
          'score' => $score,
          'patients' => $patients,
          'new_registrations' => $newReg,
          'deliveries' => $deliveries,
        ];
      }

      uasort($facilityScores, function ($a, $b) {
        return $b['score'] <=> $a['score'];
      });

      return array_slice($facilityScores, 0, 5, true);
    } catch (\Exception $e) {
      Log::error('Top facilities calculation failed: ' . $e->getMessage());
      return [];
    }
  }

  private function getBottomFacilities($startDate)
  {
    try {
      $facilityScores = [];

      foreach ($this->facilities as $facility) {
        $patients = Antenatal::where('registration_facility_id', $facility->id)
          ->distinct('user_id')
          ->count('user_id');

        $newReg = Antenatal::where('registration_facility_id', $facility->id)
          ->where('created_at', '>=', $startDate)
          ->count();

        $deliveries = Delivery::where('facility_id', $facility->id)->count();

        $attendance = DailyAttendance::where('facility_id', $facility->id)
          ->where('visit_date', '>=', $startDate)
          ->count();

        $score = ($patients * 2) + ($newReg * 3) + ($deliveries * 2) + $attendance;

        $facilityScores[$facility->id] = [
          'name' => $facility->name,
          'ward' => $facility->ward,
          'score' => $score,
          'patients' => $patients,
          'new_registrations' => $newReg,
          'deliveries' => $deliveries,
        ];
      }

      uasort($facilityScores, function ($a, $b) {
        return $a['score'] <=> $b['score'];
      });

      return array_slice($facilityScores, 0, 5, true);
    } catch (\Exception $e) {
      Log::error('Bottom facilities calculation failed: ' . $e->getMessage());
      return [];
    }
  }

  private function calculateTrend($register, $startDate)
  {
    try {
      $model = match ($register) {
        'antenatal' => Antenatal::class,
        'delivery' => Delivery::class,
        'postnatal' => PostnatalRecord::class,
        'tetanus' => TetanusVaccination::class,
        'attendance' => DailyAttendance::class,
      };

      $facilityColumn = $register === 'antenatal' ? 'registration_facility_id' : 'facility_id';

      $dateColumn = match ($register) {
        'delivery' => 'created_at',
        'postnatal', 'tetanus', 'attendance' => 'visit_date',
        default => 'created_at'
      };

      $currentPeriod = $model::whereIn($facilityColumn, $this->facilityIds)
        ->where($dateColumn, '>=', $startDate)
        ->count();

      $previousStart = Carbon::now()->subDays($this->selectedTimeframe * 2);
      $previousPeriod = $model::whereIn($facilityColumn, $this->facilityIds)
        ->whereBetween($dateColumn, [$previousStart, $startDate])
        ->count();

      if ($previousPeriod == 0) {
        return $currentPeriod > 0 ? 100 : 0;
      }

      return round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 1);
    } catch (\Exception $e) {
      Log::error('LGA Trend calculation failed: ' . $e->getMessage());
      return 0;
    }
  }

  private function calculateHighRiskCases()
  {
    try {
      return Antenatal::whereIn('registration_facility_id', $this->facilityIds)
        ->where(function ($query) {
          $query->where('age', '<', 18)
            ->orWhere('age', '>', 35)
            ->orWhere('heart_disease', 1)
            ->orWhere('kidney_disease', 1)
            ->orWhere('family_hypertension', 1)
            ->orWhere('bleeding', 1)
            ->orWhere('hemoglobin', '<', 11)
            ->orWhere('genotype', 'LIKE', '%S%')
            ->orWhere(function ($q) {
              $q->whereRaw("CASE
                                WHEN blood_pressure REGEXP '^[0-9]{2,3}/[0-9]{2,3}$'
                                THEN CAST(SUBSTRING_INDEX(blood_pressure, '/', 1) AS UNSIGNED) >= 140
                                     OR CAST(SUBSTRING_INDEX(blood_pressure, '/', -1) AS UNSIGNED) >= 90
                                ELSE FALSE
                                END");
            });
        })
        ->count();
    } catch (\Exception $e) {
      Log::error('LGA High-risk cases calculation failed: ' . $e->getMessage());
      return 0;
    }
  }

  private function calculateHighRiskCasesForFacilities($facilityIds)
  {
    try {
      return Antenatal::whereIn('registration_facility_id', $facilityIds)
        ->where(function ($query) {
          $query->where('age', '<', 18)
            ->orWhere('age', '>', 35)
            ->orWhere('heart_disease', 1)
            ->orWhere('kidney_disease', 1)
            ->orWhere('family_hypertension', 1)
            ->orWhere('bleeding', 1)
            ->orWhere('hemoglobin', '<', 11)
            ->orWhere('genotype', 'LIKE', '%S%');
        })
        ->count();
    } catch (\Exception $e) {
      return 0;
    }
  }

  private function getEmptyDashboardData()
  {
    return [
      'overview' => $this->getEmptyOverviewStats(),
      'registers' => [
        'antenatal' => ['total' => 0, 'this_period' => 0, 'trend' => 0],
        'delivery' => ['total' => 0, 'this_period' => 0, 'trend' => 0],
        'postnatal' => ['total' => 0, 'this_period' => 0, 'trend' => 0],
        'tetanus' => ['total' => 0, 'this_period' => 0, 'trend' => 0],
        'attendance' => ['total' => 0, 'this_period' => 0, 'trend' => 0],
      ],
      'trends' => ['labels' => [], 'antenatal' => [], 'delivery' => [], 'attendance' => []],
      'demographics' => ['age_groups' => []],
      'performance' => ['antenatal_coverage' => 0, 'avg_daily_attendance' => 0, 'facility_efficiency' => 0],
      'risks' => [],
      'ward_breakdown' => [],
      'top_facilities' => [],
      'bottom_facilities' => [],
    ];
  }

  private function getEmptyOverviewStats()
  {
    return [
      'total_patients' => 0,
      'new_registrations' => 0,
      'total_deliveries' => 0,
      'active_pregnancies' => 0,
      'high_risk_cases' => 0,
      'todays_attendance' => 0,
    ];
  }

  private function resetDashboardData()
  {
    $this->totalPatients = 0;
    $this->newRegistrations = 0;
    $this->totalDeliveries = 0;
    $this->activePregnancies = 0;
    $this->highRiskCases = 0;
    $this->todaysAttendance = 0;
    $this->antenatalStats = ['total' => 0, 'this_period' => 0, 'trend' => 0];
    $this->deliveryStats = ['total' => 0, 'this_period' => 0, 'trend' => 0];
    $this->postnatalStats = ['total' => 0, 'this_period' => 0, 'trend' => 0];
    $this->tetanusStats = ['total' => 0, 'this_period' => 0, 'trend' => 0];
    $this->attendanceStats = ['total' => 0, 'this_period' => 0, 'trend' => 0];
    $this->trendChartData = ['labels' => [], 'antenatal' => [], 'delivery' => [], 'attendance' => []];
    $this->ageGroupChartData = [];
    $this->performanceMetrics = ['antenatal_coverage' => 0, 'avg_daily_attendance' => 0, 'facility_efficiency' => 0];
    $this->riskAlerts = [];
    $this->wardBreakdown = [];
    $this->topFacilities = [];
    $this->bottomFacilities = [];
  }

  public function forceRefresh()
  {
    $cacheKey = "lga_dashboard_{$this->lga_id}_{$this->selectedTimeframe}_{$this->selectedRegister}_*";
    Cache::forget($cacheKey);
    $this->js('window.location.reload()');
  }

  public function refreshData()
  {
    $cacheKey = "lga_dashboard_{$this->lga_id}_{$this->selectedTimeframe}_{$this->selectedRegister}_" . now()->format('YmdH');
    Cache::forget($cacheKey);
    $this->loadDashboardData();
    toastr()->info('Dashboard data refreshed successfully.');
  }

  public function render()
  {
    return view('livewire.core.lga-officer-dashboard')
      ->layout('layouts.lgaOfficerLayout');
  }
}
