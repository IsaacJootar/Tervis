<?php

namespace Tests\Feature;

use App\Livewire\Registers\AntenatalRegister;
use App\Livewire\Registers\GeneralPatientsRegister;
use App\Models\Patient;
use App\Models\Registrations\AntenatalRegistration;
use App\Models\Registrations\GeneralPatientsRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationStrictScopeGateTest extends TestCase
{
  use DatabaseTransactions;

  private int $facilityId;
  private int $otherFacilityId;
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
      'general_patients_registrations',
      'antenatal_registrations',
    ];

    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available.");
      }
    }

    $this->facilityId = $this->createIsolatedFacilityId();
    $this->otherFacilityId = $this->createIsolatedFacilityId();
    $this->dataOfficer = $this->createUser('Data Officer', $this->facilityId);
  }

  public function test_general_registration_gate_is_enforced_across_facilities_during_din_verification(): void
  {
    $patient = $this->createPatient($this->otherFacilityId);

    GeneralPatientsRegistration::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->otherFacilityId,
      'officer_name' => 'Other Facility Officer',
      'registration_date' => now(),
    ]);

    $this->actingAs($this->dataOfficer);
    Livewire::test(GeneralPatientsRegister::class)
      ->set('din', $patient->din)
      ->call('verifyPatient')
      ->assertSet('hasOpdRegistration', true)
      ->assertSet('isPatientVerified', false)
      ->assertSet('patient_id', $patient->id)
      ->assertSet('patient_registration_facility', DB::table('facilities')->where('id', $this->otherFacilityId)->value('name'));
  }

  public function test_anc_registration_gate_is_enforced_across_facilities_during_din_verification(): void
  {
    $patient = $this->createPatient($this->otherFacilityId);

    AntenatalRegistration::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->otherFacilityId,
      'pregnancy_number' => 1,
      'is_active' => true,
      'pregnancy_status' => 'active',
      'registration_date' => now()->toDateString(),
      'date_of_booking' => now()->toDateString(),
      'lmp' => now()->subMonths(2)->toDateString(),
      'edd' => now()->addMonths(7)->toDateString(),
      'gravida' => 1,
      'parity' => 0,
      'officer_name' => 'Other ANC Officer',
    ]);

    $this->actingAs($this->dataOfficer);
    Livewire::test(AntenatalRegister::class)
      ->set('din', $patient->din)
      ->call('verifyPatient')
      ->assertSet('hasActiveAncRegistration', true)
      ->assertSet('isPatientVerified', false)
      ->assertSet('patient_id', $patient->id)
      ->assertSet('pregnancy_number', 1)
      ->assertSet('patient_registration_facility', DB::table('facilities')->where('id', $this->otherFacilityId)->value('name'));
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
      'name' => 'Scope Facility ' . $token,
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'ward_id' => $wardId,
      'address' => 'No. 10 Scope Street',
      'phone' => '08000000000',
      'email' => 'scope_facility_' . strtolower($token) . '@example.com',
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
      'first_name' => 'Scope',
      'last_name' => 'Officer',
      'username' => 'scope_officer_' . $token,
      'email' => 'scope_officer_' . $token . '@example.com',
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
      'first_name' => 'Strict',
      'last_name' => 'Scope',
      'gender' => 'Female',
      'date_of_birth' => now()->subYears(25)->toDateString(),
      'phone' => '0803' . random_int(1000000, 9999999),
      'facility_id' => $facilityId,
      'registration_date' => now()->toDateString(),
      'is_active' => true,
    ]);
  }
}

