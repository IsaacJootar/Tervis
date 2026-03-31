<?php

namespace Tests\Feature;

use App\Livewire\Central\CreateAdministrators;
use App\Livewire\Core\CreateDataOfficers;
use App\Mail\StaffWelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StaffWelcomeEmailFlowTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    Livewire::withoutLazyLoading();

    $requiredTables = ['users', 'states', 'lgas', 'wards', 'facilities'];
    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available.");
      }
    }
  }

  public function test_facility_admin_creation_sends_welcome_email(): void
  {
    Mail::fake();
    Http::fake([
      'https://api.ng.termii.com/api/sms/send' => Http::response([
        'message' => 'Successfully Sent',
        'message_id' => 'facility-admin-welcome-sms',
      ], 200),
    ]);
    config()->set('termii.enabled', true);
    config()->set('termii.api_key', 'test-key');
    [$stateId, $lgaId, $wardId, $facilityId, $facilityName] = $this->createGeoContext('ADMIN');
    $token = strtolower(Str::random(8));
    $staffEmail = "facility.admin.mailer.{$token}@example.com";
    $firstName = 'Facility' . strtoupper(Str::random(3));
    $lastName = 'AdminMailer' . strtoupper(Str::random(3));

    $centralAdmin = User::query()->create([
      'first_name' => 'Central',
      'last_name' => 'Admin',
      'username' => 'central_admin_mail_' . strtolower(Str::random(6)),
      'email' => 'central_admin_mail_' . strtolower(Str::random(6)) . '@example.com',
      'password' => bcrypt('password123'),
      'role' => 'Central Admin',
      'designation' => 'Central Admin',
      'is_active' => true,
      'account_status' => 'active',
    ]);

    try {
      $this->actingAs($centralAdmin);

      Livewire::test(CreateAdministrators::class)
        ->set('first_name', $firstName)
        ->set('last_name', $lastName)
        ->set('email', $staffEmail)
        ->set('phone', '08031112222')
        ->set('password', 'pass12345')
        ->set('password_confirmation', 'pass12345')
        ->set('role', 'Facility Administrator')
        ->set('designation', 'Facility Data Administrator')
        ->set('facility_id', $facilityId)
        ->call('store');

      $user = User::query()->where('email', $staffEmail)->first();

      $this->assertNotNull($user);
      $this->assertSame('Facility Administrator', $user->role);
      Mail::assertSent(StaffWelcomeMail::class, function (StaffWelcomeMail $mail) use ($facilityName, $staffEmail) {
        return $mail->hasTo($staffEmail)
          && $mail->user->role === 'Facility Administrator'
          && $mail->plainPassword === 'pass12345'
          && $mail->scopeLabel === $facilityName;
      });
      Http::assertSentCount(1);
    } finally {
      User::query()->where('email', $staffEmail ?? null)->delete();
      User::query()->where('id', $centralAdmin->id)->delete();
      $this->cleanupGeoContext($stateId, $lgaId, $wardId, $facilityId);
    }
  }

  public function test_staff_creation_sends_welcome_email(): void
  {
    Mail::fake();
    Http::fake([
      'https://api.ng.termii.com/api/sms/send' => Http::response([
        'message' => 'Successfully Sent',
        'message_id' => 'staff-welcome-sms',
      ], 200),
    ]);
    config()->set('termii.enabled', true);
    config()->set('termii.api_key', 'test-key');
    [$stateId, $lgaId, $wardId, $facilityId, $facilityName] = $this->createGeoContext('STAFF');
    $token = strtolower(Str::random(8));
    $staffEmail = "staff.mailer.{$token}@example.com";
    $firstName = 'Staff' . strtoupper(Str::random(3));
    $lastName = 'Mailer' . strtoupper(Str::random(3));

    $facilityAdmin = User::query()->create([
      'first_name' => 'Facility',
      'last_name' => 'Admin',
      'username' => 'facility_admin_mail_' . strtolower(Str::random(6)),
      'email' => 'facility_admin_mail_' . strtolower(Str::random(6)) . '@example.com',
      'phone' => '08030001111',
      'password' => bcrypt('password123'),
      'role' => 'Facility Administrator',
      'designation' => 'Facility Data Administrator',
      'facility_id' => $facilityId,
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'is_active' => true,
      'account_status' => 'active',
    ]);

    try {
      $this->actingAs($facilityAdmin);

      Livewire::test(CreateDataOfficers::class)
        ->set('first_name', $firstName)
        ->set('last_name', $lastName)
        ->set('email', $staffEmail)
        ->set('phone', '08035557777')
        ->set('username', 'staff_mailer_' . strtolower(Str::random(6)))
        ->set('password', 'pass12345')
        ->set('password_confirmation', 'pass12345')
        ->set('designation', 'Nurse')
        ->call('store');

      $user = User::query()->where('email', $staffEmail)->first();

      $this->assertNotNull($user);
      $this->assertSame('Data Officer', $user->role);
      Mail::assertSent(StaffWelcomeMail::class, function (StaffWelcomeMail $mail) use ($facilityName, $staffEmail) {
        return $mail->hasTo($staffEmail)
          && $mail->user->role === 'Data Officer'
          && $mail->plainPassword === 'pass12345'
          && $mail->scopeLabel === $facilityName;
      });
      Http::assertSentCount(1);
    } finally {
      User::query()->where('email', $staffEmail ?? null)->delete();
      User::query()->where('id', $facilityAdmin->id)->delete();
      $this->cleanupGeoContext($stateId, $lgaId, $wardId, $facilityId);
    }
  }

  private function createGeoContext(string $prefix): array
  {
    $token = strtoupper($prefix . Str::random(4));
    $now = now();

    $stateId = DB::table('states')->insertGetId([
      'name' => 'Mail State ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $lgaId = DB::table('lgas')->insertGetId([
      'state_id' => $stateId,
      'name' => 'Mail LGA ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $wardId = DB::table('wards')->insertGetId([
      'lga_id' => $lgaId,
      'name' => 'Mail Ward ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $facilityName = 'Mail Facility ' . $token;
    $facilityId = DB::table('facilities')->insertGetId([
      'name' => $facilityName,
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'ward_id' => $wardId,
      'state' => 'Mail State ' . $token,
      'lga' => 'Mail LGA ' . $token,
      'ward' => 'Mail Ward ' . $token,
      'address' => 'No. 10 Welcome Street',
      'phone' => '08030000000',
      'email' => 'facility_' . strtolower($token) . '@example.com',
      'type' => 'Primary',
      'is_active' => 1,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    return [$stateId, $lgaId, $wardId, $facilityId, $facilityName];
  }

  private function cleanupGeoContext(int $stateId, int $lgaId, int $wardId, int $facilityId): void
  {
    DB::table('facilities')->where('id', $facilityId)->delete();
    DB::table('wards')->where('id', $wardId)->delete();
    DB::table('lgas')->where('id', $lgaId)->delete();
    DB::table('states')->where('id', $stateId)->delete();
  }
}
