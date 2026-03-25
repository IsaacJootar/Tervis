<?php

namespace Tests\Unit;

use App\Services\Communication\TermiiSmsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TermiiSmsServiceTest extends TestCase
{
  public function test_it_sends_sms_successfully_with_termii(): void
  {
    config()->set('termii.base_url', 'https://api.ng.termii.com');
    config()->set('termii.api_key', 'test-key');
    config()->set('termii.sender_id', 'CUREVA');
    config()->set('termii.channel', 'generic');
    config()->set('termii.message_type', 'plain');
    config()->set('termii.timeout_seconds', 10);

    Http::fake([
      'https://api.ng.termii.com/api/sms/send' => Http::response([
        'message' => 'Successfully Sent',
        'message_id' => 'abc-123',
      ], 200),
    ]);

    $result = app(TermiiSmsService::class)->send('08058787875', 'Appointment reminder', ['reminder_id' => 44]);

    $this->assertTrue($result['ok']);
    $this->assertSame('sent', $result['status']);
    $this->assertSame('termii-sms', $result['provider']);
    $this->assertSame('abc-123', $result['provider_message_id']);
    $this->assertSame(200, $result['http_code']);

    Http::assertSent(function ($request) {
      $data = $request->data();
      return $request->url() === 'https://api.ng.termii.com/api/sms/send'
        && ($data['to'] ?? null) === '2348058787875'
        && ($data['from'] ?? null) === 'CUREVA'
        && ($data['api_key'] ?? null) === 'test-key';
    });
  }

  public function test_it_fails_when_termii_api_key_is_missing(): void
  {
    config()->set('termii.api_key', '');

    $result = app(TermiiSmsService::class)->send('08058787875', 'Appointment reminder');

    $this->assertFalse($result['ok']);
    $this->assertSame('failed', $result['status']);
    $this->assertSame('termii-sms', $result['provider']);
    $this->assertStringContainsString('TERMII_API_KEY is missing', $result['message']);
  }
}

