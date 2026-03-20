<?php

namespace App\Livewire\Analytics;

use App\Models\Delivery;
use App\Models\Facility;
use App\Services\DataScopeService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

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
  public $totalDeaths = 0;
  public $maternalMortalityRatio = 0.0;
  public $perinatalMortalityRate = 0.0;
  public $reviewCoverageRate = 0.0;
  public $criticalIssuesCount = 0;

  // Detailed data
  public $maternalDeaths = [];
  public $perinatalDeaths = [];
  public $filteredMaternalDeaths = [];
  public $filteredPerinatalDeaths = [];
  public $deathsByFacility = [];
  public $deathsByCause = [];
  public $deathsByTimePeriod = [];
  public $surveillanceIssues = [];

  protected $scopeService;

  public function boot(DataScopeService $scopeService): void
  {
    $this->scopeService = $scopeService;
  }

  public function mount(): void
  {
    $this->scopeInfo = $this->scopeService->getUserScope();

    if (count($this->scopeInfo['facility_ids']) > 1) {
      $this->availableFacilities = Facility::query()
        ->whereIn('id', $this->scopeInfo['facility_ids'])
        ->orderBy('name')
        ->get(['id', 'name', 'lga', 'ward', 'state'])
        ->map(fn($facility) => [
          'id' => (int) $facility->id,
          'name' => (string) ($facility->name ?? '-'),
          'lga' => (string) ($facility->lga ?? '-'),
          'ward' => (string) ($facility->ward ?? '-'),
          'state' => (string) ($facility->state ?? '-'),
        ])->toArray();
    }

    $this->dateTo = now()->toDateString();
    $this->dateFrom = now()->subDays(90)->toDateString();

    $this->loadMPDSRData();
  }

  public function updatedDateFrom(): void
  {
    $this->loadMPDSRData();
  }

  public function updatedDateTo(): void
  {
    $this->loadMPDSRData();
  }

  public function updatedDeathType(): void
  {
    $this->loadMPDSRData();
  }

  public function updatedSelectedFacilityId(): void
  {
    $this->loadMPDSRData();
  }

  public function selectFacility($facilityId): void
  {
    $this->selectedFacilityId = $facilityId ?: null;
    $this->loadMPDSRData();
  }

  public function resetToScope(): void
  {
    $this->selectedFacilityId = null;
    $this->loadMPDSRData();
  }

  public function refreshData(): void
  {
    $this->loadMPDSRData();
    try {
      toastr()->info('MPDSR surveillance data refreshed.');
    } catch (\Throwable $e) {
      // Keep silent when toastr helper is unavailable in test/runtime.
    }
  }

  public function exportSurveillanceCsv()
  {
    $rows = collect($this->filteredMaternalDeaths)
      ->map(function ($item) {
        return [
          'record_type' => 'Maternal Death',
          'death_date' => $item['death_date'] ?? '',
          'patient_name' => $item['patient_name'] ?? '',
          'din' => $item['patient_din'] ?? '',
          'facility' => $item['facility_name'] ?? '',
          'lga' => $item['lga'] ?? '',
          'state' => $item['state'] ?? '',
          'probable_cause' => $item['probable_cause'] ?? '',
          'mode_of_delivery' => $item['mode_of_delivery'] ?? '',
          'place_of_death' => $item['place_of_death'] ?? '',
          'gestational_age_weeks' => $item['gestational_age'] ?? '',
          'baby_weight_kg' => '',
          'contributing_factors' => implode('; ', (array) ($item['contributing_factors'] ?? [])),
        ];
      })
      ->merge(
        collect($this->filteredPerinatalDeaths)->map(function ($item) {
          return [
            'record_type' => (string) ($item['death_type'] ?? 'Perinatal Death'),
            'death_date' => $item['death_date'] ?? '',
            'patient_name' => $item['mother_name'] ?? '',
            'din' => $item['mother_din'] ?? '',
            'facility' => $item['facility_name'] ?? '',
            'lga' => $item['lga'] ?? '',
            'state' => $item['state'] ?? '',
            'probable_cause' => $item['probable_cause'] ?? '',
            'mode_of_delivery' => $item['mode_of_delivery'] ?? '',
            'place_of_death' => '',
            'gestational_age_weeks' => $item['gestational_age'] ?? '',
            'baby_weight_kg' => $item['baby_weight'] ?? '',
            'contributing_factors' => '',
          ];
        })
      )
      ->values();

    if ($rows->isEmpty()) {
      try {
        toastr()->error('No surveillance records available for export.');
      } catch (\Throwable $e) {
      }
      return null;
    }

    $filename = 'mpdsr_surveillance_' . now()->format('Ymd_His') . '.csv';
    $headers = array_keys($rows->first());

    return response()->streamDownload(function () use ($rows, $headers) {
      $handle = fopen('php://output', 'w');
      fputcsv($handle, $headers);
      foreach ($rows as $row) {
        fputcsv($handle, array_map(fn($v) => is_array($v) ? json_encode($v) : $v, $row));
      }
      fclose($handle);
    }, $filename, ['Content-Type' => 'text/csv']);
  }

  public function openPrintableReview()
  {
    $this->persistPrintablePayload();
    return redirect()->route('mpdsr-report-dashboard-print');
  }

  private function getFacilityIds(): array
  {
    $scopeIds = array_values(array_filter(array_map('intval', (array) ($this->scopeInfo['facility_ids'] ?? []))));
    if ($this->selectedFacilityId) {
      return [(int) $this->selectedFacilityId];
    }
    return $scopeIds;
  }

  private function persistPrintablePayload(): void
  {
    $user = Auth::user();
    $officerName = trim((string) (($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));
    if ($officerName === '') {
      $officerName = (string) ($user->name ?? $user->email ?? 'System User');
    }

    session()->put('mpdsr_review_print_payload', [
      'title' => 'MPDSR Review Sheet',
      'subtitle' => 'Maternal and Perinatal Death Surveillance and Response',
      'scope_label' => $this->resolveScopeLabel(),
      'selected_facility_id' => $this->selectedFacilityId ? (int) $this->selectedFacilityId : null,
      'facility_ids' => $this->getFacilityIds(),
      'death_type' => $this->deathType,
      'date_from' => (string) $this->dateFrom,
      'date_to' => (string) $this->dateTo,
      'generated_at' => now()->format('Y-m-d H:i:s'),
      'generated_by' => $officerName,
      'generated_by_role' => (string) ($user->role ?? 'Officer'),
      'metrics' => [
        'total_maternal_deaths' => (int) $this->totalMaternalDeaths,
        'total_perinatal_deaths' => (int) $this->totalPerinatalDeaths,
        'total_stillbirths' => (int) $this->totalStillbirths,
        'total_neonatal_deaths' => (int) $this->totalNeonatalDeaths,
        'total_deaths' => (int) $this->totalDeaths,
        'maternal_mortality_ratio' => (float) $this->maternalMortalityRatio,
        'perinatal_mortality_rate' => (float) $this->perinatalMortalityRate,
        'review_coverage_rate' => (float) $this->reviewCoverageRate,
        'critical_issues_count' => (int) $this->criticalIssuesCount,
      ],
      'surveillance_issues' => array_values($this->surveillanceIssues),
      'deaths_by_facility' => array_values($this->deathsByFacility),
      'maternal_deaths' => array_values($this->filteredMaternalDeaths),
      'perinatal_deaths' => array_values($this->filteredPerinatalDeaths),
    ]);
  }

  private function resolveScopeLabel(): string
  {
    if ($this->selectedFacilityId) {
      $facility = Facility::query()
        ->where('id', (int) $this->selectedFacilityId)
        ->first(['id', 'name', 'lga', 'state']);

      if ($facility) {
        return 'Facility: ' . (string) $facility->name . ' (ID: ' . (int) $facility->id . ')';
      }
    }

    $facilityIds = array_values(array_filter(array_map('intval', (array) ($this->scopeInfo['facility_ids'] ?? []))));
    if (count($facilityIds) === 0) {
      return 'Current Facility Scope';
    }

    $facilities = Facility::query()
      ->whereIn('id', $facilityIds)
      ->orderBy('name')
      ->get(['id', 'name']);

    if ($facilities->isEmpty()) {
      return 'Facility Scope (' . implode(', ', $facilityIds) . ')';
    }

    $labels = $facilities->map(fn($facility) => 'Facility: ' . $facility->name . ' (ID: ' . $facility->id . ')')->values();
    if ($labels->count() <= 3) {
      return $labels->implode(', ');
    }

    return $labels->take(3)->implode(', ') . ' +' . ($labels->count() - 3) . ' more';
  }

  public function loadMPDSRData(): void
  {
    try {
      $facilityIds = $this->getFacilityIds();
      if (count($facilityIds) === 0) {
        $this->resetAnalyticsPayload();
        return;
      }

      if (empty($this->dateFrom) || empty($this->dateTo)) {
        return;
      }

      $start = Carbon::parse($this->dateFrom)->startOfDay();
      $end = Carbon::parse($this->dateTo)->endOfDay();
      if ($start->gt($end)) {
        [$start, $end] = [$end, $start];
      }
      $this->dateFrom = $start->toDateString();
      $this->dateTo = $end->toDateString();

      $deliveries = $this->fetchDeliveries($facilityIds, $start, $end);
      $this->buildDeathDatasets($deliveries);
      $this->applyDeathTypeFilter();

      $maternal = collect($this->filteredMaternalDeaths);
      $perinatal = collect($this->filteredPerinatalDeaths);
      $this->deathsByFacility = $this->buildDeathsByFacility($facilityIds, $maternal, $perinatal)->all();
      $this->deathsByCause = $this->buildDeathsByCause($maternal, $perinatal)->all();
      $this->deathsByTimePeriod = $this->buildDeathsByTimePeriod($start, $end, $maternal, $perinatal)->all();
      $this->surveillanceIssues = $this->buildSurveillanceIssues($maternal, $perinatal)->all();
      $this->criticalIssuesCount = collect($this->surveillanceIssues)->where('severity', 'High')->count();

      $this->dispatch('refresh-charts');
    } catch (\Throwable $e) {
      Log::error('MPDSR data loading failed', [
        'error' => $e->getMessage(),
        'facility_ids' => $this->getFacilityIds(),
        'date_from' => $this->dateFrom,
        'date_to' => $this->dateTo,
      ]);
      $this->resetAnalyticsPayload();
      try {
        toastr()->error('Failed to load MPDSR surveillance data.');
      } catch (\Throwable $ignored) {
      }
    }
  }

  private function fetchDeliveries(array $facilityIds, Carbon $start, Carbon $end): Collection
  {
    return Delivery::query()
      ->with([
        'patient:id,din,first_name,last_name,date_of_birth',
        'facility:id,name,lga,state',
      ])
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$start->toDateString(), $end->toDateString()])
      ->orderByDesc('dodel')
      ->get([
        'id',
        'patient_id',
        'facility_id',
        'dodel',
        'mod',
        'complications',
        'seeking_care',
        'transportation',
        'partograph',
        'oxytocin',
        'blood_loss',
        'gestational_age',
        'referred_out',
        'admitted',
        'dead',
        'alive',
        'still_birth',
        'baby_dead',
        'baby_sex',
        'weight',
        'pre_term',
        'breathing',
        'newborn_care',
      ]);
  }

  private function buildDeathDatasets(Collection $deliveries): void
  {
    $maternal = $deliveries->filter(fn($delivery) => $this->isYes($delivery->dead))
      ->map(function ($delivery) {
        $patient = $delivery->patient;
        $deathDate = $delivery->dodel ? Carbon::parse($delivery->dodel) : null;
        $facilityName = (string) ($delivery->facility->name ?? 'N/A');
        $lga = (string) ($delivery->facility->lga ?? 'N/A');
        $state = (string) ($delivery->facility->state ?? 'N/A');

        return [
          'id' => (int) $delivery->id,
          'facility_id' => (int) $delivery->facility_id,
          'patient_name' => trim((string) (($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''))),
          'patient_din' => (string) ($patient->din ?? 'N/A'),
          'age' => $this->resolveAge($patient?->date_of_birth, $deathDate),
          'facility_name' => $facilityName,
          'lga' => $lga,
          'state' => $state,
          'death_date' => $deathDate ? $deathDate->format('M d, Y') : '-',
          'death_timestamp' => $deathDate ? $deathDate->format('Y-m-d') : null,
          'mode_of_delivery' => (string) ($delivery->mod ?? 'Unknown'),
          'complications' => (string) ($delivery->complications ?? ''),
          'seeking_care_delay' => $this->isDelayedCare($delivery->seeking_care) ? 'Delayed (>24hrs)' : 'Timely (<24hrs)',
          'transportation' => (string) ($delivery->transportation ?? 'Unknown'),
          'partograph_used' => $this->isYes($delivery->partograph) ? 'Yes' : 'No',
          'oxytocin_used' => $this->isYes($delivery->oxytocin) ? 'Yes' : 'No',
          'blood_loss' => $delivery->blood_loss,
          'gestational_age' => $delivery->gestational_age,
          'referred_out' => $this->isYes($delivery->referred_out) ? 'Yes' : 'No',
          'place_of_death' => $this->determinePlaceOfDeath($delivery),
          'probable_cause' => $this->determineProbableCause($delivery),
          'contributing_factors' => $this->identifyContributingFactors($delivery),
        ];
      })->values();

    $perinatal = $deliveries
      ->filter(fn($delivery) => $this->isStillbirth($delivery->still_birth) || $this->isYes($delivery->baby_dead))
      ->map(function ($delivery) {
        $patient = $delivery->patient;
        $deathDate = $delivery->dodel ? Carbon::parse($delivery->dodel) : null;
        $isStillbirth = $this->isStillbirth($delivery->still_birth);
        $facilityName = (string) ($delivery->facility->name ?? 'N/A');
        $lga = (string) ($delivery->facility->lga ?? 'N/A');
        $state = (string) ($delivery->facility->state ?? 'N/A');

        return [
          'id' => (int) $delivery->id,
          'facility_id' => (int) $delivery->facility_id,
          'mother_name' => trim((string) (($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''))),
          'mother_din' => (string) ($patient->din ?? 'N/A'),
          'mother_age' => $this->resolveAge($patient?->date_of_birth, $deathDate),
          'facility_name' => $facilityName,
          'lga' => $lga,
          'state' => $state,
          'death_date' => $deathDate ? $deathDate->format('M d, Y') : '-',
          'death_timestamp' => $deathDate ? $deathDate->format('Y-m-d') : null,
          'death_type' => $isStillbirth ? 'Stillbirth' : 'Early Neonatal Death',
          'stillbirth_type' => (string) ($delivery->still_birth ?? 'N/A'),
          'baby_sex' => (string) ($delivery->baby_sex ?? 'Unknown'),
          'baby_weight' => $delivery->weight,
          'gestational_age' => $delivery->gestational_age,
          'pre_term' => $this->isYes($delivery->pre_term) ? 'Yes' : 'No',
          'pre_term_flag' => $this->isYes($delivery->pre_term),
          'breathing_at_birth' => $this->isYes($delivery->breathing) ? 'Not breathing/crying' : 'Breathing/crying',
          'breathing_flag' => $this->isYes($delivery->breathing),
          'mode_of_delivery' => (string) ($delivery->mod ?? 'Unknown'),
          'newborn_care_provided' => $this->isYes($delivery->newborn_care) ? 'Yes' : 'No',
          'newborn_care_flag' => $this->isYes($delivery->newborn_care),
          'complications' => (string) ($delivery->complications ?? ''),
          'probable_cause' => $this->determinePerinatalCause($delivery, $isStillbirth),
        ];
      })->values();

    $this->maternalDeaths = $maternal->all();
    $this->perinatalDeaths = $perinatal->all();

    $this->totalMaternalDeaths = $maternal->count();
    $this->totalPerinatalDeaths = $perinatal->count();
    $this->totalStillbirths = $perinatal->where('death_type', 'Stillbirth')->count();
    $this->totalNeonatalDeaths = $perinatal->where('death_type', 'Early Neonatal Death')->count();
    $this->totalDeaths = $this->totalMaternalDeaths + $this->totalPerinatalDeaths;

    $liveBirths = $deliveries->filter(fn($delivery) => !$this->isStillbirth($delivery->still_birth))->count();
    $totalBirths = max($deliveries->count(), 1);

    $this->maternalMortalityRatio = $liveBirths > 0
      ? round(($this->totalMaternalDeaths / $liveBirths) * 100000, 2)
      : 0.0;

    $this->perinatalMortalityRate = $totalBirths > 0
      ? round(($this->totalPerinatalDeaths / $totalBirths) * 1000, 2)
      : 0.0;

    $knownMaternal = $maternal->filter(fn($death) => ($death['probable_cause'] ?? '') !== 'Unknown/Under Investigation')->count();
    $knownPerinatal = $perinatal->filter(fn($death) => !str_contains(strtolower((string) ($death['probable_cause'] ?? '')), 'unknown'))->count();
    $this->reviewCoverageRate = $this->totalDeaths > 0
      ? round((($knownMaternal + $knownPerinatal) / $this->totalDeaths) * 100, 1)
      : 0.0;
  }

  private function applyDeathTypeFilter(): void
  {
    $maternal = collect($this->maternalDeaths);
    $perinatal = collect($this->perinatalDeaths);

    $this->filteredMaternalDeaths = match ($this->deathType) {
      'maternal' => $maternal->all(),
      'all' => $maternal->all(),
      default => [],
    };

    $this->filteredPerinatalDeaths = match ($this->deathType) {
      'all', 'perinatal' => $perinatal->all(),
      'stillbirth' => $perinatal->where('death_type', 'Stillbirth')->values()->all(),
      'neonatal' => $perinatal->where('death_type', 'Early Neonatal Death')->values()->all(),
      default => [],
    };
  }

  private function buildDeathsByFacility(array $facilityIds, Collection $maternal, Collection $perinatal): Collection
  {
    $maternalByFacility = $maternal->groupBy('facility_id')->map->count();
    $perinatalByFacility = $perinatal->groupBy('facility_id')->map->count();

    return Facility::query()
      ->whereIn('id', $facilityIds)
      ->get(['id', 'name', 'lga'])
      ->map(function ($facility) use ($maternalByFacility, $perinatalByFacility) {
        $maternalCount = (int) ($maternalByFacility[(int) $facility->id] ?? 0);
        $perinatalCount = (int) ($perinatalByFacility[(int) $facility->id] ?? 0);

        return [
          'facility_name' => (string) ($facility->name ?? '-'),
          'lga' => (string) ($facility->lga ?? '-'),
          'maternal_deaths' => $maternalCount,
          'perinatal_deaths' => $perinatalCount,
          'total_deaths' => $maternalCount + $perinatalCount,
        ];
      })
      ->sortByDesc('total_deaths')
      ->values();
  }

  private function buildDeathsByCause(Collection $maternal, Collection $perinatal): Collection
  {
    $causes = [];

    foreach ($maternal as $death) {
      $cause = (string) ($death['probable_cause'] ?? 'Unknown/Under Investigation');
      if (!isset($causes[$cause])) {
        $causes[$cause] = ['maternal' => 0, 'perinatal' => 0];
      }
      $causes[$cause]['maternal']++;
    }

    foreach ($perinatal as $death) {
      $cause = (string) ($death['probable_cause'] ?? 'Early Neonatal Death - Cause Unknown');
      if (!isset($causes[$cause])) {
        $causes[$cause] = ['maternal' => 0, 'perinatal' => 0];
      }
      $causes[$cause]['perinatal']++;
    }

    return collect($causes)->map(function ($counts, $cause) {
      return [
        'cause' => $cause,
        'maternal_count' => (int) $counts['maternal'],
        'perinatal_count' => (int) $counts['perinatal'],
        'total' => (int) $counts['maternal'] + (int) $counts['perinatal'],
      ];
    })->sortByDesc('total')->values();
  }

  private function buildDeathsByTimePeriod(Carbon $start, Carbon $end, Collection $maternal, Collection $perinatal): Collection
  {
    $bucket = $this->resolveTimeBucket($start, $end);
    $periods = [];

    foreach ($this->generatePeriodTimeline($start, $end, $bucket) as $key => $label) {
      $periods[$key] = [
        'period' => $label,
        'maternal_deaths' => 0,
        'perinatal_deaths' => 0,
      ];
    }

    foreach ($maternal as $death) {
      $date = $death['death_timestamp'] ?? null;
      if (!$date) {
        continue;
      }
      $key = $this->periodKey(Carbon::parse($date), $bucket);
      if (!isset($periods[$key])) {
        continue;
      }
      $periods[$key]['maternal_deaths']++;
    }

    foreach ($perinatal as $death) {
      $date = $death['death_timestamp'] ?? null;
      if (!$date) {
        continue;
      }
      $key = $this->periodKey(Carbon::parse($date), $bucket);
      if (!isset($periods[$key])) {
        continue;
      }
      $periods[$key]['perinatal_deaths']++;
    }

    return collect($periods)->values();
  }

  private function buildSurveillanceIssues(Collection $maternal, Collection $perinatal): Collection
  {
    $issues = [];

    foreach ($maternal as $death) {
      $factors = collect((array) ($death['contributing_factors'] ?? []))->map(fn($item) => strtolower((string) $item));
      $deathDate = (string) ($death['death_date'] ?? '-');
      $base = [
        'case_type' => 'Maternal',
        'patient_name' => (string) ($death['patient_name'] ?? '-'),
        'din' => (string) ($death['patient_din'] ?? '-'),
        'facility' => (string) ($death['facility_name'] ?? '-'),
        'death_date' => $deathDate,
      ];

      if ($factors->contains(fn($f) => str_contains($f, 'delayed'))) {
        $issues[] = $base + [
          'issue' => 'Delay in care-seeking detected',
          'severity' => 'High',
          'recommended_action' => 'Trigger community delay review and emergency transport response audit.',
        ];
      }

      if ($factors->contains(fn($f) => str_contains($f, 'partograph'))) {
        $issues[] = $base + [
          'issue' => 'Partograph not used in labor monitoring',
          'severity' => 'High',
          'recommended_action' => 'Immediate labor monitoring compliance review and supervision.',
        ];
      }

      if (($death['probable_cause'] ?? '') === 'Unknown/Under Investigation') {
        $issues[] = $base + [
          'issue' => 'Cause not yet established',
          'severity' => 'Medium',
          'recommended_action' => 'Complete MPDSR case review within 7 days and assign probable cause.',
        ];
      }
    }

    foreach ($perinatal as $death) {
      $base = [
        'case_type' => (string) ($death['death_type'] ?? 'Perinatal'),
        'patient_name' => (string) ($death['mother_name'] ?? '-'),
        'din' => (string) ($death['mother_din'] ?? '-'),
        'facility' => (string) ($death['facility_name'] ?? '-'),
        'death_date' => (string) ($death['death_date'] ?? '-'),
      ];

      if (!empty($death['pre_term_flag'])) {
        $issues[] = $base + [
          'issue' => 'Prematurity-associated death risk',
          'severity' => 'High',
          'recommended_action' => 'Review antenatal corticosteroid coverage and neonatal stabilization pathway.',
        ];
      }

      if (!empty($death['breathing_flag'])) {
        $issues[] = $base + [
          'issue' => 'Birth asphyxia signal detected',
          'severity' => 'High',
          'recommended_action' => 'Audit newborn resuscitation readiness and staff response timeline.',
        ];
      }

      $weight = (float) ($death['baby_weight'] ?? 0);
      if ($weight > 0 && $weight < 2.5) {
        $issues[] = $base + [
          'issue' => 'Low birth weight mortality signal',
          'severity' => 'Medium',
          'recommended_action' => 'Strengthen LBW protocol adherence and early neonatal monitoring.',
        ];
      }

      if (($death['newborn_care_provided'] ?? 'No') === 'No') {
        $issues[] = $base + [
          'issue' => 'No newborn care documented',
          'severity' => 'Medium',
          'recommended_action' => 'Review immediate newborn care checklist completion for this case.',
        ];
      }
    }

    return collect($issues)
      ->sortByDesc(fn($item) => $this->severityWeight($item['severity'] ?? 'Low'))
      ->take(80)
      ->values();
  }

  private function resolveAge($dateOfBirth, ?Carbon $referenceDate): string
  {
    if (empty($dateOfBirth) || !$referenceDate) {
      return 'Unknown';
    }

    try {
      return (string) Carbon::parse($dateOfBirth)->diffInYears($referenceDate);
    } catch (\Throwable $e) {
      return 'Unknown';
    }
  }

  private function resolveTimeBucket(Carbon $start, Carbon $end): string
  {
    $diffDays = $start->diffInDays($end);
    if ($diffDays <= 31) {
      return 'day';
    }
    if ($diffDays <= 120) {
      return 'week';
    }
    return 'month';
  }

  private function generatePeriodTimeline(Carbon $start, Carbon $end, string $bucket): array
  {
    $timeline = [];
    $cursor = $start->copy();

    while ($cursor->lte($end)) {
      $key = $this->periodKey($cursor, $bucket);
      $timeline[$key] = $this->periodLabel($cursor, $bucket);

      if ($bucket === 'day') {
        $cursor->addDay();
      } elseif ($bucket === 'week') {
        $cursor->addWeek();
      } else {
        $cursor->addMonth();
      }
    }

    return $timeline;
  }

  private function periodKey(Carbon $date, string $bucket): string
  {
    return match ($bucket) {
      'day' => $date->format('Y-m-d'),
      'week' => $date->copy()->startOfWeek()->format('Y-m-d'),
      default => $date->format('Y-m'),
    };
  }

  private function periodLabel(Carbon $date, string $bucket): string
  {
    return match ($bucket) {
      'day' => $date->format('M d'),
      'week' => 'Week ' . $date->format('W, Y'),
      default => $date->format('M Y'),
    };
  }

  private function isYes($value): bool
  {
    $normalized = strtolower(trim((string) $value));
    return in_array($normalized, ['yes', 'y', 'true', '1'], true);
  }

  private function isDelayedCare($value): bool
  {
    $normalized = strtolower(trim((string) $value));
    return in_array($normalized, ['more24', 'more_24', '>24', 'delayed', 'yes'], true);
  }

  private function isStillbirth($value): bool
  {
    $normalized = strtolower(trim((string) $value));
    if ($normalized === '' || in_array($normalized, ['no', 'none', '0', 'false'], true)) {
      return false;
    }
    return true;
  }

  private function severityWeight(string $severity): int
  {
    return match (strtolower($severity)) {
      'high' => 3,
      'medium' => 2,
      default => 1,
    };
  }

  private function determinePlaceOfDeath(Delivery $delivery): string
  {
    if ($this->isYes($delivery->admitted)) {
      return 'Facility (Admitted)';
    }
    if ($this->isYes($delivery->referred_out)) {
      return 'Referred Out';
    }
    return 'Facility (Not Admitted)';
  }

  private function determineProbableCause(Delivery $delivery): string
  {
    $complications = strtolower((string) ($delivery->complications ?? ''));
    $mode = strtolower((string) ($delivery->mod ?? ''));
    $bloodLoss = (float) ($delivery->blood_loss ?? 0);

    if (str_contains($complications, 'hemorrhage') || str_contains($complications, 'bleeding') || $bloodLoss >= 1000) {
      return 'Postpartum Hemorrhage';
    }
    if (str_contains($complications, 'eclampsia') || str_contains($complications, 'pre-eclampsia') || str_contains($complications, 'hypertension')) {
      return 'Eclampsia/Pre-eclampsia';
    }
    if (str_contains($complications, 'sepsis') || str_contains($complications, 'infection')) {
      return 'Sepsis/Infection';
    }
    if ((str_contains($mode, 'cs') || str_contains($mode, 'caes')) && str_contains($complications, 'complication')) {
      return 'Complications of Cesarean Section';
    }
    if (str_contains($complications, 'rupture')) {
      return 'Uterine Rupture';
    }
    if ($this->isDelayedCare($delivery->seeking_care)) {
      return 'Delayed Care Seeking';
    }
    return 'Unknown/Under Investigation';
  }

  private function determinePerinatalCause(Delivery $delivery, bool $isStillbirth): string
  {
    $complications = strtolower((string) ($delivery->complications ?? ''));
    $gestationalAge = (int) ($delivery->gestational_age ?? 0);
    $weight = (float) ($delivery->weight ?? 0);

    if ($isStillbirth) {
      if ($this->isYes($delivery->breathing) || str_contains($complications, 'asphyxia')) {
        return 'Intrapartum Asphyxia';
      }
      if ($gestationalAge > 0 && $gestationalAge < 28) {
        return 'Extreme Prematurity';
      }
      if (str_contains($complications, 'cord') || str_contains($complications, 'placenta')) {
        return 'Placental/Cord Complications';
      }
      return 'Antepartum Stillbirth - Cause Unknown';
    }

    if ($this->isYes($delivery->pre_term) || ($gestationalAge > 0 && $gestationalAge < 37)) {
      return 'Complications of Prematurity';
    }
    if ($weight > 0 && $weight < 2.5) {
      return 'Low Birth Weight';
    }
    if ($this->isYes($delivery->breathing)) {
      return 'Birth Asphyxia';
    }
    if (str_contains($complications, 'sepsis') || str_contains($complications, 'infection')) {
      return 'Neonatal Sepsis';
    }
    return 'Early Neonatal Death - Cause Unknown';
  }

  private function identifyContributingFactors(Delivery $delivery): array
  {
    $factors = [];

    if ($this->isDelayedCare($delivery->seeking_care)) {
      $factors[] = 'Delayed care-seeking (>24 hours)';
    }
    if (!$this->isYes($delivery->partograph)) {
      $factors[] = 'Partograph not used';
    }
    if (strtolower(trim((string) $delivery->transportation)) === 'others') {
      $factors[] = 'Inadequate transportation';
    }
    if ($this->isYes($delivery->referred_out)) {
      $factors[] = 'Patient was referred out';
    }
    if (count($factors) === 0) {
      $factors[] = 'No obvious systemic factors identified';
    }

    return $factors;
  }

  private function resetAnalyticsPayload(): void
  {
    $this->totalMaternalDeaths = 0;
    $this->totalPerinatalDeaths = 0;
    $this->totalStillbirths = 0;
    $this->totalNeonatalDeaths = 0;
    $this->totalDeaths = 0;
    $this->maternalMortalityRatio = 0.0;
    $this->perinatalMortalityRate = 0.0;
    $this->reviewCoverageRate = 0.0;
    $this->criticalIssuesCount = 0;

    $this->maternalDeaths = [];
    $this->perinatalDeaths = [];
    $this->filteredMaternalDeaths = [];
    $this->filteredPerinatalDeaths = [];
    $this->deathsByFacility = [];
    $this->deathsByCause = [];
    $this->deathsByTimePeriod = [];
    $this->surveillanceIssues = [];
  }

  public function render()
  {
    $user = Auth::user();
    $layout = match (true) {
      in_array($user->role, ['State Data Administrator', 'State Administrator']) => 'layouts.stateOfficerLayout',
      in_array($user->role, ['LGA Officer', 'LGA Data Administrator', 'LGA Administrator']) => 'layouts.lgaOfficerLayout',
      in_array($user->role, ['Facility Administrator']) => 'layouts.facilityAdminLayout',
      default => 'layouts.lgaOfficerLayout'
    };

    return view('livewire.analytics.mpdsr-report-dashboard', [
      'user' => $user
    ])->layout($layout);
  }
}
