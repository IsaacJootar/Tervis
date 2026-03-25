<?php

namespace Tests\Feature;

use Tests\TestCase;

class TermiiDeliveryWebhookRouteTest extends TestCase
{
  public function test_termii_webhook_requires_token_when_configured(): void
  {
    config()->set('termii.webhook_token', 'secret-token');

    $response = $this->postJson('/webhooks/termii/delivery', [
      'status' => 'delivered',
    ]);

    $response->assertStatus(401);
  }

  public function test_termii_webhook_accepts_token_and_handles_missing_message_id_gracefully(): void
  {
    config()->set('termii.webhook_token', 'secret-token');

    $response = $this
      ->withHeader('X-Webhook-Token', 'secret-token')
      ->postJson('/webhooks/termii/delivery', [
        'status' => 'delivered',
      ]);

    $response->assertStatus(202);
    $response->assertJson([
      'ok' => false,
      'matched' => 0,
      'status' => 'ignored',
    ]);
  }
}

