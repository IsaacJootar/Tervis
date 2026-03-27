<?php

namespace App\Jobs;

use App\Models\Reminder;
use App\Services\Communication\ReminderDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchReminderJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public int $timeout = 120;
  public int $tries = 3;

  public function __construct(private readonly int $reminderId)
  {
  }

  public function handle(ReminderDispatchService $service): void
  {
    // Re-hydrate inside the worker so the queue only carries a small scalar id.
    $reminder = Reminder::query()->find($this->reminderId);
    if (!$reminder) {
      return;
    }

    if (in_array($reminder->status, ['sent', 'canceled'], true)) {
      return;
    }

    $service->dispatchReminder($reminder);
  }

  public function failed(\Throwable $exception): void
  {
    $reminder = Reminder::query()->find($this->reminderId);
    if (!$reminder || in_array($reminder->status, ['sent', 'canceled'], true)) {
      return;
    }

    $reminder->status = 'failed';
    $reminder->failed_at = now();
    $reminder->save();
  }
}
