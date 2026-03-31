<?php

namespace App\Services\Patients;

use App\Mail\PatientWelcomeMail;
use App\Models\Patient;
use App\Models\User;
use App\Services\Communication\WelcomeSmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PatientPortalAccountService
{
  public function __construct(
    private readonly WelcomeSmsService $welcomeSmsService,
  ) {
  }

  /**
   * Ensure a clinical patient record has exactly one linked portal account.
   * New accounts use DIN as both username and temporary password for this rollout.
   */
  public function ensureForPatient(Patient $patient): User
  {
    $patient->loadMissing(['portalAccount', 'facility']);

    $user = $patient->portalAccount
      ?? User::query()->where('patient_id', $patient->id)->first()
      ?? $this->findPatientUserByDin($patient)
      ?? $this->findLegacyPatientUser($patient);

    $shouldSendWelcomeEmail = !$user && $this->hasDeliverablePatientEmail($patient);

    $payload = [
      'first_name' => $patient->first_name,
      'last_name' => $patient->last_name,
      'phone' => $patient->phone,
      'email' => $this->resolveEmail($patient, $user?->id),
      'role' => 'Patient',
      'designation' => 'Patient',
      'facility_id' => $patient->facility_id,
      'state_id' => $patient->state_id,
      'lga_id' => $patient->lga_id,
      'patient_id' => $patient->id,
      'is_active' => true,
      'account_status' => 'active',
    ];

    if ($this->canUseUniqueValue('username', (string) $patient->din, $user?->id)) {
      $payload['username'] = (string) $patient->din;
    }

    if ($user) {
      $user->fill($payload);
      $user->save();

      return $user->fresh();
    }

    $payload['username'] = (string) $patient->din;
    $payload['password'] = Hash::make((string) $patient->din);

    $user = User::query()->create($payload);

    if ($shouldSendWelcomeEmail) {
      $this->sendWelcomeEmailAfterCommit($patient);
    }

    $this->sendWelcomeSmsAfterCommit($patient);

    return $user;
  }

  private function findPatientUserByDin(Patient $patient): ?User
  {
    if (empty($patient->din)) {
      return null;
    }

    return User::query()
      ->where('role', 'Patient')
      ->where('username', (string) $patient->din)
      ->first();
  }

  private function findLegacyPatientUser(Patient $patient): ?User
  {
    return User::query()
      ->where('role', 'Patient')
      ->whereNull('patient_id')
      ->where('facility_id', $patient->facility_id)
      ->where('first_name', $patient->first_name)
      ->where('last_name', $patient->last_name)
      ->orderBy('id')
      ->first();
  }

  private function canUseUniqueValue(string $column, mixed $value, ?int $ignoreUserId = null): bool
  {
    if ($value === null || $value === '') {
      return false;
    }

    return !User::query()
      ->where($column, $value)
      ->when($ignoreUserId, fn($query) => $query->where('id', '!=', $ignoreUserId))
      ->exists();
  }

  private function resolveEmail(Patient $patient, ?int $ignoreUserId = null): string
  {
    $preferred = trim((string) ($patient->email ?? ''));
    if ($preferred !== '' && $this->canUseUniqueValue('email', $preferred, $ignoreUserId)) {
      return $preferred;
    }

    $fallback = sprintf('%s@patients.cureva.local', (string) $patient->din);
    if ($this->canUseUniqueValue('email', $fallback, $ignoreUserId)) {
      return $fallback;
    }

    return sprintf('%s.%d@patients.cureva.local', (string) $patient->din, (int) $patient->id);
  }

  private function hasDeliverablePatientEmail(Patient $patient): bool
  {
    $email = trim((string) ($patient->email ?? ''));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return false;
    }

    return !str_ends_with(strtolower($email), '@patients.cureva.local');
  }

  private function sendWelcomeEmailAfterCommit(Patient $patient): void
  {
    $callback = function () use ($patient): void {
      try {
        Mail::to($patient->email)->send(new PatientWelcomeMail(
          patient: $patient->fresh(['facility']),
          facilityName: $patient->facility?->name ?? 'your assigned facility',
        ));
      } catch (Throwable $exception) {
        report($exception);
      }
    };

    if (DB::transactionLevel() > 0) {
      DB::afterCommit($callback);

      return;
    }

    $callback();
  }

  private function sendWelcomeSmsAfterCommit(Patient $patient): void
  {
    $this->welcomeSmsService->sendAfterCommit(
      $patient->phone,
      sprintf(
        'Welcome to Cureva. Facility: %s. DIN: %s. Username: %s. Password: %s.',
        $patient->facility?->name ?? 'your assigned facility',
        $patient->din,
        $patient->din,
        $patient->din
      ),
      [
        'type' => 'patient_welcome',
        'patient_id' => $patient->id,
        'facility_id' => $patient->facility_id,
      ]
    );
  }
}
