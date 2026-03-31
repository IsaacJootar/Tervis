<?php

namespace Tests\Feature;

use App\Livewire\Analytics\RealTimeDashboard;
use App\Models\Patient;
use App\Models\RiskPrediction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class RealTimeDashboardScopeTest extends TestCase
{
  use DatabaseTransactions;

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
      'risk_predictions',
      'antenatal_registrations',
      'antenatal_follow_up_assessments',
      'deliveries',
      'postnatal_records',
      'tetanus_vaccinations',
      'daily_attendances',
      'clinical_notes',
    ];

    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available.");
      }
    }
  }

  public function test_state_user_can_view_prediction_din_and_filter_all_facilities_within_state_scope(): void
  {
    [$stateId, $lgaOneId] = $this->createStateAndLga();
    [, $lgaTwoId] = $this->createStateAndLga($stateId);

    $facilityOneId = $this->createFacility($stateId, $lgaOneId);
    $facilityTwoId = $this->createFacility($stateId, $lgaTwoId);

    $stateUser = $this->createUser([
      'role' => 'State Data Administrator',
      'state_id' => $stateId,
      'lga_id' => null,
      'facility_id' => null,
    ]);

    $patient = $this->createPatient($facilityOneId, $stateId, $lgaOneId);
    $this->createRiskPrediction($patient->id, $facilityOneId);

    $this->actingAs($stateUser);

    $component = Livewire::test(RealTimeDashboard::class)
      ->assertSee($patient->din)
      ->set('selectedFacilityId', (string) $facilityTwoId);

    $facilities = $component->get('facilities');

    $this->assertCount(2, $facilities);
    $this->assertSame($facilityTwoId, $component->get('selectedFacilityId'));
    $this->assertEqualsCanonicalizing(
      [$facilityOneId, $facilityTwoId],
      $facilities->pluck('id')->all()
    );
  }

  public function test_lga_user_cannot_force_out_of_scope_facility_filter(): void
  {
    [$stateId, $allowedLgaId] = $this->createStateAndLga();
    [, $otherLgaId] = $this->createStateAndLga($stateId);

    $allowedFacilityId = $this->createFacility($stateId, $allowedLgaId);
    $secondAllowedFacilityId = $this->createFacility($stateId, $allowedLgaId);
    $blockedFacilityId = $this->createFacility($stateId, $otherLgaId);

    $lgaUser = $this->createUser([
      'role' => 'LGA Officer',
      'state_id' => $stateId,
      'lga_id' => $allowedLgaId,
      'facility_id' => null,
    ]);

    $this->actingAs($lgaUser);

    $component = Livewire::test(RealTimeDashboard::class)
      ->set('selectedFacilityId', (string) $blockedFacilityId);

    $facilities = $component->get('facilities');

    $this->assertEqualsCanonicalizing(
      [$allowedFacilityId, $secondAllowedFacilityId],
      $facilities->pluck('id')->all()
    );
    $this->assertNull($component->get('selectedFacilityId'));
    $this->assertSame('lga', $component->get('metrics')['scope_info']['scope_type']);
    $this->assertEqualsCanonicalizing(
      [$allowedFacilityId, $secondAllowedFacilityId],
      $component->get('metrics')['scope_info']['facility_ids']
    );
  }

  private function createStateAndLga(?int $stateId = null): array
  {
    $token = Str::upper(Str::random(5));
    $now = now();

    if (!$stateId) {
      $stateId = DB::table('states')->insertGetId([
        'name' => 'Dashboard State ' . $token,
        'created_at' => $now,
        'updated_at' => $now,
      ]);
    }

    $lgaId = DB::table('lgas')->insertGetId([
      'state_id' => $stateId,
      'name' => 'Dashboard LGA ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    return [$stateId, $lgaId];
  }

  private function createFacility(int $stateId, int $lgaId): int
  {
    $token = Str::upper(Str::random(5));
    $now = now();

    $wardId = DB::table('wards')->insertGetId([
      'lga_id' => $lgaId,
      'name' => 'Dashboard Ward ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    return (int) DB::table('facilities')->insertGetId([
      'name' => 'Dashboard Facility ' . $token,
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'ward_id' => $wardId,
      'address' => 'No. 20 Dashboard Street',
      'phone' => '08000000000',
      'email' => 'dashboard_facility_' . strtolower($token) . '@example.com',
      'type' => 'Primary',
      'is_active' => 1,
      'created_at' => $now,
      'updated_at' => $now,
    ]);
  }

  private function createUser(array $overrides): User
  {
    $token = strtolower(Str::random(10));

    return User::query()->create(array_merge([
      'first_name' => 'Dashboard',
      'last_name' => 'Officer',
      'username' => 'dashboard_officer_' . $token,
      'email' => 'dashboard_officer_' . $token . '@example.com',
      'phone' => '0800000' . random_int(100, 999),
      'password' => bcrypt('password'),
      'role' => 'Facility Administrator',
      'facility_id' => null,
      'lga_id' => null,
      'state_id' => null,
      'is_active' => true,
    ], $overrides));
  }

  private function createPatient(int $facilityId, int $stateId, int $lgaId): Patient
  {
    return Patient::query()->create([
      'din' => str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
      'first_name' => 'Realtime',
      'last_name' => 'Patient',
      'gender' => 'Female',
      'date_of_birth' => now()->subYears(28)->toDateString(),
      'phone' => '0803' . random_int(1000000, 9999999),
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'facility_id' => $facilityId,
      'registration_date' => now()->toDateString(),
      'is_active' => true,
    ]);
  }

  private function createRiskPrediction(int $patientId, int $facilityId): void
  {
    RiskPrediction::query()->create([
      'user_id' => $patientId,
      'facility_id' => $facilityId,
      'total_risk_score' => 48,
      'risk_level' => 'high',
      'risk_percentage' => 72.5,
      'identified_risks' => [['factor' => 'Hypertension']],
      'ai_recommendations' => ['Urgent follow-up'],
      'prediction_confidence' => ['overall_confidence' => 92],
      'assessment_date' => now()->toDateString(),
      'next_assessment_due' => now()->addDays(3)->toDateString(),
      'predicted_outcomes' => ['High risk'],
      'model_version' => 'test-model',
      'prediction_timestamp' => now(),
      'assessment_type' => 'manual',
      'officer_name' => 'Test Officer',
      'officer_role' => 'State Data Administrator',
      'officer_designation' => 'Analytics Test',
    ]);
  }
}
