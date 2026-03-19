<?php

namespace App\Livewire\Core;

use App\Models\AntenatalFollowUpAssessment;
use App\Models\ChildHealthActivityRecord;
use App\Models\DailyAttendance;
use App\Models\Delivery;
use App\Models\DoctorAssessment;
use App\Models\DrugDispenseLine;
use App\Models\Facility;
use App\Models\FamilyPlanningFollowUp;
use App\Models\ImmunizationRecord;
use App\Models\InpatientAdmission;
use App\Models\Invoice;
use App\Models\LabTest;
use App\Models\NutritionRecord;
use App\Models\PostnatalRecord;
use App\Models\Prescription;
use App\Models\Referral;
use App\Models\Registrations\AntenatalRegistration;
use App\Models\Registrations\FamilyPlanningRegistration;
use App\Models\TetanusVaccination;
use App\Services\DataScopeService;
use App\Services\Reports\NhmisFieldValueResolver;
use App\Services\Reports\NhmisFieldRegistry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class FacilityReports extends Component
{
  public $scopeInfo = [];
  public $selectedFacilityId = null;
  public $availableFacilities = [];

  public $sections = [];
  public $selected_section = 'all';
  public $selected_report = '';
  public $date_from = '';
  public $date_to = '';

  public $report_catalog = [];
  public $report_title = '';
  public $report_description = '';
  public $source_route_name = null;
  public $source_route_url = null;

  public $result_columns = [];
  public $result_rows = [];
  public $show_results = false;
  public $result_count = 0;

  public $history_rows = [];
  public $feedback_message = '';
  public $feedback_type = 'info';
  public $cards_ready = false;
  public $card_reports_in_view = 0;
  public $card_generated_records = 0;
  public $card_facilities_in_scope = 0;
  public $card_date_window = '-';

  protected $scopeService;

  public function boot(DataScopeService $scopeService)
  {
    $this->scopeService = $scopeService;
  }

  public function mount()
  {
    $this->scopeInfo = $this->scopeService->getUserScope();

    if (count($this->scopeInfo['facility_ids']) > 1) {
      $this->availableFacilities = Facility::whereIn('id', $this->scopeInfo['facility_ids'])
        ->orderBy('name')
        ->get(['id', 'name', 'lga', 'ward', 'state'])
        ->toArray();
    }

    $this->sections = [
      'all' => 'All Sections',
      'nhmis' => 'NHMIS Monthly',
      'anc' => 'ANC',
      'family_planning' => 'Family Planning',
      'child_health' => 'Child Health',
      'clinical' => 'Clinical & Laboratory',
      'finance' => 'Invoices & Payments',
    ];

    $this->report_catalog = $this->buildReportCatalog();
    $this->selected_report = 'monthly_nhmis_summary';

    $this->date_from = Carbon::now()->startOfMonth()->format('Y-m-d');
    $this->date_to = Carbon::now()->format('Y-m-d');

    $this->history_rows = session()->get('reports_hub_history', []);
    $this->refreshCardStats();
  }

  public function updatedSelectedSection()
  {
    $filtered = $this->getFilteredReports();
    if (!isset($filtered[$this->selected_report])) {
      $this->selected_report = array_key_first($filtered) ?: '';
    }

    $this->show_results = false;
    $this->result_rows = [];
    $this->result_columns = [];
    $this->result_count = 0;
    $this->feedback_message = '';
    $this->refreshCardStats();
  }

  public function updatedSelectedReport()
  {
    $this->show_results = false;
    $this->result_rows = [];
    $this->result_columns = [];
    $this->result_count = 0;
    $this->feedback_message = '';
    $this->refreshCardStats();
  }

  public function selectFacility($facilityId)
  {
    $this->selectedFacilityId = $facilityId ?: null;
    $this->show_results = false;
    $this->result_rows = [];
    $this->result_columns = [];
    $this->result_count = 0;
    $this->feedback_message = '';
    $this->refreshCardStats();
  }

  public function resetToScope()
  {
    $this->selectedFacilityId = null;
    $this->show_results = false;
    $this->result_rows = [];
    $this->result_columns = [];
    $this->result_count = 0;
    $this->feedback_message = '';
    $this->refreshCardStats();
  }

  public function updatedDateFrom()
  {
    $this->refreshCardStats();
  }

  public function updatedDateTo()
  {
    $this->refreshCardStats();
  }

  public function generateReport()
  {
    if (empty($this->selected_report) || !isset($this->report_catalog[$this->selected_report])) {
      $this->notify('error', 'Please select a report to generate.');
      return;
    }

    if (empty($this->date_from) || empty($this->date_to)) {
      $this->notify('error', 'Please select both From and To dates.');
      return;
    }

    if (Carbon::parse($this->date_from)->gt(Carbon::parse($this->date_to))) {
      $this->notify('error', 'From date cannot be greater than To date.');
      return;
    }

    try {
      [$columns, $rows] = $this->runReport($this->selected_report);
      $definition = $this->report_catalog[$this->selected_report];

      $this->report_title = $definition['name'];
      $this->report_description = $definition['description'];
      $this->source_route_name = $definition['source_route_name'] ?? null;
      $this->source_route_url = $this->resolveRouteUrl($this->source_route_name);

      $this->result_columns = $columns;
      $this->result_rows = $rows;
      $this->result_count = count($rows);
      $this->show_results = true;

      $this->persistPrintablePayload($definition, $columns, $rows);
      $this->appendHistory($definition['name'], $this->result_count);
      $this->refreshCardStats();

      $this->notify('success', 'Report generated successfully.');
    } catch (\Throwable $e) {
      Log::error('Reports hub generation failed', [
        'report' => $this->selected_report,
        'error' => $e->getMessage(),
      ]);
      $this->notify('error', 'Unable to generate report. Please try again.');
    }
  }

  public function loadStatsCards()
  {
    $this->refreshCardStats();
    $this->cards_ready = true;
  }

  public function exportCurrentCsv()
  {
    if (!$this->show_results || empty($this->result_rows) || empty($this->result_columns)) {
      $this->notify('error', 'No generated report to export.');
      return;
    }

    $filename = 'report_' . str($this->selected_report)->slug('_') . '_' . now()->format('Ymd_His') . '.csv';
    $headers = array_map(fn($col) => $col['label'], $this->result_columns);

    return response()->streamDownload(function () use ($headers) {
      $handle = fopen('php://output', 'w');
      fputcsv($handle, $headers);

      foreach ($this->result_rows as $row) {
        $line = [];
        foreach ($this->result_columns as $column) {
          $value = $row[$column['key']] ?? '';
          if (is_array($value)) {
            $value = json_encode($value);
          }
          $line[] = $value;
        }
        fputcsv($handle, $line);
      }

      fclose($handle);
    }, $filename, ['Content-Type' => 'text/csv']);
  }

  private function notify(string $type, string $message): void
  {
    $this->feedback_type = $type === 'success' ? 'success' : 'danger';
    $this->feedback_message = $message;

    try {
      if ($type === 'success') {
        toastr()->success($message);
      } else {
        toastr()->error($message);
      }
    } catch (\Throwable $e) {
      // Keep UI feedback working even when flasher/toastr server hook is unavailable.
    }

    $this->dispatch('app-toast', type: $type, message: $message);
  }

  private function persistPrintablePayload(array $definition, array $columns, array $rows): void
  {
    $user = Auth::user();
    $officerName = trim((string) (($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));
    if ($officerName === '') {
      $officerName = (string) ($user->name ?? $user->email ?? 'System User');
    }
    $officerRole = (string) ($user->role ?? 'Officer');

    $normalizedRows = collect($rows)->map(function ($row) use ($columns) {
      $normalized = [];
      foreach ($columns as $column) {
        $key = $column['key'];
        $value = $row[$key] ?? '';
        if (is_array($value)) {
          $value = json_encode($value);
        }
        $normalized[$key] = (string) $value;
      }
      return $normalized;
    })->toArray();

    $summaryKeyValues = [];
    if ($this->selected_report === 'monthly_nhmis_summary') {
      $summaryKeyValues = $this->buildNhmisSummaryKeyValues($this->getFacilityIds(), $this->date_from, $this->date_to);
    }

    session()->put('reports_hub_print_payload', [
      'title' => $definition['name'] ?? 'Facility Report',
      'report_key' => $this->selected_report,
      'description' => $definition['description'] ?? '',
      'section' => $definition['section'] ?? 'all',
      'section_label' => $this->sections[$this->selected_section] ?? ucfirst((string) ($definition['section'] ?? 'all')),
      'scope_label' => $this->resolveScopeLabel(),
      'facility_ids' => $this->getFacilityIds(),
      'selected_facility_id' => $this->selectedFacilityId ? (int) $this->selectedFacilityId : null,
      'generated_at' => now()->format('Y-m-d H:i:s'),
      'date_from' => $this->date_from,
      'date_to' => $this->date_to,
      'columns' => $columns,
      'rows' => $normalizedRows,
      'rows_count' => count($normalizedRows),
      'generated_by' => $officerName,
      'generated_by_role' => $officerRole,
      'summary_key_values' => $summaryKeyValues,
    ]);
  }

  private function resolveScopeLabel(): string
  {
    if ($this->selectedFacilityId) {
      $selected = collect($this->availableFacilities)->firstWhere('id', (int) $this->selectedFacilityId);
      if ($selected) {
        return 'Facility: ' . (string) ($selected['name'] ?? 'Selected Facility') . ' (ID: ' . (int) $selected['id'] . ')';
      }

      $facility = Facility::find((int) $this->selectedFacilityId);
      if ($facility) {
        return 'Facility: ' . (string) ($facility->name ?? 'Selected Facility') . ' (ID: ' . (int) $facility->id . ')';
      }
    }

    $scopeFacilityIds = array_values(array_filter(array_map('intval', (array) ($this->scopeInfo['facility_ids'] ?? []))));
    if (count($scopeFacilityIds) === 0) {
      return 'Current Tenant Scope';
    }

    $nameById = Facility::query()
      ->whereIn('id', $scopeFacilityIds)
      ->pluck('name', 'id')
      ->toArray();

    if (count($nameById) === 0) {
      return 'Tenant Facilities (' . implode(', ', $scopeFacilityIds) . ')';
    }

    $labeled = [];
    foreach ($scopeFacilityIds as $id) {
      $name = $nameById[$id] ?? null;
      if (!empty($name)) {
        $labeled[] = 'Facility: ' . $name . ' (ID: ' . $id . ')';
      }
    }

    if (count($labeled) <= 3) {
      return implode(', ', $labeled);
    }

    return implode(', ', array_slice($labeled, 0, 3)) . ' +' . (count($labeled) - 3) . ' more';
  }

  private function getFacilityIds(): array
  {
    return $this->selectedFacilityId ? [(int) $this->selectedFacilityId] : $this->scopeInfo['facility_ids'];
  }

  private function refreshCardStats(): void
  {
    $this->card_reports_in_view = count($this->getFilteredReports());
    $this->card_generated_records = (int) $this->result_count;
    $this->card_facilities_in_scope = count($this->scopeInfo['facility_ids'] ?? []);

    $from = $this->date_from ? Carbon::parse($this->date_from)->format('d M Y') : '-';
    $to = $this->date_to ? Carbon::parse($this->date_to)->format('d M Y') : '-';
    $this->card_date_window = "{$from} - {$to}";
  }

  private function getFilteredReports(): array
  {
    if ($this->selected_section === 'all') {
      return $this->report_catalog;
    }

    return collect($this->report_catalog)
      ->filter(fn($meta) => $meta['section'] === $this->selected_section)
      ->toArray();
  }

  private function buildReportCatalog(): array
  {
    return [
      'monthly_nhmis_summary' => [
        'name' => 'Monthly NHMIS Summary',
        'section' => 'nhmis',
        'description' => 'High-level NHMIS source totals for the selected date window.',
        'source_route_name' => 'monthly-report-dashboard',
      ],
      'daily_anc_register' => [
        'name' => 'Daily ANC Register',
        'section' => 'anc',
        'description' => 'ANC registrations captured during the selected period.',
      ],
      'anc_follow_up_register' => [
        'name' => 'ANC Follow-up Register',
        'section' => 'anc',
        'description' => 'Follow-up ANC assessment records and next return dates.',
      ],
      'daily_family_planning_register' => [
        'name' => 'Daily Family Planning Register',
        'section' => 'family_planning',
        'description' => 'Family planning baseline registrations and next appointments.',
      ],
      'child_immunization_register' => [
        'name' => 'Child Immunization Register',
        'section' => 'child_health',
        'description' => 'Routine child immunization entries with dose count summary.',
      ],
      'immunization_summary_register' => [
        'name' => 'Immunization Summary Register (Monthly)',
        'section' => 'child_health',
        'description' => 'Facility-level monthly immunization summary register layout.',
      ],
      'immunization_summary_tally' => [
        'name' => 'Immunization Summary Tally (Monthly)',
        'section' => 'child_health',
        'description' => 'Monthly immunization tally template layout with dose bands.',
      ],
      'nutrition_growth_register' => [
        'name' => 'Nutrition/Growth Monitoring Register',
        'section' => 'child_health',
        'description' => 'Nutrition growth monitoring records and MUAC/growth outcomes.',
      ],
      'vaccination_schedule_register' => [
        'name' => 'Vaccination Schedule Register',
        'section' => 'child_health',
        'description' => 'Child activity register tabs (vaccination, weight, breastfeeding, AEFI).',
      ],
      'laboratory_register' => [
        'name' => 'Laboratory Register',
        'section' => 'clinical',
        'description' => 'Patient laboratory entries recorded in the selected period.',
      ],
      'prescription_dispensing_register' => [
        'name' => 'Prescription/Dispensing Register',
        'section' => 'clinical',
        'description' => 'Prescribed and dispensed medication records by status.',
      ],
      'referrals_register' => [
        'name' => 'Referral Register',
        'section' => 'clinical',
        'description' => 'Patient referral records, outcomes, and completion status.',
      ],
      'invoices_payments_register' => [
        'name' => 'Invoices & Payments Register',
        'section' => 'finance',
        'description' => 'Invoice totals, paid/outstanding values, and billing status.',
      ],
    ];
  }

  private function runReport(string $reportKey): array
  {
    return match ($reportKey) {
      'monthly_nhmis_summary' => $this->reportMonthlyNhmisSummary(),
      'daily_anc_register' => $this->reportDailyAncRegister(),
      'anc_follow_up_register' => $this->reportAncFollowUpRegister(),
      'daily_family_planning_register' => $this->reportDailyFamilyPlanningRegister(),
      'child_immunization_register' => $this->reportChildImmunizationRegister(),
      'immunization_summary_register' => $this->reportImmunizationSummaryRegister(),
      'immunization_summary_tally' => $this->reportImmunizationSummaryTally(),
      'nutrition_growth_register' => $this->reportNutritionGrowthRegister(),
      'vaccination_schedule_register' => $this->reportVaccinationScheduleRegister(),
      'laboratory_register' => $this->reportLaboratoryRegister(),
      'prescription_dispensing_register' => $this->reportPrescriptionDispensingRegister(),
      'referrals_register' => $this->reportReferralsRegister(),
      'invoices_payments_register' => $this->reportInvoicesPaymentsRegister(),
      default => [[], []],
    };
  }

  private function reportMonthlyNhmisSummary(): array
  {
    $facilityIds = $this->getFacilityIds();
    /** @var NhmisFieldRegistry $registry */
    $registry = app(NhmisFieldRegistry::class);
    /** @var NhmisFieldValueResolver $valueResolver */
    $valueResolver = app(NhmisFieldValueResolver::class);
    $templateFields = $registry->loadOrSyncMatrix();
    $keyValues = $this->buildNhmisSummaryKeyValues($facilityIds, $this->date_from, $this->date_to);
    $resolvedValues = $valueResolver->resolveValues(
      $facilityIds,
      $this->date_from,
      $this->date_to,
      $keyValues,
      $templateFields
    );

    $columns = [
      ['key' => 'field_no', 'label' => 'Field No'],
      ['key' => 'field_name', 'label' => 'Field Name'],
      ['key' => 'value', 'label' => 'Value'],
    ];

    $rows = collect($templateFields)->map(function ($field) use ($keyValues, $resolvedValues) {
      $fieldNo = (int) ($field['field_no'] ?? 0);
      $keys = collect($field['summary_keys'] ?? [])->filter()->unique()->values()->toArray();
      if (count($keys) > 0) {
        $value = 0;
        foreach ($keys as $k) {
          $value += (int) ($keyValues[$k] ?? 0);
        }
      } else {
        $value = (int) ($resolvedValues[$fieldNo] ?? 0);
      }

      return [
        'field_no' => $fieldNo,
        'field_name' => $field['field_label'] ?? ('Field ' . (string) ($fieldNo ?: '')),
        'value' => $value,
      ];
    })->toArray();

    return [$columns, $rows];
  }

  private function buildNhmisSummaryKeyValues(array $facilityIds, string $from, string $to): array
  {
    $fromDate = Carbon::parse($from)->startOfDay();
    $toDate = Carbon::parse($to)->endOfDay();

    $immunization = ImmunizationRecord::query()
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$from, $to])
      ->get();

    $nutrition = NutritionRecord::query()
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$from, $to])
      ->get();

    $activity = ChildHealthActivityRecord::query()
      ->whereIn('facility_id', $facilityIds)
      ->whereBetween('visit_date', [$from, $to])
      ->get();

    $summary = [
      'immunization.bcg' => 0,
      'immunization.opv0' => 0,
      'immunization.opv1' => 0,
      'immunization.opv2' => 0,
      'immunization.opv3' => 0,
      'immunization.penta1' => 0,
      'immunization.penta2' => 0,
      'immunization.penta3' => 0,
      'immunization.pcv1' => 0,
      'immunization.pcv2' => 0,
      'immunization.pcv3' => 0,
      'immunization.ipv1' => 0,
      'immunization.ipv2' => 0,
      'immunization.mcv1' => 0,
      'immunization.mcv2' => 0,
      'immunization.yf' => 0,
      'immunization.hepb0' => 0,
      'immunization.hpv' => 0,
      'child_health.exclusive_breastfeeding' => 0,
      'child_health.muac_screened' => 0,
      'child_health.sam_new_cases' => 0,
      'child_health.mam_new_cases' => 0,
      'child_health.sam_admissions' => 0,
      'child_health.weight_monitoring_entries' => 0,
      'child_health.aefi_reported_cases' => 0,
      'pharmacy.prescriptions_total' => 0,
      'pharmacy.prescriptions_dispensed' => 0,
      'pharmacy.prescriptions_pending' => 0,
      'pharmacy.prescriptions_partial' => 0,
      'pharmacy.dispense_lines' => 0,
      'pharmacy.dispensed_quantity_total' => 0,
    ];

    $seenDoseFingerprints = [];
    $vaccineFieldMap = [
      'bcg_date' => 'immunization.bcg',
      'opv0_date' => 'immunization.opv0',
      'opv1_date' => 'immunization.opv1',
      'opv2_date' => 'immunization.opv2',
      'opv3_date' => 'immunization.opv3',
      'penta1_date' => 'immunization.penta1',
      'penta2_date' => 'immunization.penta2',
      'penta3_date' => 'immunization.penta3',
      'pcv1_date' => 'immunization.pcv1',
      'pcv2_date' => 'immunization.pcv2',
      'pcv3_date' => 'immunization.pcv3',
      'ipv1_date' => 'immunization.ipv1',
      'ipv2_date' => 'immunization.ipv2',
      'mr1_date' => 'immunization.mcv1',
      'mr2_date' => 'immunization.mcv2',
      'yf_date' => 'immunization.yf',
      'hepb0_date' => 'immunization.hepb0',
      'hpv_date' => 'immunization.hpv',
    ];

    foreach ($immunization as $row) {
      foreach ($vaccineFieldMap as $field => $summaryKey) {
        $this->incrementSummaryDose(
          $summary,
          $seenDoseFingerprints,
          $summaryKey,
          $row->patient_id,
          $row->linked_child_id,
          $row->{$field} ?? null,
          $fromDate,
          $toDate
        );
      }
    }

    $activityVaccineMap = [
      'bcg' => 'immunization.bcg',
      'hepbo' => 'immunization.hepb0',
      'opv0' => 'immunization.opv0',
      'opv1' => 'immunization.opv1',
      'opv2' => 'immunization.opv2',
      'opv3' => 'immunization.opv3',
      'penta1' => 'immunization.penta1',
      'penta2' => 'immunization.penta2',
      'penta3' => 'immunization.penta3',
      'pcv1' => 'immunization.pcv1',
      'pcv2' => 'immunization.pcv2',
      'pcv3' => 'immunization.pcv3',
      'ipv1' => 'immunization.ipv1',
      'ipv2' => 'immunization.ipv2',
      'measles1' => 'immunization.mcv1',
      'measles2' => 'immunization.mcv2',
      'yfever' => 'immunization.yf',
    ];

    foreach ($activity as $row) {
      $dates = (array) ($row->vaccination_dates ?? []);
      foreach ($activityVaccineMap as $field => $summaryKey) {
        $this->incrementSummaryDose(
          $summary,
          $seenDoseFingerprints,
          $summaryKey,
          $row->patient_id,
          $row->linked_child_id,
          $dates[$field] ?? null,
          $fromDate,
          $toDate
        );
      }
    }

    $exclusiveBySubject = [];
    foreach ($nutrition as $row) {
      if ($row->age_group === '0-5 months' && $row->infant_feeding === 'Exclusive BF') {
        $exclusiveBySubject[$this->buildSummarySubjectKey($row->patient_id, $row->linked_child_id)] = true;
      }
    }
    foreach ($activity as $row) {
      $hasExclusive = collect((array) ($row->breastfeeding_entries ?? []))
        ->contains(fn($value) => $value === 'E');
      if ($hasExclusive) {
        $exclusiveBySubject[$this->buildSummarySubjectKey($row->patient_id, $row->linked_child_id)] = true;
      }
    }

    $summary['child_health.exclusive_breastfeeding'] = count($exclusiveBySubject);
    $summary['child_health.muac_screened'] = $nutrition->whereNotNull('muac_value_mm')->count();
    $summary['child_health.sam_new_cases'] = $nutrition->where('muac_class', 'Red')->count();
    $summary['child_health.mam_new_cases'] = $nutrition->where('muac_class', 'Yellow')->count();
    $summary['child_health.sam_admissions'] = $nutrition->where('admission_status', 'Admitted HP OTP')->count();
    $summary['child_health.weight_monitoring_entries'] = $activity->sum(fn($row) => count((array) ($row->weight_entries ?? [])));
    $summary['child_health.aefi_reported_cases'] = $activity->sum(function ($row) {
      return collect((array) ($row->aefi_cases ?? []))
        ->filter(fn($case) => !empty(trim((string) ($case['vaccine'] ?? ''))))
        ->count();
    });

    if (Schema::hasTable('prescriptions')) {
      $prescriptions = Prescription::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('prescribed_date', [$from, $to])
        ->get(['status']);

      $summary['pharmacy.prescriptions_total'] = $prescriptions->count();
      $summary['pharmacy.prescriptions_dispensed'] = $prescriptions->where('status', 'dispensed')->count();
      $summary['pharmacy.prescriptions_pending'] = $prescriptions->where('status', 'pending')->count();
      $summary['pharmacy.prescriptions_partial'] = $prescriptions->where('status', 'partial')->count();
    }

    if (Schema::hasTable('drug_dispense_lines')) {
      $dispenseLines = DrugDispenseLine::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('dispensed_date', [$from, $to])
        ->get(['quantity']);

      $summary['pharmacy.dispense_lines'] = $dispenseLines->count();
      $summary['pharmacy.dispensed_quantity_total'] = (float) $dispenseLines->sum(fn($line) => (float) ($line->quantity ?? 0));
    }

    return $summary;
  }

  private function incrementSummaryDose(
    array &$metrics,
    array &$seenFingerprints,
    string $metricKey,
    $patientId,
    $linkedChildId,
    $dateValue,
    Carbon $fromDate,
    Carbon $toDate
  ): void {
    $normalizedDate = $this->normalizeSummaryDate($dateValue);
    if (!$normalizedDate) {
      return;
    }

    $doseDate = Carbon::parse($normalizedDate);
    if ($doseDate->lt($fromDate) || $doseDate->gt($toDate)) {
      return;
    }

    $fingerprint = implode('|', [
      $metricKey,
      $this->buildSummarySubjectKey($patientId, $linkedChildId),
      $normalizedDate,
    ]);

    if (isset($seenFingerprints[$fingerprint])) {
      return;
    }

    $seenFingerprints[$fingerprint] = true;
    $metrics[$metricKey] = (int) ($metrics[$metricKey] ?? 0) + 1;
  }

  private function buildSummarySubjectKey($patientId, $linkedChildId): string
  {
    if (!empty($linkedChildId)) {
      return 'child:' . $linkedChildId;
    }
    return 'patient:' . ($patientId ?? 'unknown');
  }

  private function normalizeSummaryDate($dateValue): ?string
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

  private function reportDailyAncRegister(): array
  {
    $rows = AntenatalRegistration::query()
      ->with(['patient:id,din,first_name,last_name,date_of_birth', 'facility:id,name'])
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('registration_date', [$this->date_from, $this->date_to])
      ->orderByDesc('registration_date')
      ->limit(3000)
      ->get()
      ->values()
      ->map(function ($item, $index) {
        $age = null;
        if (!empty($item->patient?->date_of_birth)) {
          $age = Carbon::parse($item->patient->date_of_birth)->age;
        } elseif (!empty($item->age)) {
          $age = (int) $item->age;
        }

        $kahn = strtolower((string) ($item->kahn_test ?? ''));
        $notes = strtolower((string) (($item->special_instructions ?? '') . ' ' . ($item->comments ?? '') . ' ' . ($item->special_points ?? '')));
        $urine = strtolower((string) ($item->urine_analysis ?? ''));
        $status = strtoupper((string) ($item->pregnancy_status ?? ''));

        return [
          'sn' => $index + 1,
          'date' => optional($item->registration_date)->format('Y-m-d'),
          'patient_name' => trim(($item->patient->first_name ?? '') . ' ' . ($item->patient->last_name ?? '')),
          'card_number' => $item->patient->din ?? '-',
          'age_10_14' => ($age !== null && $age >= 10 && $age <= 14) ? '1' : '',
          'age_15_19' => ($age !== null && $age >= 15 && $age <= 19) ? '1' : '',
          'age_20_34' => ($age !== null && $age >= 20 && $age <= 34) ? '1' : '',
          'age_35_49' => ($age !== null && $age >= 35 && $age <= 49) ? '1' : '',
          'age_50_plus' => ($age !== null && $age >= 50) ? '1' : '',
          'parity' => $item->parity ?? '-',
          'att_n' => ((int) ($item->pregnancy_number ?? 1) === 1) ? 'Y' : '',
          'att_r' => ((int) ($item->pregnancy_number ?? 1) > 1) ? 'Y' : '',
          'lmp' => optional($item->lmp)->format('Y-m-d'),
          'gest_age_weeks' => $item->gestational_age_weeks ?? '-',
          'weight_kg' => $item->weight ?? '-',
          'height_m' => $item->height ?? '-',
          'blood_pressure' => $item->blood_pressure ?? '-',
          'no_of_visits' => $item->pregnancy_number ?? 1,
          'counsel_hiv' => str_contains($notes, 'hiv') ? 'Y' : '',
          'counsel_fgm' => str_contains($notes, 'fgm') ? 'Y' : '',
          'counsel_fp' => str_contains($notes, 'family planning') ? 'Y' : '',
          'counsel_nutrition' => str_contains($notes, 'nutrition') ? 'Y' : '',
          'counsel_early_bf' => str_contains($notes, 'early bf') ? 'Y' : '',
          'counsel_excl_bf' => str_contains($notes, 'exclusive bf') ? 'Y' : '',
          'syphilis_nd' => $kahn === '' ? 'Y' : '',
          'syphilis_pos' => str_contains($kahn, 'pos') ? 'Y' : '',
          'syphilis_neg' => str_contains($kahn, 'neg') ? 'Y' : '',
          'syphilis_trt' => str_contains($notes, 'syphilis treated') ? 'Y' : '',
          'hepb_nd' => str_contains($notes, 'hepb') ? '' : 'Y',
          'hepb_pos' => str_contains($notes, 'hepb positive') ? 'Y' : '',
          'hepb_neg' => str_contains($notes, 'hepb negative') ? 'Y' : '',
          'hepb_ref' => str_contains($notes, 'hepb referral') ? 'Y' : '',
          'hepc_nd' => str_contains($notes, 'hepc') ? '' : 'Y',
          'hepc_pos' => str_contains($notes, 'hepc positive') ? 'Y' : '',
          'hepc_neg' => str_contains($notes, 'hepc negative') ? 'Y' : '',
          'hepc_ref' => str_contains($notes, 'hepc referral') ? 'Y' : '',
          'blood_hb_pcv' => $item->hemoglobin ?? '-',
          'blood_sugar' => str_contains($urine, 'sugar') ? 'Y' : '',
          'urine_sugar' => str_contains($urine, 'sugar') ? 'Y' : '',
          'urine_protein' => str_contains($urine, 'protein') ? 'Y' : '',
          'llin_given' => str_contains($notes, 'llin') ? 'Y' : '',
          'ipt1' => str_contains($notes, 'ipt1') ? 'Y' : '',
          'ipt2' => str_contains($notes, 'ipt2') ? 'Y' : '',
          'ipt3' => str_contains($notes, 'ipt3') ? 'Y' : '',
          'ipt4_plus' => str_contains($notes, 'ipt4') || str_contains($notes, 'ipt5') ? 'Y' : '',
          'hematinics' => !empty($item->hemoglobin) ? 'Y' : '',
          'td_dose' => '-',
          'problems' => $item->comments ?? '-',
          'outcome_nt' => $status === '' ? 'Y' : '',
          'outcome_t' => str_contains($status, 'TREATED') ? 'Y' : '',
          'outcome_a' => str_contains($status, 'ADMITTED') ? 'Y' : '',
          'outcome_ro' => str_contains($status, 'REFERRED') ? 'Y' : '',
        ];
      })->toArray();

    return [[
      ['key' => 'sn', 'label' => 'S/N'],
      ['key' => 'date', 'label' => 'Date'],
      ['key' => 'patient_name', 'label' => 'Patient Name'],
      ['key' => 'card_number', 'label' => 'Card Number'],
      ['key' => 'age_10_14', 'label' => 'Age 10-14'],
      ['key' => 'age_15_19', 'label' => 'Age 15-19'],
      ['key' => 'age_20_34', 'label' => 'Age 20-34'],
      ['key' => 'age_35_49', 'label' => 'Age 35-49'],
      ['key' => 'age_50_plus', 'label' => 'Age >=50'],
      ['key' => 'parity', 'label' => 'Parity'],
      ['key' => 'att_n', 'label' => 'Att N'],
      ['key' => 'att_r', 'label' => 'Att R'],
      ['key' => 'lmp', 'label' => 'LMP'],
      ['key' => 'gest_age_weeks', 'label' => 'Gest Age (Wks)'],
      ['key' => 'weight_kg', 'label' => 'Weight (kg)'],
      ['key' => 'height_m', 'label' => 'Height (m)'],
      ['key' => 'blood_pressure', 'label' => 'Blood Pressure'],
      ['key' => 'no_of_visits', 'label' => 'No. of Visits'],
      ['key' => 'counsel_hiv', 'label' => 'Counsel HIV'],
      ['key' => 'counsel_fgm', 'label' => 'Counsel FGM'],
      ['key' => 'counsel_fp', 'label' => 'Counsel FP'],
      ['key' => 'counsel_nutrition', 'label' => 'Counsel Nutrition'],
      ['key' => 'counsel_early_bf', 'label' => 'Counsel Early BF'],
      ['key' => 'counsel_excl_bf', 'label' => 'Counsel Excl BF'],
      ['key' => 'syphilis_nd', 'label' => 'Syphilis ND'],
      ['key' => 'syphilis_pos', 'label' => 'Syphilis Pos'],
      ['key' => 'syphilis_neg', 'label' => 'Syphilis Neg'],
      ['key' => 'syphilis_trt', 'label' => 'Syphilis Trt'],
      ['key' => 'hepb_nd', 'label' => 'HepB ND'],
      ['key' => 'hepb_pos', 'label' => 'HepB Pos'],
      ['key' => 'hepb_neg', 'label' => 'HepB Neg'],
      ['key' => 'hepb_ref', 'label' => 'HepB Ref'],
      ['key' => 'hepc_nd', 'label' => 'HepC ND'],
      ['key' => 'hepc_pos', 'label' => 'HepC Pos'],
      ['key' => 'hepc_neg', 'label' => 'HepC Neg'],
      ['key' => 'hepc_ref', 'label' => 'HepC Ref'],
      ['key' => 'blood_hb_pcv', 'label' => 'Blood HB/PCV'],
      ['key' => 'blood_sugar', 'label' => 'Blood Sugar'],
      ['key' => 'urine_sugar', 'label' => 'Urine Sugar'],
      ['key' => 'urine_protein', 'label' => 'Urine Protein'],
      ['key' => 'llin_given', 'label' => 'LLIN Given'],
      ['key' => 'ipt1', 'label' => 'IPT1'],
      ['key' => 'ipt2', 'label' => 'IPT2'],
      ['key' => 'ipt3', 'label' => 'IPT3'],
      ['key' => 'ipt4_plus', 'label' => 'IPT4+'],
      ['key' => 'hematinics', 'label' => 'Hematinics'],
      ['key' => 'td_dose', 'label' => 'TD Dose'],
      ['key' => 'problems', 'label' => 'Problems'],
      ['key' => 'outcome_nt', 'label' => 'Outcome NT'],
      ['key' => 'outcome_t', 'label' => 'Outcome T'],
      ['key' => 'outcome_a', 'label' => 'Outcome A'],
      ['key' => 'outcome_ro', 'label' => 'Outcome RO'],
    ], $rows];
  }

  private function reportAncFollowUpRegister(): array
  {
    $rows = AntenatalFollowUpAssessment::query()
      ->with(['patient:id,din,first_name,last_name', 'facility:id,name'])
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('visit_date', [$this->date_from, $this->date_to])
      ->orderByDesc('visit_date')
      ->limit(3000)
      ->get()
      ->map(fn($item) => [
        'visit_date' => optional($item->visit_date)->format('Y-m-d'),
        'din' => $item->patient->din ?? '-',
        'patient_name' => trim(($item->patient->first_name ?? '') . ' ' . ($item->patient->last_name ?? '')),
        'facility' => $item->facility->name ?? '-',
        'bp' => $item->bp ?? '-',
        'weight' => $item->weight ?? '-',
        'next_return_date' => optional($item->next_return_date)->format('Y-m-d'),
      ])->toArray();

    return [[
      ['key' => 'visit_date', 'label' => 'Visit Date'],
      ['key' => 'din', 'label' => 'DIN'],
      ['key' => 'patient_name', 'label' => 'Patient Name'],
      ['key' => 'facility', 'label' => 'Facility'],
      ['key' => 'bp', 'label' => 'BP'],
      ['key' => 'weight', 'label' => 'Weight (kg)'],
      ['key' => 'next_return_date', 'label' => 'Next Return Date'],
    ], $rows];
  }

  private function reportDailyFamilyPlanningRegister(): array
  {
    $rows = FamilyPlanningRegistration::query()
      ->with(['patient:id,din,first_name,last_name,phone,date_of_birth,gender', 'facility:id,name'])
      ->withCount('followUps')
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('registration_date', [$this->date_from, $this->date_to])
      ->orderByDesc('registration_date')
      ->limit(3000)
      ->get()
      ->values()
      ->map(function ($item, $index) {
        $method = strtolower((string) ($item->contraceptive_selected ?? ''));
        $age = $item->patient?->date_of_birth ? Carbon::parse($item->patient->date_of_birth)->age : null;
        $isRevisit = ((int) ($item->follow_ups_count ?? 0) > 0);

        $isOral = str_contains($method, 'pill') || str_contains($method, 'oral');
        $isInjectable = str_contains($method, 'inject');
        $isIucd = str_contains($method, 'iucd') || str_contains($method, 'iud');
        $isCondom = str_contains($method, 'condom');
        $isImplant = str_contains($method, 'implant');
        $isSterilizationM = str_contains($method, 'vasect');
        $isSterilizationF = str_contains($method, 'tubal') || str_contains($method, 'btl');
        $isNatural = str_contains($method, 'natural') || str_contains($method, 'cycle') || str_contains($method, 'lam');
        $isEmergency = str_contains($method, 'emergency');
        $isLam = str_contains($method, 'lam');
        $isCycle = str_contains($method, 'cycle');

        $referralSource = strtolower((string) ($item->referral_source ?? ''));

        return [
          'sn' => $index + 1,
          'date' => optional($item->registration_date)->format('Y-m-d'),
          'din' => $item->patient->din ?? '-',
          'patient_name' => trim(($item->patient->first_name ?? '') . ' ' . ($item->patient->last_name ?? '')),
          'client_card_number' => $item->client_reg_number ?? '-',
          'address_tel' => $item->patient->phone ?? '-',
          'sex' => $item->patient->gender ?? '-',
          'age_10_14' => ($age !== null && $age >= 10 && $age <= 14) ? '1' : '',
          'age_15_19' => ($age !== null && $age >= 15 && $age <= 19) ? '1' : '',
          'age_20_24' => ($age !== null && $age >= 20 && $age <= 24) ? '1' : '',
          'age_25_49' => ($age !== null && $age >= 25 && $age <= 49) ? '1' : '',
          'age_50_plus' => ($age !== null && $age >= 50) ? '1' : '',
          'weight_kg' => $item->weight ?? '-',
          'blood_pressure' => $item->blood_pressure ?? '-',
          'source_of_referral' => $item->referral_source ?? '-',
          'parity' => $item->children_born_alive ?? '-',
          'counselled_fp' => !empty($item->contraceptive_selected) ? 'Y' : '',
          'counselled_ppfp' => str_contains(strtolower((string) ($item->other_observations ?? '')), 'ppfp') ? 'Y' : '',
          'first_time_modern_user' => $item->prior_contraceptive === false ? 'Y' : '',
          'emergency_contra' => $isEmergency ? 'Y' : '',
          'type_client_na' => !$isRevisit ? 'Y' : '',
          'type_client_rv' => $isRevisit ? 'Y' : '',

          // Oral Pills
          'oral_name' => $isOral ? ($item->brand_size_model ?: 'YES') : '',
          'oral_na' => ($isOral && !$isRevisit) ? 'Y' : '',
          'oral_rv' => ($isOral && $isRevisit) ? 'Y' : '',

          // Injectable
          'injectable_name' => $isInjectable ? ($item->brand_size_model ?: 'YES') : '',
          'injectable_na' => ($isInjectable && !$isRevisit) ? 'Y' : '',
          'injectable_rv' => ($isInjectable && $isRevisit) ? 'Y' : '',
          'self_inj' => ($isInjectable && str_contains(strtolower((string) ($item->other_observations ?? '')), 'self')) ? 'Y' : '',

          // IUCDs
          'iucd_type' => $isIucd ? ($item->brand_size_model ?: 'IUCD') : '',
          'iucd_in' => $isIucd ? 'Y' : '',
          'iucd_na' => ($isIucd && !$isRevisit) ? 'Y' : '',
          'iucd_rv' => ($isIucd && $isRevisit) ? 'Y' : '',

          // Condoms
          'condom_type' => $isCondom ? 'Condom' : '',
          'condom_qty' => $isCondom ? ($item->cycle_duration ?: '1') : '',
          'condom_rv' => ($isCondom && $isRevisit) ? 'Y' : '',

          // Implants
          'implant_type' => $isImplant ? ($item->brand_size_model ?: 'Implant') : '',
          'implant_in' => $isImplant ? 'Y' : '',
          'implant_na' => ($isImplant && !$isRevisit) ? 'Y' : '',
          'implant_rv' => ($isImplant && $isRevisit) ? 'Y' : '',

          // Sterilization
          'sterilization_m' => $isSterilizationM ? 'Y' : '',
          'sterilization_f' => $isSterilizationF ? 'Y' : '',

          // Natural methods
          'natural_cycle' => $isCycle ? 'Y' : '',
          'natural_lam' => $isLam ? 'Y' : '',
          'natural_other' => ($isNatural && !$isCycle && !$isLam) ? 'Y' : '',

          // Referred (To)
          'referred_or' => str_contains($referralSource, 'oral') ? 'Y' : '',
          'referred_li' => str_contains($referralSource, 'inject') ? 'Y' : '',
          'referred_ip' => str_contains($referralSource, 'implant') ? 'Y' : '',
          'referred_iud' => (str_contains($referralSource, 'iud') || str_contains($referralSource, 'iucd')) ? 'Y' : '',
          'referred_sr' => str_contains($referralSource, 'steril') ? 'Y' : '',
          'referred_mi' => str_contains($referralSource, 'misc') ? 'Y' : '',

          // Extra captured fields to preserve full recorded context.
          'facility' => $item->facility->name ?? '-',
          'method_selected' => $item->contraceptive_selected ?? '-',
          'next_appointment' => optional($item->next_appointment)->format('Y-m-d'),
          'last_menstrual_period' => optional($item->last_menstrual_period)->format('Y-m-d'),
          'other_observations' => $item->other_observations ?? '-',
        ];
      })->toArray();

    return [[
      ['key' => 'sn', 'label' => 'S/N'],
      ['key' => 'date', 'label' => 'Date'],
      ['key' => 'din', 'label' => 'DIN'],
      ['key' => 'patient_name', 'label' => 'Patient Name'],
      ['key' => 'client_card_number', 'label' => 'Client Card No'],
      ['key' => 'address_tel', 'label' => 'Address/Tel'],
      ['key' => 'sex', 'label' => 'Sex'],
      ['key' => 'age_10_14', 'label' => 'Age 10-14'],
      ['key' => 'age_15_19', 'label' => 'Age 15-19'],
      ['key' => 'age_20_24', 'label' => 'Age 20-24'],
      ['key' => 'age_25_49', 'label' => 'Age 25-49'],
      ['key' => 'age_50_plus', 'label' => 'Age >=50'],
      ['key' => 'weight_kg', 'label' => 'Weight (kg)'],
      ['key' => 'blood_pressure', 'label' => 'Blood Pressure'],
      ['key' => 'source_of_referral', 'label' => 'Source of Referral'],
      ['key' => 'parity', 'label' => 'Parity'],
      ['key' => 'counselled_fp', 'label' => 'Counselled FP'],
      ['key' => 'counselled_ppfp', 'label' => 'Counselled PPFP'],
      ['key' => 'first_time_modern_user', 'label' => 'First Time Modern User'],
      ['key' => 'emergency_contra', 'label' => 'Emergency Contra'],
      ['key' => 'type_client_na', 'label' => 'Type Client NA'],
      ['key' => 'type_client_rv', 'label' => 'Type Client RV'],
      ['key' => 'oral_name', 'label' => 'Oral Name'],
      ['key' => 'oral_na', 'label' => 'Oral NA'],
      ['key' => 'oral_rv', 'label' => 'Oral RV'],
      ['key' => 'injectable_name', 'label' => 'Injectable Name'],
      ['key' => 'injectable_na', 'label' => 'Injectable NA'],
      ['key' => 'injectable_rv', 'label' => 'Injectable RV'],
      ['key' => 'self_inj', 'label' => 'Self Inj'],
      ['key' => 'iucd_type', 'label' => 'IUCD Type'],
      ['key' => 'iucd_in', 'label' => 'IUCD IN'],
      ['key' => 'iucd_na', 'label' => 'IUCD NA'],
      ['key' => 'iucd_rv', 'label' => 'IUCD RV'],
      ['key' => 'condom_type', 'label' => 'Condom Type'],
      ['key' => 'condom_qty', 'label' => 'Condom Qty'],
      ['key' => 'condom_rv', 'label' => 'Condom RV'],
      ['key' => 'implant_type', 'label' => 'Implant Type'],
      ['key' => 'implant_in', 'label' => 'Implant IN'],
      ['key' => 'implant_na', 'label' => 'Implant NA'],
      ['key' => 'implant_rv', 'label' => 'Implant RV'],
      ['key' => 'sterilization_m', 'label' => 'Sterilization M'],
      ['key' => 'sterilization_f', 'label' => 'Sterilization F'],
      ['key' => 'natural_cycle', 'label' => 'Natural Cycle'],
      ['key' => 'natural_lam', 'label' => 'Natural LAM'],
      ['key' => 'natural_other', 'label' => 'Natural Other'],
      ['key' => 'referred_or', 'label' => 'Referred OR'],
      ['key' => 'referred_li', 'label' => 'Referred LI'],
      ['key' => 'referred_ip', 'label' => 'Referred IP'],
      ['key' => 'referred_iud', 'label' => 'Referred IUD'],
      ['key' => 'referred_sr', 'label' => 'Referred SR'],
      ['key' => 'referred_mi', 'label' => 'Referred MI'],
      ['key' => 'facility', 'label' => 'Facility'],
      ['key' => 'method_selected', 'label' => 'Method Selected'],
      ['key' => 'next_appointment', 'label' => 'Next Appointment'],
      ['key' => 'last_menstrual_period', 'label' => 'Last Menstrual Period'],
      ['key' => 'other_observations', 'label' => 'Other Observations'],
    ], $rows];
  }

  private function reportChildImmunizationRegister(): array
  {
    $rows = ImmunizationRecord::query()
      ->with(['patient:id,din,first_name,last_name,date_of_birth,gender', 'linkedChild:id,first_name,last_name,date_of_birth,gender', 'facility:id,name'])
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('visit_date', [$this->date_from, $this->date_to])
      ->orderByDesc('visit_date')
      ->limit(3000)
      ->get()
      ->map(function ($item) {
        $childName = trim(($item->linkedChild->first_name ?? '') . ' ' . ($item->linkedChild->last_name ?? ''));
        if ($childName === '') {
          $childName = trim(($item->patient->first_name ?? '') . ' ' . ($item->patient->last_name ?? ''));
        }

        $childDob = $item->linkedChild?->date_of_birth ?? $item->patient?->date_of_birth;
        $childSex = $item->linkedChild?->gender ?? $item->patient?->gender;

        return [
          'visit_date' => optional($item->visit_date)->format('Y-m-d'),
          'child_name' => $childName ?: '-',
          'child_card_no' => $item->immunization_card_no ?? '-',
          'sex' => $childSex ?? '-',
          'follow_up_address' => $item->follow_up_address ?? '-',
          'phone_number' => $item->follow_up_phone ?? '-',
          'dob' => $childDob ? Carbon::parse($childDob)->format('Y-m-d') : '-',
          'hepb0' => optional($item->hepb0_date)->format('Y-m-d'),
          'opv0' => optional($item->opv0_date)->format('Y-m-d'),
          'bcg' => optional($item->bcg_date)->format('Y-m-d'),
          'opv1' => optional($item->opv1_date)->format('Y-m-d'),
          'penta1' => optional($item->penta1_date)->format('Y-m-d'),
          'pcv1' => optional($item->pcv1_date)->format('Y-m-d'),
          'rota1' => optional($item->rota1_date)->format('Y-m-d'),
          'opv2' => optional($item->opv2_date)->format('Y-m-d'),
          'penta2' => optional($item->penta2_date)->format('Y-m-d'),
          'pcv2' => optional($item->pcv2_date)->format('Y-m-d'),
          'rota2' => optional($item->rota2_date)->format('Y-m-d'),
          'opv3' => optional($item->opv3_date)->format('Y-m-d'),
          'penta3' => optional($item->penta3_date)->format('Y-m-d'),
          'pcv3' => optional($item->pcv3_date)->format('Y-m-d'),
          'ipv' => optional($item->ipv1_date)->format('Y-m-d'),
          'vita1' => optional($item->vita1_date)->format('Y-m-d'),
          'vita2' => optional($item->vita2_date)->format('Y-m-d'),
          'measles1' => optional($item->mr1_date)->format('Y-m-d'),
          'yellow_fever' => optional($item->yf_date)->format('Y-m-d'),
          'men_a' => optional($item->mena_date)->format('Y-m-d'),
          'measles2' => optional($item->mr2_date)->format('Y-m-d'),
          'comments' => $item->comments ?? '-',
        ];
      })->toArray();

    return [[
      ['key' => 'visit_date', 'label' => 'Visit Date'],
      ['key' => 'child_name', 'label' => 'Child Name'],
      ['key' => 'child_card_no', 'label' => 'Card No'],
      ['key' => 'sex', 'label' => 'Sex'],
      ['key' => 'follow_up_address', 'label' => 'Follow-up Address'],
      ['key' => 'phone_number', 'label' => 'Phone'],
      ['key' => 'dob', 'label' => 'DOB'],
      ['key' => 'hepb0', 'label' => 'HepB0'],
      ['key' => 'opv0', 'label' => 'OPV0'],
      ['key' => 'bcg', 'label' => 'BCG'],
      ['key' => 'opv1', 'label' => 'OPV1'],
      ['key' => 'penta1', 'label' => 'PENTA1'],
      ['key' => 'pcv1', 'label' => 'PCV1'],
      ['key' => 'rota1', 'label' => 'ROTA1'],
      ['key' => 'opv2', 'label' => 'OPV2'],
      ['key' => 'penta2', 'label' => 'PENTA2'],
      ['key' => 'pcv2', 'label' => 'PCV2'],
      ['key' => 'rota2', 'label' => 'ROTA2'],
      ['key' => 'opv3', 'label' => 'OPV3'],
      ['key' => 'penta3', 'label' => 'PENTA3'],
      ['key' => 'pcv3', 'label' => 'PCV3'],
      ['key' => 'ipv', 'label' => 'IPV'],
      ['key' => 'vita1', 'label' => 'Vit A1'],
      ['key' => 'vita2', 'label' => 'Vit A2'],
      ['key' => 'measles1', 'label' => 'Measles 1'],
      ['key' => 'yellow_fever', 'label' => 'Yellow Fever'],
      ['key' => 'men_a', 'label' => 'Men A'],
      ['key' => 'measles2', 'label' => 'Measles 2'],
      ['key' => 'comments', 'label' => 'Comments'],
    ], $rows];
  }

  private function reportImmunizationSummaryRegister(): array
  {
    $facilityColumns = ['id', 'name'];
    if (Schema::hasColumn('facilities', 'ward')) {
      $facilityColumns[] = 'ward';
    }
    if (Schema::hasColumn('facilities', 'is_active')) {
      $facilityColumns[] = 'is_active';
    }
    if (Schema::hasColumn('facilities', 'ownership')) {
      $facilityColumns[] = 'ownership';
    }
    if (Schema::hasColumn('facilities', 'type')) {
      $facilityColumns[] = 'type';
    }

    $facilities = Facility::query()
      ->whereIn('id', $this->getFacilityIds())
      ->get($facilityColumns);

    $records = ImmunizationRecord::query()
      ->with(['patient:id,date_of_birth', 'linkedChild:id,date_of_birth'])
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('visit_date', [$this->date_from, $this->date_to])
      ->get();

    $rows = $facilities->values()->map(function ($facility, $index) use ($records) {
      $facilityRows = $records->where('facility_id', $facility->id)->values();
      $facilityType = strtolower(trim((string) ($facility->ownership ?? $facility->type ?? '')));

      return [
        'sn' => $index + 1,
        'ward_name' => $facility->ward ?? '-',
        'facility_name' => $facility->name ?? '-',
        'facility_type_public' => (str_contains($facilityType, 'public') || str_contains($facilityType, 'government')) ? 'Y' : '',
        'facility_type_private' => str_contains($facilityType, 'private') ? 'Y' : '',
        'timeliness_reporting' => $facilityRows->count() > 0 ? 'T' : 'N',
        'completeness_reporting' => $facilityRows->count() > 0 ? 'T' : 'N',
        'session_fixed' => 0,
        'session_outreach' => 0,
        'session_mobile' => 0,
        'dvd_mtk_received' => 'N',
        'hepb0_0_24h' => $this->countDoseByAgeBand($facilityRows, 'hepb0_date', '0_24h'),
        'hepb0_24h_2w' => $this->countDoseByAgeBand($facilityRows, 'hepb0_date', '24h_2w'),
        'opv0_0_2w' => $this->countDoseByAgeBand($facilityRows, 'opv0_date', '0_2w'),
        'bcg_0_11m' => $this->countDoseByAgeBand($facilityRows, 'bcg_date', '0_11m'),
        'opv1_6w_11m' => $this->countDoseByAgeBand($facilityRows, 'opv1_date', '6w_11m'),
        'opv1_12_23m' => $this->countDoseByAgeBand($facilityRows, 'opv1_date', '12_23m'),
        'opv1_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'opv1_date', '24m_plus'),
        'penta1_6w_11m' => $this->countDoseByAgeBand($facilityRows, 'penta1_date', '6w_11m'),
        'penta1_12_23m' => $this->countDoseByAgeBand($facilityRows, 'penta1_date', '12_23m'),
        'penta1_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'penta1_date', '24m_plus'),
        'pcv1_6w_11m' => $this->countDoseByAgeBand($facilityRows, 'pcv1_date', '6w_11m'),
        'pcv1_12_23m' => $this->countDoseByAgeBand($facilityRows, 'pcv1_date', '12_23m'),
        'pcv1_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'pcv1_date', '24m_plus'),
        'rota1_6w_11m' => $this->countDoseByAgeBand($facilityRows, 'rota1_date', '6w_11m'),
        'rota1_12_23m' => $this->countDoseByAgeBand($facilityRows, 'rota1_date', '12_23m'),
        'rota1_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'rota1_date', '24m_plus'),
        'opv2_10w_11m' => $this->countDoseByAgeBand($facilityRows, 'opv2_date', '10w_11m'),
        'opv2_12_23m' => $this->countDoseByAgeBand($facilityRows, 'opv2_date', '12_23m'),
        'opv2_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'opv2_date', '24m_plus'),
        'penta2_10w_11m' => $this->countDoseByAgeBand($facilityRows, 'penta2_date', '10w_11m'),
        'penta2_12_23m' => $this->countDoseByAgeBand($facilityRows, 'penta2_date', '12_23m'),
        'penta2_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'penta2_date', '24m_plus'),
        'pcv2_10w_11m' => $this->countDoseByAgeBand($facilityRows, 'pcv2_date', '10w_11m'),
        'pcv2_12_23m' => $this->countDoseByAgeBand($facilityRows, 'pcv2_date', '12_23m'),
        'pcv2_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'pcv2_date', '24m_plus'),
      ];
    })->toArray();

    $columns = [
      ['key' => 'sn', 'label' => 'S/N'],
      ['key' => 'ward_name', 'label' => 'Ward'],
      ['key' => 'facility_name', 'label' => 'Health Facility'],
      ['key' => 'facility_type_public', 'label' => 'Type Public'],
      ['key' => 'facility_type_private', 'label' => 'Type Private'],
      ['key' => 'timeliness_reporting', 'label' => 'Timeliness'],
      ['key' => 'completeness_reporting', 'label' => 'Completeness'],
      ['key' => 'session_fixed', 'label' => 'Sessions Fixed'],
      ['key' => 'session_outreach', 'label' => 'Sessions Outreach'],
      ['key' => 'session_mobile', 'label' => 'Sessions Mobile'],
      ['key' => 'dvd_mtk_received', 'label' => 'DVD-MTK'],
      ['key' => 'hepb0_0_24h', 'label' => 'HepB0 0-24h'],
      ['key' => 'hepb0_24h_2w', 'label' => 'HepB0 >24h-2w'],
      ['key' => 'opv0_0_2w', 'label' => 'OPV0 0-2w'],
      ['key' => 'bcg_0_11m', 'label' => 'BCG 0-11m'],
      ['key' => 'opv1_6w_11m', 'label' => 'OPV1 6w-11m'],
      ['key' => 'opv1_12_23m', 'label' => 'OPV1 12-23m'],
      ['key' => 'opv1_24m_plus', 'label' => 'OPV1 24m+'],
      ['key' => 'penta1_6w_11m', 'label' => 'PENTA1 6w-11m'],
      ['key' => 'penta1_12_23m', 'label' => 'PENTA1 12-23m'],
      ['key' => 'penta1_24m_plus', 'label' => 'PENTA1 24m+'],
      ['key' => 'pcv1_6w_11m', 'label' => 'PCV1 6w-11m'],
      ['key' => 'pcv1_12_23m', 'label' => 'PCV1 12-23m'],
      ['key' => 'pcv1_24m_plus', 'label' => 'PCV1 24m+'],
      ['key' => 'rota1_6w_11m', 'label' => 'ROTA1 6w-11m'],
      ['key' => 'rota1_12_23m', 'label' => 'ROTA1 12-23m'],
      ['key' => 'rota1_24m_plus', 'label' => 'ROTA1 24m+'],
      ['key' => 'opv2_10w_11m', 'label' => 'OPV2 10w-11m'],
      ['key' => 'opv2_12_23m', 'label' => 'OPV2 12-23m'],
      ['key' => 'opv2_24m_plus', 'label' => 'OPV2 24m+'],
      ['key' => 'penta2_10w_11m', 'label' => 'PENTA2 10w-11m'],
      ['key' => 'penta2_12_23m', 'label' => 'PENTA2 12-23m'],
      ['key' => 'penta2_24m_plus', 'label' => 'PENTA2 24m+'],
      ['key' => 'pcv2_10w_11m', 'label' => 'PCV2 10w-11m'],
      ['key' => 'pcv2_12_23m', 'label' => 'PCV2 12-23m'],
      ['key' => 'pcv2_24m_plus', 'label' => 'PCV2 24m+'],
    ];

    return [$columns, $rows];
  }

  private function reportImmunizationSummaryTally(): array
  {
    $facilities = Facility::query()
      ->whereIn('id', $this->getFacilityIds())
      ->get(['id', 'name']);

    $records = ImmunizationRecord::query()
      ->with(['patient:id,date_of_birth', 'linkedChild:id,date_of_birth'])
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('visit_date', [$this->date_from, $this->date_to])
      ->get();

    $tt = TetanusVaccination::query()
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('dose_date', [$this->date_from, $this->date_to])
      ->get(['facility_id', 'current_tt_dose']);

    $rows = $facilities->values()->map(function ($facility, $index) use ($records, $tt) {
      $facilityRows = $records->where('facility_id', $facility->id)->values();
      $ttRows = $tt->where('facility_id', $facility->id);

      return [
        'sn' => $index + 1,
        'facility_name' => $facility->name,
        'rota3_10_23w' => $this->countDoseByAgeBand($facilityRows, 'rota3_date', '10_23w'),
        'rota3_12_23m' => $this->countDoseByAgeBand($facilityRows, 'rota3_date', '12_23m'),
        'rota3_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'rota3_date', '24m_plus'),
        'opv3_14w_11m' => $this->countDoseByAgeBand($facilityRows, 'opv3_date', '14w_11m'),
        'opv3_12_23m' => $this->countDoseByAgeBand($facilityRows, 'opv3_date', '12_23m'),
        'opv3_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'opv3_date', '24m_plus'),
        'penta3_14w_11m' => $this->countDoseByAgeBand($facilityRows, 'penta3_date', '14w_11m'),
        'penta3_12_23m' => $this->countDoseByAgeBand($facilityRows, 'penta3_date', '12_23m'),
        'penta3_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'penta3_date', '24m_plus'),
        'pcv3_14w_11m' => $this->countDoseByAgeBand($facilityRows, 'pcv3_date', '14w_11m'),
        'pcv3_12_23m' => $this->countDoseByAgeBand($facilityRows, 'pcv3_date', '12_23m'),
        'pcv3_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'pcv3_date', '24m_plus'),
        'rota2_10_23w' => $this->countDoseByAgeBand($facilityRows, 'rota2_date', '10_23w'),
        'rota2_12_23m' => $this->countDoseByAgeBand($facilityRows, 'rota2_date', '12_23m'),
        'rota2_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'rota2_date', '24m_plus'),
        'ipv_14w_11m' => $this->countDoseByAgeBand($facilityRows, 'ipv1_date', '14w_11m'),
        'ipv_12_23m' => $this->countDoseByAgeBand($facilityRows, 'ipv1_date', '12_23m'),
        'vita_6_11m' => $this->countDoseByAgeBand($facilityRows, 'vita1_date', '6_11m'),
        'vita_12_59m' => $this->countDoseByAgeBand($facilityRows, 'vita2_date', '12_59m'),
        'measles1_9_11m' => $this->countDoseByAgeBand($facilityRows, 'mr1_date', '9_11m'),
        'measles1_12_23m' => $this->countDoseByAgeBand($facilityRows, 'mr1_date', '12_23m'),
        'measles1_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'mr1_date', '24m_plus'),
        'yf_9_11m' => $this->countDoseByAgeBand($facilityRows, 'yf_date', '9_11m'),
        'yf_12_23m' => $this->countDoseByAgeBand($facilityRows, 'yf_date', '12_23m'),
        'yf_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'yf_date', '24m_plus'),
        'mena_1_5y' => $this->countDoseByAgeBand($facilityRows, 'mena_date', '1_5y'),
        'mena_5m_plus' => $this->countDoseByAgeBand($facilityRows, 'mena_date', '5m_plus'),
        'measles2_15_23m' => $this->countDoseByAgeBand($facilityRows, 'mr2_date', '15_23m'),
        'measles2_24m_plus' => $this->countDoseByAgeBand($facilityRows, 'mr2_date', '24m_plus'),
        'td1_p' => $ttRows->where('current_tt_dose', 'TT1')->count(),
        'td1_np' => 0,
        'td2_p' => $ttRows->where('current_tt_dose', 'TT2')->count(),
        'td2_np' => 0,
        'td3_p' => $ttRows->where('current_tt_dose', 'TT3')->count(),
        'td3_np' => 0,
        'td4_p' => $ttRows->where('current_tt_dose', 'TT4')->count(),
        'td4_np' => 0,
        'td5_p' => $ttRows->where('current_tt_dose', 'TT5')->count(),
        'td5_np' => 0,
        'comments' => '',
      ];
    })->toArray();

    $columns = [
      ['key' => 'sn', 'label' => 'S/N'],
      ['key' => 'facility_name', 'label' => 'Facility'],
      ['key' => 'rota3_10_23w', 'label' => 'ROTA3 10-23w'],
      ['key' => 'rota3_12_23m', 'label' => 'ROTA3 12-23m'],
      ['key' => 'rota3_24m_plus', 'label' => 'ROTA3 24m+'],
      ['key' => 'opv3_14w_11m', 'label' => 'OPV3 14w-11m'],
      ['key' => 'opv3_12_23m', 'label' => 'OPV3 12-23m'],
      ['key' => 'opv3_24m_plus', 'label' => 'OPV3 24m+'],
      ['key' => 'penta3_14w_11m', 'label' => 'PENTA3 14w-11m'],
      ['key' => 'penta3_12_23m', 'label' => 'PENTA3 12-23m'],
      ['key' => 'penta3_24m_plus', 'label' => 'PENTA3 24m+'],
      ['key' => 'pcv3_14w_11m', 'label' => 'PCV3 14w-11m'],
      ['key' => 'pcv3_12_23m', 'label' => 'PCV3 12-23m'],
      ['key' => 'pcv3_24m_plus', 'label' => 'PCV3 24m+'],
      ['key' => 'rota2_10_23w', 'label' => 'ROTA2 10-23w'],
      ['key' => 'rota2_12_23m', 'label' => 'ROTA2 12-23m'],
      ['key' => 'rota2_24m_plus', 'label' => 'ROTA2 24m+'],
      ['key' => 'ipv_14w_11m', 'label' => 'IPV 14w-11m'],
      ['key' => 'ipv_12_23m', 'label' => 'IPV 12-23m'],
      ['key' => 'vita_6_11m', 'label' => 'VitA 6-11m'],
      ['key' => 'vita_12_59m', 'label' => 'VitA 12-59m'],
      ['key' => 'measles1_9_11m', 'label' => 'Measles1 9-11m'],
      ['key' => 'measles1_12_23m', 'label' => 'Measles1 12-23m'],
      ['key' => 'measles1_24m_plus', 'label' => 'Measles1 24m+'],
      ['key' => 'yf_9_11m', 'label' => 'YF 9-11m'],
      ['key' => 'yf_12_23m', 'label' => 'YF 12-23m'],
      ['key' => 'yf_24m_plus', 'label' => 'YF 24m+'],
      ['key' => 'mena_1_5y', 'label' => 'MenA 1-5y'],
      ['key' => 'mena_5m_plus', 'label' => 'MenA >5m'],
      ['key' => 'measles2_15_23m', 'label' => 'Measles2 15-23m'],
      ['key' => 'measles2_24m_plus', 'label' => 'Measles2 24m+'],
      ['key' => 'td1_p', 'label' => 'TD1 P'],
      ['key' => 'td1_np', 'label' => 'TD1 NP'],
      ['key' => 'td2_p', 'label' => 'TD2 P'],
      ['key' => 'td2_np', 'label' => 'TD2 NP'],
      ['key' => 'td3_p', 'label' => 'TD3 P'],
      ['key' => 'td3_np', 'label' => 'TD3 NP'],
      ['key' => 'td4_p', 'label' => 'TD4 P'],
      ['key' => 'td4_np', 'label' => 'TD4 NP'],
      ['key' => 'td5_p', 'label' => 'TD5 P'],
      ['key' => 'td5_np', 'label' => 'TD5 NP'],
      ['key' => 'comments', 'label' => 'Comments'],
    ];

    return [$columns, $rows];
  }

  private function countDoseByAgeBand($records, string $field, string $band): int
  {
    return $records->filter(function ($record) use ($field, $band) {
      $doseDate = $record->{$field} ?? null;
      if (empty($doseDate)) {
        return false;
      }

      $dob = $record->linkedChild?->date_of_birth ?? $record->patient?->date_of_birth;
      if (empty($dob)) {
        return false;
      }

      try {
        $dobDate = Carbon::parse($dob);
        $vaxDate = Carbon::parse($doseDate);
      } catch (\Throwable $e) {
        return false;
      }

      if ($vaxDate->lt(Carbon::parse($this->date_from)) || $vaxDate->gt(Carbon::parse($this->date_to))) {
        return false;
      }

      $ageDays = $dobDate->diffInDays($vaxDate);
      $ageMonths = $dobDate->diffInMonths($vaxDate);

      return match ($band) {
        '0_24h' => $ageDays <= 1,
        '24h_2w' => $ageDays > 1 && $ageDays <= 14,
        '0_2w' => $ageDays <= 14,
        '0_11m' => $ageMonths <= 11,
        '6w_11m' => $ageDays >= 42 && $ageMonths <= 11,
        '10w_11m' => $ageDays >= 70 && $ageMonths <= 11,
        '10_23w' => $ageDays >= 70 && $ageDays <= 161,
        '14w_11m' => $ageDays >= 98 && $ageMonths <= 11,
        '6_11m' => $ageMonths >= 6 && $ageMonths <= 11,
        '9_11m' => $ageMonths >= 9 && $ageMonths <= 11,
        '12_23m' => $ageMonths >= 12 && $ageMonths <= 23,
        '12_59m' => $ageMonths >= 12 && $ageMonths <= 59,
        '15_23m' => $ageMonths >= 15 && $ageMonths <= 23,
        '1_5y' => $ageMonths >= 12 && $ageMonths <= 60,
        '5m_plus' => $ageMonths > 5,
        '24m_plus' => $ageMonths >= 24,
        default => false,
      };
    })->count();
  }

  private function reportNutritionGrowthRegister(): array
  {
    $records = NutritionRecord::query()
      ->with(['patient:id,din,first_name,last_name,date_of_birth,gender', 'linkedChild:id,first_name,last_name,date_of_birth,gender', 'facility:id,name'])
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('visit_date', [$this->date_from, $this->date_to])
      ->orderByDesc('visit_date')
      ->limit(3000)
      ->get();

    $firstVisitMap = $records
      ->groupBy(fn($item) => ($item->linked_child_id ? 'child:' . $item->linked_child_id : 'patient:' . $item->patient_id))
      ->map(fn($group) => $group->min(fn($item) => optional($item->visit_date)->format('Y-m-d')));

    $rows = $records->values()->map(function ($item, $index) use ($firstVisitMap) {
      $childName = trim(($item->linkedChild->first_name ?? '') . ' ' . ($item->linkedChild->last_name ?? ''));
      if ($childName === '') {
        $childName = trim(($item->patient->first_name ?? '') . ' ' . ($item->patient->last_name ?? ''));
      }

      $subjectKey = $item->linked_child_id ? 'child:' . $item->linked_child_id : 'patient:' . $item->patient_id;
      $visitDate = optional($item->visit_date)->format('Y-m-d');
      $isNewVisit = ($firstVisitMap[$subjectKey] ?? null) === $visitDate;

      $ageGroup = strtolower((string) ($item->age_group ?? ''));
      $infantFeeding = strtolower((string) ($item->infant_feeding ?? ''));
      $compFeeding = strtolower((string) ($item->complementary_feeding ?? ''));
      $counselling = collect((array) ($item->counselling_topics ?? []))->map(fn($v) => strtolower((string) $v))->toArray();
      $suppGroups = collect((array) ($item->supplementary_feeding_groups ?? []))->map(fn($v) => strtolower((string) $v))->toArray();
      $otpProvider = strtolower((string) ($item->otp_provider ?? ''));
      $admissionStatus = strtolower((string) ($item->admission_status ?? ''));
      $outcomeStatus = strtolower((string) ($item->outcome_status ?? ''));

      $dob = $item->linkedChild?->date_of_birth ?? $item->patient?->date_of_birth;
      $sex = $item->linkedChild?->gender ?? $item->patient?->gender;
      $ageMonths = null;
      if (!empty($dob) && !empty($visitDate)) {
        try {
          $ageMonths = Carbon::parse($dob)->diffInMonths(Carbon::parse($visitDate));
        } catch (\Throwable $e) {
          $ageMonths = null;
        }
      }

      return [
        'sn' => $index + 1,
        'date' => $visitDate,
        'patient_name' => $childName ?: '-',
        'card_number' => $item->patient->din ?? '-',
        'sex' => $sex ?? '-',
        'dob' => $dob ? Carbon::parse($dob)->format('Y-m-d') : '-',
        'age_months' => $ageMonths ?? '-',
        'age_0_5' => str_contains($ageGroup, '0-5') ? '1' : '',
        'age_6_23' => str_contains($ageGroup, '6-23') ? '1' : '',
        'age_24_59' => str_contains($ageGroup, '24-59') ? '1' : '',
        'visit_n' => $isNewVisit ? 'Y' : '',
        'visit_r' => !$isNewVisit ? 'Y' : '',
        'iycf_exclusive_bf' => str_contains($infantFeeding, 'exclusive') ? 'Y' : '',
        'iycf_bf_water' => str_contains($infantFeeding, 'water') ? 'Y' : '',
        'iycf_bf_others' => str_contains($infantFeeding, 'partial') ? 'Y' : '',
        'iycf_not_bf' => str_contains($infantFeeding, 'no') ? 'Y' : '',
        'iycf_bf_other_foods' => str_contains($compFeeding, 'bf') ? 'Y' : '',
        'iycf_other_foods_only' => (str_contains($compFeeding, 'food') && !str_contains($compFeeding, 'bf')) ? 'Y' : '',
        'iycf_not_started_cf' => str_contains($compFeeding, 'not started') ? 'Y' : '',
        'counselling_maternal_nutr' => in_array('maternal nutrition', $counselling, true) ? 'Y' : '',
        'counselling_exclusive_bf' => in_array('exclusive breastfeeding', $counselling, true) ? 'Y' : '',
        'counselling_complementary' => in_array('complementary feeding', $counselling, true) ? 'Y' : '',
        'counselling_wash' => in_array('wash', $counselling, true) ? 'Y' : '',
        'support_group' => $item->support_group_referred ? 'Y' : '',
        'height_length_cm' => $item->height_cm ?? '-',
        'weight_kg' => $item->weight_kg ?? '-',
        'oedema' => $item->oedema ?? '-',
        'muac_red' => $item->muac_class === 'Red' ? 'Y' : '',
        'muac_yellow' => $item->muac_class === 'Yellow' ? 'Y' : '',
        'muac_green' => $item->muac_class === 'Green' ? 'Y' : '',
        'growing_well' => $item->growth_status === 'Growing Well' ? 'Y' : '',
        'not_growing_well' => $item->growth_status === 'Not Growing Well' ? 'Y' : '',
        'vit_a_6_11' => in_array('vitamin_a_6_11', $suppGroups, true) ? 'Y' : '',
        'vit_a_12_59' => in_array('vitamin_a_12_59', $suppGroups, true) ? 'Y' : '',
        'deworm_12_23' => in_array('deworming_12_23', $suppGroups, true) ? 'Y' : '',
        'deworm_24_59' => in_array('deworming_24_59', $suppGroups, true) ? 'Y' : '',
        'mnp_given' => $item->mnp_given ? 'Y' : '',
        'source_self' => str_contains($otpProvider, 'self') ? 'Y' : '',
        'source_hf_no_otp' => str_contains($otpProvider, 'hf') ? 'Y' : '',
        'source_chips' => str_contains($otpProvider, 'chip') ? 'Y' : '',
        'eligibility_admit_hf' => str_contains($admissionStatus, 'admitted') ? 'Y' : '',
        'eligibility_trans_in' => str_contains($admissionStatus, 'transfer') ? 'Y' : '',
        'eligibility_ref_sc' => str_contains($admissionStatus, 'ref') ? 'Y' : '',
        'eligibility_not_meet' => str_contains($admissionStatus, 'not') ? 'Y' : '',
        'outcome_recovered' => str_contains($outcomeStatus, 'recover') ? 'Y' : '',
        'outcome_defaulted' => str_contains($outcomeStatus, 'default') ? 'Y' : '',
        'outcome_died' => str_contains($outcomeStatus, 'died') ? 'Y' : '',
        'outcome_non_recovered' => str_contains($outcomeStatus, 'non') ? 'Y' : '',
      ];
    })->toArray();

    return [[
      ['key' => 'sn', 'label' => 'S/N'],
      ['key' => 'date', 'label' => 'Date'],
      ['key' => 'patient_name', 'label' => 'Patient Name'],
      ['key' => 'card_number', 'label' => 'Card Number'],
      ['key' => 'sex', 'label' => 'Sex'],
      ['key' => 'dob', 'label' => 'DOB'],
      ['key' => 'age_months', 'label' => 'Age (Months)'],
      ['key' => 'age_0_5', 'label' => 'Age 0-5'],
      ['key' => 'age_6_23', 'label' => 'Age 6-23'],
      ['key' => 'age_24_59', 'label' => 'Age 24-59'],
      ['key' => 'visit_n', 'label' => 'Visit N'],
      ['key' => 'visit_r', 'label' => 'Visit R'],
      ['key' => 'iycf_exclusive_bf', 'label' => 'Excl BF'],
      ['key' => 'iycf_bf_water', 'label' => 'BF+Water'],
      ['key' => 'iycf_bf_others', 'label' => 'BF+Others'],
      ['key' => 'iycf_not_bf', 'label' => 'Not BF'],
      ['key' => 'iycf_bf_other_foods', 'label' => 'BF+Other Foods'],
      ['key' => 'iycf_other_foods_only', 'label' => 'Other Foods Only'],
      ['key' => 'iycf_not_started_cf', 'label' => 'Not Started CF'],
      ['key' => 'counselling_maternal_nutr', 'label' => 'Counselling Maternal Nutr'],
      ['key' => 'counselling_exclusive_bf', 'label' => 'Counselling Excl BF'],
      ['key' => 'counselling_complementary', 'label' => 'Counselling Complementary'],
      ['key' => 'counselling_wash', 'label' => 'Counselling WASH'],
      ['key' => 'support_group', 'label' => 'Support Group'],
      ['key' => 'height_length_cm', 'label' => 'Height/Length (cm)'],
      ['key' => 'weight_kg', 'label' => 'Weight (kg)'],
      ['key' => 'oedema', 'label' => 'Oedema'],
      ['key' => 'muac_red', 'label' => 'MUAC Red'],
      ['key' => 'muac_yellow', 'label' => 'MUAC Yellow'],
      ['key' => 'muac_green', 'label' => 'MUAC Green'],
      ['key' => 'growing_well', 'label' => 'Growing Well'],
      ['key' => 'not_growing_well', 'label' => 'Not Growing Well'],
      ['key' => 'vit_a_6_11', 'label' => 'Vit A 6-11'],
      ['key' => 'vit_a_12_59', 'label' => 'Vit A 12-59'],
      ['key' => 'deworm_12_23', 'label' => 'Deworm 12-23'],
      ['key' => 'deworm_24_59', 'label' => 'Deworm 24-59'],
      ['key' => 'mnp_given', 'label' => 'MNP Given'],
      ['key' => 'source_self', 'label' => 'Source Self'],
      ['key' => 'source_hf_no_otp', 'label' => 'Source HF No OTP'],
      ['key' => 'source_chips', 'label' => 'Source CHIPS'],
      ['key' => 'eligibility_admit_hf', 'label' => 'Eligibility Admit HF'],
      ['key' => 'eligibility_trans_in', 'label' => 'Eligibility Trans In'],
      ['key' => 'eligibility_ref_sc', 'label' => 'Eligibility Ref SC'],
      ['key' => 'eligibility_not_meet', 'label' => 'Eligibility Not Meet'],
      ['key' => 'outcome_recovered', 'label' => 'Outcome Recovered'],
      ['key' => 'outcome_defaulted', 'label' => 'Outcome Defaulted'],
      ['key' => 'outcome_died', 'label' => 'Outcome Died'],
      ['key' => 'outcome_non_recovered', 'label' => 'Outcome Non-Recovered'],
    ], $rows];
  }

  private function reportVaccinationScheduleRegister(): array
  {
    $rows = ChildHealthActivityRecord::query()
      ->with(['patient:id,din,first_name,last_name', 'facility:id,name'])
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('visit_date', [$this->date_from, $this->date_to])
      ->orderByDesc('visit_date')
      ->limit(3000)
      ->get()
      ->map(function ($item) {
        $weights = (array) ($item->weight_entries ?? []);
        $breastfeeding = (array) ($item->breastfeeding_entries ?? []);
        $aefi = (array) ($item->aefi_cases ?? []);

        return [
          'visit_date' => optional($item->visit_date)->format('Y-m-d'),
          'din' => $item->patient->din ?? '-',
          'patient_name' => trim(($item->patient->first_name ?? '') . ' ' . ($item->patient->last_name ?? '')),
          'facility' => $item->facility->name ?? '-',
          'vaccines_logged' => count((array) ($item->vaccination_dates ?? [])),
          'weight_entries' => count($weights),
          'breastfeeding_entries' => count($breastfeeding),
          'aefi_cases' => count($aefi),
        ];
      })->toArray();

    return [[
      ['key' => 'visit_date', 'label' => 'Visit Date'],
      ['key' => 'din', 'label' => 'DIN'],
      ['key' => 'patient_name', 'label' => 'Patient Name'],
      ['key' => 'facility', 'label' => 'Facility'],
      ['key' => 'vaccines_logged', 'label' => 'Vaccines Logged'],
      ['key' => 'weight_entries', 'label' => 'Weight Entries'],
      ['key' => 'breastfeeding_entries', 'label' => 'Breastfeeding Entries'],
      ['key' => 'aefi_cases', 'label' => 'AEFI Cases'],
    ], $rows];
  }

  private function reportLaboratoryRegister(): array
  {
    $rows = LabTest::query()
      ->with(['patient:id,din,first_name,last_name', 'facility:id,name'])
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('visit_date', [$this->date_from, $this->date_to])
      ->orderByDesc('visit_date')
      ->limit(3000)
      ->get()
      ->map(fn($item) => [
        'visit_date' => optional($item->visit_date)->format('Y-m-d'),
        'din' => $item->patient->din ?? '-',
        'patient_name' => trim(($item->patient->first_name ?? '') . ' ' . ($item->patient->last_name ?? '')),
        'facility' => $item->facility->name ?? '-',
        'lab_no' => $item->lab_no ?? '-',
        'specimen' => $item->specimen ?? '-',
        'examination' => $item->examination ?? '-',
        'officer' => $item->officer_name ?? '-',
      ])->toArray();

    return [[
      ['key' => 'visit_date', 'label' => 'Visit Date'],
      ['key' => 'din', 'label' => 'DIN'],
      ['key' => 'patient_name', 'label' => 'Patient Name'],
      ['key' => 'facility', 'label' => 'Facility'],
      ['key' => 'lab_no', 'label' => 'Lab No'],
      ['key' => 'specimen', 'label' => 'Specimen'],
      ['key' => 'examination', 'label' => 'Examination'],
      ['key' => 'officer', 'label' => 'Officer'],
    ], $rows];
  }

  private function reportPrescriptionDispensingRegister(): array
  {
    $rows = Prescription::query()
      ->with(['patient:id,din,first_name,last_name', 'facility:id,name'])
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('prescribed_date', [$this->date_from, $this->date_to])
      ->orderByDesc('prescribed_date')
      ->limit(3000)
      ->get()
      ->map(fn($item) => [
        'prescribed_date' => optional($item->prescribed_date)->format('Y-m-d'),
        'din' => $item->patient->din ?? '-',
        'patient_name' => trim(($item->patient->first_name ?? '') . ' ' . ($item->patient->last_name ?? '')),
        'facility' => $item->facility->name ?? '-',
        'drug_name' => $item->drug_name ?? '-',
        'quantity_prescribed' => $item->quantity_prescribed ?? '-',
        'quantity_dispensed' => $item->quantity_dispensed ?? '-',
        'status' => ucfirst((string) $item->status),
      ])->toArray();

    return [[
      ['key' => 'prescribed_date', 'label' => 'Prescribed Date'],
      ['key' => 'din', 'label' => 'DIN'],
      ['key' => 'patient_name', 'label' => 'Patient Name'],
      ['key' => 'facility', 'label' => 'Facility'],
      ['key' => 'drug_name', 'label' => 'Drug'],
      ['key' => 'quantity_prescribed', 'label' => 'Qty Prescribed'],
      ['key' => 'quantity_dispensed', 'label' => 'Qty Dispensed'],
      ['key' => 'status', 'label' => 'Status'],
    ], $rows];
  }

  private function reportReferralsRegister(): array
  {
    $rows = Referral::query()
      ->with(['patient:id,din,first_name,last_name', 'facility:id,name'])
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('referral_date', [$this->date_from, $this->date_to])
      ->orderByDesc('referral_date')
      ->limit(3000)
      ->get()
      ->map(fn($item) => [
        'referral_date' => optional($item->referral_date)->format('Y-m-d'),
        'din' => $item->patient->din ?? '-',
        'patient_name' => trim(($item->patient->first_name ?? '') . ' ' . ($item->patient->last_name ?? '')),
        'facility' => $item->facility->name ?? '-',
        'referred_to' => $item->referred_to ?? '-',
        'service_provided' => $item->service_provided ?? '-',
        'follow_up_needed' => $item->follow_up_needed ?? '-',
      ])->toArray();

    return [[
      ['key' => 'referral_date', 'label' => 'Referral Date'],
      ['key' => 'din', 'label' => 'DIN'],
      ['key' => 'patient_name', 'label' => 'Patient Name'],
      ['key' => 'facility', 'label' => 'Facility'],
      ['key' => 'referred_to', 'label' => 'Referred To'],
      ['key' => 'service_provided', 'label' => 'Service Provided'],
      ['key' => 'follow_up_needed', 'label' => 'Follow-up Needed'],
    ], $rows];
  }

  private function reportInvoicesPaymentsRegister(): array
  {
    $rows = Invoice::query()
      ->with(['patient:id,din,first_name,last_name', 'facility:id,name'])
      ->whereIn('facility_id', $this->getFacilityIds())
      ->whereBetween('invoice_date', [$this->date_from, $this->date_to])
      ->orderByDesc('invoice_date')
      ->limit(3000)
      ->get()
      ->map(fn($item) => [
        'invoice_date' => optional($item->invoice_date)->format('Y-m-d'),
        'invoice_code' => $item->invoice_code ?? '-',
        'din' => $item->patient->din ?? '-',
        'patient_name' => trim(($item->patient->first_name ?? '') . ' ' . ($item->patient->last_name ?? '')),
        'facility' => $item->facility->name ?? '-',
        'total_amount' => (float) ($item->total_amount ?? 0),
        'amount_paid' => (float) ($item->amount_paid ?? 0),
        'outstanding_amount' => (float) ($item->outstanding_amount ?? 0),
        'status' => ucfirst((string) $item->status),
      ])->toArray();

    return [[
      ['key' => 'invoice_date', 'label' => 'Invoice Date'],
      ['key' => 'invoice_code', 'label' => 'Invoice Code'],
      ['key' => 'din', 'label' => 'DIN'],
      ['key' => 'patient_name', 'label' => 'Patient Name'],
      ['key' => 'facility', 'label' => 'Facility'],
      ['key' => 'total_amount', 'label' => 'Total Amount'],
      ['key' => 'amount_paid', 'label' => 'Amount Paid'],
      ['key' => 'outstanding_amount', 'label' => 'Outstanding'],
      ['key' => 'status', 'label' => 'Status'],
    ], $rows];
  }

  private function appendHistory(string $reportName, int $rows): void
  {
    $scopeLabel = $this->selectedFacilityId
      ? optional(Facility::find($this->selectedFacilityId))->name
      : ucfirst((string) ($this->scopeInfo['scope_type'] ?? 'facility')) . ' Scope';

    array_unshift($this->history_rows, [
      'generated_at' => now()->format('Y-m-d H:i:s'),
      'report_name' => $reportName,
      'section' => $this->sections[$this->selected_section] ?? 'All Sections',
      'date_from' => $this->date_from,
      'date_to' => $this->date_to,
      'scope' => $scopeLabel ?: 'Scope',
      'records' => $rows,
      'generated_by' => trim((Auth::user()->first_name ?? '') . ' ' . (Auth::user()->last_name ?? '')),
    ]);

    $this->history_rows = array_slice($this->history_rows, 0, 100);
    session()->put('reports_hub_history', $this->history_rows);
  }

  private function resolveRouteUrl(?string $routeName): ?string
  {
    if (!$routeName || !Route::has($routeName)) {
      return null;
    }

    try {
      return route($routeName);
    } catch (\Throwable $e) {
      return null;
    }
  }

  public function getVisibleReportsProperty()
  {
    return $this->getFilteredReports();
  }

  public function render()
  {
    $user = Auth::user();
    $layout = match (true) {
      in_array($user->role, ['State Data Administrator']) => 'layouts.stateOfficerLayout',
      in_array($user->role, ['LGA Officer']) => 'layouts.lgaOfficerLayout',
      in_array($user->role, ['Facility Administrator']) => 'layouts.facilityAdminLayout',
      default => 'layouts.facilityAdminLayout'
    };

    return view('livewire.core.facility-reports')->layout($layout);
  }
}
