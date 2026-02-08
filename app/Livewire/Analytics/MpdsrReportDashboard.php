<?php

namespace App\Livewire\Analytics;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use App\Models\Facility;
use App\Models\Delivery;
use Illuminate\Support\Facades\DB;
use App\Services\DataScopeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\PostnatalRecord;
use App\Models\ClinicalNote;

class MpdsrReportDashboard extends Component
{
  public $scopeInfo = [];
  public $selectedFacilityId = null;
  public $availableFacilities = [];
  public $dateFrom;
  public $dateTo;
  public $deathType = 'all'; // all, maternal, perinatal, stillbirth, neonatal

  // Summary metrics
  public $totalMaternalDeaths = 0;
  public $totalPerinatalDeaths = 0;
  public $totalStillbirths = 0;
  public $totalNeonatalDeaths = 0;
  public $maternalMortalityRatio = 0;
  public $perinatalMortalityRate = 0;

  // Detailed data
  public $maternalDeaths = [];
  public $perinatalDeaths = [];
  public $deathsByFacility = [];
  public $deathsByCause = [];
  public $deathsByTimePeriod = [];

  protected $scopeService;

  public function boot(DataScopeService $scopeService)
  {
    $this->scopeService = $scopeService;
  }

  public function mount()
  {
    // Get user scope information
    $this->scopeInfo = $this->scopeService->getUserScope();

    // Load available facilities for dropdown (if multi-facility scope)
    if (count($this->scopeInfo['facility_ids']) > 1) {
      $this->availableFacilities = Facility::whereIn('id', $this->scopeInfo['facility_ids'])
        ->orderBy('name')
        ->get()
        ->map(function ($facility) {
          return [
            'id' => $facility->id,
            'name' => $facility->name,
            'lga' => $facility->lga,
            'ward' => $facility->ward,
            'state' => $facility->state
          ];
        })->toArray();
    }

    // Set default date range (last 90 days)
    $this->dateTo = Carbon::now()->format('Y-m-d');
    $this->dateFrom = Carbon::now()->subDays(90)->format('Y-m-d');

    $this->loadMPDSRData();

    Log::info('MPDSR Dashboard Initialized', [
      'user_id' => Auth::id(),
      'scope_type' => $this->scopeInfo['scope_type'],
      'facility_count' => count($this->scopeInfo['facility_ids'])
    ]);
  }

  public function updatedDateFrom()
  {
    $this->loadMPDSRData();
  }

  public function updatedDateTo()
  {
    $this->loadMPDSRData();
  }

  public function updatedDeathType()
  {
    $this->loadMPDSRData();
  }

  public function selectFacility($facilityId)
  {
    $this->selectedFacilityId = $facilityId;
    $this->loadMPDSRData();

    $facilityName = Facility::find($facilityId)->name ?? 'Unknown';
    toastr()->info("Viewing MPDSR data for {$facilityName}");
  }

  public function resetToScope()
  {
    $this->selectedFacilityId = null;
    $this->loadMPDSRData();

    $scopeLabel = $this->scopeInfo['scope_type'] === 'lga' ? 'LGA' : ($this->scopeInfo['scope_type'] === 'state' ? 'State' : 'Facility');
    toastr()->info("Viewing MPDSR data for entire {$scopeLabel}");
  }

  private function getFacilityIds()
  {
    return $this->selectedFacilityId
      ? [$this->selectedFacilityId]
      : $this->scopeInfo['facility_ids'];
  }

  public function loadMPDSRData()
  {
    try {
      $facilityIds = $this->getFacilityIds();

      // Load maternal deaths
      $this->loadMaternalDeaths($facilityIds);

      // Load perinatal deaths
      $this->loadPerinatalDeaths($facilityIds);

      // Calculate summary metrics
      $this->calculateSummaryMetrics($facilityIds);

      // Load analysis data
      $this->loadDeathsByFacility($facilityIds);
      $this->loadDeathsByCause($facilityIds);
      $this->loadDeathsByTimePeriod($facilityIds);
    } catch (\Exception $e) {
      Log::error('MPDSR data loading failed: ' . $e->getMessage());
      toastr()->error('Failed to load MPDSR data');
    }
  }

