<?php

namespace App\Livewire\Workspaces\Modules;

use App\Models\Activity;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use App\Models\Reminder;
use App\Models\ReminderDispatchLog;
use App\Services\Communication\ReminderDispatchService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class Reminders extends Component
{
  use WithPagination;

  protected $paginationTheme = 'bootstrap';

  public $patientId;
  public $patient;

  public $patient_din;
  public $first_name;
  public $last_name;
  public $middle_name;
  public $patient_phone;
  public $patient_email;
  public $patient_dob;
  public $patient_age;
  public $patient_gender;

  public $facility_id;
  public $facility_name;
  public $facility_state;
  public $facility_lga;
  public $facility_ward;
  public $officer_name;
  public $officer_role;

  public $hasAccess = false;
  public $accessError = '';
  public $activation_time;

  public function mount($patientId)
  {
    $this->patientId = $patientId;

    $user = Auth::user();
    if (!$user || !in_array($user->role, ['Data Officer', 'Doctor', 'Medical Officer'], true)) {
      abort(403, 'Unauthorized: Access denied for this role.');
    }

    $this->facility_id = $user->facility_id;
    $this->officer_name = $user->full_name ?? ($user->first_name . ' ' . $user->last_name);
    $this->officer_role = $user->role;

    if ($this->facility_id) {
      $facility = Facility::with(['stateRelation', 'lgaRelation'])->find($this->facility_id);
      $this->facility_name = $facility?->name ?? 'Unknown Facility';
      $this->facility_state = $facility?->stateRelation?->name ?? 'N/A';
      $this->facility_lga = $facility?->lgaRelation?->name ?? 'N/A';
      $this->facility_ward = $facility?->ward ?? 'N/A';
    }

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
      $this->syncFromModules(false);
    }
  }

  private function validatePatientAccess(): void
  {
    $patient = Patient::with(['facility'])->find($this->patientId);

    if (!$patient) {
      $this->hasAccess = false;
      $this->accessError = 'Patient not found in the system.';
      return;
    }

    $todaysActivation = DinActivation::where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereDate('visit_date', today())
      ->first();

    if (!$todaysActivation) {
      $this->hasAccess = false;
      $this->accessError = 'Patient has not been checked in today. Please activate DIN first.';
      return;
    }

    $this->hasAccess = true;
    $this->patient = $patient;
    $this->activation_time = Carbon::parse($todaysActivation->check_in_time)->format('h:i A');
  }

  private function loadPatientData(): void
  {
    if (!$this->patient) {
      return;
    }

    $this->patient_din = $this->patient->din;
    $this->first_name = $this->patient->first_name;
    $this->last_name = $this->patient->last_name;
    $this->middle_name = $this->patient->middle_name;
    $this->patient_phone = $this->patient->phone;
    $this->patient_email = $this->patient->email;
    $this->patient_dob = $this->patient->date_of_birth?->format('d M Y');
    $this->patient_gender = $this->patient->gender;
    $this->patient_age = $this->patient->date_of_birth ? $this->patient->date_of_birth->age : null;
  }

  public function syncFromModules(bool $showToast = true): void
  {
    if (!$this->hasAccess) {
      toastr()->error('No access to sync reminders.');
      return;
    }

    $result = app(ReminderDispatchService::class)->syncFromModuleDates(
      (int) $this->patientId,
      (int) $this->facility_id,
      (string) $this->officer_name,
      (string) $this->officer_role
    );

    $this->logActivity('sync', 'Synchronized reminders from module date fields.', $result);
    if ($showToast) {
      toastr()->success("Reminder collation refreshed: {$result['created']} added, {$result['updated']} updated.");
    }
  }

  private function logActivity(string $action, string $description, array $meta = []): void
  {
    try {
      Activity::query()->create([
        'patient_id' => $this->patientId,
        'facility_id' => $this->facility_id,
        'module' => 'reminders',
        'action' => $action,
        'description' => $description,
        'performed_by' => $this->officer_name,
        'meta' => $meta,
      ]);
    } catch (\Exception $e) {
      // Activity logging should not block workflow.
    }
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function placeholder()
  {
    return view('placeholder');
  }

  public function render()
  {
    $remindersQuery = Reminder::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id);

    $summary = [
      'total' => (clone $remindersQuery)->count(),
      'pending' => (clone $remindersQuery)->where('status', 'pending')->count(),
      'sent' => (clone $remindersQuery)->where('status', 'sent')->count(),
      'failed' => (clone $remindersQuery)->where('status', 'failed')->count(),
    ];

    $reminders = (clone $remindersQuery)
      ->latest('reminder_date')
      ->latest('id')
      ->paginate(10, ['*'], 'reminders_page');

    $dispatchLogs = ReminderDispatchLog::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->latest('id')
      ->paginate(10, ['*'], 'dispatch_logs_page');

    return view('livewire.workspaces.modules.reminders.index', [
      'summary' => $summary,
      'reminders' => $reminders,
      'dispatchLogs' => $dispatchLogs,
    ]);
  }
}
