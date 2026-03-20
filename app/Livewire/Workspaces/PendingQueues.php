<?php

namespace App\Livewire\Workspaces;

use App\Models\LabTestOrder;
use App\Models\Prescription;
use App\Models\Reminder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PendingQueues extends Component
{
  public int $facilityId = 0;

  public function mount(): void
  {
    $user = Auth::user();
    $this->facilityId = (int) ($user->facility_id ?? 0);
  }

  public function render()
  {
    $pendingLabs = LabTestOrder::query()
      ->with('patient:id,first_name,last_name,din')
      ->where('facility_id', $this->facilityId)
      ->where('status', 'pending')
      ->latest('id')
      ->limit(300)
      ->get();

    $pendingPrescriptions = Prescription::query()
      ->with('patient:id,first_name,last_name,din')
      ->where('facility_id', $this->facilityId)
      ->where('status', 'pending')
      ->latest('id')
      ->limit(300)
      ->get();

    $dueReminders = Reminder::query()
      ->with('patient:id,first_name,last_name,din')
      ->where('facility_id', $this->facilityId)
      ->whereIn('status', ['pending', 'queued'])
      ->whereDate('reminder_date', '<=', now()->toDateString())
      ->latest('id')
      ->limit(300)
      ->get();

    $summary = [
      'pending_labs' => $pendingLabs->count(),
      'pending_prescriptions' => $pendingPrescriptions->count(),
      'due_reminders' => $dueReminders->count(),
    ];

    return view('livewire.workspaces.pending-queues', [
      'summary' => $summary,
      'pendingLabs' => $pendingLabs,
      'pendingPrescriptions' => $pendingPrescriptions,
      'dueReminders' => $dueReminders,
    ])->layout('layouts.dataOfficerLayout');
  }
}

