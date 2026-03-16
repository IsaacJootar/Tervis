<?php

namespace App\Services\Communication;

class SmsPlaceholderService
{
  /**
   * @return array{ok:bool,status:string,provider:string,message:string,payload:array<string,mixed>}
   */
  public function send(string $to, string $message, array $context = []): array
  {
    $to = trim($to);

    if ($to === '') {
      return [
        'ok' => false,
        'status' => 'failed',
        'provider' => 'placeholder-sms',
        'message' => 'SMS recipient phone is missing.',
        'payload' => $context,
      ];
    }

    return [
      'ok' => true,
      'status' => 'sent',
      'provider' => 'placeholder-sms',
      'message' => 'SMS sent via placeholder service (simulation only).',
      'payload' => [
        'to' => $to,
        'message_length' => mb_strlen($message),
        'simulated_at' => now()->toDateTimeString(),
      ] + $context,
    ];
  }
}

