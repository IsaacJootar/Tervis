<?php

namespace App\Livewire\Analytics;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use App\Models\Facility;
use App\Models\Delivery;
use App\Models\InpatientAdmission;
use App\Models\Antenatal;
use App\Models\ClinicalNote;
use App\Models\DoctorAssessment;
use App\Models\LabTest;
use App\Models\ChildHealthActivityRecord;
use App\Models\ImmunizationRecord;
use App\Models\NutritionRecord;
use App\Models\PostnatalRecord;
use App\Models\DailyAttendance;
use App\Services\DataScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\TetanusVaccination;

class MonthlyReportDashboard extends Component
{
  public $scopeInfo = [];
  public $selectedFacilityId = null;
  public $availableFacilities = [];
  public $selectedMonth;
  public $selectedYear;
  public $reportData = [];
  public $facilityInfo = [];
  public $summaryStats = [];
  public $reportGenerated = false; // Track if report has been generated

  protected $scopeService;

  public function boot(DataScopeService $scopeService)
  {
    $this->scopeService = $scopeService;
  }

  public function mount()
  {
    $this->scopeInfo = $this->scopeService->getUserScope();

    // Load facilities for dropdown
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

    // Default to current month
    $this->selectedMonth = Carbon::now()->format('m');
    $this->selectedYear = Carbon::now()->format('Y');

    // Don't load data until user clicks generate
  }

  public function generateReport()
  {
    $this->reportGenerated = true;
    $this->loadReportData();
    toastr()->info('Report generated successfully');
  }

  public function updatedSelectedMonth()
  {
    $this->reportGenerated = false; // Reset when filters change
  }

  public function updatedSelectedYear()
  {
    $this->reportGenerated = false; // Reset when filters change
  }

  public function selectFacility($facilityId)
  {
    $this->selectedFacilityId = $facilityId;
    $this->loadReportData();
  }

  public function resetToScope()
  {
    $this->selectedFacilityId = null;
    $this->loadReportData();
  }

  public function loadReportData()
  {
    try {
      $facilityIds = $this->selectedFacilityId
        ? [$this->selectedFacilityId]
        : $this->scopeInfo['facility_ids'];

      $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
      $endDate = $startDate->copy()->endOfMonth();

      // Load facility info
      $this->loadFacilityInfo($facilityIds);

      // Section 1: Health Facility Attendance
      $this->reportData['attendance'] = $this->getAttendanceData($facilityIds, $startDate, $endDate);

      // Section 2: Inpatient Care
      $this->reportData['inpatient'] = $this->getInpatientData($facilityIds, $startDate, $endDate);

      // Section 3: Mortality
      $this->reportData['mortality'] = $this->getMortalityData($facilityIds, $startDate, $endDate);

      // Section 4 & 5: Maternal Health
      $this->reportData['maternal'] = $this->getMaternalHealthData($facilityIds, $startDate, $endDate);

      // Section 6: Newborn Health
      $this->reportData['newborn'] = $this->getNewbornData($facilityIds, $startDate, $endDate);

      // Section 7: Immunization
      $this->reportData['immunization'] = $this->getImmunizationData($facilityIds, $startDate, $endDate);

      // Section 8: Child Health
      $this->reportData['child_health'] = $this->getChildHealthData($facilityIds, $startDate, $endDate);

      // Section 9: Other Services
      $this->reportData['other_services'] = $this->getOtherServicesData($facilityIds, $startDate, $endDate);

      // Summary statistics
      $this->calculateSummaryStats();
    } catch (\Exception $e) {
      Log::error('Monthly report data loading failed: ' . $e->getMessage());
      toastr()->error('Failed to load report data');
    }
  }

