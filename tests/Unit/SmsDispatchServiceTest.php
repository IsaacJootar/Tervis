<?php

namespace Tests\Unit;

use App\Services\Communication\SmsDispatchService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsDispatchServiceTest extends TestCase
{
  public function test_it_uses_placeholder_when_termii_is_disabled(): void
  {
    config()->set('termii.enabled', false);

    $result = app(SmsDispatchService::class)->send('08058787875', 'Hello');

    $this->assertTrue($result['ok']);
    $this->assertSame('sent', $result['status']);
    $this->assertSame('placeholder-sms', $result['provider']);
  }

  public function test_it_uses_termii_when_enabled(): void
  {
    config()->set('termii.enabled', true);
    config()->set('termii.base_url', 'https://api.ng.termii.com');
    config()->set('termii.api_key', 'live-key');
    config()->set('termii.sender_id', 'CUREVA');

    Http::fake([
      'https://api.ng.termii.com/api/sms/send' => Http::response([
        'message' => 'Successfully Sent',
        'message_id' => 'msg-999',
      ], 200),
    ]);

    $result = app(SmsDispatchService::class)->send('08058787875', 'Hello');

    $this->assertTrue($result['ok']);
    $this->assertSame('sent', $result['status']);
    $this->assertSame('termii-sms', $result['provider']);
    $this->assertSame('msg-999', $result['provider_message_id']);
  }
}

