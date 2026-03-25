<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Communication\TermiiDeliveryWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TermiiDeliveryWebhookController extends Controller
{
  public function __invoke(Request $request, TermiiDeliveryWebhookService $service): JsonResponse
  {
    $token = trim((string) config('termii.webhook_token', ''));
    if ($token !== '') {
      $provided = trim((string) ($request->header('X-Webhook-Token') ?? $request->input('token', '')));
      if (!hash_equals($token, $provided)) {
        return response()->json([
          'ok' => false,
          'message' => 'Unauthorized webhook token.',
        ], 401);
      }
    }

    $payload = $request->all();
    if (!is_array($payload)) {
      $payload = [];
    }

    $result = $service->process($payload);
    $httpCode = $result['ok'] ? 200 : 202;

    return response()->json($result, $httpCode);
  }
}