  private function loadFacilityInfo($facilityIds)
  {
    if (count($facilityIds) === 1) {
      $facility = Facility::find($facilityIds[0]);
      $this->facilityInfo = [
        'name' => $facility->name,
        'state' => $facility->state,
        'lga' => $facility->lga,
        'ward' => $facility->ward,
        'type' => 'Public', // You can add this to facilities table
        'beds' => 0 // You can add this to facilities table
      ];
    } else {
      $this->facilityInfo = [
        'name' => count($facilityIds) . ' Facilities (Aggregated)',
        'state' => $this->scopeInfo['scope_type'] === 'state' ? Auth::user()->state->name : 'Multiple',
        'lga' => $this->scopeInfo['scope_type'] === 'lga' ? Auth::user()->lga->name : 'Multiple',
        'ward' => 'Multiple',
        'type' => 'Multiple',
        'beds' => 0
      ];
    }
  }

  // ============================================================================
  // SECTION 1: HEALTH FACILITY ATTENDANCE (Fields 1-2)
  // ============================================================================
  private function getAttendanceData($facilityIds, $startDate, $endDate)
  {
    $attendanceData = [];

    // General and Outpatient Attendance by age groups and gender
    $attendances = DailyAttendance::whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get();

    // Initialize counters
    $ageGroups = [
      '0-28d' => [0, 28],
      '29d-11m' => [29, 335],
      '12-59m' => [1, 4],
      '5-9y' => [5, 9],
      '10-19y' => [10, 19],
      '20+' => [20, 150]
    ];

    foreach (['general', 'outpatient'] as $type) {
      foreach (['male', 'female'] as $gender) {
        foreach (array_keys($ageGroups) as $group) {
          $attendanceData["{$type}_{$gender}_{$group}"] = 0;
        }
      }
    }

    foreach ($attendances as $attendance) {
      $age = Carbon::parse($attendance->date_of_birth)->age;
      $ageDays = Carbon::parse($attendance->date_of_birth)->diffInDays(Carbon::now());

      $ageGroup = $this->getAgeGroup($age, $ageDays);
      $gender = strtolower($attendance->gender);

      if ($ageGroup && in_array($gender, ['male', 'female'])) {
        $attendanceData["general_{$gender}_{$ageGroup}"]++;
        $attendanceData["outpatient_{$gender}_{$ageGroup}"]++; // Same for now, can differentiate
      }
    }

    return $attendanceData;
  }

  // ============================================================================
  // SECTION 2: INPATIENT CARE (Fields 3-4)
  // ============================================================================
  private function getInpatientData($facilityIds, $startDate, $endDate)
  {
    $inpatientData = [];

    $admissions = InpatientAdmission::query()
      ->with(['patient:id,gender,date_of_birth'])
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('admitted_at', [$startDate, $endDate])
      ->get();

    $discharges = InpatientAdmission::query()
      ->with(['patient:id,gender,date_of_birth'])
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('discharged_at', [$startDate, $endDate])
      ->whereIn('status', ['discharged', 'referred'])
      ->get();

    foreach (['admissions', 'discharges'] as $type) {
      foreach (['male', 'female'] as $gender) {
        foreach (['0-28d', '29d-11m', '12-59m', '5-9y', '10-19y', '20+'] as $group) {
          $inpatientData["{$type}_{$gender}_{$group}"] = 0;
        }
      }
    }

    foreach ($admissions as $record) {
      $patient = $record->patient;
      if (!$patient || !$patient->date_of_birth) {
        continue;
      }

      $age = Carbon::parse($patient->date_of_birth)->age;
      $ageDays = Carbon::parse($patient->date_of_birth)->diffInDays(Carbon::now());
      $ageGroup = $this->getAgeGroup($age, $ageDays);
      $gender = strtolower((string) $patient->gender);

      if ($ageGroup && in_array($gender, ['male', 'female'], true)) {
        $inpatientData["admissions_{$gender}_{$ageGroup}"]++;
      }
    }

    foreach ($discharges as $record) {
      $patient = $record->patient;
      if (!$patient || !$patient->date_of_birth) {
        continue;
      }

      $age = Carbon::parse($patient->date_of_birth)->age;
      $ageDays = Carbon::parse($patient->date_of_birth)->diffInDays(Carbon::now());
      $ageGroup = $this->getAgeGroup($age, $ageDays);
      $gender = strtolower((string) $patient->gender);

      if ($ageGroup && in_array($gender, ['male', 'female'], true)) {
        $inpatientData["discharges_{$gender}_{$ageGroup}"]++;
      }
    }

    $inpatientData['total_admissions'] = $admissions->count();
    $inpatientData['total_discharges'] = $discharges->count();

    return $inpatientData;
  }

