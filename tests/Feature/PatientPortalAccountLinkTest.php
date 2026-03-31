<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PatientPortalAccountLinkTest extends TestCase
{
  use DatabaseTransactions;

  protected function setUp(): void
  {
    parent::setUp();

    $requiredTables = ['users', 'patients', 'states', 'lgas', 'wards', 'facilities'];
    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available.");
      }
    }
  }

  public function test_patient_user_can_link_directly_to_patient_record_and_expose_din(): void
  {
    [$stateId, $lgaId, $wardId, $facilityId] = $this->createGeoContext();

    $patient = Patient::query()->create([
      'din' => '12345678',
      'first_name' => 'Portal',
      'last_name' => 'Patient',
      'gender' => 'Female',
      'date_of_birth' => now()->subYears(24)->toDateString(),
      'phone' => '08031234567',
      'email' => 'portal.patient@example.com',
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'ward_id' => $wardId,
      'facility_id' => $facilityId,
      'registration_date' => now()->toDateString(),
      'is_active' => true,
    ]);

    $user = User::query()->create([
      'first_name' => 'Portal',
      'last_name' => 'Patient',
      'username' => '12345678',
      'email' => 'portal.user@example.com',
      'phone' => '08039876543',
      'password' => Hash::make('12345678'),
      'role' => 'Patient',
      'designation' => 'Patient',
      'facility_id' => $facilityId,
      'lga_id' => $lgaId,
      'state_id' => $stateId,
      'patient_id' => $patient->id,
      'is_active' => true,
    ]);

    $freshUser = User::query()->with('patient')->findOrFail($user->id);

    $this->assertSame($patient->id, $freshUser->patient?->id);
    $this->assertSame('12345678', $freshUser->din);
    $this->assertSame('12345678', $freshUser->DIN);
    $this->assertSame($user->id, $patient->fresh()->portalAccount?->id);
  }

  public function test_patient_can_authenticate_with_din_credentials(): void
  {
    [$stateId, $lgaId, $wardId, $facilityId] = $this->createGeoContext();

    $patient = Patient::query()->create([
      'din' => '87654321',
      'first_name' => 'Auth',
      'last_name' => 'Patient',
      'gender' => 'Female',
      'date_of_birth' => now()->subYears(30)->toDateString(),
      'phone' => '08035554444',
      'email' => 'auth.patient@example.com',
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'ward_id' => $wardId,
      'facility_id' => $facilityId,
      'registration_date' => now()->toDateString(),
      'is_active' => true,
    ]);

    User::query()->create([
      'first_name' => 'Auth',
      'last_name' => 'Patient',
      'username' => '87654321',
      'email' => 'auth.user@example.com',
      'phone' => '08036667777',
      'password' => Hash::make('87654321'),
      'role' => 'Patient',
      'designation' => 'Patient',
      'facility_id' => $facilityId,
      'lga_id' => $lgaId,
      'state_id' => $stateId,
      'patient_id' => $patient->id,
      'is_active' => true,
    ]);

    $this->assertTrue(Auth::attempt([
      'username' => '87654321',
      'password' => '87654321',
    ]));
  }

  private function createGeoContext(): array
  {
    $token = Str::upper(Str::random(5));
    $now = now();

    $stateId = DB::table('states')->insertGetId([
      'name' => 'Portal State ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $lgaId = DB::table('lgas')->insertGetId([
      'state_id' => $stateId,
      'name' => 'Portal LGA ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $wardId = DB::table('wards')->insertGetId([
      'lga_id' => $lgaId,
      'name' => 'Portal Ward ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $facilityId = DB::table('facilities')->insertGetId([
      'name' => 'Portal Facility ' . $token,
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'ward_id' => $wardId,
      'address' => 'No. 12 Portal Street',
      'phone' => '08000000000',
      'email' => 'portal_' . strtolower($token) . '@example.com',
      'type' => 'Primary',
      'is_active' => 1,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    return [$stateId, $lgaId, $wardId, $facilityId];
  }
}
