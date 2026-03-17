<?php

namespace Tests\Feature;

use App\Models\FacilityModuleAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModuleEnabledMiddlewareTest extends TestCase
{
  use DatabaseTransactions;

  protected function setUp(): void
  {
    parent::setUp();

    if (
      !Schema::hasTable('users')
      || !Schema::hasTable('facilities')
      || !Schema::hasTable('facility_module_accesses')
    ) {
      $this->markTestSkipped('Required module access tables are not available.');
    }

    Route::middleware(['web', 'auth', 'module.enabled:attendance'])
      ->get('/__test/module-enabled/attendance', fn() => response('ok', 200));
  }

  public function test_disabled_module_route_returns_403(): void
  {
    $facilityId = $this->firstFacilityIdOrSkip();
    $user = $this->createDataOfficer($facilityId);

    FacilityModuleAccess::query()->updateOrCreate(
      ['facility_id' => $facilityId, 'module_key' => 'attendance'],
      [
        'module_label' => 'Attendance',
        'is_enabled' => false,
        'last_changed_by_user_id' => $user->id,
      ]
    );

    $this->actingAs($user)
      ->get('/__test/module-enabled/attendance')
      ->assertForbidden();
  }

  public function test_enabled_module_route_returns_200(): void
  {
    $facilityId = $this->firstFacilityIdOrSkip();
    $user = $this->createDataOfficer($facilityId);

    FacilityModuleAccess::query()->updateOrCreate(
      ['facility_id' => $facilityId, 'module_key' => 'attendance'],
      [
        'module_label' => 'Attendance',
        'is_enabled' => true,
        'last_changed_by_user_id' => $user->id,
      ]
    );

    $this->actingAs($user)
      ->get('/__test/module-enabled/attendance')
      ->assertOk()
      ->assertSeeText('ok');
  }

  public function test_missing_module_row_defaults_to_allowed(): void
  {
    $facilityId = $this->firstFacilityIdOrSkip();
    $user = $this->createDataOfficer($facilityId);

    FacilityModuleAccess::query()
      ->where('facility_id', $facilityId)
      ->where('module_key', 'attendance')
      ->delete();

    $this->actingAs($user)
      ->get('/__test/module-enabled/attendance')
      ->assertOk()
      ->assertSeeText('ok');
  }

  public function test_other_facility_disabled_row_does_not_block_current_facility_user(): void
  {
    $facilityId = $this->firstFacilityIdOrSkip();
    $otherFacilityId = (int) DB::table('facilities')
      ->where('id', '!=', $facilityId)
      ->value('id');

    if ($otherFacilityId <= 0) {
      $this->markTestSkipped('Second facility not available for scope isolation assertion.');
    }

    $user = $this->createDataOfficer($facilityId);

    FacilityModuleAccess::query()->updateOrCreate(
      ['facility_id' => $otherFacilityId, 'module_key' => 'attendance'],
      [
        'module_label' => 'Attendance',
        'is_enabled' => false,
        'last_changed_by_user_id' => $user->id,
      ]
    );

    FacilityModuleAccess::query()
      ->where('facility_id', $facilityId)
      ->where('module_key', 'attendance')
      ->delete();

    $this->actingAs($user)
      ->get('/__test/module-enabled/attendance')
      ->assertOk()
      ->assertSeeText('ok');
  }

  private function firstFacilityIdOrSkip(): int
  {
    $facilityId = (int) DB::table('facilities')->value('id');
    if ($facilityId <= 0) {
      $this->markTestSkipped('No facilities available for module middleware tests.');
    }

    return $facilityId;
  }

  private function createDataOfficer(int $facilityId): User
  {
    $token = Str::lower(Str::random(10));

    return User::query()->create([
      'first_name' => 'Module',
      'last_name' => 'Tester',
      'username' => 'module_tester_' . $token,
      'email' => 'module_tester_' . $token . '@example.com',
      'phone' => '0800000' . random_int(100, 999),
      'password' => bcrypt('password'),
      'role' => 'Data Officer',
      'facility_id' => $facilityId,
      'is_active' => true,
    ]);
  }
}
