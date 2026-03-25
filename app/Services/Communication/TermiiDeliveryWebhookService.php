<?php

namespace App\Services\Communication;

use App\Models\ReminderDispatchLog;
use Illuminate\Support\Arr;

class TermiiDeliveryWebhookService
{
  /**
   * @param array<string,mixed> $payload
   * @return array{ok:bool,matched:int,status:string,message:string}
   */
  public function process(array $payload): array
  {
    $messageId = $this->extractMessageId($payload);
    if ($messageId === '') {
      return [
        'ok' => false,
        'matched' => 0,
        'status' => 'ignored',
        'message' => 'Missing provider message id in callback payload.',
      ];
    }

    $deliveryStatus = $this->extractStatus($payload);
    $deliveryMessage = $this->extractMessage($payload);

    $log = ReminderDispatchLog::query()
      ->with('reminder:id,status,sent_at,failed_at')
      ->where('provider', 'termii-sms')
      ->where('provider_message_id', $messageId)
      ->latest('id')
      ->first();

    if (!$log) {
      return [
        'ok' => false,
        'matched' => 0,
        'status' => 'ignored',
        'message' => 'No dispatch log found for provider message id.',
      ];
    }

    $log->delivery_status = $deliveryStatus !== '' ? $deliveryStatus : 'unknown';
    $log->delivery_message = $deliveryMessage !== '' ? $deliveryMessage : null;
    $log->delivery_payload = $payload;
    $log->delivery_updated_at = now();
    $log->save();

    $normalized = strtolower($deliveryStatus);
    $isDelivered = in_array($normalized, ['delivered', 'sent', 'success', 'successful'], true);
    $isFailed = in_array($normalized, ['failed', 'undelivered', 'rejected', 'error'], true);

    if ($log->reminder) {
      if ($isDelivered) {
        $log->reminder->status = 'sent';
        $log->reminder->sent_at = $log->reminder->sent_at ?: now();
        $log->reminder->failed_at = null;
        $log->reminder->save();
      } elseif ($isFailed && $log->reminder->status !== 'sent') {
        $log->reminder->status = 'failed';
        $log->reminder->failed_at = now();
        $log->reminder->save();
      }
    }

    return [
      'ok' => true,
      'matched' => 1,
      'status' => $log->delivery_status ?? 'unknown',
      'message' => 'Callback processed.',
    ];
  }

  /**
   * @param array<string,mixed> $payload
   */
  private function extractMessageId(array $payload): string
  {
    $candidates = [
      Arr::get($payload, 'message_id'),
      Arr::get($payload, 'messageId'),
      Arr::get($payload, 'sms_id'),
      Arr::get($payload, 'data.message_id'),
      Arr::get($payload, 'data.messageId'),
      Arr::get($payload, 'data.sms_id'),
      Arr::get($payload, 'data.id'),
    ];

    foreach ($candidates as $candidate) {
      $value = trim((string) $candidate);
      if ($value !== '') {
        return $value;
      }
    }

    return '';
  }

  /**
   * @param array<string,mixed> $payload
   */
  private function extractStatus(array $payload): string
  {
    $candidates = [
      Arr::get($payload, 'status'),
      Arr::get($payload, 'delivery_status'),
      Arr::get($payload, 'message_status'),
      Arr::get($payload, 'data.status'),
      Arr::get($payload, 'data.delivery_status'),
      Arr::get($payload, 'data.message_status'),
    ];

    foreach ($candidates as $candidate) {
      $value = trim((string) $candidate);
      if ($value !== '') {
        return $value;
      }
    }

    return 'unknown';
  }

  /**
   * @param array<string,mixed> $payload
   */
  private function extractMessage(array $payload): string
  {
    $candidates = [
      Arr::get($payload, 'message'),
      Arr::get($payload, 'description'),
      Arr::get($payload, 'reason'),
      Arr::get($payload, 'data.message'),
      Arr::get($payload, 'data.description'),
      Arr::get($payload, 'data.reason'),
    ];

    foreach ($candidates as $candidate) {
      $value = trim((string) $candidate);
      if ($value !== '') {
        return $value;
      }
    }

    return '';
  }
}

