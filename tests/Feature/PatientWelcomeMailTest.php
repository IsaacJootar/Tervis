<?php

namespace Tests\Feature;

use App\Mail\PatientWelcomeMail;
use App\Models\Patient;
use App\Models\User;
use App\Services\Patients\PatientPortalAccountService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PatientWelcomeMailTest extends TestCase
{
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

  public function test_welcome_email_is_sent_after_commit_for_new_patient_with_email(): void
  {
    Mail::fake();
    Http::fake([
      'https://api.ng.termii.com/api/sms/send' => Http::response([
        'message' => 'Successfully Sent',
        'message_id' => 'patient-welcome-sms',
      ], 200),
    ]);
    config()->set('termii.enabled', true);
    config()->set('termii.api_key', 'test-key');
    [$stateId, $lgaId, $wardId, $facilityId, $facilityName] = $this->createGeoContext();
    $token = strtolower(Str::random(8));
    $email = "welcome.patient.{$token}@example.com";

    try {
      $din = Patient::generateDIN();
      $patient = Patient::query()->create([
        'din' => $din,
        'first_name' => 'Welcome',
        'last_name' => 'Patient',
        'gender' => 'Female',
        'date_of_birth' => now()->subYears(23)->toDateString(),
        'phone' => '08032223344',
        'email' => $email,
        'state_id' => $stateId,
        'lga_id' => $lgaId,
        'ward_id' => $wardId,
        'facility_id' => $facilityId,
        'registration_date' => now()->toDateString(),
        'is_active' => true,
      ]);

      app(PatientPortalAccountService::class)->ensureForPatient($patient);

      $user = User::query()->where('username', $din)->first();

      $this->assertNotNull($user);
      Mail::assertSent(PatientWelcomeMail::class, function (PatientWelcomeMail $mail) use ($facilityName, $din, $email) {
        return $mail->hasTo($email)
          && $mail->patient->din === $din
          && $mail->facilityName === $facilityName;
      });
      Http::assertSentCount(1);
    } finally {
      User::query()->where('email', $email ?? null)->delete();
      Patient::query()->where('email', $email ?? null)->delete();
      $this->cleanupGeoContext($stateId, $lgaId, $wardId, $facilityId);
    }
  }

  public function test_welcome_email_is_not_sent_when_patient_has_no_real_email(): void
  {
    Mail::fake();
    Http::fake();
    config()->set('termii.enabled', true);
    config()->set('termii.api_key', 'test-key');
    [$stateId, $lgaId, $wardId, $facilityId] = $this->createGeoContext();

    try {
      $din = Patient::generateDIN();
      $patient = Patient::query()->create([
        'din' => $din,
        'first_name' => 'NoEmail',
        'last_name' => 'Patient',
        'gender' => 'Female',
        'date_of_birth' => now()->subYears(25)->toDateString(),
        'phone' => '08035556677',
        'state_id' => $stateId,
        'lga_id' => $lgaId,
        'ward_id' => $wardId,
        'facility_id' => $facilityId,
        'registration_date' => now()->toDateString(),
        'is_active' => true,
      ]);

      app(PatientPortalAccountService::class)->ensureForPatient($patient);

      Mail::assertNothingSent();
      Http::assertSentCount(1);
    } finally {
      User::query()->where('patient_id', optional($patient ?? null)->id)->delete();
      Patient::query()->where('id', optional($patient ?? null)->id)->delete();
      $this->cleanupGeoContext($stateId, $lgaId, $wardId, $facilityId);
    }
  }

  private function createGeoContext(): array
  {
    $token = Str::upper(Str::random(5));
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
      'address' => 'No. 1 Mail Lane',
      'phone' => '08030000000',
      'email' => 'mail_' . strtolower($token) . '@example.com',
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
