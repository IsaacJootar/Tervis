<?php

namespace App\Services\Communication;

use App\Models\AntenatalFollowUpAssessment;
use App\Models\DoctorAssessment;
use App\Models\FamilyPlanningFollowUp;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\ReminderDispatchLog;
use App\Models\Registrations\FamilyPlanningRegistration;
use App\Models\TetanusVaccination;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ReminderDispatchService
{
  private static ?bool $hasProviderTrackingColumns = null;

  public function __construct(
    private readonly SmsDispatchService $smsService,
    private readonly EmailPlaceholderService $emailService,
  ) {
  }

  /**
   * Create/update reminders from module date fields for a patient.
   *
   * @return array{created:int,updated:int,total:int}
   */
  public function syncFromModuleDates(
    int $patientId,
    int $facilityId,
    ?string $officerName = null,
    ?string $officerRole = null
  ): array {
    $patient = Patient::query()->find($patientId);
    if (!$patient) {
      return ['created' => 0, 'updated' => 0, 'total' => 0];
    }

    $created = 0;
    $updated = 0;

    foreach ($this->buildSourceRows($patientId, $facilityId) as $row) {
      $result = $this->upsertSourceReminder($patient, $facilityId, $row, $officerName, $officerRole);
      $created += $result['created'];
      $updated += $result['updated'];
    }

    return [
      'created' => $created,
      'updated' => $updated,
      'total' => $created + $updated,
    ];
  }

  /**
   * @return array{total:int,sent:int,failed:int,skipped:int}
   */
  public function dispatchDueForPatient(int $patientId, int $facilityId): array
  {
    return $this->dispatchDueGlobal($facilityId, $patientId);
  }

  /**
   * @return array{total:int,sent:int,failed:int,skipped:int}
   */
  public function dispatchDueGlobal(?int $facilityId = null, ?int $patientId = null): array
  {
    $query = Reminder::query()
      ->whereIn('status', ['pending', 'queued', 'failed'])
      ->whereDate('reminder_date', '<=', today())
      ->orderBy('reminder_date')
      ->orderBy('id');

    if ($facilityId) {
      $query->where('facility_id', $facilityId);
    }

    if ($patientId) {
      $query->where('patient_id', $patientId);
    }

    $sent = 0;
    $failed = 0;
    $skipped = 0;

    $reminders = $query->get();
    foreach ($reminders as $reminder) {
      $result = $this->dispatchReminder($reminder);
      if ($result['status'] === 'sent') {
        $sent++;
      } elseif ($result['status'] === 'failed') {
        $failed++;
      } else {
        $skipped++;
      }
    }

    return [
      'total' => $reminders->count(),
      'sent' => $sent,
      'failed' => $failed,
      'skipped' => $skipped,
    ];
  }

  /**
   * @return array{status:string,sent:int,failed:int,skipped:int}
   */
  public function dispatchReminder(Reminder $reminder): array
  {
    $reminder->loadMissing('patient');

    $channels = collect((array) ($reminder->channels ?? []))
      ->map(fn($channel) => strtolower((string) $channel))
      ->filter(fn($channel) => in_array($channel, ['sms', 'email'], true))
      ->unique()
      ->values();

    if ($channels->isEmpty()) {
      $channels = collect(['sms', 'email']);
    }

    if ($reminder->status === 'canceled') {
      return ['status' => 'skipped', 'sent' => 0, 'failed' => 0, 'skipped' => 1];
    }

    if (!$reminder->queued_at) {
      $reminder->queued_at = now();
    }
    $reminder->status = 'queued';
    $reminder->save();

    $recipientPhone = trim((string) ($reminder->recipient_phone ?: $reminder->patient?->phone ?: ''));
    $recipientEmail = trim((string) ($reminder->recipient_email ?: $reminder->patient?->email ?: ''));

    $sent = 0;
    $failed = 0;
    $skipped = 0;

    foreach ($channels as $channel) {
      if ($channel === 'sms') {
        $result = $this->smsService->send(
          $recipientPhone,
          $reminder->message,
          ['reminder_id' => $reminder->id, 'patient_id' => $reminder->patient_id]
        );

        $this->storeDispatchLog($reminder, 'sms', $recipientPhone, null, $reminder->message, $result);
      } elseif ($channel === 'email') {
        $result = $this->emailService->send(
          $recipientEmail,
          $reminder->title,
          $reminder->message,
          ['reminder_id' => $reminder->id, 'patient_id' => $reminder->patient_id]
        );

        $this->storeDispatchLog($reminder, 'email', $recipientEmail, $reminder->title, $reminder->message, $result);
      } else {
        $result = [
          'ok' => false,
          'status' => 'skipped',
          'provider' => 'placeholder',
          'message' => 'Unsupported channel.',
          'payload' => ['channel' => $channel],
        ];
      }

      if (!empty($result['ok'])) {
        $sent++;
      } elseif (($result['status'] ?? '') === 'skipped') {
        $skipped++;
      } else {
        $failed++;
      }
    }

    if ($sent > 0) {
      $reminder->status = 'sent';
      $reminder->sent_at = now();
      $reminder->failed_at = null;
    } elseif ($failed > 0) {
      $reminder->status = 'failed';
      $reminder->failed_at = now();
    } else {
      $reminder->status = 'queued';
    }

    $reminder->save();

    return [
      'status' => $reminder->status,
      'sent' => $sent,
      'failed' => $failed,
      'skipped' => $skipped,
    ];
  }

  /**
   * @return Collection<int,array<string,mixed>>
   */
  private function buildSourceRows(int $patientId, int $facilityId): Collection
  {
    $rows = collect();

    $doctorAppointments = DoctorAssessment::query()
      ->where('patient_id', $patientId)
      ->where('facility_id', $facilityId)
      ->whereNotNull('next_appointment_date')
      ->get(['id', 'next_appointment_date', 'final_diagnosis']);

    foreach ($doctorAppointments as $record) {
      $rows->push([
        'source_module' => 'doctor_assessment',
        'source_record_id' => (int) $record->id,
        'reminder_date' => Carbon::parse($record->next_appointment_date)->format('Y-m-d'),
        'title' => 'Doctor Follow-up Appointment',
        'message' => $record->final_diagnosis
          ? 'Follow-up visit due. Diagnosis note: ' . $record->final_diagnosis
          : 'Follow-up visit due from doctor assessment.',
        'meta' => ['source' => 'Doctor Assessment'],
      ]);
    }

    $ttAppointments = TetanusVaccination::query()
      ->where('patient_id', $patientId)
      ->where('facility_id', $facilityId)
      ->whereNotNull('next_appointment_date')
      ->get(['id', 'next_appointment_date', 'current_tt_dose']);

    foreach ($ttAppointments as $record) {
      $rows->push([
        'source_module' => 'anc_tetanus',
        'source_record_id' => (int) $record->id,
        'reminder_date' => Carbon::parse($record->next_appointment_date)->format('Y-m-d'),
        'title' => 'TT Vaccination Appointment',
        'message' => $record->current_tt_dose
          ? 'TT follow-up due for dose ' . $record->current_tt_dose . '.'
          : 'TT follow-up appointment is due.',
        'meta' => ['source' => 'ANC Tetanus Vaccination'],
      ]);
    }

    $ancFollowUpAppointments = AntenatalFollowUpAssessment::query()
      ->where('patient_id', $patientId)
      ->where('facility_id', $facilityId)
      ->whereNotNull('next_return_date')
      ->get(['id', 'next_return_date', 'clinical_remarks']);

    foreach ($ancFollowUpAppointments as $record) {
      $rows->push([
        'source_module' => 'anc_follow_up',
        'source_record_id' => (int) $record->id,
        'reminder_date' => Carbon::parse($record->next_return_date)->format('Y-m-d'),
        'title' => 'ANC Follow-up Return Date',
        'message' => $record->clinical_remarks
          ? 'ANC return due. Note: ' . $record->clinical_remarks
          : 'ANC follow-up return date is due.',
        'meta' => ['source' => 'ANC Follow-up Assessment'],
      ]);
    }

    $familyPlanningAppointments = FamilyPlanningFollowUp::query()
      ->where('patient_id', $patientId)
      ->where('facility_id', $facilityId)
      ->whereNotNull('next_appointment_date')
      ->get(['id', 'next_appointment_date', 'method_supplied']);

    foreach ($familyPlanningAppointments as $record) {
      $rows->push([
        'source_module' => 'family_planning_follow_up',
        'source_record_id' => (int) $record->id,
        'reminder_date' => Carbon::parse($record->next_appointment_date)->format('Y-m-d'),
        'title' => 'Family Planning Follow-up',
        'message' => $record->method_supplied
          ? 'Family planning review due for method: ' . $record->method_supplied
          : 'Family planning follow-up appointment is due.',
        'meta' => ['source' => 'Family Planning Follow-up'],
      ]);
    }

    $familyPlanningRegistrationAppointments = FamilyPlanningRegistration::query()
      ->where('patient_id', $patientId)
      ->where('facility_id', $facilityId)
      ->whereNotNull('next_appointment')
      ->get(['id', 'next_appointment', 'contraceptive_selected']);

    foreach ($familyPlanningRegistrationAppointments as $record) {
      $rows->push([
        'source_module' => 'family_planning_registration',
        'source_record_id' => (int) $record->id,
        'reminder_date' => Carbon::parse($record->next_appointment)->format('Y-m-d'),
        'title' => 'Family Planning Registration Follow-up',
        'message' => $record->contraceptive_selected
          ? 'Family planning follow-up due for method: ' . $record->contraceptive_selected
          : 'Family planning registration follow-up appointment is due.',
        'meta' => ['source' => 'Family Planning Registration'],
      ]);
    }

    return $rows;
  }

  /**
   * @param  array<string,mixed>  $row
   * @return array{created:int,updated:int}
   */
  private function upsertSourceReminder(
    Patient $patient,
    int $facilityId,
    array $row,
    ?string $officerName,
    ?string $officerRole
  ): array {
    $existing = Reminder::query()
      ->where('patient_id', $patient->id)
      ->where('facility_id', $facilityId)
      ->where('source_module', (string) $row['source_module'])
      ->where('source_record_id', (int) $row['source_record_id'])
      ->first();

    $payload = [
      'title' => (string) $row['title'],
      'message' => (string) $row['message'],
      'reminder_date' => (string) $row['reminder_date'],
      'reminder_time' => null,
      'channels' => ['sms', 'email'],
      'recipient_phone' => $patient->phone,
      'recipient_email' => $patient->email,
      'created_by' => $officerName,
      'created_by_role' => $officerRole,
      'meta' => (array) ($row['meta'] ?? []),
    ];

    if (!$existing) {
      Reminder::query()->create([
        'patient_id' => $patient->id,
        'facility_id' => $facilityId,
        'source_module' => (string) $row['source_module'],
        'source_record_id' => (int) $row['source_record_id'],
        'status' => 'pending',
      ] + $payload);

      return ['created' => 1, 'updated' => 0];
    }

    $existing->fill($payload);

    // Keep canceled reminders canceled unless user explicitly reactivates.
    if ($existing->status !== 'canceled') {
      $existing->status = 'pending';
    }

    if (!$existing->isDirty()) {
      return ['created' => 0, 'updated' => 0];
    }

    $existing->save();

    return ['created' => 0, 'updated' => 1];
  }

  /**
   * @param  array{
   *   ok:bool,
   *   status:string,
   *   provider:string,
   *   message:string,
   *   payload:array<string,mixed>,
   *   provider_message_id?:string|null,
   *   http_code?:int|null
   * }  $result
   */
  private function storeDispatchLog(
    Reminder $reminder,
    string $channel,
    ?string $recipient,
    ?string $subject,
    ?string $message,
    array $result
  ): void {
    $payload = [
      'reminder_id' => $reminder->id,
      'patient_id' => $reminder->patient_id,
      'facility_id' => $reminder->facility_id,
      'channel' => $channel,
      'status' => !empty($result['ok']) ? 'sent' : (($result['status'] ?? '') === 'skipped' ? 'skipped' : 'failed'),
      'provider' => (string) ($result['provider'] ?? 'placeholder'),
      'recipient' => $recipient,
      'subject' => $subject,
      'message' => $message,
      'provider_message' => (string) ($result['message'] ?? ''),
      'provider_payload' => (array) ($result['payload'] ?? []),
      'sent_at' => !empty($result['ok']) ? now() : null,
      'failed_at' => !empty($result['ok']) ? null : now(),
    ];

    if ($this->hasProviderTrackingColumns()) {
      $payload['provider_message_id'] = isset($result['provider_message_id']) ? (string) $result['provider_message_id'] : null;
      $payload['provider_http_code'] = isset($result['http_code']) ? (int) $result['http_code'] : null;
    }

    ReminderDispatchLog::query()->create($payload);
  }

  private function hasProviderTrackingColumns(): bool
  {
    if (self::$hasProviderTrackingColumns !== null) {
      return self::$hasProviderTrackingColumns;
    }

    self::$hasProviderTrackingColumns = Schema::hasTable('reminder_dispatch_logs')
      && Schema::hasColumn('reminder_dispatch_logs', 'provider_message_id')
      && Schema::hasColumn('reminder_dispatch_logs', 'provider_http_code');

    return self::$hasProviderTrackingColumns;
  }
}
