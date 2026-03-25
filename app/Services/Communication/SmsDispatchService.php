<?php

namespace App\Services\Communication;

class SmsDispatchService
{
  public function __construct(
    private readonly SmsPlaceholderService $placeholderService,
    private readonly TermiiSmsService $termiiService,
  ) {
  }

  /**
   * @return array{
   *   ok:bool,
   *   status:string,
   *   provider:string,
   *   message:string,
   *   payload:array<string,mixed>,
   *   provider_message_id?:string|null,
   *   http_code?:int|null
   * }
   */
  public function send(string $to, string $message, array $context = []): array
  {
    if ((bool) config('termii.enabled', false)) {
      return $this->termiiService->send($to, $message, $context);
    }

    return $this->placeholderService->send($to, $message, $context);
  }
}

