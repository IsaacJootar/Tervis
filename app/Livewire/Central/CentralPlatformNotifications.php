<?php

namespace App\Livewire\Central;

use App\Models\Facility;
use App\Models\FacilityModuleAccess;
use App\Models\Reminder;
use App\Models\ReminderDispatchLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class CentralPlatformNotifications extends Component
{
  public string $selected_facility_id = '';
  public string $selected_channel = 'all';

  public function mount(): void
  {
    $user = Auth::user();
    if (!$user || !in_array((string) $user->role, ['Central Admin', 'Central Administrator'], true)) {
      abort(403, 'Unauthorized: Central admin access only.');
    }
  }

  public function render()
  {
    $hasFacilities = Schema::hasTable('facilities');
    $hasReminders = Schema::hasTable('reminders');
    $hasDispatchLogs = Schema::hasTable('reminder_dispatch_logs');
    $hasModuleAccess = Schema::hasTable('facility_module_accesses');

    $facilityId = (int) $this->selected_facility_id;
    $channel = trim($this->selected_channel);

    $facilityRows = $hasFacilities
      ? Facility::query()->select(['id', 'name'])->orderBy('name')->get()
      : collect();

    $failedDispatches = collect();
    if ($hasDispatchLogs) {
      $query = ReminderDispatchLog::query()
        ->with('facility:id,name')
        ->select(['id', 'facility_id', 'channel', 'status', 'recipient', 'provider', 'failed_at', 'created_at'])
        ->where('status', 'failed')
        ->latest('id')
        ->limit(400);

      if ($facilityId > 0) {
        $query->where('facility_id', $facilityId);
      }
      if ($channel !== '' && $channel !== 'all') {
        $query->where('channel', $channel);
      }

      $failedDispatches = $query->get();
    }

    $dueReminders = collect();
    if ($hasReminders) {
      $query = Reminder::query()
        ->with(['facility:id,name', 'patient:id,first_name,last_name,din'])
        ->select(['id', 'facility_id', 'patient_id', 'title', 'reminder_date', 'reminder_time', 'status', 'channels', 'created_at'])
        ->whereIn('status', ['pending', 'queued'])
        ->whereDate('reminder_date', '<=', now()->toDateString())
        ->latest('id')
        ->limit(400);

      if ($facilityId > 0) {
        $query->where('facility_id', $facilityId);
      }

      $dueReminders = $query->get();
    }

    $disabledModuleRows = collect();
    if ($hasModuleAccess) {
      $query = FacilityModuleAccess::query()
        ->with('facility:id,name')
        ->select(['id', 'facility_id', 'module_label', 'module_key', 'updated_at'])
        ->where('is_enabled', false)
        ->latest('updated_at')
        ->limit(400);

      if ($facilityId > 0) {
        $query->where('facility_id', $facilityId);
      }

      $disabledModuleRows = $query->get();
    }

    $summary = [
      'failed_24h' => $failedDispatches->where('created_at', '>=', now()->subDay())->count(),
      'failed_total' => $failedDispatches->count(),
      'due_today' => $dueReminders->where('reminder_date', now()->toDateString())->count(),
      'due_total' => $dueReminders->count(),
      'facilities_with_disabled_modules' => $disabledModuleRows->pluck('facility_id')->unique()->count(),
    ];

    return view('livewire.central.central-platform-notifications', [
      'summary' => $summary,
      'facilityRows' => $facilityRows,
      'failedDispatches' => $failedDispatches,
      'dueReminders' => $dueReminders,
      'disabledModuleRows' => $disabledModuleRows,
    ])->layout('layouts.centralAdminLayout');
  }
}

