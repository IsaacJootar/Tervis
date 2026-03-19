<?php

namespace Tests\Feature;

use App\Livewire\Workspaces\Modules\Activities;
use App\Livewire\Workspaces\Modules\Appointments;
use App\Livewire\Workspaces\Modules\Clinical\DoctorAssessments;
use App\Livewire\Workspaces\Modules\Clinical\Prescriptions;
use App\Livewire\Workspaces\Modules\Reminders;
use App\Livewire\Workspaces\Modules\Visits;
use App\Livewire\Workspaces\PatientWorkspace;
use App\Models\Activity;
use App\Models\DoctorAssessment;
use App\Models\DrugCatalogItem;
use App\Models\DrugStockBatch;
use App\Models\Invoice;
use App\Models\LabTestOrder;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Registrations\FamilyPlanningRegistration;
use App\Models\Registrations\DinActivation;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class WorkspaceAggregationChainTest extends TestCase
{
  use DatabaseTransactions;

  private int $facilityId;
  private User $dataOfficer;

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
      'din_activations',
      'doctor_assessments',
      'activities',
      'reminders',
      'visits',
      'visit_events',
    ];

    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available.");
      }
    }

    $this->facilityId = $this->ensureFacilityId();
    $this->dataOfficer = $this->createUser('Data Officer', $this->facilityId);
  }

  public function test_reminders_component_syncs_from_doctor_assessment_source_dates(): void
  {
    $patient = $this->createPatient();
    $this->createActivation($patient, now()->toDateString(), '08:15:00');

    $assessment = DoctorAssessment::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'visit_date' => now()->toDateString(),
      'next_appointment_date' => now()->addDays(3)->toDateString(),
      'final_diagnosis' => 'Routine follow-up',
    ]);

    $this->actingAs($this->dataOfficer);

    Livewire::test(Reminders::class, ['patientId' => $patient->id])
      ->assertSet('hasAccess', true);

    $this->assertDatabaseHas('reminders', [
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'source_module' => 'doctor_assessment',
      'source_record_id' => $assessment->id,
      'status' => 'pending',
    ]);

    $this->assertDatabaseHas('activities', [
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'module' => 'reminders',
      'action' => 'sync',
    ]);
  }

  public function test_appointments_summary_reflects_upcoming_and_fulfilled_statuses(): void
  {
    $patient = $this->createPatient();
    $appointmentDate = now()->addDays(2)->toDateString();

    $this->createActivation($patient, now()->toDateString(), '09:00:00');

    $assessment = DoctorAssessment::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'visit_date' => now()->toDateString(),
      'next_appointment_date' => $appointmentDate,
      'final_diagnosis' => 'Review required',
    ]);

    $this->actingAs($this->dataOfficer);

    $component = Livewire::test(Appointments::class, ['patientId' => $patient->id])
      ->assertSet('hasAccess', true);

    $initialSummary = $component->viewData('summary');

    $this->assertSame(1, (int) ($initialSummary['upcoming'] ?? 0));
    $this->assertSame(0, (int) ($initialSummary['fulfilled'] ?? 0));

    $assessment->update([
      'next_appointment_date' => now()->subDay()->toDateString(),
    ]);

    $fulfilledSummary = $component
      ->call('$refresh')
      ->viewData('summary');

    $this->assertSame(0, (int) ($fulfilledSummary['upcoming'] ?? 0));
    $this->assertSame(1, (int) ($fulfilledSummary['fulfilled'] ?? 0));
  }

  public function test_visits_sync_creates_visit_and_event_rows_from_activity_timeline(): void
  {
    $patient = $this->createPatient();
    $this->createActivation($patient, now()->toDateString(), '07:45:00');

    $activity = Activity::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'module' => 'laboratory',
      'action' => 'record',
      'description' => 'Lab result captured',
      'performed_by' => 'Data Officer QA',
      'meta' => ['sample_id' => 'LAB-001'],
      'created_at' => now()->subMinutes(30),
      'updated_at' => now()->subMinutes(30),
    ]);

    $this->actingAs($this->dataOfficer);

    Livewire::test(Visits::class, ['patientId' => $patient->id])
      ->assertSet('hasAccess', true)
      ->call('syncVisits', false);

    /** @var Visit|null $visit */
    $visit = Visit::query()
      ->where('patient_id', $patient->id)
      ->where('facility_id', $this->facilityId)
      ->whereDate('visit_date', now()->toDateString())
      ->first();

    $this->assertNotNull($visit);
    $this->assertGreaterThanOrEqual(1, (int) $visit->total_events);

    $event = VisitEvent::query()
      ->where('visit_id', $visit->id)
      ->where('activity_id', $activity->id)
      ->first();

    $this->assertNotNull($event);
    $this->assertSame('laboratory', $event->module);
    $this->assertSame('record', $event->action);
  }

  public function test_workspace_modules_block_access_without_todays_activation(): void
  {
    $patient = $this->createPatient();
    $this->actingAs($this->dataOfficer);

    Livewire::test(Activities::class, ['patientId' => $patient->id])
      ->assertSet('hasAccess', false)
      ->assertSet('accessError', 'Patient has not been checked in today. Please activate DIN first.');
  }

  public function test_doctor_lab_prescription_billing_chain_transitions_end_to_end(): void
  {
    $requiredTables = [
      'lab_test_orders',
      'prescriptions',
      'drug_catalog_items',
      'drug_stock_batches',
      'drug_stock_movements',
      'drug_dispense_lines',
      'invoices',
      'invoice_lines',
      'patient_payments',
      'payment_allocations',
    ];

    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available for full chain test.");
      }
    }

    $patient = $this->createPatient();
    $this->createActivation($patient, now()->toDateString(), '08:30:00');
    $this->actingAs($this->dataOfficer);

    Livewire::test(DoctorAssessments::class, ['patientId' => $patient->id])
      ->assertSet('hasAccess', true)
      ->set('assessment_note', 'Patient assessed with likely uncomplicated infection.')
      ->set('final_diagnosis', 'Suspected uncomplicated infection')
      ->set('test_orders', [
        ['test_name' => 'Malaria Parasite', 'specimen' => 'Blood'],
      ])
      ->set('drug_orders', [
        [
          'drug_name' => 'Amoxicillin',
          'dosage' => '500mg',
          'frequency' => 'BD',
          'duration' => '5 days',
          'route' => 'Oral',
          'instructions' => 'After meals',
          'quantity_prescribed' => 1,
        ],
      ])
      ->call('store');

    $labOrder = LabTestOrder::query()
      ->where('patient_id', $patient->id)
      ->where('facility_id', $this->facilityId)
      ->where('status', 'pending')
      ->latest('id')
      ->first();

    $pendingPrescription = Prescription::query()
      ->where('patient_id', $patient->id)
      ->where('facility_id', $this->facilityId)
      ->where('status', 'pending')
      ->latest('id')
      ->first();

    $this->assertNotNull($labOrder, 'Doctor assessment did not create pending lab order.');
    $this->assertNotNull($pendingPrescription, 'Doctor assessment did not create pending prescription.');

    // Mark as completed to represent lab execution in this full-chain integration test.
    $labOrder->update([
      'status' => 'completed',
      'completed_at' => now(),
      'completed_by' => $this->dataOfficer->first_name . ' ' . $this->dataOfficer->last_name,
      'completion_notes' => 'Completed during integration workflow test.',
    ]);

    $labOrder->refresh();
    $this->assertSame('completed', $labOrder->status);

    $catalogItem = DrugCatalogItem::query()->create([
      'facility_id' => $this->facilityId,
      'drug_name' => 'Amoxicillin',
      'formulation' => 'Capsule',
      'strength' => '500mg',
      'route' => 'Oral',
      'reorder_level' => 10,
      'is_active' => true,
    ]);

    $batch = DrugStockBatch::query()->create([
      'facility_id' => $this->facilityId,
      'drug_catalog_item_id' => $catalogItem->id,
      'batch_number' => 'BATCH-' . strtoupper(Str::random(6)),
      'received_date' => now()->toDateString(),
      'expiry_date' => now()->addMonths(12)->toDateString(),
      'quantity_received' => 20,
      'quantity_available' => 20,
      'is_active' => true,
    ]);

    Livewire::test(Prescriptions::class, ['patientId' => $patient->id])
      ->set('selected_prescription_map', [(string) $pendingPrescription->id => true])
      ->set('selected_catalog_id', $catalogItem->id)
      ->set('entry_quantity', 1)
      ->call('addToCart')
      ->set('charge_amount', 2500)
      ->call('checkoutDispensing');

    $pendingPrescription->refresh();
    $batch->refresh();

    $this->assertSame('dispensed', $pendingPrescription->status);
    $this->assertEquals(19.0, (float) $batch->quantity_available);

    $this->assertDatabaseHas('drug_dispense_lines', [
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'prescription_id' => $pendingPrescription->id,
      'drug_catalog_item_id' => $catalogItem->id,
      'drug_name' => 'Amoxicillin',
    ]);

    /** @var Invoice|null $invoice */
    $invoice = Invoice::query()
      ->where('patient_id', $patient->id)
      ->where('facility_id', $this->facilityId)
      ->latest('id')
      ->first();

    $this->assertNotNull($invoice, 'Checkout did not generate invoice.');
    $this->assertSame('unpaid', $invoice->status);
    $this->assertEquals(2500.0, (float) $invoice->total_amount);
    $this->assertEquals(2500.0, (float) $invoice->outstanding_amount);

    $this->assertDatabaseHas('invoice_lines', [
      'invoice_id' => $invoice->id,
      'module' => 'prescriptions',
      'line_amount' => 2500,
    ]);
  }

  public function test_patient_workspace_verification_detects_fp_registration_across_facilities(): void
  {
    if (!Schema::hasTable('family_planning_registrations')) {
      $this->markTestSkipped('Required table family_planning_registrations is not available.');
    }

    $otherFacilityId = $this->ensureSecondFacilityId($this->facilityId);
    $patient = $this->createPatient($otherFacilityId);
    $this->createActivation($patient, now()->toDateString(), '10:05:00');

    $registration = FamilyPlanningRegistration::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $otherFacilityId,
      'registration_date' => now()->subDays(10)->toDateString(),
      'officer_name' => 'Scope Officer',
      'officer_role' => 'Data Officer',
    ]);

    $this->actingAs($this->dataOfficer);
    Livewire::test(PatientWorkspace::class)
      ->set('din', $patient->din)
      ->call('verifyPatient')
      ->assertSet('isPatientVerified', true)
      ->assertSet('has_family_planning_registration', true);

    $this->assertNotNull($registration->id);
  }

  public function test_appointments_and_activities_are_facility_scoped_for_same_patient(): void
  {
    $otherFacilityId = $this->ensureSecondFacilityId($this->facilityId);
    $patient = $this->createPatient($otherFacilityId);
    $this->createActivation($patient, now()->toDateString(), '10:30:00');

    DoctorAssessment::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'visit_date' => now()->toDateString(),
      'next_appointment_date' => now()->addDay()->toDateString(),
      'final_diagnosis' => 'Facility A diagnosis',
    ]);

    DoctorAssessment::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $otherFacilityId,
      'visit_date' => now()->toDateString(),
      'next_appointment_date' => now()->addDays(2)->toDateString(),
      'final_diagnosis' => 'Facility B diagnosis',
    ]);

    Activity::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'module' => 'assessments',
      'action' => 'create',
      'description' => 'Facility A assessment logged',
      'performed_by' => $this->dataOfficer->full_name,
      'created_at' => now()->subMinutes(5),
      'updated_at' => now()->subMinutes(5),
    ]);

    Activity::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $otherFacilityId,
      'module' => 'assessments',
      'action' => 'create',
      'description' => 'Facility B assessment logged',
      'performed_by' => 'Other Facility Officer',
      'created_at' => now()->subMinutes(4),
      'updated_at' => now()->subMinutes(4),
    ]);

    $this->actingAs($this->dataOfficer);
    $appointments = Livewire::test(Appointments::class, ['patientId' => $patient->id])
      ->assertSet('hasAccess', true);

    $summary = (array) $appointments->viewData('summary');
    $this->assertSame(1, (int) ($summary['total'] ?? 0));
    $this->assertSame(1, (int) ($summary['upcoming'] ?? 0));

    $this->assertSame(
      1,
      (int) Activity::query()
        ->where('patient_id', $patient->id)
        ->where('facility_id', $this->facilityId)
        ->count()
    );
    $this->assertSame(2, (int) Activity::query()->where('patient_id', $patient->id)->count());
  }

  private function createActivation(Patient $patient, string $visitDate, string $checkInTime): DinActivation
  {
    return DinActivation::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'visit_date' => $visitDate,
      'check_in_time' => $checkInTime,
      'patient_din' => $patient->din,
      'patient_first_name' => $patient->first_name,
      'patient_last_name' => $patient->last_name,
      'patient_phone' => $patient->phone,
      'patient_age' => $patient->date_of_birth ? $patient->date_of_birth->age : null,
      'patient_gender' => $patient->gender,
      'officer_name' => $this->dataOfficer->full_name,
      'officer_role' => $this->dataOfficer->role,
    ]);
  }

  private function ensureFacilityId(): int
  {
    $facilityId = (int) DB::table('facilities')->value('id');
    if ($facilityId > 0) {
      return $facilityId;
    }

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
      'first_name' => 'Data',
      'last_name' => 'Officer',
      'username' => 'data_officer_' . $token,
      'email' => 'data_officer_' . $token . '@example.com',
      'phone' => '0800000' . random_int(100, 999),
      'password' => bcrypt('password'),
      'role' => $role,
      'facility_id' => $facilityId,
      'is_active' => true,
    ]);
  }

  private function createPatient(?int $facilityId = null): Patient
  {
    $facilityId = $facilityId ?: $this->facilityId;

    return Patient::query()->create([
      'din' => str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'gender' => 'Female',
      'date_of_birth' => now()->subYears(5)->toDateString(),
      'phone' => '0802' . random_int(1000000, 9999999),
      'facility_id' => $facilityId,
      'registration_date' => now()->toDateString(),
      'is_active' => true,
    ]);
  }

  private function ensureSecondFacilityId(int $primaryFacilityId): int
  {
    $existingId = (int) DB::table('facilities')
      ->where('id', '!=', $primaryFacilityId)
      ->value('id');

    if ($existingId > 0) {
      return $existingId;
    }

    $primary = DB::table('facilities')->where('id', $primaryFacilityId)->first();
    if (!$primary) {
      return $this->ensureFacilityId();
    }

    $token = Str::upper(Str::random(5));
    $now = now();

    return (int) DB::table('facilities')->insertGetId([
      'name' => 'Secondary Facility ' . $token,
      'state_id' => $primary->state_id,
      'lga_id' => $primary->lga_id,
      'ward_id' => $primary->ward_id,
      'address' => 'No. 2 Scope Street',
      'phone' => '08000000001',
      'email' => 'secondary_' . strtolower($token) . '@example.com',
      'type' => $primary->type ?? 'Primary',
      'is_active' => 1,
      'created_at' => $now,
      'updated_at' => $now,
    ]);
  }
}
