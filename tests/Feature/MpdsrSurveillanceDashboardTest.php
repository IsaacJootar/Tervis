<?php

namespace Tests\Feature;

use App\Livewire\Analytics\MpdsrReportDashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class MpdsrSurveillanceDashboardTest extends TestCase
{
  use DatabaseTransactions;

  private int $facilityId;
  private User $facilityAdmin;

  protected function setUp(): void
  {
    parent::setUp();

    $requiredTables = ['users', 'states', 'lgas', 'wards', 'facilities', 'patients', 'deliveries'];
    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available.");
      }
    }

    $this->facilityId = $this->ensureFacilityId();
    $this->facilityAdmin = $this->createUser('Facility Administrator', $this->facilityId);
  }

  public function test_mpdsr_surveillance_computes_death_metrics_and_exports_report(): void
  {
    $patientId = $this->seedPatient($this->facilityId, '17340001');

    $this->seedDelivery($patientId, [
      'dead' => 'yes',
      'mod' => 'SVD',
      'complications' => 'Postpartum hemorrhage',
      'seeking_care' => 'more24',
      'partograph' => 'no',
      'blood_loss' => 1300,
    ]);

    $this->seedDelivery($patientId, [
      'still_birth' => 'fresh',
      'mod' => 'SVD',
      'complications' => 'Birth asphyxia',
      'pre_term' => 'yes',
      'breathing' => 'yes',
      'newborn_care' => 'no',
      'weight' => 2.1,
      'gestational_age' => 33,
    ]);

    $this->seedDelivery($patientId, [
      'baby_dead' => 'yes',
      'mod' => 'CS',
      'complications' => 'Neonatal sepsis',
      'pre_term' => 'no',
      'breathing' => 'yes',
      'newborn_care' => 'yes',
      'weight' => 2.8,
      'gestational_age' => 39,
    ]);

    $this->actingAs($this->facilityAdmin);

    Livewire::test(MpdsrReportDashboard::class)
      ->set('dateFrom', now()->subDays(3)->toDateString())
      ->set('dateTo', now()->addDay()->toDateString())
      ->call('loadMPDSRData')
      ->assertSet('totalMaternalDeaths', 1)
      ->assertSet('totalPerinatalDeaths', 2)
      ->assertSet('totalStillbirths', 1)
      ->assertSet('totalNeonatalDeaths', 1)
      ->assertSet('totalDeaths', 3)
      ->call('exportSurveillanceCsv')
      ->assertFileDownloaded();
  }

  public function test_mpdsr_printable_review_sheet_route_and_livewire_action_work(): void
  {
    $this->actingAs($this->facilityAdmin);

    Livewire::test(MpdsrReportDashboard::class)
      ->set('dateFrom', now()->subDays(7)->toDateString())
      ->set('dateTo', now()->toDateString())
      ->call('loadMPDSRData')
      ->call('openPrintableReview')
      ->assertRedirect(route('mpdsr-report-dashboard-print'));

    $response = $this->get(route('mpdsr-report-dashboard-print'));
    $response->assertStatus(200);
    $response->assertSee('MPDSR Review Sheet');
    $response->assertSee('Print Review Sheet');
  }

  private function seedPatient(int $facilityId, string $din): int
  {
    return (int) DB::table('patients')->insertGetId([
      'din' => $din,
      'first_name' => 'Case',
      'last_name' => 'Patient',
      'gender' => 'Female',
      'date_of_birth' => now()->subYears(27)->toDateString(),
      'phone' => '08010000000',
      'facility_id' => $facilityId,
      'registration_date' => now()->toDateString(),
      'is_active' => 1,
      'created_at' => now(),
      'updated_at' => now(),
    ]);
  }

  private function seedDelivery(int $patientId, array $overrides = []): void
  {
    DB::table('deliveries')->insert(array_merge([
      'patient_id' => $patientId,
      'facility_id' => $this->facilityId,
      'dodel' => now()->toDateString(),
      'dead' => 'no',
      'still_birth' => null,
      'baby_dead' => 'no',
      'mod' => 'SVD',
      'complications' => null,
      'seeking_care' => null,
      'transportation' => 'ambulance',
      'partograph' => 'yes',
      'oxytocin' => 'yes',
      'referred_out' => 'no',
      'admitted' => 'yes',
      'blood_loss' => 300,
      'gestational_age' => 39,
      'pre_term' => 'no',
      'breathing' => 'no',
      'newborn_care' => 'yes',
      'baby_sex' => 'Male',
      'weight' => 3.0,
      'officer_name' => 'Test Midwife',
      'officer_role' => 'Midwife',
      'officer_designation' => 'NO',
      'created_at' => now(),
      'updated_at' => now(),
    ], $overrides));
  }

  private function ensureFacilityId(): int
  {
    // Always create a dedicated facility so the assertions are not affected by pre-existing rows.
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
      'state' => 'Test State ' . $token,
      'lga' => 'Test LGA ' . $token,
      'ward' => 'Test Ward ' . $token,
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
      'username' => 'mpdsr_admin_' . $token,
      'email' => 'mpdsr_admin_' . $token . '@example.com',
      'phone' => '0800000' . random_int(100, 999),
      'password' => bcrypt('password'),
      'role' => $role,
      'facility_id' => $facilityId,
      'is_active' => true,
    ]);
  }
}
