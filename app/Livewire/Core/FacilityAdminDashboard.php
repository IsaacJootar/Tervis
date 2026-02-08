<?php

namespace App\Livewire\Core;

use Carbon\Carbon;
use App\Models\User;
use App\Models\State;
use App\Models\Facility;
use App\Models\Antenatal;
use App\Models\Delivery;
use App\Models\DailyAttendance;
use App\Models\PostnatalRecord;
use App\Models\TetanusVaccination;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class facilityAdminDashboard extends Component
{
  public $facility_id, $facility_name, $state_name, $lga_name, $ward_name, $user;
  public $selectedTimeframe = '30'; // a month for now
  public $selectedRegister = 'all'; // default to all registers

  // Dashboard data properties
  public $totalPatients = 0;
  public $newRegistrations = 0;
  public $totalDeliveries = 0;
  public $activePregnancies = 0;
  public $highRiskCases = 0;
  public $todaysAttendance = 0;

  // Register statistics
  public $antenatalStats = [];
  public $deliveryStats = [];
  public $postnatalStats = [];
  public $tetanusStats = [];
  public $attendanceStats = [];

  // Charts data
  public $trendChartData = [];
  public $ageGroupChartData = [];
  public $performanceMetrics = [];
  public $riskAlerts = [];

  public function mount()
  {
    $user = $this->user = Auth::user();



    $facility = Facility::find($user->facility_id);


    $this->facility_id = $facility->id;
    $this->facility_name = $facility->name;
    $this->state_name = $facility->state;
    $this->lga_name = $facility->lga;
    $this->ward_name = $facility->ward;

    // Load initial dashboard data
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

  // Update the loadDashboardData method to dispatch events
  public function loadDashboardData()
  {
    $this->dispatch('loading'); // Trigger loading state

    try {
      // Improved cache key with hour timestamp for automatic hourly refresh
      $cacheKey = "dashboard_data_{$this->facility_id}_{$this->selectedTimeframe}_{$this->selectedRegister}_" . now()->format('YmdH');

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

      $this->dispatch('loaded'); // Trigger loaded state
    } catch (\Exception $e) {
      Log::error('Dashboard data loading failed: ' . $e->getMessage(), [
        'facility_id' => $this->facility_id,
        'timeframe' => $this->selectedTimeframe
      ]);
      toastr()->error('Error loading dashboard data.');
      $this->resetDashboardData();
      $this->dispatch('loaded');
    }
  }

  // calculateDashboardMetrics - uses selectedRegister filter
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
      ];
    } catch (\Exception $e) {
      Log::error('Dashboard metrics calculation failed: ' . $e->getMessage());
      return $this->getEmptyDashboardData();
    }
  }

  // Filter register stats based on selectedRegister
  private function getFilteredRegisterStats($startDate)
  {
    $allStats = $this->getRegisterStats($startDate);

    if ($this->selectedRegister === 'all') {
      return $allStats;
    }

    // Return only the selected register stats, keeping the structure
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

  // Filter trend data based on selectedRegister
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

        // Only calculate data for selected register or all, just the 3 for now, at the basis of admin dash view
        if ($this->selectedRegister === 'all' || $this->selectedRegister === 'antenatal') {
          $antenatalCount = Antenatal::where('registration_facility_id', $this->facility_id)
            ->whereDate('created_at', $date)
            ->count();
          $antenatalData[] = $antenatalCount;
        } else {
          $antenatalData[] = 0;
        }

        if ($this->selectedRegister === 'all' || $this->selectedRegister === 'delivery') {
          $deliveryCount = Delivery::where('facility_id', $this->facility_id)
            ->whereDate('created_at', $date)
            ->count();
          $deliveryData[] = $deliveryCount;
        } else {
          $deliveryData[] = 0;
        }

        if ($this->selectedRegister === 'all' || $this->selectedRegister === 'attendance') {
          $attendanceCount = DailyAttendance::where('facility_id', $this->facility_id)
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
      Log::error('Trend data calculation failed: ' . $e->getMessage());
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
      // Validate date
      if (!$startDate instanceof Carbon) {
        $startDate = Carbon::parse($startDate);
      }

      // Count unique patients through antenatal registrations (correct way for now)
      $totalPatients = Antenatal::where('registration_facility_id', $this->facility_id)
        ->distinct('user_id')
        ->count('user_id');

      $newRegistrations = Antenatal::where('registration_facility_id', $this->facility_id)
        ->where('created_at', '>=', $startDate)
        ->count();

      $totalDeliveries = Delivery::where('facility_id', $this->facility_id)->count();

      $activePregnancies = Antenatal::where('registration_facility_id', $this->facility_id)
        ->whereDate('edd', '>', Carbon::now())
        ->count();

      $highRiskCases = $this->calculateHighRiskCases();

      $todaysAttendance = DailyAttendance::where('facility_id', $this->facility_id)
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
      Log::error('Overview stats calculation failed: ' . $e->getMessage());
      return $this->getEmptyOverviewStats();
    }
  }

  private function getRegisterStats($startDate)
  {
    try {
      return [
        'antenatal' => [
          'total' => Antenatal::where('registration_facility_id', $this->facility_id)->count(),
          'this_period' => Antenatal::where('registration_facility_id', $this->facility_id)
            ->where('created_at', '>=', $startDate)->count(),
          'trend' => $this->calculateTrend('antenatal', $startDate),
        ],
        'delivery' => [
          'total' => Delivery::where('facility_id', $this->facility_id)->count(),
          'this_period' => Delivery::where('facility_id', $this->facility_id)
            ->where('created_at', '>=', $startDate)->count(),
          'trend' => $this->calculateTrend('delivery', $startDate),
        ],
        'postnatal' => [
          'total' => PostnatalRecord::where('facility_id', $this->facility_id)->count(),
          'this_period' => PostnatalRecord::where('facility_id', $this->facility_id)
            ->where('visit_date', '>=', $startDate)->count(),
          'trend' => $this->calculateTrend('postnatal', $startDate),
        ],
        'tetanus' => [
          'total' => TetanusVaccination::where('facility_id', $this->facility_id)->count(),
          'this_period' => TetanusVaccination::where('facility_id', $this->facility_id)
            ->where('visit_date', '>=', $startDate)->count(),
          'trend' => $this->calculateTrend('tetanus', $startDate),
        ],
        'attendance' => [
          'total' => DailyAttendance::where('facility_id', $this->facility_id)->count(),
          'this_period' => DailyAttendance::where('facility_id', $this->facility_id)
            ->where('visit_date', '>=', $startDate)->count(),
          'trend' => $this->calculateTrend('attendance', $startDate),
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Register stats calculation failed: ' . $e->getMessage());
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
      $ageGroups = Antenatal::where('registration_facility_id', $this->facility_id)
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
      Log::error('Demographic data calculation failed: ' . $e->getMessage());
      return ['age_groups' => []];
    }
  }

  private function getPerformanceMetrics($startDate)
  {
    try {
      // 1. ANTENATAL COVERAGE - Percentage of pregnant women in catchment area (same facility) who have antenatal records
      // IMPROVED: Compare facility patients against active pregnancies instead of total system patients
      $facilityPatients = Antenatal::where('registration_facility_id', $this->facility_id)
        ->distinct('user_id')
        ->count('user_id');

      $activePregnancies = Antenatal::where('registration_facility_id', $this->facility_id)
        ->whereDate('edd', '>', Carbon::now())
        ->distinct('user_id')
        ->count('user_id');

      $antenatalCoverage = $facilityPatients > 0
        ? round(($activePregnancies / $facilityPatients) * 100, 1)
        : 0;

      // 2. AVERAGE DAILY ATTENDANCE - Average patients seen per day in the selected period
      $totalAttendanceRecords = DailyAttendance::where('facility_id', $this->facility_id)
        ->where('visit_date', '>=', $startDate)
        ->count();

      $avgDailyAttendance = round($totalAttendanceRecords / max($this->selectedTimeframe, 1), 1);

      // 3. FACILITY EFFICIENCY - Multiple metrics
      // Visit frequency per active patient
      $recentVisits = DailyAttendance::where('facility_id', $this->facility_id)
        ->where('visit_date', '>=', Carbon::now()->subDays(30))  // like a month
        ->count();

      // Calculate efficiency score (0-100)
      if ($activePregnancies > 0) {
        $visitsPerActivePatient = $recentVisits / $activePregnancies;
        // Normalize to 0-100 scale (assuming 3+ visits per month per patient is excellent)
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
      Log::error('Performance metrics calculation failed: ' . $e->getMessage());
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
      // ANTENATAL RISKS
      // High-risk pregnancies - IMPROVED: Now uses comprehensive criteria
      $highRiskCount = $this->calculateHighRiskCases();

      if ($highRiskCount > 0) {
        $alerts[] = [
          'type' => 'warning',
          'title' => 'High-Risk Pregnancies',
          'message' => "{$highRiskCount} patients require immediate attention",
          'count' => $highRiskCount,
          'icon' => 'bx-warning'
        ];
      }

      // Overdue deliveries -
      $overdueCount = Antenatal::where('registration_facility_id', $this->facility_id)
        ->whereDate('edd', '<', Carbon::now())
        ->whereDoesntHave('user.deliveries', function ($query) {
          $query->where('facility_id', $this->facility_id);
        })
        ->count();

      if ($overdueCount > 0) {
        $alerts[] = [
          'type' => 'danger',
          'title' => 'Overdue Deliveries',
          'message' => "{$overdueCount} patients past expected delivery date",
          'count' => $overdueCount,
          'icon' => 'bx-calendar-x'
        ];
      }

      // TETANUS VACCINATION RISKS
      // Patients overdue for TT doses (more than 6 months since last dose)
      $overdueVaccinations = TetanusVaccination::where('facility_id', $this->facility_id)
        ->where('dose_number', '<', 5) // Haven't completed all 5 doses
        ->whereDate('next_appointment_date', '<', Carbon::now())
        ->whereNotNull('next_appointment_date')
        ->count();

      if ($overdueVaccinations > 0) {
        $alerts[] = [
          'type' => 'warning',
          'title' => 'Overdue TT Vaccinations',
          'message' => "{$overdueVaccinations} patients have missed TT vaccination appointments",
          'count' => $overdueVaccinations,
          'icon' => 'bx-injection'
        ];
      }

      // POSTNATAL RISKS
      // High blood pressure in postnatal visits
      $highBpPostnatal = PostnatalRecord::where('facility_id', $this->facility_id)
        ->where(function ($query) {
          $query->where('systolic_bp', '>', 140)
            ->orWhere('diastolic_bp', '>', 90);
        })
        ->whereDate('visit_date', '>=', Carbon::now()->subDays(30)) // Last 30 days
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

      // Low birth weight babies
      $lowBirthWeight = PostnatalRecord::where('facility_id', $this->facility_id)
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

      // DELIVERY RISKS
      // Emergency deliveries/complications
      $emergencyDeliveries = Delivery::where('facility_id', $this->facility_id)
        ->where('mod', 'CS') // Cesarean sections
        ->whereDate('created_at', '>=', Carbon::now()->subDays(7)) // about Last week
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

      // ATTENDANCE RISKS
      // Patients with no recent attendance (inactive for 30+ days)
      $recentAttendees = DailyAttendance::where('facility_id', $this->facility_id)
        ->whereDate('visit_date', '>=', Carbon::now()->subDays(30))
        ->distinct('user_id')
        ->pluck('user_id');

      $totalActivePatients = Antenatal::where('registration_facility_id', $this->facility_id)
        ->whereDate('edd', '>', Carbon::now()) // Still pregnant
        ->distinct('user_id')
        ->count('user_id');

      $inactivePatients = max(0, $totalActivePatients - $recentAttendees->count());

      if ($inactivePatients > 0) {
        $alerts[] = [
          'type' => 'warning',
          'title' => 'Inactive Patients',
          'message' => "{$inactivePatients} active patients haven't attended in 30+ days",
          'count' => $inactivePatients,
          'icon' => 'bx-user-x'
        ];
      }
    } catch (\Exception $e) {
      Log::error('Risk alerts calculation failed: ' . $e->getMessage());
      // Return empty alerts on error - should be triggering my toast notifications here too, I will come back later
    }

    return $alerts;
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

      $currentPeriod = $model::where($facilityColumn, $this->facility_id)
        ->where($dateColumn, '>=', $startDate)
        ->count();

      $previousStart = Carbon::now()->subDays($this->selectedTimeframe * 2);
      $previousPeriod = $model::where($facilityColumn, $this->facility_id)
        ->whereBetween($dateColumn, [$previousStart, $startDate])
        ->count();

      if ($previousPeriod == 0) {
        return $currentPeriod > 0 ? 100 : 0;
      }

      return round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 1);
    } catch (\Exception $e) {
      Log::error('Trend calculation failed: ' . $e->getMessage());
      return 0;
    }
  }

  private function calculateHighRiskCases()
  {
    try {

      // Includes: age (<18 or >35), heart disease, kidney disease, family hypertension,
      // bleeding, anemia (Hb <11), sickle cell (genotype with S), and hypertension (BP >= 140/90)
      return Antenatal::where('registration_facility_id', $this->facility_id)
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
      Log::error('High-risk cases calculation failed: ' . $e->getMessage());
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
  }

  // Method to reload the page, I may remove the other one later
  public function forceRefresh()
  {
    $cacheKey = "dashboard_data_{$this->facility_id}_{$this->selectedTimeframe}_{$this->selectedRegister}_*";
    Cache::forget($cacheKey);
    $this->js('window.location.reload()');
  }

  public function refreshData()
  {
    $cacheKey = "dashboard_data_{$this->facility_id}_{$this->selectedTimeframe}_{$this->selectedRegister}_" . now()->format('YmdH');
    Cache::forget($cacheKey);
    $this->loadDashboardData();
    toastr()->info('Dashboard data refreshed successfully.');
  }



  public function render()
  {
    return view('livewire.core.facility-admin-dashboard')
      ->layout('layouts.facilityAdminLayout');
  }
}
