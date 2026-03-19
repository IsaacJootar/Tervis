<?php

namespace Tests\Feature;

use App\Livewire\Core\FacilityAdministration;
use App\Models\FacilityFeeSchedule;
use App\Models\FacilityServiceCatalogItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class FacilityAdministrationWorkflowTest extends TestCase
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
      'facility_service_catalog_items',
      'facility_fee_schedules',
      'facility_module_accesses',
      'facility_admin_audits',
    ];

    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available.");
      }
    }

    $this->facilityId = $this->ensureFacilityId();
    $this->facilityAdmin = $this->createUser('Facility Administrator', $this->facilityId);
  }

  public function test_mount_seeds_module_access_rows_for_facility(): void
  {
    DB::table('facility_module_accesses')
      ->where('facility_id', $this->facilityId)
      ->delete();

    $this->actingAs($this->facilityAdmin);
    Livewire::test(FacilityAdministration::class);

    $this->assertDatabaseHas('facility_module_accesses', [
      'facility_id' => $this->facilityId,
      'module_key' => 'attendance',
      'is_enabled' => 1,
    ]);

    $this->assertDatabaseHas('facility_module_accesses', [
      'facility_id' => $this->facilityId,
      'module_key' => 'reports',
      'is_enabled' => 1,
    ]);
  }

  public function test_service_and_fee_schedule_workflow_enforces_single_active_fee(): void
  {
    $this->actingAs($this->facilityAdmin);
    $existingFeeAuditCount = DB::table('facility_admin_audits')
      ->where('facility_id', $this->facilityId)
      ->where('action', 'fee_schedule_created')
      ->count();

    $component = Livewire::test(FacilityAdministration::class)
      ->set('service_code', 'lab-101')
      ->set('service_name', 'Basic Lab Panel')
      ->set('service_category', 'Laboratory')
      ->set('service_description', 'Initial diagnostics panel')
      ->set('service_base_fee', 1500)
      ->set('service_is_active', true)
      ->call('saveService');

    /** @var FacilityServiceCatalogItem|null $service */
    $service = FacilityServiceCatalogItem::query()
      ->where('facility_id', $this->facilityId)
      ->where('service_code', 'LAB-101')
      ->latest('id')
      ->first();

    $this->assertNotNull($service);
    $this->assertSame('Basic Lab Panel', $service->service_name);
    $this->assertDatabaseHas('facility_admin_audits', [
      'facility_id' => $this->facilityId,
      'action' => 'service_created',
      'target_id' => $service->id,
    ]);

    $component
      ->set('fee_service_id', $service->id)
      ->set('fee_amount', 1500)
      ->set('fee_effective_from', now()->subDays(20)->toDateString())
      ->set('fee_effective_to', null)
      ->set('fee_notes', 'Initial fee')
      ->set('fee_is_active', true)
      ->call('saveFeeSchedule');

    $firstFee = FacilityFeeSchedule::query()
      ->where('facility_id', $this->facilityId)
      ->where('facility_service_catalog_item_id', $service->id)
      ->orderBy('id')
      ->first();

    $this->assertNotNull($firstFee);
    $this->assertTrue((bool) $firstFee->is_active);

    $component
      ->set('fee_service_id', $service->id)
      ->set('fee_amount', 2000)
      ->set('fee_effective_from', now()->subDays(1)->toDateString())
      ->set('fee_effective_to', null)
      ->set('fee_notes', 'Revised fee')
      ->set('fee_is_active', true)
      ->call('saveFeeSchedule');

    $fees = FacilityFeeSchedule::query()
      ->where('facility_id', $this->facilityId)
      ->where('facility_service_catalog_item_id', $service->id)
      ->orderBy('id')
      ->get();

    $this->assertCount(2, $fees);
    $this->assertFalse((bool) $fees->first()->is_active);
    $this->assertTrue((bool) $fees->last()->is_active);

    $this->assertSame($existingFeeAuditCount + 2, DB::table('facility_admin_audits')
      ->where('facility_id', $this->facilityId)
      ->where('action', 'fee_schedule_created')
      ->count());

    $component->call('toggleServiceStatus', $service->id);
    $this->assertFalse((bool) $service->fresh()->is_active);
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
      'username' => 'facility_admin_' . $token,
      'email' => 'facility_admin_' . $token . '@example.com',
      'phone' => '0800000' . random_int(100, 999),
      'password' => bcrypt('password'),
      'role' => $role,
      'facility_id' => $facilityId,
      'is_active' => true,
    ]);
  }
}
