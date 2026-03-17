<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CentralLegacyRoutesTest extends TestCase
{
  use DatabaseTransactions;

  protected function setUp(): void
  {
    parent::setUp();

    if (!Schema::hasTable('users')) {
      $this->markTestSkipped('Users table is not available.');
    }
  }

  public function test_central_dashboard_route_is_accessible(): void
  {
    $user = $this->createCentralAdmin();

    $this->actingAs($user)
      ->get('/central/central-admin-dashboard')
      ->assertOk();
  }

  public function test_legacy_central_admin_facilities_route_redirects_to_active_route(): void
  {
    $user = $this->createCentralAdmin();

    $this->actingAs($user)
      ->get('/central-admin/facilities')
      ->assertRedirect('/central/create-facility');
  }

  public function test_legacy_central_admin_reports_route_redirects_to_reports_hub(): void
  {
    $user = $this->createCentralAdmin();

    $this->actingAs($user)
      ->get('/central-admin/reports/system')
      ->assertRedirect('/core/reports-hub');
  }

  public function test_legacy_central_admin_notifications_route_redirects_to_reminders_hub(): void
  {
    $user = $this->createCentralAdmin();

    $this->actingAs($user)
      ->get('/central-admin/notifications')
      ->assertRedirect('/core/reminders-notifications-hub');
  }

  public function test_legacy_central_users_route_redirects_to_create_administrators(): void
  {
    $user = $this->createCentralAdmin();

    $this->actingAs($user)
      ->get('/central/central-admin/users')
      ->assertRedirect('/central/create-administrators');
  }

  private function createCentralAdmin(): User
  {
    $token = Str::lower(Str::random(10));

    return User::query()->create([
      'first_name' => 'Central',
      'last_name' => 'Tester',
      'username' => 'central_tester_' . $token,
      'email' => 'central_tester_' . $token . '@example.com',
      'phone' => '0800000' . random_int(100, 999),
      'password' => bcrypt('password'),
      'role' => 'Central Admin',
      'is_active' => true,
      'account_status' => 'active',
    ]);
  }
}

