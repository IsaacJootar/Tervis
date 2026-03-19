<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class RolePermissionMiddlewareTest extends TestCase
{
  use DatabaseTransactions;

  protected function setUp(): void
  {
    parent::setUp();

    if (!Schema::hasTable('users')) {
      $this->markTestSkipped('Users table is not available.');
    }

    Route::middleware(['web', 'auth', 'permission.check:core.staff.manage'])
      ->get('/__test/permission/core-staff', fn() => response('ok', 200));

    Route::middleware(['web', 'auth', 'permission.check:workspace.dashboard.view'])
      ->get('/__test/permission/workspace-dashboard', fn() => response('ok', 200));

    Route::middleware(['web', 'auth', 'permission.check:central.admins.manage'])
      ->get('/__test/permission/central-admins', fn() => response('ok', 200));
  }

  public function test_facility_admin_has_default_core_staff_permission(): void
  {
    $user = $this->createUser('Facility Administrator');

    $this->actingAs($user)
      ->get('/__test/permission/core-staff')
      ->assertOk()
      ->assertSeeText('ok');
  }

  public function test_data_officer_is_blocked_from_core_staff_permission(): void
  {
    $user = $this->createUser('Data Officer');

    $this->actingAs($user)
      ->get('/__test/permission/core-staff')
      ->assertForbidden();
  }

  public function test_explicit_permission_override_blocks_access(): void
  {
    if (!Schema::hasTable('role_permissions')) {
      $this->markTestSkipped('role_permissions table is not available for override assertion.');
    }

    $user = $this->createUser('Facility Administrator');

    DB::table('role_permissions')->updateOrInsert(
      ['role_name' => 'Facility Administrator', 'permission_key' => 'core.staff.manage'],
      [
        'permission_label' => 'Manage Staff Accounts',
        'permission_group' => 'core',
        'is_allowed' => false,
        'last_changed_by_user_id' => $user->id,
        'updated_at' => now(),
        'created_at' => now(),
      ]
    );

    $this->actingAs($user)
      ->get('/__test/permission/core-staff')
      ->assertForbidden();
  }

  public function test_central_admin_has_default_central_permission(): void
  {
    $user = $this->createUser('Central Admin');

    if (Schema::hasTable('role_permissions')) {
      DB::table('role_permissions')->updateOrInsert(
        ['role_name' => 'Central Admin', 'permission_key' => 'central.admins.manage'],
        [
          'permission_label' => 'Manage Administrators',
          'permission_group' => 'central',
          'is_allowed' => true,
          'last_changed_by_user_id' => $user->id,
          'updated_at' => now(),
          'created_at' => now(),
        ]
      );
    }

    $this->actingAs($user)
      ->get('/__test/permission/central-admins')
      ->assertOk()
      ->assertSeeText('ok');
  }

  private function createUser(string $role): User
  {
    $token = Str::lower(Str::random(10));
    $facilityId = (int) DB::table('facilities')->value('id');

    return User::query()->create([
      'first_name' => 'Perm',
      'last_name' => 'Tester',
      'username' => 'perm_tester_' . $token,
      'email' => 'perm_tester_' . $token . '@example.com',
      'phone' => '0800000' . random_int(100, 999),
      'password' => bcrypt('password'),
      'role' => $role,
      'facility_id' => $facilityId > 0 ? $facilityId : null,
      'is_active' => true,
      'account_status' => 'active',
    ]);
  }
}
