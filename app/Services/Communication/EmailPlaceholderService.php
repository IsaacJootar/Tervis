<?php

namespace App\Services\Communication;

class EmailPlaceholderService
{
  /**
   * @return array{ok:bool,status:string,provider:string,message:string,payload:array<string,mixed>}
   */
  public function send(string $to, string $subject, string $body, array $context = []): array
  {
    $to = strtolower(trim($to));

    if ($to === '') {
      return [
        'ok' => false,
        'status' => 'failed',
        'provider' => 'placeholder-email',
        'message' => 'Email recipient is missing.',
        'payload' => $context,
      ];
    }

    return [
      'ok' => true,
      'status' => 'sent',
      'provider' => 'placeholder-email',
      'message' => 'Email sent via placeholder service (simulation only).',
      'payload' => [
        'to' => $to,
        'subject' => $subject,
        'body_length' => mb_strlen($body),
        'simulated_at' => now()->toDateTimeString(),
      ] + $context,
    ];
  }
}