  private function loadMaternalDeaths($facilityIds)
  {
    $this->maternalDeaths = Delivery::with(['user', 'facility', 'state', 'lga'])
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$this->dateFrom, $this->dateTo])
      ->where('dead', 'yes')
      ->orderBy('dodel', 'desc')
      ->get()
      ->map(function ($delivery) {
        return [
          'id' => $delivery->id,
          'patient_name' => $delivery->user->first_name . ' ' . $delivery->user->last_name,
          'patient_din' => $delivery->user->DIN,
          'age' => $delivery->cl_sex ?? 'Unknown',
          'facility_name' => $delivery->facility->name ?? 'N/A',
          'lga' => $delivery->facility->lga ?? 'N/A',
          'state' => $delivery->facility->state ?? 'N/A',
          'death_date' => Carbon::parse($delivery->dodel)->format('M d, Y'),
          'death_timestamp' => $delivery->dodel,
          'mode_of_delivery' => $delivery->mod ?? 'Unknown',
          'complications' => $delivery->complications ?? 'Not recorded',
          'seeking_care_delay' => $delivery->seeking_care === 'more24' ? 'Delayed (>24hrs)' : 'Timely (<24hrs)',
          'transportation' => $delivery->transportation ?? 'Unknown',
          'partograph_used' => $delivery->partograph === 'yes' ? 'Yes' : 'No',
          'oxytocin_used' => $delivery->oxytocin === 'yes' ? 'Yes' : 'No',
          'blood_loss' => $delivery->blood_loss ?? 'Not recorded',
          'gestational_age' => $delivery->gestational_age ?? 'Unknown',
          'referred_out' => $delivery->referred_out === 'yes' ? 'Yes' : 'No',
          'place_of_death' => $this->determinePlaceOfDeath($delivery),
          'probable_cause' => $this->determineProbableCause($delivery),
          'contributing_factors' => $this->identifyContributingFactors($delivery),
        ];
      });

    $this->totalMaternalDeaths = $this->maternalDeaths->count();
  }

  private function loadPerinatalDeaths($facilityIds)
  {
    $this->perinatalDeaths = Delivery::with(['user', 'facility'])
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$this->dateFrom, $this->dateTo])
      ->where(function ($query) {
        $query->whereNotNull('still_birth')
          ->orWhere('baby_dead', 'yes');
      })
      ->orderBy('dodel', 'desc')
      ->get()
      ->map(function ($delivery) {
        $isStillbirth = !empty($delivery->still_birth);
        $isNeonatalDeath = $delivery->baby_dead === 'yes' && empty($delivery->still_birth);

        return [
          'id' => $delivery->id,
          'mother_name' => $delivery->user->first_name . ' ' . $delivery->user->last_name,
          'mother_din' => $delivery->user->DIN,
          'mother_age' => $delivery->cl_sex ?? 'Unknown',
          'facility_name' => $delivery->facility->name ?? 'N/A',
          'lga' => $delivery->facility->lga ?? 'N/A',
          'death_date' => Carbon::parse($delivery->dodel)->format('M d, Y'),
          'death_timestamp' => $delivery->dodel,
          'death_type' => $isStillbirth ? 'Stillbirth' : 'Early Neonatal Death',
          'stillbirth_type' => $delivery->still_birth ?? 'N/A',
          'baby_sex' => $delivery->baby_sex ?? 'Unknown',
          'baby_weight' => $delivery->weight ?? 'Not recorded',
          'gestational_age' => $delivery->gestational_age ?? 'Unknown',
          'pre_term' => $delivery->pre_term === 'yes' ? 'Yes' : 'No',
          'breathing_at_birth' => $delivery->breathing === 'yes' ? 'Not breathing/crying' : 'Normal',
          'mode_of_delivery' => $delivery->mod ?? 'Unknown',
          'newborn_care_provided' => $delivery->newborn_care === 'yes' ? 'Yes' : 'No',
          'temperature' => $delivery->temperature ?? 'Not recorded',
          'complications' => $delivery->complications ?? 'Not recorded',
          'probable_cause' => $this->determinePerinatalCause($delivery, $isStillbirth),
        ];
      });

    $this->totalPerinatalDeaths = $this->perinatalDeaths->count();
    $this->totalStillbirths = $this->perinatalDeaths->where('death_type', 'Stillbirth')->count();
    $this->totalNeonatalDeaths = $this->perinatalDeaths->where('death_type', 'Early Neonatal Death')->count();
  }

  private function calculateSummaryMetrics($facilityIds)
  {
    // Calculate Maternal Mortality Ratio (per 100,000 live births)
    $totalLiveBirths = Delivery::whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$this->dateFrom, $this->dateTo])
      ->where(function ($query) {
        $query->where('alive', 'yes')
          ->orWhereNull('still_birth');
      })
      ->count();

    $this->maternalMortalityRatio = $totalLiveBirths > 0
      ? round(($this->totalMaternalDeaths / $totalLiveBirths) * 100000, 2)
      : 0;

    // Calculate Perinatal Mortality Rate (per 1,000 births)
    $totalBirths = Delivery::whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$this->dateFrom, $this->dateTo])
      ->count();

    $this->perinatalMortalityRate = $totalBirths > 0
      ? round(($this->totalPerinatalDeaths / $totalBirths) * 1000, 2)
      : 0;
  }

  private function loadDeathsByFacility($facilityIds)
  {
    $maternalByFacility = Delivery::select('facility_id', DB::raw('COUNT(*) as count'))
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$this->dateFrom, $this->dateTo])
      ->where('dead', 'yes')
      ->groupBy('facility_id')
      ->get()
      ->keyBy('facility_id')
      ->map(fn($item) => $item->count);

    $perinatalByFacility = Delivery::select('facility_id', DB::raw('COUNT(*) as count'))
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$this->dateFrom, $this->dateTo])
      ->where(function ($query) {
        $query->whereNotNull('still_birth')
          ->orWhere('baby_dead', 'yes');
      })
      ->groupBy('facility_id')
      ->get()
      ->keyBy('facility_id')
      ->map(fn($item) => $item->count);

    $this->deathsByFacility = Facility::whereIn('id', $facilityIds)
      ->get()
      ->map(function ($facility) use ($maternalByFacility, $perinatalByFacility) {
        return [
          'facility_name' => $facility->name,
          'lga' => $facility->lga,
          'maternal_deaths' => $maternalByFacility[$facility->id] ?? 0,
          'perinatal_deaths' => $perinatalByFacility[$facility->id] ?? 0,
          'total_deaths' => ($maternalByFacility[$facility->id] ?? 0) + ($perinatalByFacility[$facility->id] ?? 0),
        ];
      })
      ->sortByDesc('total_deaths')
      ->values();
  }

  private function loadDeathsByCause($facilityIds)
  {
    $causes = [];

    // Maternal death causes
    foreach ($this->maternalDeaths as $death) {
      $cause = $death['probable_cause'];
      if (!isset($causes[$cause])) {
        $causes[$cause] = ['maternal' => 0, 'perinatal' => 0, 'type' => 'maternal'];
      }
      $causes[$cause]['maternal']++;
    }

    // Perinatal death causes
    foreach ($this->perinatalDeaths as $death) {
      $cause = $death['probable_cause'];
      if (!isset($causes[$cause])) {
        $causes[$cause] = ['maternal' => 0, 'perinatal' => 0, 'type' => 'perinatal'];
      }
      $causes[$cause]['perinatal']++;
    }

    $this->deathsByCause = collect($causes)->map(function ($data, $cause) {
      return [
        'cause' => $cause,
        'maternal_count' => $data['maternal'],
        'perinatal_count' => $data['perinatal'],
        'total' => $data['maternal'] + $data['perinatal'],
        'type' => $data['maternal'] > 0 ? 'maternal' : 'perinatal',
      ];
    })->sortByDesc('total')->values();
  }

  private function loadDeathsByTimePeriod($facilityIds)
  {
    $startDate = Carbon::parse($this->dateFrom);
    $endDate = Carbon::parse($this->dateTo);
    $diffInDays = $startDate->diffInDays($endDate);

    // Determine grouping based on date range
    if ($diffInDays <= 31) {
      $groupBy = 'day';
      $format = 'Y-m-d';
    } elseif ($diffInDays <= 90) {
      $groupBy = 'week';
      $format = 'Y-W';
    } else {
      $groupBy = 'month';
      $format = 'Y-m';
    }

    $maternalByPeriod = Delivery::selectRaw("DATE_FORMAT(dodel, '%Y-%m-%d') as period, COUNT(*) as count")
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$this->dateFrom, $this->dateTo])
      ->where('dead', 'yes')
      ->groupBy('period')
      ->get()
      ->keyBy('period')
      ->map(fn($item) => $item->count);

    $perinatalByPeriod = Delivery::selectRaw("DATE_FORMAT(dodel, '%Y-%m-%d') as period, COUNT(*) as count")
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$this->dateFrom, $this->dateTo])
      ->where(function ($query) {
        $query->whereNotNull('still_birth')
          ->orWhere('baby_dead', 'yes');
      })
      ->groupBy('period')
      ->get()
      ->keyBy('period')
      ->map(fn($item) => $item->count);

    // Generate all periods in range
    $periods = [];
    $currentDate = $startDate->copy();

    while ($currentDate <= $endDate) {
      $periodKey = $currentDate->format($format);
      $displayLabel = $this->formatPeriodLabel($currentDate, $groupBy);

      if (!isset($periods[$periodKey])) {
        $periods[$periodKey] = [
          'period' => $displayLabel,
          'maternal_deaths' => 0,
          'perinatal_deaths' => 0,
        ];
      }

      $dateKey = $currentDate->format('Y-m-d');
      $periods[$periodKey]['maternal_deaths'] += $maternalByPeriod[$dateKey] ?? 0;
      $periods[$periodKey]['perinatal_deaths'] += $perinatalByPeriod[$dateKey] ?? 0;

      $currentDate->addDay();
    }

    $this->deathsByTimePeriod = collect($periods)->values();
  }

  private function formatPeriodLabel($date, $groupBy)
  {
    switch ($groupBy) {
      case 'day':
        return $date->format('M d');
      case 'week':
        return 'Week ' . $date->format('W, Y');
      case 'month':
        return $date->format('M Y');
      default:
        return $date->format('M d, Y');
    }
  }

  private function determinePlaceOfDeath($delivery)
  {
    if ($delivery->admitted === 'yes') {
      return 'Facility (Admitted)';
    } elseif ($delivery->referred_out === 'yes') {
      return 'Referred Out';
    } else {
      return 'Facility (Not Admitted)';
    }
  }

  private function determineProbableCause($delivery)
  {
    // Analyze delivery record to determine probable cause
    $complications = strtolower($delivery->complications ?? '');
    $mod = $delivery->mod;
    $bloodLoss = $delivery->blood_loss;

    if (str_contains($complications, 'hemorrhage') || str_contains($complications, 'bleeding') || ($bloodLoss && $bloodLoss > 1000)) {
      return 'Postpartum Hemorrhage';
    } elseif (str_contains($complications, 'eclampsia') || str_contains($complications, 'hypertension')) {
      return 'Eclampsia/Pre-eclampsia';
    } elseif (str_contains($complications, 'sepsis') || str_contains($complications, 'infection')) {
      return 'Sepsis/Infection';
    } elseif ($mod === 'CS' && str_contains($complications, 'complication')) {
      return 'Complications of Cesarean Section';
    } elseif (str_contains($complications, 'rupture')) {
      return 'Uterine Rupture';
    } elseif ($delivery->seeking_care === 'more24') {
      return 'Delayed Care Seeking';
    } else {
      return 'Unknown/Under Investigation';
    }
  }

  private function determinePerinatalCause($delivery, $isStillbirth)
  {
    $complications = strtolower($delivery->complications ?? '');
    $gestationalAge = $delivery->gestational_age;
    $weight = $delivery->weight;

    if ($isStillbirth) {
      if (str_contains($complications, 'asphyxia') || $delivery->breathing === 'yes') {
        return 'Intrapartum Asphyxia';
      } elseif ($gestationalAge && $gestationalAge < 28) {
        return 'Extreme Prematurity';
      } elseif (str_contains($complications, 'cord') || str_contains($complications, 'placenta')) {
        return 'Placental/Cord Complications';
      } else {
        return 'Antepartum Stillbirth - Cause Unknown';
      }
    } else {
      // Early neonatal death
      if ($delivery->pre_term === 'yes' || ($gestationalAge && $gestationalAge < 37)) {
        return 'Complications of Prematurity';
      } elseif ($weight && $weight < 2.5) {
        return 'Low Birth Weight';
      } elseif ($delivery->breathing === 'yes') {
        return 'Birth Asphyxia';
      } elseif (str_contains($complications, 'sepsis') || str_contains($complications, 'infection')) {
        return 'Neonatal Sepsis';
      } else {
        return 'Early Neonatal Death - Cause Unknown';
      }
    }
  }

  private function identifyContributingFactors($delivery)
  {
    $factors = [];

    if ($delivery->seeking_care === 'more24') {
      $factors[] = 'Delayed care-seeking (>24 hours)';
    }

    if ($delivery->partograph === 'no') {
      $factors[] = 'Partograph not used';
    }

    if ($delivery->transportation === 'others') {
      $factors[] = 'Inadequate transportation';
    }

    if ($delivery->referred_out === 'yes') {
      $factors[] = 'Patient was referred';
    }

    if (empty($factors)) {
      $factors[] = 'No obvious systemic factors identified';
    }

    return $factors;
  }

  public function refreshData()
  {
    $this->loadMPDSRData();
    toastr()->info('MPDSR data refreshed successfully');
  }

  public function render()
  {
    $user = Auth::user();
    $layout = match (true) {
      in_array($user->role, ['State Data Administrator']) => 'layouts.stateOfficerLayout',
      in_array($user->role, ['LGA Officer']) => 'layouts.lgaOfficerLayout',
      in_array($user->role, ['Facility Administrator']) => 'layouts.facilityAdminLayout',
      default => 'layouts.lgaOfficerLayout'
    };

    return view('livewire.analytics.mpdsr-report-dashboard', [
      'user' => $user
    ])->layout($layout);
  }
}
