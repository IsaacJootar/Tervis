<?php

namespace App\Livewire\Core;

use App\Models\AntenatalFollowUpAssessment;
use App\Models\DoctorAssessment;
use App\Models\FamilyPlanningFollowUp;
use App\Models\Reminder;
use App\Models\ReminderDispatchLog;
use App\Models\Registrations\FamilyPlanningRegistration;
use App\Models\TetanusVaccination;
use App\Services\AI\WorkspaceAiAssistantService;
use App\Services\Communication\ReminderDispatchService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FacilityRemindersHub extends Component
{
  public $facility_id;
  public $officer_name;
  public $officer_role;

  public $search = '';
  public $statusFilter = 'all';
  public $channelFilter = 'all';
  public $dateFrom = '';
  public $dateTo = '';
  public bool $showAiAssistant = false;
  public string $aiAssistantSummary = '';
  public string $aiAssistantRiskLevel = 'low';
  public ?string $aiAssistantGeneratedAt = null;
  public array $aiAssistantItems = [];

  public function mount()
  {
    $user = Auth::user();
    if (!$user) {
      abort(403, 'Unauthorized.');
    }

    $this->facility_id = $user->facility_id;
    $this->officer_name = $user->full_name ?? ($user->first_name . ' ' . $user->last_name);
    $this->officer_role = $user->role;

    $this->dateTo = now()->format('Y-m-d');
    $this->dateFrom = now()->subDays(30)->format('Y-m-d');
  }

  public function updated($name): void
  {
    if (in_array((string) $name, ['search', 'statusFilter', 'channelFilter', 'dateFrom', 'dateTo'], true)) {
      $this->refreshAiAssistantIfOpen();
    }
  }

  public function syncFacilitySources(): void
  {
    $patientIds = $this->collectPatientIdsForFacilitySources();
    $service = app(ReminderDispatchService::class);

    $created = 0;
    $updated = 0;

    foreach ($patientIds as $patientId) {
      $result = $service->syncFromModuleDates((int) $patientId, (int) $this->facility_id, (string) $this->officer_name, (string) $this->officer_role);
      $created += (int) ($result['created'] ?? 0);
      $updated += (int) ($result['updated'] ?? 0);
    }

    toastr()->success("Facility sync complete: {$created} created, {$updated} updated across {$patientIds->count()} patients.");
  }

  public function dispatchDueFacility(): void
  {
    $result = app(ReminderDispatchService::class)->queueDueGlobal((int) $this->facility_id, null);
    toastr()->success("Dispatch queued: {$result['queued']} of {$result['total']} due reminders.");
    $this->refreshAiAssistantIfOpen();
  }

  public function dispatchSingle(int $reminderId): void
  {
    $reminder = Reminder::query()
      ->where('facility_id', $this->facility_id)
      ->findOrFail($reminderId);

    $result = app(ReminderDispatchService::class)->dispatchReminder($reminder);
    if (($result['status'] ?? '') === 'sent') {
      toastr()->success('Reminder sent.');
      $this->refreshAiAssistantIfOpen();
      return;
    }

    toastr()->warning('Reminder processed with status: ' . ($result['status'] ?? 'unknown'));
  }

  public function cancelReminder(int $reminderId): void
  {
    $reminder = Reminder::query()
      ->where('facility_id', $this->facility_id)
      ->findOrFail($reminderId);

    $reminder->status = 'canceled';
    $reminder->save();

    toastr()->success('Reminder canceled.');
    $this->refreshAiAssistantIfOpen();
  }

  public function requeueReminder(int $reminderId): void
  {
    $reminder = Reminder::query()
      ->where('facility_id', $this->facility_id)
      ->findOrFail($reminderId);

    $reminder->status = 'pending';
    $reminder->failed_at = null;
    $reminder->queued_at = null;
    $reminder->save();

    toastr()->success('Reminder requeued.');
    $this->refreshAiAssistantIfOpen();
  }

  public function useAiAssistant(): void
  {
    $this->showAiAssistant = true;
    $this->refreshAiAssistant();
  }

  public function hideAiAssistant(): void
  {
    $this->showAiAssistant = false;
  }

  public function refreshAiAssistant(): void
  {
    $summary = $this->buildReminderSummary();
    $analysis = app(WorkspaceAiAssistantService::class)->analyzeRemindersHub($summary);

    $this->aiAssistantSummary = (string) ($analysis['summary'] ?? '');
    $this->aiAssistantRiskLevel = (string) ($analysis['risk_level'] ?? 'low');
    $this->aiAssistantItems = (array) ($analysis['items'] ?? []);
    $this->aiAssistantGeneratedAt = (string) ($analysis['generated_at'] ?? now()->format('M d, Y h:i A'));
  }

  private function refreshAiAssistantIfOpen(): void
  {
    if (!$this->showAiAssistant) {
      return;
    }

    $this->refreshAiAssistant();
  }

  private function applyReminderFilters($query)
  {
    if ($this->dateFrom) {
      $query->whereDate('reminder_date', '>=', $this->dateFrom);
    }

    if ($this->dateTo) {
      $query->whereDate('reminder_date', '<=', $this->dateTo);
    }

    if ($this->statusFilter !== 'all') {
      $query->where('status', $this->statusFilter);
    }

    if ($this->channelFilter !== 'all') {
      $query->whereJsonContains('channels', $this->channelFilter);
    }

    if ($this->search !== '') {
      $search = trim($this->search);
      $query->where(function ($q) use ($search) {
        $q->where('title', 'like', '%' . $search . '%')
          ->orWhere('message', 'like', '%' . $search . '%')
          ->orWhere('source_module', 'like', '%' . $search . '%')
          ->orWhereHas('patient', function ($patientQuery) use ($search) {
            $patientQuery->where('din', 'like', '%' . $search . '%')
              ->orWhere('first_name', 'like', '%' . $search . '%')
              ->orWhere('last_name', 'like', '%' . $search . '%')
              ->orWhere('phone', 'like', '%' . $search . '%')
              ->orWhere('email', 'like', '%' . $search . '%');
          });
      });
    }

    return $query;
  }

  private function buildReminderSummary(): array
  {
    $query = $this->applyReminderFilters(
      Reminder::query()->where('facility_id', $this->facility_id)
    );

    return [
      'total' => (clone $query)->count(),
      'pending' => (clone $query)->where('status', 'pending')->count(),
      'sent' => (clone $query)->where('status', 'sent')->count(),
      'failed' => (clone $query)->where('status', 'failed')->count(),
      'canceled' => (clone $query)->where('status', 'canceled')->count(),
    ];
  }

  private function collectPatientIdsForFacilitySources(): Collection
  {
    $doctor = DoctorAssessment::query()
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_appointment_date')
      ->pluck('patient_id');

    $tt = TetanusVaccination::query()
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_appointment_date')
      ->pluck('patient_id');

    $anc = AntenatalFollowUpAssessment::query()
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_return_date')
      ->pluck('patient_id');

    $fp = FamilyPlanningFollowUp::query()
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_appointment_date')
      ->pluck('patient_id');

    $fpRegistration = FamilyPlanningRegistration::query()
      ->where('facility_id', $this->facility_id)
      ->whereNotNull('next_appointment')
      ->pluck('patient_id');

    return $doctor->merge($tt)->merge($anc)->merge($fp)->merge($fpRegistration)->filter()->unique()->values();
  }

  public function render()
  {
    $remindersQuery = $this->applyReminderFilters(
      Reminder::query()
        ->where('facility_id', $this->facility_id)
        ->with('patient:id,first_name,last_name,din,phone,email')
    );

    $summary = $this->buildReminderSummary();

    $reminders = (clone $remindersQuery)
      ->latest('reminder_date')
      ->latest('id')
      ->get();

    $dispatchLogsQuery = ReminderDispatchLog::query()
      ->where('facility_id', $this->facility_id)
      ->with('reminder:id,title', 'patient:id,first_name,last_name,din');

    if ($this->dateFrom) {
      $dispatchLogsQuery->whereDate('created_at', '>=', $this->dateFrom);
    }

    if ($this->dateTo) {
      $dispatchLogsQuery->whereDate('created_at', '<=', $this->dateTo);
    }

    if ($this->channelFilter !== 'all') {
      $dispatchLogsQuery->where('channel', $this->channelFilter);
    }

    if ($this->search !== '') {
      $search = trim($this->search);
      $dispatchLogsQuery->where(function ($q) use ($search) {
        $q->where('recipient', 'like', '%' . $search . '%')
          ->orWhere('provider_message', 'like', '%' . $search . '%')
          ->orWhereHas('reminder', function ($reminderQuery) use ($search) {
            $reminderQuery->where('title', 'like', '%' . $search . '%');
          })
          ->orWhereHas('patient', function ($patientQuery) use ($search) {
            $patientQuery->where('din', 'like', '%' . $search . '%')
              ->orWhere('first_name', 'like', '%' . $search . '%')
              ->orWhere('last_name', 'like', '%' . $search . '%');
          });
      });
    }

    if ($this->statusFilter !== 'all') {
      if (in_array($this->statusFilter, ['sent', 'failed'], true)) {
        $dispatchLogsQuery->where('status', $this->statusFilter);
      }
      if ($this->statusFilter === 'canceled') {
        $dispatchLogsQuery->where('status', 'skipped');
      }
    }

    $dispatchLogs = (clone $dispatchLogsQuery)
      ->latest('id')
      ->get();

    return view('livewire.core.facility-reminders-hub', [
      'summary' => $summary,
      'reminders' => $reminders,
      'dispatchLogs' => $dispatchLogs,
    ])->layout('layouts.facilityAdminLayout');
  }
}
