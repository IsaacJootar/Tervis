<?php

namespace App\Services\Communication;

use Illuminate\Support\Facades\DB;
use Throwable;

class WelcomeSmsService
{
  public function __construct(
    private readonly SmsDispatchService $smsService,
  ) {
  }

  public function sendAfterCommit(?string $phone, string $message, array $context = []): void
  {
    $phone = trim((string) $phone);
    if ($phone === '') {
      return;
    }

    $payload = ['channel' => 'dnd'] + $context;

    $callback = function () use ($phone, $message, $payload): void {
      try {
        $result = $this->smsService->send($phone, $message, $payload);

        // Termii may reject DND on accounts/routes that are not yet fully
        // activated. In that case, fall back once to the generic route so
        // onboarding SMS can still be delivered on currently enabled accounts.
        if ($this->shouldFallbackToGeneric($result, (string) ($payload['channel'] ?? ''))) {
          $fallbackPayload = ['channel' => 'generic'] + $context;
          $this->smsService->send($phone, $message, $fallbackPayload);
        }
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

  /**
   * @param array{ok?:bool,provider?:string,message?:string,payload?:array<string,mixed>} $result
   */
  private function shouldFallbackToGeneric(array $result, string $requestedChannel): bool
  {
    if (strtolower($requestedChannel) !== 'dnd') {
      return false;
    }

    if (!empty($result['ok']) || ($result['provider'] ?? '') !== 'termii-sms') {
      return false;
    }

    $message = strtolower((string) ($result['message'] ?? ''));

    return str_contains($message, 'country inactive')
      || str_contains($message, 'route')
      || str_contains($message, 'activate');
  }
}
