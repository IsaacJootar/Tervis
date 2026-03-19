<?php

namespace Tests\Feature;

use App\Livewire\Workspaces\Modules\Child\ActivityRegister;
use App\Livewire\Workspaces\Modules\Child\Immunizations;
use App\Livewire\Workspaces\Modules\HealthInsurance;
use App\Models\Activity;
use App\Models\ChildHealthActivityRecord;
use App\Models\ImmunizationRecord;
use App\Models\LinkedChild;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ChildHealthInsuranceWorkflowTest extends TestCase
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
      'linked_children',
      'din_activations',
      'activities',
      'immunization_records',
      'child_health_activity_records',
    ];

    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available.");
      }
    }

    $this->facilityId = $this->ensureFacilityId();
    $this->dataOfficer = $this->createUser('Data Officer', $this->facilityId);
  }

  public function test_immunization_store_creates_summary_and_activity_log(): void
  {
    $patient = $this->createPatient();
    $child = $this->createLinkedChild($patient, now()->subMonths(9)->toDateString());
    $this->createActivation($patient, now()->toDateString(), '08:10:00');

    $validDate = $child->date_of_birth->copy()->addDay()->format('Y-m-d');

    $this->actingAs($this->dataOfficer);
    Livewire::test(Immunizations::class, ['patientId' => $patient->id])
      ->assertSet('hasAccess', true)
      ->set('linked_child_id', $child->id)
      ->set('hepb0_date', $validDate)
      ->set('opv0_date', $validDate)
      ->call('store');

    /** @var ImmunizationRecord|null $record */
    $record = ImmunizationRecord::query()
      ->where('patient_id', $patient->id)
      ->where('facility_id', $this->facilityId)
      ->latest('id')
      ->first();

    $this->assertNotNull($record);
    $this->assertSame($child->id, (int) $record->linked_child_id);
    $this->assertSame(2, (int) ($record->summary_map['given_vaccines_count'] ?? 0));
    $this->assertTrue((bool) ($record->summary_map['hepb0'] ?? false));
    $this->assertTrue((bool) ($record->summary_map['opv0'] ?? false));

    $this->assertDatabaseHas('activities', [
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'module' => 'child_immunization',
      'action' => 'create',
    ]);
  }

  public function test_immunization_rejects_vaccine_date_earlier_than_child_dob(): void
  {
    $patient = $this->createPatient();
    $child = $this->createLinkedChild($patient, now()->subMonths(6)->toDateString());
    $this->createActivation($patient, now()->toDateString(), '08:20:00');

    $invalidDate = $child->date_of_birth->copy()->subDay()->format('Y-m-d');

    $this->actingAs($this->dataOfficer);
    Livewire::test(Immunizations::class, ['patientId' => $patient->id])
      ->set('linked_child_id', $child->id)
      ->set('hepb0_date', $invalidDate)
      ->call('store');

    $this->assertDatabaseMissing('immunization_records', [
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'linked_child_id' => $child->id,
      'hepb0_date' => $invalidDate,
    ]);
  }

  public function test_child_activity_register_store_persists_summary_weight_and_activity(): void
  {
    $patient = $this->createPatient();
    $child = $this->createLinkedChild($patient, now()->subMonths(10)->toDateString());
    $this->createActivation($patient, now()->toDateString(), '08:30:00');

    $this->actingAs($this->dataOfficer);
    Livewire::test(ActivityRegister::class, ['patientId' => $patient->id])
      ->assertSet('hasAccess', true)
      ->set('linked_child_id', $child->id)
      ->set('vaccination_dates.bcg', now()->subDays(1)->toDateString())
      ->set('weight_entry_date', now()->toDateString())
      ->set('weight_entry_age_months', 10)
      ->set('weight_entry_kg', 8.4)
      ->call('addWeightEntry')
      ->call('store');

    /** @var ChildHealthActivityRecord|null $record */
    $record = ChildHealthActivityRecord::query()
      ->where('patient_id', $patient->id)
      ->where('facility_id', $this->facilityId)
      ->latest('id')
      ->first();

    $this->assertNotNull($record);
    $this->assertSame($child->id, (int) $record->linked_child_id);
    $this->assertSame(1, (int) ($record->summary_map['vaccines_completed'] ?? 0));
    $this->assertSame(1, (int) ($record->summary_map['weight_entries_count'] ?? 0));
    $this->assertCount(1, (array) $record->weight_entries);

    $this->assertDatabaseHas('activities', [
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'module' => 'child_health_activity_register',
      'action' => 'create',
    ]);
  }

  public function test_health_insurance_lifecycle_activate_update_deactivate_logs_history(): void
  {
    $patient = $this->createPatient();
    $this->createActivation($patient, now()->toDateString(), '09:00:00');

    $this->actingAs($this->dataOfficer);
    $component = Livewire::test(HealthInsurance::class, ['patientId' => $patient->id])
      ->assertSet('hasAccess', true)
      ->set('is_nhis_subscriber', true)
      ->set('nhis_number', 'NHIS-001-ABC')
      ->set('nhis_provider', 'National Health Insurance')
      ->set('nhis_expiry_date', now()->addYear()->toDateString())
      ->set('nhis_plan_type', 'Family')
      ->set('nhis_principal_name', 'Jane Principal')
      ->set('nhis_principal_number', 'PR-1001')
      ->call('saveInsurance');

    $patient->refresh();
    $this->assertTrue((bool) $patient->is_nhis_subscriber);
    $this->assertSame('Family', $patient->nhis_plan_type);

    $component
      ->set('is_nhis_subscriber', true)
      ->set('nhis_number', 'NHIS-001-ABC')
      ->set('nhis_provider', 'Updated Provider')
      ->set('nhis_expiry_date', now()->addMonths(8)->toDateString())
      ->set('nhis_plan_type', 'Family')
      ->set('nhis_principal_name', 'Jane Principal Updated')
      ->set('nhis_principal_number', 'PR-1002')
      ->call('saveInsurance');

    $patient->refresh();
    $this->assertTrue((bool) $patient->is_nhis_subscriber);
    $this->assertSame('Family', $patient->nhis_plan_type);
    $this->assertSame('Updated Provider', $patient->nhis_provider);
    $this->assertSame('Jane Principal Updated', $patient->nhis_principal_name);
    $this->assertSame('PR-1002', $patient->nhis_principal_number);

    $component->call('deactivateCoverage');

    $patient->refresh();
    $this->assertFalse((bool) $patient->is_nhis_subscriber);
    $this->assertNull($patient->nhis_number);
    $this->assertNull($patient->nhis_provider);
    $this->assertNull($patient->nhis_expiry_date);

    $this->assertDatabaseHas('activities', [
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'module' => 'health_insurance',
      'action' => 'activate',
    ]);
    $this->assertDatabaseHas('activities', [
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'module' => 'health_insurance',
      'action' => 'update',
    ]);
    $this->assertDatabaseHas('activities', [
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'module' => 'health_insurance',
      'action' => 'deactivate',
    ]);
  }

  public function test_health_insurance_requires_principal_fields_for_family_plan(): void
  {
    $patient = $this->createPatient();
    $this->createActivation($patient, now()->toDateString(), '09:15:00');

    $this->actingAs($this->dataOfficer);
    Livewire::test(HealthInsurance::class, ['patientId' => $patient->id])
      ->set('is_nhis_subscriber', true)
      ->set('nhis_number', 'NHIS-VAL-001')
      ->set('nhis_provider', 'Test Provider')
      ->set('nhis_expiry_date', now()->addMonths(6)->toDateString())
      ->set('nhis_plan_type', 'Family')
      ->set('nhis_principal_name', null)
      ->set('nhis_principal_number', null)
      ->call('saveInsurance')
      ->assertHasErrors(['nhis_principal_name', 'nhis_principal_number']);
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

  private function createLinkedChild(Patient $patient, string $dob): LinkedChild
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
      'facility_id' => $this->facilityId,
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
      'first_name' => 'Health',
      'last_name' => 'Officer',
      'username' => 'health_officer_' . $token,
      'email' => 'health_officer_' . $token . '@example.com',
      'phone' => '0800000' . random_int(100, 999),
      'password' => bcrypt('password'),
      'role' => $role,
      'facility_id' => $facilityId,
      'is_active' => true,
    ]);
  }

  private function createPatient(): Patient
  {
    return Patient::query()->create([
      'din' => str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
      'first_name' => 'Janet',
      'last_name' => 'Doe',
      'gender' => 'Female',
      'date_of_birth' => now()->subYears(28)->toDateString(),
      'phone' => '0803' . random_int(1000000, 9999999),
      'facility_id' => $this->facilityId,
      'registration_date' => now()->toDateString(),
      'is_active' => true,
    ]);
  }
}
