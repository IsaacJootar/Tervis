<?php

namespace App\Services\Communication;

use Illuminate\Support\Facades\Http;
use Throwable;

class TermiiSmsService
{
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
    $apiKey = trim((string) config('termii.api_key', ''));
    if ($apiKey === '') {
      return [
        'ok' => false,
        'status' => 'failed',
        'provider' => 'termii-sms',
        'message' => 'TERMII_API_KEY is missing.',
        'payload' => $context,
        'provider_message_id' => null,
        'http_code' => null,
      ];
    }

    $normalizedTo = $this->normalizePhoneNumber($to);
    if ($normalizedTo === '') {
      return [
        'ok' => false,
        'status' => 'failed',
        'provider' => 'termii-sms',
        'message' => 'SMS recipient phone number is invalid.',
        'payload' => ['raw_to' => $to] + $context,
        'provider_message_id' => null,
        'http_code' => null,
      ];
    }

    $baseUrl = rtrim((string) config('termii.base_url', 'https://api.ng.termii.com'), '/');
    $payload = [
      'to' => $normalizedTo,
      'from' => (string) config('termii.sender_id', 'CUREVA'),
      'sms' => $message,
      'type' => (string) config('termii.message_type', 'plain'),
      'channel' => (string) config('termii.channel', 'generic'),
      'api_key' => $apiKey,
    ];

    try {
      $response = Http::acceptJson()
        ->asJson()
        ->timeout((int) config('termii.timeout_seconds', 15))
        ->post($baseUrl . '/api/sms/send', $payload);
    } catch (Throwable $exception) {
      return [
        'ok' => false,
        'status' => 'failed',
        'provider' => 'termii-sms',
        'message' => 'Termii request failed: ' . $exception->getMessage(),
        'payload' => [
          'to' => $normalizedTo,
          'channel' => $payload['channel'],
          'error' => $exception->getMessage(),
        ] + $context,
        'provider_message_id' => null,
        'http_code' => null,
      ];
    }

    $responseData = $response->json();
    if (!is_array($responseData)) {
      $responseData = [];
    }

    $providerMessageId = (string) (
      $responseData['message_id']
      ?? ($responseData['messageId']
        ?? ($responseData['data']['message_id'] ?? ''))
    );

    $providerMessage = trim((string) (
      $responseData['message']
      ?? ($responseData['status']
        ?? $response->body())
    ));

    $ok = $response->successful() && ($providerMessageId !== '' || str_contains(strtolower($providerMessage), 'success'));

    return [
      'ok' => $ok,
      'status' => $ok ? 'sent' : 'failed',
      'provider' => 'termii-sms',
      'message' => $providerMessage !== '' ? $providerMessage : ($ok ? 'SMS sent via Termii.' : 'Termii SMS failed.'),
      'payload' => [
        'to' => $normalizedTo,
        'from' => $payload['from'],
        'channel' => $payload['channel'],
        'type' => $payload['type'],
        'http_status' => $response->status(),
        'response' => $responseData,
      ] + $context,
      'provider_message_id' => $providerMessageId !== '' ? $providerMessageId : null,
      'http_code' => $response->status(),
    ];
  }

  private function normalizePhoneNumber(string $rawPhone): string
  {
    $digits = preg_replace('/\D+/', '', trim($rawPhone)) ?? '';
    if ($digits === '') {
      return '';
    }

    // Nigeria local format: 080xxxxxxxx -> 23480xxxxxxxx
    if (str_starts_with($digits, '0') && strlen($digits) === 11) {
      return '234' . substr($digits, 1);
    }

    // Nigeria format without leading zero: 80xxxxxxxx
    if (strlen($digits) === 10) {
      return '234' . $digits;
    }

    // International format (E.164 digits without +)
    if (strlen($digits) >= 11 && strlen($digits) <= 15) {
      return $digits;
    }

    return '';
  }
}

