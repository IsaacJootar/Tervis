<?php

return [
  'enabled' => (bool) env('TERMII_ENABLED', false),
  'base_url' => rtrim((string) env('TERMII_BASE_URL', 'https://api.ng.termii.com'), '/'),
  'api_key' => env('TERMII_API_KEY'),
  'sender_id' => env('TERMII_SENDER_ID', 'CUREVA'),
  'channel' => env('TERMII_CHANNEL', 'generic'),
  'message_type' => env('TERMII_MESSAGE_TYPE', 'plain'),
  'timeout_seconds' => (int) env('TERMII_TIMEOUT_SECONDS', 15),
  'webhook_token' => env('TERMII_WEBHOOK_TOKEN'),
  'auto_dispatch_enabled' => (bool) env('REMINDER_AUTO_DISPATCH', false),
  'auto_dispatch_with_sync' => (bool) env('REMINDER_AUTO_DISPATCH_WITH_SYNC', true),
];
