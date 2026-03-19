<?php

namespace Tests\Feature;

use App\Livewire\Core\AdmittedPatients;
use App\Livewire\Core\BedManagement;
use App\Livewire\Core\FacilitySections;
use App\Models\Bed;
use App\Models\BedSection;
use App\Models\InpatientAdmission;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CoreFacilityWorkflowTransitionsTest extends TestCase
{
  use DatabaseTransactions;

  private int $facilityId;
  private User $facilityAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    $requiredTables = [
      'users',
      'states',
      'lgas',
      'wards',
      'facilities',
      'patients',
      'bed_sections',
      'beds',
      'inpatient_admissions',
      'activities',
    ];

    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available.");
      }
    }

    $this->facilityId = $this->ensureFacilityId();
    $this->facilityAdmin = $this->createUser('Facility Administrator', $this->facilityId);
  }

  public function test_bed_code_is_auto_generated_when_blank(): void
  {
    $section = BedSection::query()->create([
      'facility_id' => $this->facilityId,
      'name' => 'Medical Ward ' . Str::upper(Str::random(4)),
      'details' => 'General adult ward',
      'is_active' => true,
    ]);

    $this->actingAs($this->facilityAdmin);

    Livewire::test(BedManagement::class)
      ->set('bed_section_id', $section->id)
      ->set('bed_code', '')
      ->set('room_label', 'Room A')
      ->set('bed_type', 'general')
      ->set('status', 'available')
      ->set('is_active', true)
      ->call('saveBed');

    $bed = Bed::query()
      ->where('facility_id', $this->facilityId)
      ->where('bed_section_id', $section->id)
      ->latest('id')
      ->first();

    $this->assertNotNull($bed);
    $this->assertStringStartsWith('BED-', $bed->bed_code);
    $this->assertSame('available', $bed->status);
  }

  public function test_section_cannot_be_deactivated_when_occupied_beds_exist(): void
  {
    $patient = $this->createPatient();

    $section = BedSection::query()->create([
      'facility_id' => $this->facilityId,
      'name' => 'Emergency ' . Str::upper(Str::random(4)),
      'is_active' => true,
    ]);

    Bed::query()->create([
      'facility_id' => $this->facilityId,
      'bed_section_id' => $section->id,
      'bed_code' => 'OCC-' . Str::upper(Str::random(5)),
      'ward_section' => $section->name,
      'bed_type' => 'emergency',
      'status' => 'occupied',
      'is_active' => true,
      'occupied_by_patient_id' => $patient->id,
      'occupied_since' => now()->subHour(),
      'last_status_changed_at' => now(),
    ]);

    $this->actingAs($this->facilityAdmin);

    Livewire::test(FacilitySections::class)
      ->call('toggleStatus', $section->id);

    $this->assertTrue((bool) $section->fresh()->is_active);
  }

  public function test_admit_and_discharge_flow_updates_bed_and_activity_log(): void
  {
    $patient = $this->createPatient();

    $section = BedSection::query()->create([
      'facility_id' => $this->facilityId,
      'name' => 'Pediatrics ' . Str::upper(Str::random(4)),
      'is_active' => true,
    ]);

    $bed = Bed::query()->create([
      'facility_id' => $this->facilityId,
      'bed_section_id' => $section->id,
      'bed_code' => 'PED-' . Str::upper(Str::random(5)),
      'ward_section' => $section->name,
      'bed_type' => 'pediatric',
      'status' => 'available',
      'is_active' => true,
      'last_status_changed_at' => now(),
    ]);

    $this->actingAs($this->facilityAdmin);

    $admittedAt = now()->subHours(2)->format('Y-m-d\TH:i');

    Livewire::test(AdmittedPatients::class)
      ->set('admission_patient_id', $patient->id)
      ->set('bed_section_id', $section->id)
      ->set('bed_id', $bed->id)
      ->set('admitted_at', $admittedAt)
      ->set('admission_reason', 'Observation admission')
      ->call('admitPatient');

    $admission = InpatientAdmission::query()
      ->where('facility_id', $this->facilityId)
      ->where('patient_id', $patient->id)
      ->where('is_active', true)
      ->latest('id')
      ->first();

    $this->assertNotNull($admission);
    $this->assertSame(InpatientAdmission::STATUS_ADMITTED, $admission->status);

    $bed->refresh();
    $this->assertSame('occupied', $bed->status);
    $this->assertSame($patient->id, (int) $bed->occupied_by_patient_id);

    $this->assertDatabaseHas('activities', [
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'module' => 'inpatient',
      'action' => 'admit',
    ]);

    Livewire::test(AdmittedPatients::class)
      ->set('close_admission_id', $admission->id)
      ->set('close_action', InpatientAdmission::STATUS_DISCHARGED)
      ->set('close_at', now()->format('Y-m-d\TH:i'))
      ->set('close_note', 'Stable and discharged')
      ->call('completeCloseAdmission');

    $this->assertDatabaseHas('inpatient_admissions', [
      'id' => $admission->id,
      'status' => InpatientAdmission::STATUS_DISCHARGED,
      'is_active' => 0,
    ]);

    $bed->refresh();
    $this->assertSame('available', $bed->status);
    $this->assertNull($bed->occupied_by_patient_id);

    $this->assertDatabaseHas('activities', [
      'patient_id' => $patient->id,
      'facility_id' => $this->facilityId,
      'module' => 'inpatient',
      'action' => 'discharge',
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
      'first_name' => 'Core',
      'last_name' => 'Workflow',
      'username' => 'core_workflow_' . $token,
      'email' => 'core_workflow_' . $token . '@example.com',
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
      'first_name' => 'John',
      'last_name' => 'Doe',
      'gender' => 'Male',
      'date_of_birth' => now()->subYears(6)->toDateString(),
      'phone' => '0801' . random_int(1000000, 9999999),
      'facility_id' => $this->facilityId,
      'registration_date' => now()->toDateString(),
      'is_active' => true,
    ]);
  }
}

