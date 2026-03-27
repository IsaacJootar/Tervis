<?php

namespace Tests\Feature;

use App\Jobs\DispatchReminderJob;
use App\Models\Patient;
use App\Models\Reminder;
use App\Services\Communication\ReminderDispatchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReminderDispatchQueueingTest extends TestCase
{
  use DatabaseTransactions;

  private int $facilityId;
  private Patient $patient;

  protected function setUp(): void
  {
    parent::setUp();

    $this->facilityId = $this->createIsolatedFacilityId();
    $this->patient = $this->createPatient($this->facilityId);
  }

  public function test_queue_due_global_only_queues_due_pending_and_failed_reminders(): void
  {
    Queue::fake();

    $duePending = $this->createReminder('pending', now()->subDay()->toDateString());
    $dueFailed = $this->createReminder('failed', now()->toDateString());
    $futurePending = $this->createReminder('pending', now()->addDay()->toDateString());
    $alreadyQueued = $this->createReminder('queued', now()->subDay()->toDateString());

    $result = app(ReminderDispatchService::class)->queueDueGlobal($this->facilityId, $this->patient->id);

    $this->assertSame(2, (int) $result['total']);
    $this->assertSame(2, (int) $result['queued']);

    Queue::assertPushed(DispatchReminderJob::class, 2);

    $this->assertSame('queued', (string) $duePending->fresh()->status);
    $this->assertNotNull($duePending->fresh()->queued_at);

    $this->assertSame('queued', (string) $dueFailed->fresh()->status);
    $this->assertNotNull($dueFailed->fresh()->queued_at);
    $this->assertNull($dueFailed->fresh()->failed_at);

    $this->assertSame('pending', (string) $futurePending->fresh()->status);
    $this->assertSame('queued', (string) $alreadyQueued->fresh()->status);
  }

  private function createReminder(string $status, string $date): Reminder
  {
    return Reminder::query()->create([
      'patient_id' => $this->patient->id,
      'facility_id' => $this->facilityId,
      'title' => 'Reminder ' . $status,
      'message' => 'Reminder message',
      'reminder_date' => $date,
      'status' => $status,
      'channels' => ['sms'],
      'recipient_phone' => $this->patient->phone,
      'recipient_email' => $this->patient->email,
      'failed_at' => $status === 'failed' ? now()->subHour() : null,
      'queued_at' => $status === 'queued' ? now()->subMinutes(10) : null,
    ]);
  }

  private function createIsolatedFacilityId(): int
  {
    $token = Str::upper(Str::random(5));
    $now = now();

    $stateId = DB::table('states')->insertGetId([
      'name' => 'Queue State ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $lgaId = DB::table('lgas')->insertGetId([
      'state_id' => $stateId,
      'name' => 'Queue LGA ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $wardId = DB::table('wards')->insertGetId([
      'lga_id' => $lgaId,
      'name' => 'Queue Ward ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    return (int) DB::table('facilities')->insertGetId([
      'name' => 'Queue Facility ' . $token,
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'ward_id' => $wardId,
      'address' => 'No. 1 Queue Street',
      'phone' => '08000000000',
      'email' => 'queue_' . strtolower($token) . '@example.com',
      'type' => 'Primary',
      'is_active' => 1,
      'created_at' => $now,
      'updated_at' => $now,
    ]);
  }

  private function createPatient(int $facilityId): Patient
  {
    return Patient::query()->create([
      'din' => str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
      'first_name' => 'Queue',
      'last_name' => 'Patient',
      'gender' => 'Female',
      'date_of_birth' => now()->subYears(28)->toDateString(),
      'phone' => '0803' . random_int(1000000, 9999999),
      'email' => 'queue_patient_' . strtolower(Str::random(6)) . '@example.com',
      'facility_id' => $facilityId,
      'registration_date' => now()->toDateString(),
      'is_active' => true,
    ]);
  }
}
