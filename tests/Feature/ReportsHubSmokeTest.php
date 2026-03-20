<?php

namespace Tests\Feature;

use App\Livewire\Core\FacilityReports;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsHubSmokeTest extends TestCase
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
      'daily_attendances',
      'antenatal_registrations',
      'antenatal_follow_up_assessments',
      'family_planning_registrations',
      'immunization_records',
      'nutrition_records',
      'child_health_activity_records',
      'lab_tests',
      'prescriptions',
      'referrals',
      'invoices',
      'tetanus_vaccinations',
      'drug_dispense_lines',
    ];

    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available.");
      }
    }

    $this->facilityId = $this->ensureFacilityId();
    $this->facilityAdmin = $this->createUser('Facility Administrator', $this->facilityId);
  }

  public function test_reports_hub_can_generate_all_report_types_without_exception(): void
  {
    $this->actingAs($this->facilityAdmin);

    $reportKeys = [
      'monthly_nhmis_summary',
      'daily_anc_register',
      'anc_follow_up_register',
      'daily_family_planning_register',
      'child_immunization_register',
      'immunization_summary_register',
      'immunization_summary_tally',
      'nutrition_growth_register',
      'vaccination_schedule_register',
      'laboratory_register',
      'prescription_dispensing_register',
      'referrals_register',
      'invoices_payments_register',
    ];

    foreach ($reportKeys as $reportKey) {
      $component = Livewire::test(FacilityReports::class)
        ->set('selected_report', $reportKey)
        ->set('date_from', now()->startOfMonth()->toDateString())
        ->set('date_to', now()->toDateString())
        ->call('generateReport');

      $this->assertTrue(
        (bool) $component->get('show_results'),
        'Report failed: ' . $reportKey . ' | feedback=' . (string) $component->get('feedback_message')
      );
      $this->assertSame('success', $component->get('feedback_type'), 'Unexpected feedback type for ' . $reportKey);
    }
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
      'first_name' => 'Facility',
      'last_name' => 'Admin',
      'username' => 'reports_admin_' . $token,
      'email' => 'reports_admin_' . $token . '@example.com',
      'phone' => '0800000' . random_int(100, 999),
      'password' => bcrypt('password'),
      'role' => $role,
      'facility_id' => $facilityId,
      'is_active' => true,
    ]);
  }
}
