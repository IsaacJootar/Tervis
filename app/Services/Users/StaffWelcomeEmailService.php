<?php

namespace App\Services\Users;

use App\Mail\StaffWelcomeMail;
use App\Models\User;
use App\Services\Communication\WelcomeSmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StaffWelcomeEmailService
{
  public function __construct(
    private readonly WelcomeSmsService $welcomeSmsService,
  ) {
  }

  /**
   * Send onboarding mail only for real deliverable staff emails.
   * The send is deferred until commit so account creation does not break.
   */
  public function sendForNewAccount(User $user, string $plainPassword, ?string $scopeLabel = null): void
  {
    $email = trim((string) ($user->email ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return;
    }

    $callback = function () use ($user, $plainPassword, $scopeLabel): void {
      try {
        Mail::to($user->email)->send(new StaffWelcomeMail(
          user: $user->fresh(['facility', 'state', 'lga']),
          plainPassword: $plainPassword,
          scopeLabel: $scopeLabel,
        ));
      } catch (Throwable $exception) {
        report($exception);
      }
    };

    if (DB::transactionLevel() > 0) {
      DB::afterCommit($callback);
    } else {
      $callback();
    }

    $this->welcomeSmsService->sendAfterCommit(
      $user->phone,
      $this->buildSmsMessage($user, $plainPassword, $scopeLabel),
      [
        'type' => 'staff_welcome',
        'user_id' => $user->id,
        'role' => $user->role,
        'facility_id' => $user->facility_id,
      ]
    );
  }

  private function buildSmsMessage(User $user, string $plainPassword, ?string $scopeLabel = null): string
  {
    $assignment = $scopeLabel ? " Assignment: {$scopeLabel}." : '';

    return sprintf(
      'Welcome to Cureva. Role: %s.%s Username: %s. Password: %s.',
      $user->role,
      $assignment,
      $user->username,
      $plainPassword
    );
  }
}