  // ============================================================================
  // SECTION 3: MORTALITY (Fields 5-9)
  // ============================================================================
  private function getMortalityData($facilityIds, $startDate, $endDate)
  {
    $mortalityData = [];

    // Maternal deaths
    $maternalDeaths = Delivery::whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$startDate, $endDate])
      ->where('dead', 'yes')
      ->get();

    $mortalityData['maternal_deaths_10_19'] = 0;
    $mortalityData['maternal_deaths_20+'] = 0;
    $mortalityData['total_maternal_deaths'] = $maternalDeaths->count();

    // Maternal death causes (from complications field)
    $causes = [
      'pph' => 0,
      'sepsis' => 0,
      'obstructed' => 0,
      'abortion' => 0,
      'malaria' => 0,
      'anaemia' => 0,
      'hiv' => 0,
      'other' => 0
    ];

    foreach ($maternalDeaths as $death) {
      $complications = strtolower($death->complications ?? '');
      if (str_contains($complications, 'hemorrhage') || str_contains($complications, 'bleeding')) {
        $causes['pph']++;
      } elseif (str_contains($complications, 'sepsis') || str_contains($complications, 'infection')) {
        $causes['sepsis']++;
      } elseif (str_contains($complications, 'obstruct')) {
        $causes['obstructed']++;
      } elseif (str_contains($complications, 'abortion')) {
        $causes['abortion']++;
      } elseif (str_contains($complications, 'malaria')) {
        $causes['malaria']++;
      } elseif (str_contains($complications, 'anaemia') || str_contains($complications, 'anemia')) {
        $causes['anaemia']++;
      } elseif (str_contains($complications, 'hiv')) {
        $causes['hiv']++;
      } else {
        $causes['other']++;
      }
    }
    $mortalityData['maternal_death_causes'] = $causes;

    // Neonatal deaths
    $neonatalDeaths = Delivery::whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$startDate, $endDate])
      ->where('baby_dead', 'yes')
      ->get();

    $mortalityData['neonatal_deaths'] = $neonatalDeaths->count();
    $mortalityData['neonatal_causes'] = [
      'premature' => $neonatalDeaths->where('pre_term', 'yes')->count(),
      'tetanus' => 0, // Would need clinical notes
      'congenital' => 0,
      'other' => 0
    ];

    // Under-5 deaths (would need daily attendance with deaths)
    $mortalityData['under5_deaths'] = [
      'malaria' => 0,
      'pneumonia' => 0,
      'malnutrition' => 0,
      'other' => 0
    ];

    return $mortalityData;
  }

  // ============================================================================
  // SECTION 4: MATERNAL HEALTH - ANTENATAL CARE (Fields 10-33)
  // ============================================================================
  private function getMaternalHealthData($facilityIds, $startDate, $endDate)
  {
    $maternalData = [];

    // Antenatal attendance
    $antenatals = Antenatal::whereIn('registration_facility_id', $facilityIds)
      ->whereBetween('date_of_booking', [$startDate, $endDate])
      ->get();

    $ageGroups = [
      '10-14' => [10, 14],
      '15-19' => [15, 19],
      '20-35' => [20, 35],
      '35-49' => [35, 49],
      '50+' => [50, 100]
    ];

    foreach ($ageGroups as $group => $range) {
      $maternalData["anc_attendance_{$group}"] = $antenatals
        ->whereBetween('age', $range)->count();
    }

    // First visit by gestational age
    $maternalData['anc_first_before20wks'] = 0;
    $maternalData['anc_first_after20wks'] = 0;

    // Other ANC metrics
    $maternalData['4th_anc_visit'] = 0;
    $maternalData['8th_anc_visit'] = 0;
    $maternalData['ipt1'] = 0;
    $maternalData['ipt2'] = 0;
    $maternalData['ipt3'] = 0;

    // Delivery data
    $deliveries = Delivery::whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$startDate, $endDate])
      ->get();

    $maternalData['total_deliveries'] = $deliveries->count();
    $maternalData['cesarean_deliveries'] = $deliveries->where('mod', 'CS')->count();
    $maternalData['preterm_births'] = $deliveries->where('pre_term', 'yes')->count();
    $maternalData['partograph_used'] = $deliveries->where('partograph', 'yes')->count();
    $maternalData['skilled_attendant'] = $deliveries->whereIn('took_delivery', ['Doctor', 'Nurse', 'Midwife'])->count();
    $maternalData['oxytocin_received'] = $deliveries->where('oxytocin', 'yes')->count();
    $maternalData['decision_care_24hrs'] = $deliveries->where('seeking_care', 'less24')->count();
    $maternalData['delivery_complications'] = $deliveries->whereNotNull('complications')->count();

    return $maternalData;
  }

  // ============================================================================
  // SECTION 6: NEWBORN HEALTH (Fields 48-62)
  // ============================================================================
  private function getNewbornData($facilityIds, $startDate, $endDate)
  {
    $newbornData = [];

    $deliveries = Delivery::whereIn('facility_id', $facilityIds)
      ->whereBetween('dodel', [$startDate, $endDate])
      ->get();

    // Live births by weight and gender
    $liveBirths = $deliveries->where('alive', 'yes');

    $newbornData['live_births_m_low'] = $liveBirths->where('baby_sex', 'male')->where('weight', '<', 2.5)->count();
    $newbornData['live_births_m_normal'] = $liveBirths->where('baby_sex', 'male')->where('weight', '>=', 2.5)->count();
    $newbornData['live_births_f_low'] = $liveBirths->where('baby_sex', 'female')->where('weight', '<', 2.5)->count();
    $newbornData['live_births_f_normal'] = $liveBirths->where('baby_sex', 'female')->where('weight', '>=', 2.5)->count();
    $newbornData['total_live_births'] = $liveBirths->count();

    // Stillbirths
    $stillbirths = $deliveries->whereNotNull('still_birth');
    $newbornData['macerated_stillbirths'] = $stillbirths->where('still_birth', 'macerated')->count();
    $newbornData['fresh_stillbirths'] = $stillbirths->where('still_birth', 'fresh')->count();
    $newbornData['total_stillbirths'] = $stillbirths->count();

    // Immediate newborn care
    $newbornData['cord_clamped'] = $deliveries->where('clamped', 'yes')->count();
    $newbornData['chx_gel_applied'] = $deliveries->where('CKX_gel', 'yes')->count();
    $newbornData['skin_to_skin'] = $deliveries->where('breast', 'yes')->count();
    $newbornData['temp_taken'] = $deliveries->whereNotNull('temperature')->count();

    // Newborn complications
    $newbornData['not_breathing'] = $deliveries->where('breathing', 'yes')->count();
    $newbornData['resuscitated'] = 0; // Would need additional field
    $newbornData['newborn_care_provided'] = $deliveries->where('newborn_care', 'yes')->count();

    return $newbornData;
  }

  // ============================================================================
  // SECTION 7: IMMUNIZATION (Fields 63-97)
  // ============================================================================
  private function getImmunizationData($facilityIds, $startDate, $endDate)
  {
    $immunizationData = [
      'td_pregnant' => 0,
      'tt1' => 0,
      'tt2' => 0,
      'tt3' => 0,
      'tt4' => 0,
      'tt5' => 0,
      'bcg' => 0,
      'opv0' => 0,
      'opv1' => 0,
      'opv2' => 0,
      'opv3' => 0,
      'penta1' => 0,
      'penta2' => 0,
      'penta3' => 0,
      'pcv1' => 0,
      'pcv2' => 0,
      'pcv3' => 0,
      'ipv1' => 0,
      'ipv2' => 0,
      'mcv1' => 0,
      'mcv2' => 0,
      'yf' => 0,
      'hepb0' => 0,
      'rota1' => 0,
      'rota2' => 0,
      'rota3' => 0,
      'mena' => 0,
      'vita1' => 0,
      'vita2' => 0,
      'hpv' => 0,
    ];

    // Tetanus vaccinations
    $tetanus = TetanusVaccination::whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get();

    $immunizationData['td_pregnant'] = $tetanus->count();
    $immunizationData['tt1'] = $tetanus->where('current_tt_dose', 'TT1')->count();
    $immunizationData['tt2'] = $tetanus->where('current_tt_dose', 'TT2')->count();
    $immunizationData['tt3'] = $tetanus->where('current_tt_dose', 'TT3')->count();
    $immunizationData['tt4'] = $tetanus->where('current_tt_dose', 'TT4')->count();
    $immunizationData['tt5'] = $tetanus->where('current_tt_dose', 'TT5')->count();

    $seenDoseFingerprints = [];

    $immunizationFieldToKey = [
      'bcg_date' => 'bcg',
      'opv0_date' => 'opv0',
      'opv1_date' => 'opv1',
      'opv2_date' => 'opv2',
      'opv3_date' => 'opv3',
      'penta1_date' => 'penta1',
      'penta2_date' => 'penta2',
      'penta3_date' => 'penta3',
      'pcv1_date' => 'pcv1',
      'pcv2_date' => 'pcv2',
      'pcv3_date' => 'pcv3',
      'ipv1_date' => 'ipv1',
      'ipv2_date' => 'ipv2',
      'mr1_date' => 'mcv1',
      'mr2_date' => 'mcv2',
      'yf_date' => 'yf',
      'hepb0_date' => 'hepb0',
      'rota1_date' => 'rota1',
      'rota2_date' => 'rota2',
      'rota3_date' => 'rota3',
      'mena_date' => 'mena',
      'vita1_date' => 'vita1',
      'vita2_date' => 'vita2',
      'hpv_date' => 'hpv',
    ];

    $childImm = ImmunizationRecord::whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get();

    foreach ($childImm as $record) {
      foreach ($immunizationFieldToKey as $field => $metricKey) {
        $this->incrementUniqueDoseCount(
          $immunizationData,
          $seenDoseFingerprints,
          $metricKey,
          $record->patient_id,
          $record->linked_child_id,
          $record->{$field} ?? null
        );
      }
    }

    $activityFieldToKey = [
      'bcg' => 'bcg',
      'hepbo' => 'hepb0',
      'opv0' => 'opv0',
      'opv1' => 'opv1',
      'opv2' => 'opv2',
      'opv3' => 'opv3',
      'penta1' => 'penta1',
      'penta2' => 'penta2',
      'penta3' => 'penta3',
      'pcv1' => 'pcv1',
      'pcv2' => 'pcv2',
      'pcv3' => 'pcv3',
      'rota1' => 'rota1',
      'rota2' => 'rota2',
      'rota3' => 'rota3',
      'ipv1' => 'ipv1',
      'ipv2' => 'ipv2',
      'measles1' => 'mcv1',
      'measles2' => 'mcv2',
      'yfever' => 'yf',
      'mening' => 'mena',
      'vita1' => 'vita1',
      'vita2' => 'vita2',
    ];

    $activityRecords = ChildHealthActivityRecord::whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get(['patient_id', 'linked_child_id', 'vaccination_dates']);

    foreach ($activityRecords as $record) {
      $vaccinationDates = (array) ($record->vaccination_dates ?? []);
      foreach ($activityFieldToKey as $field => $metricKey) {
        $this->incrementUniqueDoseCount(
          $immunizationData,
          $seenDoseFingerprints,
          $metricKey,
          $record->patient_id,
          $record->linked_child_id,
          $vaccinationDates[$field] ?? null
        );
      }
    }

    return $immunizationData;
  }

  // ============================================================================
  // SECTION 8: CHILD HEALTH & NUTRITION (Fields 98-114)
  // ============================================================================
  private function getChildHealthData($facilityIds, $startDate, $endDate)
  {
    $childHealthData = [];

    $nutrition = NutritionRecord::whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get();

    $activityRecords = ChildHealthActivityRecord::whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get(['patient_id', 'linked_child_id', 'vaccination_dates', 'weight_entries', 'breastfeeding_entries', 'aefi_cases']);

    // MONTHLY SUMMARY MAPPING ANCHORS:
    // - `exclusive_breastfeeding`: union of Nutrition (0-5 months + Exclusive BF) and Activity Register E entries
    // - `sam_admissions`: muac_class=Red + admission_status=Admitted HP OTP
    // - `vitamin_a`: unique VitA1/VitA2 dose dates from Immunization + Activity Register
    // - `not_growing_well`: growth_status=Not Growing Well
    $childHealthData['birth_registrations'] = $activityRecords->pluck('linked_child_id')->filter()->unique()->count();

    $exclusiveChildren = [];
    foreach ($nutrition as $record) {
      if ($record->age_group === '0-5 months' && $record->infant_feeding === 'Exclusive BF') {
        $exclusiveChildren[$this->buildSubjectKey($record->patient_id, $record->linked_child_id)] = true;
      }
    }
    foreach ($activityRecords as $record) {
      $bfEntries = (array) ($record->breastfeeding_entries ?? []);
      $hasExclusive = collect($bfEntries)->contains(fn($value) => $value === 'E');
      if ($hasExclusive) {
        $exclusiveChildren[$this->buildSubjectKey($record->patient_id, $record->linked_child_id)] = true;
      }
    }

    $childHealthData['exclusive_breastfeeding'] = count($exclusiveChildren);
    $childHealthData['muac_screened'] = $nutrition->whereNotNull('muac_value_mm')->count();
    $childHealthData['sam_new_cases'] = $nutrition->where('muac_class', 'Red')->count();
    $childHealthData['mam_new_cases'] = $nutrition->where('muac_class', 'Yellow')->count();

    $vitaminASets = [];
    $this->collectVitaminADosesFromImmunizationRecords($vitaminASets, $facilityIds, $startDate, $endDate);
    foreach ($activityRecords as $record) {
      $dates = (array) ($record->vaccination_dates ?? []);
      $this->collectDoseFingerprint($vitaminASets, $record->patient_id, $record->linked_child_id, $dates['vita1'] ?? null);
      $this->collectDoseFingerprint($vitaminASets, $record->patient_id, $record->linked_child_id, $dates['vita2'] ?? null);
    }

    $childHealthData['vitamin_a'] = count($vitaminASets);
    $childHealthData['deworming'] = 0;
    $childHealthData['sam_admissions'] = $nutrition
      ->where('muac_class', 'Red')
      ->where('admission_status', 'Admitted HP OTP')
      ->count();
    $childHealthData['diarrhoea_cases'] = 0;
    $childHealthData['pneumonia_cases'] = 0;
    $childHealthData['mnp_given'] = $nutrition->where('mnp_given', true)->count();
    $childHealthData['not_growing_well'] = $nutrition->where('growth_status', 'Not Growing Well')->count();

    // Supplemental metrics from Vaccination Schedule register (not rendered in NHMIS rows yet).
    $childHealthData['weight_monitoring_entries'] = $activityRecords
      ->sum(fn($record) => count((array) ($record->weight_entries ?? [])));
    $childHealthData['aefi_reported_cases'] = $activityRecords
      ->sum(fn($record) => collect((array) ($record->aefi_cases ?? []))
        ->filter(fn($case) => !empty(trim((string) ($case['vaccine'] ?? ''))))
        ->count());

    return $childHealthData;
  }
  private function incrementUniqueDoseCount(array &$metrics, array &$seenFingerprints, string $metricKey, $patientId, $linkedChildId, $dateValue): void
  {
    $normalizedDate = $this->normalizeDateToKey($dateValue);
    if (!$normalizedDate) {
      return;
    }

    $fingerprint = implode('|', [
      $metricKey,
      $this->buildSubjectKey($patientId, $linkedChildId),
      $normalizedDate,
    ]);

    if (isset($seenFingerprints[$fingerprint])) {
      return;
    }

    $seenFingerprints[$fingerprint] = true;
    $metrics[$metricKey] = ($metrics[$metricKey] ?? 0) + 1;
  }

  private function collectVitaminADosesFromImmunizationRecords(array &$doseSet, array $facilityIds, $startDate, $endDate): void
  {
    $vitaminARecords = ImmunizationRecord::whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get(['patient_id', 'linked_child_id', 'vita1_date', 'vita2_date']);

    foreach ($vitaminARecords as $record) {
      $this->collectDoseFingerprint($doseSet, $record->patient_id, $record->linked_child_id, $record->vita1_date);
      $this->collectDoseFingerprint($doseSet, $record->patient_id, $record->linked_child_id, $record->vita2_date);
    }
  }

  private function collectDoseFingerprint(array &$doseSet, $patientId, $linkedChildId, $dateValue): void
  {
    $normalizedDate = $this->normalizeDateToKey($dateValue);
    if (!$normalizedDate) {
      return;
    }

    $doseSet[$this->buildSubjectKey($patientId, $linkedChildId) . '|' . $normalizedDate] = true;
  }

  private function buildSubjectKey($patientId, $linkedChildId): string
  {
    if (!empty($linkedChildId)) {
      return 'child:' . $linkedChildId;
    }

    return 'patient:' . ($patientId ?? 'unknown');
  }

  private function normalizeDateToKey($dateValue): ?string
  {
    if (empty($dateValue)) {
      return null;
    }

    if ($dateValue instanceof \DateTimeInterface) {
      return Carbon::instance($dateValue)->format('Y-m-d');
    }

    try {
      return Carbon::parse($dateValue)->format('Y-m-d');
    } catch (\Throwable $e) {
      return null;
    }
  }

  // ============================================================================
  // SECTION 9: OTHER HEALTH SERVICES (Fields 115-183)
  // ============================================================================
  private function getOtherServicesData($facilityIds, $startDate, $endDate)
  {
    $otherServicesData = [];

    // Family Planning (from postnatal records)
    $postnatal = PostnatalRecord::whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get();

    $otherServicesData['fp_counselled'] = $postnatal->where('family_planning', 'Counseled')->count();
    $otherServicesData['fp_acceptors'] = $postnatal->where('family_planning', 'Accepted')->count();
    $otherServicesData['modern_contraception'] = 0;
    $otherServicesData['postpartum_fp'] = $postnatal->whereIn('family_planning', ['Counseled', 'Accepted'])->count();

    // Doctor assessment + laboratory driven mapping for "Other Services".
    $labTests = LabTest::query()
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get(['clinician_diagnosis', 'report_values', 'mcs_results']);

    $doctorAssessments = DoctorAssessment::query()
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$startDate, $endDate])
      ->get(['final_diagnosis', 'assessment_note']);

    $containsAny = function ($text, array $needles): bool {
      $value = strtolower((string) $text);
      if ($value === '') {
        return false;
      }
      foreach ($needles as $needle) {
        if (str_contains($value, $needle)) {
          return true;
        }
      }
      return false;
    };

    $otherServicesData['malaria_tested'] = $labTests
      ->filter(fn($record) => in_array(data_get($record->report_values, 'mp'), ['Positive', 'Negative'], true))
      ->count();

    $otherServicesData['malaria_positive'] = $labTests
      ->where('report_values.mp', 'Positive')
      ->count();

    $malariaDiagnosedFromAssessments = $doctorAssessments
      ->filter(fn($record) => $containsAny($record->final_diagnosis, ['malaria']))
      ->count();

    $malariaMentionedInLabRequests = $labTests
      ->filter(fn($record) => $containsAny($record->clinician_diagnosis, ['malaria']))
      ->count();

    $otherServicesData['malaria_cases'] = $malariaDiagnosedFromAssessments + $malariaMentionedInLabRequests;

    $otherServicesData['tb_screening'] = $labTests
      ->filter(fn($record) => in_array(data_get($record->mcs_results, 'tb'), ['Positive', 'Negative'], true))
      ->count();

    $otherServicesData['hepb_tested'] = $labTests
      ->filter(fn($record) => in_array(data_get($record->mcs_results, 'hbsag'), ['Positive', 'Negative'], true))
      ->count();

    $otherServicesData['hepc_tested'] = $labTests
      ->filter(fn($record) => in_array(data_get($record->mcs_results, 'hcv'), ['Positive', 'Negative'], true))
      ->count();

    $otherServicesData['gbv_cases'] = $doctorAssessments
      ->filter(fn($record) => $containsAny(($record->final_diagnosis ?? '') . ' ' . ($record->assessment_note ?? ''), ['gbv', 'gender based violence']))
      ->count();

    $otherServicesData['diabetes_cases'] = 0;
    $otherServicesData['hypertension_cases'] = 0;

    return $otherServicesData;
  }

  // ============================================================================
  // SUMMARY STATISTICS & HELPER FUNCTIONS
  // ============================================================================
  private function calculateSummaryStats()
  {
    $this->summaryStats = [
      'total_attendance' => array_sum(array_filter($this->reportData['attendance'] ?? [], 'is_numeric')),
      'total_deliveries' => $this->reportData['maternal']['total_deliveries'] ?? 0,
      'cesarean_rate' => $this->reportData['maternal']['total_deliveries'] > 0
        ? round(($this->reportData['maternal']['cesarean_deliveries'] / $this->reportData['maternal']['total_deliveries']) * 100, 1)
        : 0,
      'maternal_deaths' => $this->reportData['mortality']['total_maternal_deaths'] ?? 0,
      'stillbirth_rate' => ($this->reportData['maternal']['total_deliveries'] ?? 0) > 0
        ? round((($this->reportData['newborn']['total_stillbirths'] ?? 0) / $this->reportData['maternal']['total_deliveries']) * 1000, 1)
        : 0,
      'total_vaccinations' => array_sum(array_filter($this->reportData['immunization'] ?? [], 'is_numeric')),
    ];
  }

  private function getAgeGroup($ageYears, $ageDays)
  {
    if ($ageDays <= 28) return '0-28d';
    if ($ageDays <= 335) return '29d-11m';
    if ($ageYears >= 1 && $ageYears <= 4) return '12-59m';
    if ($ageYears >= 5 && $ageYears <= 9) return '5-9y';
    if ($ageYears >= 10 && $ageYears <= 19) return '10-19y';
    if ($ageYears >= 20) return '20+';
    return null;
  }

  public function exportPDF()
  {
    // Redirect to print view
    return redirect()->route('analytics.monthly-report.print', [
      'facilityId' => $this->selectedFacilityId,
      'month' => $this->selectedMonth,
      'year' => $this->selectedYear
    ]);
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

    return view('livewire.analytics.monthly-report-dashboard', [
      'user' => $user
    ])->layout($layout);
  }
}

