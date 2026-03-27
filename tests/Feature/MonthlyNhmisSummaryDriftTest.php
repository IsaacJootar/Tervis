<?php

namespace Tests\Feature;

use App\Livewire\Core\FacilityReports;
use App\Models\ChildHealthActivityRecord;
use App\Models\DrugDispenseLine;
use App\Models\ImmunizationRecord;
use App\Models\LinkedChild;
use App\Models\NutritionRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\ReportSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class MonthlyNhmisSummaryDriftTest extends TestCase
{
  use DatabaseTransactions;

  private int $facilityId;
  private int $otherFacilityId;
  private User $facilityAdmin;

  protected function setUp(): void
  {
    parent::setUp();
    Livewire::withoutLazyLoading();

    $requiredTables = [
      'users',
      'states',
      'lgas',
      'wards',
      'facilities',
      'patients',
      'linked_children',
      'immunization_records',
      'child_health_activity_records',
      'nutrition_records',
      'prescriptions',
      'drug_dispense_lines',
      'daily_attendances',
      'antenatal_registrations',
      'antenatal_follow_up_assessments',
      'deliveries',
      'postnatal_records',
      'tetanus_vaccinations',
      'lab_tests',
      'doctor_assessments',
      'family_planning_registrations',
      'family_planning_follow_ups',
      'referrals',
    ];

    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available.");
      }
    }

    $this->facilityId = $this->createIsolatedFacilityId();
    $this->otherFacilityId = $this->createIsolatedFacilityId();
    $this->facilityAdmin = $this->createUser('Facility Administrator', $this->facilityId);
  }

  public function test_monthly_nhmis_summary_respects_window_and_prevents_cross_month_drift(): void
  {
    $primaryPatient = $this->createPatient($this->facilityId);
    $primaryChild = $this->createLinkedChild($primaryPatient, $this->facilityId, now()->subYears(1)->toDateString());

    $secondaryPatient = $this->createPatient($this->otherFacilityId);
    $secondaryChild = $this->createLinkedChild($secondaryPatient, $this->otherFacilityId, now()->subYears(1)->toDateString());

    $this->seedJanRecordsForPrimaryFacility($primaryPatient, $primaryChild);
    $this->seedFebRecordsForPrimaryFacility($primaryPatient, $primaryChild);
    $this->seedNoiseForOtherFacility($secondaryPatient, $secondaryChild);

    $this->assertSame(1, (int) NutritionRecord::query()
      ->where('facility_id', $this->facilityId)
      ->where('patient_id', $primaryPatient->id)
      ->whereBetween('visit_date', ['2026-01-01', '2026-01-31'])
      ->whereNotNull('muac_value_mm')
      ->count());
    $this->assertSame(1, (int) NutritionRecord::query()
      ->where('facility_id', $this->facilityId)
      ->where('patient_id', $primaryPatient->id)
      ->whereBetween('visit_date', ['2026-02-01', '2026-02-28'])
      ->whereNotNull('muac_value_mm')
      ->count());

    $this->actingAs($this->facilityAdmin);
    $component = Livewire::test(FacilityReports::class)
      ->set('selected_report', 'monthly_nhmis_summary');

    $component
      ->set('date_from', '2026-01-01')
      ->set('date_to', '2026-01-31')
      ->call('generateReport')
      ->assertSet('show_results', true);
    [$janSummary, $janPayload] = $this->extractSummaryKeyValues();
    $this->assertSame('2026-01-01', (string) ($janPayload['date_from'] ?? ''));
    $this->assertSame('2026-01-31', (string) ($janPayload['date_to'] ?? ''));
    $this->assertMonthlyKeyValues($janSummary, [
      'immunization.bcg' => 1,
      'child_health.exclusive_breastfeeding' => 1,
      'child_health.muac_screened' => 1,
      'pharmacy.prescriptions_total' => 1,
      'pharmacy.prescriptions_dispensed' => 1,
      'pharmacy.prescriptions_pending' => 0,
      'pharmacy.dispense_lines' => 1,
      'pharmacy.dispensed_quantity_total' => 2.0,
    ]);

    $component
      ->set('date_from', '2026-02-01')
      ->set('date_to', '2026-02-28')
      ->call('generateReport')
      ->assertSet('show_results', true);
    [$febSummary, $febPayload] = $this->extractSummaryKeyValues();
    $this->assertSame('2026-02-01', (string) ($febPayload['date_from'] ?? ''));
    $this->assertSame('2026-02-28', (string) ($febPayload['date_to'] ?? ''));
    $this->assertMonthlyKeyValues($febSummary, [
      'immunization.bcg' => 1,
      'child_health.exclusive_breastfeeding' => 0,
      'child_health.muac_screened' => 1,
      'pharmacy.prescriptions_total' => 1,
      'pharmacy.prescriptions_dispensed' => 0,
      'pharmacy.prescriptions_pending' => 1,
      'pharmacy.dispense_lines' => 1,
      'pharmacy.dispensed_quantity_total' => 3.0,
    ]);

    $component
      ->set('date_from', '2026-01-01')
      ->set('date_to', '2026-02-28')
      ->call('generateReport')
      ->assertSet('show_results', true);
    [$combinedSummary, $combinedPayload] = $this->extractSummaryKeyValues();
    $this->assertSame('2026-01-01', (string) ($combinedPayload['date_from'] ?? ''));
    $this->assertSame('2026-02-28', (string) ($combinedPayload['date_to'] ?? ''));
    $this->assertMonthlyKeyValues($combinedSummary, [
      'immunization.bcg' => 2,
      'child_health.exclusive_breastfeeding' => 1,
      'child_health.muac_screened' => 2,
      'pharmacy.prescriptions_total' => 2,
      'pharmacy.prescriptions_dispensed' => 1,
      'pharmacy.prescriptions_pending' => 1,
      'pharmacy.dispense_lines' => 2,
      'pharmacy.dispensed_quantity_total' => 5.0,
    ]);

    // Re-run January window to ensure no carry-over from previous wider run.
    $component
      ->set('date_from', '2026-01-01')
      ->set('date_to', '2026-01-31')
      ->call('generateReport')
      ->assertSet('show_results', true);
    [$janSummaryAfterCombinedRun, $janRepeatPayload] = $this->extractSummaryKeyValues();
    $this->assertSame('2026-01-01', (string) ($janRepeatPayload['date_from'] ?? ''));
    $this->assertSame('2026-01-31', (string) ($janRepeatPayload['date_to'] ?? ''));
    $this->assertMonthlyKeyValues($janSummaryAfterCombinedRun, [
      'immunization.bcg' => 1,
      'child_health.exclusive_breastfeeding' => 1,
      'pharmacy.prescriptions_total' => 1,
      'pharmacy.dispense_lines' => 1,
      'pharmacy.dispensed_quantity_total' => 2.0,
    ]);
  }

  public function test_monthly_nhmis_summary_multi_quarter_windows_are_stable(): void
  {
    $patient = $this->createPatient($this->facilityId);
    $child = $this->createLinkedChild($patient, $this->facilityId, now()->subYears(2)->toDateString());

    $this->seedMonthlyPackage($patient, $child, '2026-01-11', 'dispensed', 1);
    $this->seedMonthlyPackage($patient, $child, '2026-02-11', 'pending', 2);
    $this->seedMonthlyPackage($patient, $child, '2026-03-11', 'dispensed', 3);
    $this->seedMonthlyPackage($patient, $child, '2026-04-11', 'pending', 4);
    $this->seedMonthlyPackage($patient, $child, '2026-05-11', 'dispensed', 5);
    $this->seedMonthlyPackage($patient, $child, '2026-06-11', 'pending', 6);

    $this->actingAs($this->facilityAdmin);
    $component = Livewire::test(FacilityReports::class)
      ->set('selected_report', 'monthly_nhmis_summary');

    $component
      ->set('date_from', '2026-01-01')
      ->set('date_to', '2026-03-31')
      ->call('generateReport')
      ->assertSet('show_results', true);
    [$q1Summary, $q1Payload] = $this->extractSummaryKeyValues();
    $this->assertSame('2026-01-01', (string) ($q1Payload['date_from'] ?? ''));
    $this->assertSame('2026-03-31', (string) ($q1Payload['date_to'] ?? ''));
    $this->assertMonthlyKeyValues($q1Summary, [
      'immunization.bcg' => 3,
      'child_health.muac_screened' => 3,
      'pharmacy.prescriptions_total' => 3,
      'pharmacy.prescriptions_dispensed' => 2,
      'pharmacy.prescriptions_pending' => 1,
      'pharmacy.dispense_lines' => 3,
      'pharmacy.dispensed_quantity_total' => 6.0,
    ]);

    $component
      ->set('date_from', '2026-04-01')
      ->set('date_to', '2026-06-30')
      ->call('generateReport')
      ->assertSet('show_results', true);
    [$q2Summary, $q2Payload] = $this->extractSummaryKeyValues();
    $this->assertSame('2026-04-01', (string) ($q2Payload['date_from'] ?? ''));
    $this->assertSame('2026-06-30', (string) ($q2Payload['date_to'] ?? ''));
    $this->assertMonthlyKeyValues($q2Summary, [
      'immunization.bcg' => 3,
      'child_health.muac_screened' => 3,
      'pharmacy.prescriptions_total' => 3,
      'pharmacy.prescriptions_dispensed' => 1,
      'pharmacy.prescriptions_pending' => 2,
      'pharmacy.dispense_lines' => 3,
      'pharmacy.dispensed_quantity_total' => 15.0,
    ]);

    $component
      ->set('date_from', '2026-01-01')
      ->set('date_to', '2026-06-30')
      ->call('generateReport')
      ->assertSet('show_results', true);
    [$halfYearSummary, $halfYearPayload] = $this->extractSummaryKeyValues();
    $this->assertSame('2026-01-01', (string) ($halfYearPayload['date_from'] ?? ''));
    $this->assertSame('2026-06-30', (string) ($halfYearPayload['date_to'] ?? ''));
    $this->assertMonthlyKeyValues($halfYearSummary, [
      'immunization.bcg' => 6,
      'child_health.muac_screened' => 6,
      'pharmacy.prescriptions_total' => 6,
      'pharmacy.prescriptions_dispensed' => 3,
      'pharmacy.prescriptions_pending' => 3,
      'pharmacy.dispense_lines' => 6,
      'pharmacy.dispensed_quantity_total' => 21.0,
    ]);

    // Re-run Q1 to verify wider-window generation does not pollute later narrow windows.
    $component
      ->set('date_from', '2026-01-01')
      ->set('date_to', '2026-03-31')
      ->call('generateReport')
      ->assertSet('show_results', true);
    [$q1RepeatSummary] = $this->extractSummaryKeyValues();
    $this->assertMonthlyKeyValues($q1RepeatSummary, [
      'immunization.bcg' => 3,
      'child_health.muac_screened' => 3,
      'pharmacy.prescriptions_total' => 3,
      'pharmacy.dispense_lines' => 3,
      'pharmacy.dispensed_quantity_total' => 6.0,
    ]);
  }

  public function test_monthly_nhmis_summary_handles_high_volume_monthly_dataset(): void
  {
    $patient = $this->createPatient($this->facilityId);
    $reportDate = '2026-07-15';

    for ($i = 1; $i <= 120; $i++) {
      $isDispensed = ($i % 3) === 0;
      $status = $isDispensed ? 'dispensed' : 'pending';
      $quantity = 1.0;
      $date = '2026-07-' . str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT);

      $prescription = Prescription::query()->create([
        'patient_id' => $patient->id,
        'facility_id' => $this->facilityId,
        'month_year' => '2026-07-01',
        'prescribed_date' => $date,
        'drug_name' => 'Drug ' . $i,
        'status' => $status,
        'quantity_prescribed' => $quantity,
        'quantity_dispensed' => $isDispensed ? $quantity : 0,
        'prescribed_by' => 'Volume Clinician',
        'dispensed_by' => $isDispensed ? 'Volume Pharmacist' : null,
        'dispensed_date' => $isDispensed ? $date : null,
      ]);

      DrugDispenseLine::query()->create([
        'patient_id' => $patient->id,
        'facility_id' => $this->facilityId,
        'month_year' => '2026-07-01',
        'dispensed_date' => $reportDate,
        'dispense_code' => 'DS-HV-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
        'prescription_id' => $prescription->id,
        'drug_name' => 'Drug ' . $i,
        'quantity' => $quantity,
        'dispensed_by' => 'Volume Pharmacist',
      ]);
    }

    $this->actingAs($this->facilityAdmin);
    Livewire::test(FacilityReports::class)
      ->set('selected_report', 'monthly_nhmis_summary')
      ->set('date_from', '2026-07-01')
      ->set('date_to', '2026-07-31')
      ->call('generateReport')
      ->assertSet('show_results', true);

    [$summary, $payload] = $this->extractSummaryKeyValues();
    $this->assertSame('2026-07-01', (string) ($payload['date_from'] ?? ''));
    $this->assertSame('2026-07-31', (string) ($payload['date_to'] ?? ''));
    $this->assertMonthlyKeyValues($summary, [
      'pharmacy.prescriptions_total' => 120,
      'pharmacy.prescriptions_dispensed' => 40,
      'pharmacy.prescriptions_pending' => 80,
      'pharmacy.dispense_lines' => 120,
      'pharmacy.dispensed_quantity_total' => 120.0,
    ]);
  }

  public function test_monthly_nhmis_summary_full_year_high_volume_mixed_modules_is_deterministic(): void
  {
    $pairs = [];
    for ($i = 1; $i <= 8; $i++) {
      $patient = $this->createPatient($this->facilityId);
      $child = $this->createLinkedChild($patient, $this->facilityId, now()->subYears(2)->toDateString());
      $pairs[] = ['patient' => $patient, 'child' => $child, 'qty' => $i];
    }

    for ($month = 1; $month <= 12; $month++) {
      foreach ($pairs as $idx => $pair) {
        $day = str_pad((string) (($idx % 8) + 1), 2, '0', STR_PAD_LEFT);
        $date = sprintf('2026-%02d-%s', $month, $day);
        $status = (($month + $idx) % 2 === 0) ? 'dispensed' : 'pending';
        $this->seedMonthlyPackage(
          $pair['patient'],
          $pair['child'],
          $date,
          $status,
          (int) $pair['qty']
        );
      }
    }

    $this->actingAs($this->facilityAdmin);
    $component = Livewire::test(FacilityReports::class)
      ->set('selected_report', 'monthly_nhmis_summary');

    $component
      ->set('date_from', '2026-01-01')
      ->set('date_to', '2026-12-31')
      ->call('generateReport')
      ->assertSet('show_results', true);
    [$yearSummary, $yearPayload] = $this->extractSummaryKeyValues();
    $this->assertSame('2026-01-01', (string) ($yearPayload['date_from'] ?? ''));
    $this->assertSame('2026-12-31', (string) ($yearPayload['date_to'] ?? ''));
    $this->assertMonthlyKeyValues($yearSummary, [
      'immunization.bcg' => 96,
      'child_health.muac_screened' => 96,
      'pharmacy.prescriptions_total' => 96,
      'pharmacy.prescriptions_dispensed' => 48,
      'pharmacy.prescriptions_pending' => 48,
      'pharmacy.dispense_lines' => 96,
      'pharmacy.dispensed_quantity_total' => 432.0,
    ]);

    $component
      ->set('date_from', '2026-10-01')
      ->set('date_to', '2026-12-31')
      ->call('generateReport')
      ->assertSet('show_results', true);
    [$q4Summary, $q4Payload] = $this->extractSummaryKeyValues();
    $this->assertSame('2026-10-01', (string) ($q4Payload['date_from'] ?? ''));
    $this->assertSame('2026-12-31', (string) ($q4Payload['date_to'] ?? ''));
    $this->assertMonthlyKeyValues($q4Summary, [
      'immunization.bcg' => 24,
      'child_health.muac_screened' => 24,
      'pharmacy.prescriptions_total' => 24,
      'pharmacy.prescriptions_dispensed' => 12,
      'pharmacy.prescriptions_pending' => 12,
      'pharmacy.dispense_lines' => 24,
      'pharmacy.dispensed_quantity_total' => 108.0,
    ]);

    // Re-run full year to guarantee no drift after narrowing window.
    $component
      ->set('date_from', '2026-01-01')
      ->set('date_to', '2026-12-31')
      ->call('generateReport')
      ->assertSet('show_results', true);
    [$yearRepeatSummary] = $this->extractSummaryKeyValues();
    $this->assertMonthlyKeyValues($yearRepeatSummary, [
      'immunization.bcg' => 96,
      'child_health.muac_screened' => 96,
      'pharmacy.prescriptions_total' => 96,
      'pharmacy.dispense_lines' => 96,
      'pharmacy.dispensed_quantity_total' => 432.0,
    ]);
  }

  private function extractSummaryKeyValues(): array
  {
    $snapshotKey = (string) session('reports_hub_print_snapshot_key', '');
    $this->assertNotSame('', $snapshotKey);
    $this->assertNull(session('reports_hub_print_payload'));

    $snapshot = ReportSnapshot::query()
      ->where('snapshot_key', $snapshotKey)
      ->first();

    $this->assertNotNull($snapshot);

    $payload = (array) ($snapshot->payload ?? []);
    $this->assertNotEmpty($payload);

    $summary = (array) ($payload['summary_key_values'] ?? []);
    $this->assertNotEmpty($summary);

    return [$summary, $payload];
  }

  private function assertMonthlyKeyValues(array $summary, array $expected): void
  {
    foreach ($expected as $key => $value) {
      $this->assertArrayHasKey($key, $summary, "Missing summary key: {$key}");
      if (is_float($value)) {
        $this->assertEquals($value, (float) $summary[$key], "Unexpected float value for {$key}");
        continue;
      }
      $this->assertSame((int) $value, (int) $summary[$key], "Unexpected integer value for {$key}");
    }
  }

  private function seedMonthlyPackage(Patient $patient, LinkedChild $child, string $visitDate, string $prescriptionStatus, int $quantity): void
  {
    $monthYear = substr($visitDate, 0, 7) . '-01';
    $dispenseCodeDate = str_replace('-', '', substr($visitDate, 0, 7));

    ImmunizationRecord::query()->create([
      'patient_id' => $patient->id,
      'linked_child_id' => $child->id,
      'facility_id' => $this->facilityId,
      'month_year' => $monthYear,
      'visit_date' => $visitDate,
      'bcg_date' => $visitDate,
      'officer_name' => 'QA Officer',
      'officer_role' => 'Facility Administrator',
    ]);

    ChildHealthActivityRecord::query()->create([
      'patient_id' => $patient->id,
      'linked_child_id' => $child->id,
      'facility_id' => $this->facilityId,
      'month_year' => $monthYear,
      'visit_date' => $visitDate,
      'vaccination_dates' => ['bcg' => $visitDate],
      'breastfeeding_entries' => ['month' => 'NO'],
      'weight_entries' => [],
      'officer_name' => 'QA Officer',
      'officer_role' => 'Facility Administrator',
    ]);

    NutritionRecord::query()->create([
      'patient_id' => $patient->id,
      'linked_child_id' => $child->id,
      'facility_id' => $this->facilityId,
      'month_year' => $monthYear,
      'visit_date' => $visitDate,
      'age_group' => '6-23 months',
      'infant_feeding' => 'Not BF',
      'muac_value_mm' => 125,
      'muac_class' => 'Green',
      'officer_name' => 'QA Officer',
      'officer_role' => 'Facility Administrator',
    ]);

    $prescription = Prescription::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'month_year' => $monthYear,
      'prescribed_date' => $visitDate,
      'drug_name' => 'Package Drug ' . $visitDate,
      'status' => $prescriptionStatus,
      'quantity_prescribed' => $quantity,
      'quantity_dispensed' => $prescriptionStatus === 'dispensed' ? $quantity : 0,
      'prescribed_by' => 'QA Clinician',
      'dispensed_by' => $prescriptionStatus === 'dispensed' ? 'QA Pharmacist' : null,
      'dispensed_date' => $prescriptionStatus === 'dispensed' ? $visitDate : null,
    ]);

    DrugDispenseLine::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'month_year' => $monthYear,
      'dispensed_date' => $visitDate,
      'dispense_code' => 'DS-PKG-' . $dispenseCodeDate . '-' . $quantity,
      'prescription_id' => $prescription->id,
      'drug_name' => 'Package Drug ' . $visitDate,
      'quantity' => $quantity,
      'dispensed_by' => 'QA Pharmacist',
    ]);
  }

  private function seedJanRecordsForPrimaryFacility(Patient $patient, LinkedChild $child): void
  {
    ImmunizationRecord::query()->create([
      'patient_id' => $patient->id,
      'linked_child_id' => $child->id,
      'facility_id' => $this->facilityId,
      'month_year' => '2026-01-01',
      'visit_date' => '2026-01-10',
      'bcg_date' => '2026-01-10',
      'officer_name' => 'QA Officer',
      'officer_role' => 'Facility Administrator',
    ]);

    ChildHealthActivityRecord::query()->create([
      'patient_id' => $patient->id,
      'linked_child_id' => $child->id,
      'facility_id' => $this->facilityId,
      'month_year' => '2026-01-01',
      'visit_date' => '2026-01-10',
      'vaccination_dates' => ['bcg' => '2026-01-10'],
      'breastfeeding_entries' => ['birth' => 'E'],
      'weight_entries' => [],
      'officer_name' => 'QA Officer',
      'officer_role' => 'Facility Administrator',
    ]);

    NutritionRecord::query()->create([
      'patient_id' => $patient->id,
      'linked_child_id' => $child->id,
      'facility_id' => $this->facilityId,
      'month_year' => '2026-01-01',
      'visit_date' => '2026-01-10',
      'age_group' => '0-5 months',
      'infant_feeding' => 'Exclusive BF',
      'muac_value_mm' => 110,
      'muac_class' => 'Red',
      'officer_name' => 'QA Officer',
      'officer_role' => 'Facility Administrator',
    ]);

    $prescription = Prescription::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'month_year' => '2026-01-01',
      'prescribed_date' => '2026-01-15',
      'drug_name' => 'Amoxicillin',
      'status' => 'dispensed',
      'quantity_prescribed' => 2,
      'quantity_dispensed' => 2,
      'prescribed_by' => 'QA Clinician',
      'dispensed_by' => 'QA Pharmacist',
      'dispensed_date' => '2026-01-15',
    ]);

    DrugDispenseLine::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'month_year' => '2026-01-01',
      'dispensed_date' => '2026-01-15',
      'dispense_code' => 'DS-JAN-001',
      'prescription_id' => $prescription->id,
      'drug_name' => 'Amoxicillin',
      'quantity' => 2,
      'dispensed_by' => 'QA Pharmacist',
    ]);
  }

  private function seedFebRecordsForPrimaryFacility(Patient $patient, LinkedChild $child): void
  {
    ImmunizationRecord::query()->create([
      'patient_id' => $patient->id,
      'linked_child_id' => $child->id,
      'facility_id' => $this->facilityId,
      'month_year' => '2026-02-01',
      'visit_date' => '2026-02-14',
      'bcg_date' => '2026-02-14',
      'officer_name' => 'QA Officer',
      'officer_role' => 'Facility Administrator',
    ]);

    ChildHealthActivityRecord::query()->create([
      'patient_id' => $patient->id,
      'linked_child_id' => $child->id,
      'facility_id' => $this->facilityId,
      'month_year' => '2026-02-01',
      'visit_date' => '2026-02-14',
      'vaccination_dates' => ['bcg' => '2026-02-14'],
      'breastfeeding_entries' => ['month_7' => 'NO'],
      'weight_entries' => [],
      'officer_name' => 'QA Officer',
      'officer_role' => 'Facility Administrator',
    ]);

    NutritionRecord::query()->create([
      'patient_id' => $patient->id,
      'linked_child_id' => $child->id,
      'facility_id' => $this->facilityId,
      'month_year' => '2026-02-01',
      'visit_date' => '2026-02-14',
      'age_group' => '6-23 months',
      'infant_feeding' => 'Not BF',
      'muac_value_mm' => 132,
      'muac_class' => 'Green',
      'officer_name' => 'QA Officer',
      'officer_role' => 'Facility Administrator',
    ]);

    $prescription = Prescription::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'month_year' => '2026-02-01',
      'prescribed_date' => '2026-02-20',
      'drug_name' => 'Paracetamol',
      'status' => 'pending',
      'quantity_prescribed' => 3,
      'prescribed_by' => 'QA Clinician',
    ]);

    DrugDispenseLine::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'month_year' => '2026-02-01',
      'dispensed_date' => '2026-02-20',
      'dispense_code' => 'DS-FEB-001',
      'prescription_id' => $prescription->id,
      'drug_name' => 'Paracetamol',
      'quantity' => 3,
      'dispensed_by' => 'QA Pharmacist',
    ]);
  }

  private function seedNoiseForOtherFacility(Patient $patient, LinkedChild $child): void
  {
    ImmunizationRecord::query()->create([
      'patient_id' => $patient->id,
      'linked_child_id' => $child->id,
      'facility_id' => $this->otherFacilityId,
      'month_year' => '2026-01-01',
      'visit_date' => '2026-01-12',
      'bcg_date' => '2026-01-12',
      'officer_name' => 'Other Officer',
      'officer_role' => 'Facility Administrator',
    ]);

    Prescription::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->otherFacilityId,
      'month_year' => '2026-01-01',
      'prescribed_date' => '2026-01-12',
      'drug_name' => 'Other Facility Drug',
      'status' => 'dispensed',
      'quantity_prescribed' => 9,
      'quantity_dispensed' => 9,
      'prescribed_by' => 'Other Clinician',
      'dispensed_by' => 'Other Pharmacist',
      'dispensed_date' => '2026-01-12',
    ]);

    DrugDispenseLine::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->otherFacilityId,
      'month_year' => '2026-01-01',
      'dispensed_date' => '2026-01-12',
      'dispense_code' => 'DS-OTH-001',
      'drug_name' => 'Other Facility Drug',
      'quantity' => 9,
      'dispensed_by' => 'Other Pharmacist',
    ]);
  }

  private function createIsolatedFacilityId(): int
  {
    $token = Str::upper(Str::random(5));
    $now = now();

    $stateId = DB::table('states')->insertGetId([
      'name' => 'Test State ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $lgaId = DB::table('lgas')->insertGetId([
      'state_id' => $stateId,
      'name' => 'Test LGA ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $wardId = DB::table('wards')->insertGetId([
      'lga_id' => $lgaId,
      'name' => 'Test Ward ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    return (int) DB::table('facilities')->insertGetId([
      'name' => 'Test Facility ' . $token,
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'ward_id' => $wardId,
      'address' => 'No. 1 Test Street',
      'phone' => '08000000000',
      'email' => 'facility_' . strtolower($token) . '@example.com',
      'type' => 'Primary',
      'is_active' => 1,
      'created_at' => $now,
      'updated_at' => $now,
    ]);
  }

  private function createUser(string $role, int $facilityId): User
  {
    $token = strtolower(Str::random(10));

    return User::query()->create([
      'first_name' => 'Facility',
      'last_name' => 'Admin',
      'username' => 'facility_admin_' . $token,
      'email' => 'facility_admin_' . $token . '@example.com',
      'phone' => '0800000' . random_int(100, 999),
      'password' => bcrypt('password'),
      'role' => $role,
      'facility_id' => $facilityId,
      'is_active' => true,
    ]);
  }

  private function createPatient(int $facilityId): Patient
  {
    return Patient::query()->create([
      'din' => str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
      'first_name' => 'Report',
      'last_name' => 'Patient',
      'gender' => 'Female',
      'date_of_birth' => now()->subYears(24)->toDateString(),
      'phone' => '0802' . random_int(1000000, 9999999),
      'facility_id' => $facilityId,
      'registration_date' => now()->toDateString(),
      'is_active' => true,
    ]);
  }

  private function createLinkedChild(Patient $patient, int $facilityId, string $dob): LinkedChild
  {
    return LinkedChild::query()->create([
      'linked_child_id' => 'LC-' . str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT),
      'parent_patient_id' => $patient->id,
      'first_name' => 'Baby',
      'last_name' => $patient->last_name,
      'gender' => 'Female',
      'date_of_birth' => $dob,
      'relationship' => 'Child',
      'is_active' => true,
      'facility_id' => $facilityId,
    ]);
  }
}
