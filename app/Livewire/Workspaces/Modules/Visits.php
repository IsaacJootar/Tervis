<?php

namespace App\Livewire\Workspaces\Modules;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use App\Models\Visit;
use App\Models\VisitEvent;
use App\Services\Visits\VisitCollationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class Visits extends Component
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
  public $patient_dob;
  public $patient_age;
  public $patient_gender;

  public $facility_id;
  public $facility_name;
  public $facility_state;
  public $facility_lga;
  public $facility_ward;
  public $officer_name;

  public $hasAccess = false;
  public $accessError = '';
  public $activation_time;

  public $selectedVisitId = null;

  public function mount($patientId)
  {
    $this->patientId = $patientId;

    $user = Auth::user();
    $this->facility_id = $user->facility_id;
    $this->officer_name = $user->full_name ?? ($user->first_name . ' ' . $user->last_name);

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
    $this->patient_dob = $this->patient->date_of_birth?->format('d M Y');
    $this->patient_gender = $this->patient->gender;
    $this->patient_age = $this->patient->date_of_birth ? $this->patient->date_of_birth->age : null;
  }

  public function syncVisits(bool $showToast = true): void
  {
    if (!$this->hasAccess) {
      return;
    }

    /** @var VisitCollationService $service */
    $service = app(VisitCollationService::class);
    $result = $service->syncPatientFacility(
      (int) $this->patientId,
      (int) $this->facility_id,
      null,
      null,
      $this->officer_name
    );

    if (!$this->selectedVisitId) {
      $this->selectedVisitId = Visit::query()
        ->forPatientFacility((int) $this->patientId, (int) $this->facility_id)
        ->orderByDesc('visit_date')
        ->orderByDesc('id')
        ->value('id');
    }

    if ($showToast) {
      toastr()->success(
        "Visits synced: {$result['visit_dates']} date(s), {$result['events_upserted']} event update(s)."
      );
    }
  }

  public function selectVisit(int $visitId): void
  {
    $exists = Visit::query()
      ->forPatientFacility((int) $this->patientId, (int) $this->facility_id)
      ->whereKey($visitId)
      ->exists();

    if ($exists) {
      $this->selectedVisitId = $visitId;
      $this->resetPage('eventsPage');
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
    $visitsQuery = Visit::query()
      ->forPatientFacility((int) $this->patientId, (int) $this->facility_id)
      ->orderByDesc('visit_date')
      ->orderByDesc('id');

    $summaryQuery = Visit::query()
      ->forPatientFacility((int) $this->patientId, (int) $this->facility_id);

    $summary = [
      'total_visits' => (clone $summaryQuery)->count(),
      'total_events' => (int) (clone $summaryQuery)->sum('total_events'),
      'open_visits' => (clone $summaryQuery)->where('status', 'open')->count(),
      'this_month' => (clone $summaryQuery)
        ->whereBetween('visit_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
        ->count(),
    ];

    $visits = $visitsQuery->paginate(12, ['*'], 'visitsPage');

    if (!$this->selectedVisitId && $visits->count() > 0) {
      $this->selectedVisitId = $visits->first()->id;
    }

    $selectedVisit = null;
    $events = VisitEvent::query()->whereRaw('1 = 0')->paginate(10, ['*'], 'eventsPage');

    if ($this->selectedVisitId) {
      $selectedVisit = Visit::query()
        ->forPatientFacility((int) $this->patientId, (int) $this->facility_id)
        ->with('activation')
        ->find($this->selectedVisitId);

      if ($selectedVisit) {
        $events = VisitEvent::query()
          ->where('visit_id', $selectedVisit->id)
          ->orderByDesc('event_time')
          ->paginate(12, ['*'], 'eventsPage');
      }
    }

    return view('livewire.workspaces.modules.visits.index', [
      'summary' => $summary,
      'visits' => $visits,
      'selectedVisit' => $selectedVisit,
      'events' => $events,
    ]);
  }

  public function sectionLabel(?string $sectionKey): string
  {
    if (!$sectionKey) {
      return 'Unknown Section';
    }

    $labels = [
      'attendance' => 'Attendance',
      'doctor-assessment' => 'Doctor Assessment',
      'anc_tetanus' => 'TT Vaccination',
      'child_immunization' => 'Immunization',
      'child_nutrition' => 'Nutrition',
      'child_health_activity_register' => 'Vaccination Schedule',
      'laboratory' => 'Laboratory',
      'prescriptions' => 'Prescriptions',
      'invoices' => 'Invoices & Payments',
      'referrals' => 'Referrals',
      'reminders' => 'Reminders',
      'family_planning' => 'Family Planning',
      'health_insurance' => 'Health Insurance',
    ];

    if (isset($labels[$sectionKey])) {
      return $labels[$sectionKey];
    }

    return str($sectionKey)->replace(['_', '-'], ' ')->title()->value();
  }

  public function sectionIcon(?string $sectionKey): string
  {
    $icons = [
      'attendance' => 'bx bx-time',
      'doctor-assessment' => 'bx bx-user-check',
      'anc_tetanus' => 'bx bx-injection',
      'child_immunization' => 'bx bx-shield-plus',
      'child_nutrition' => 'bx bx-bowl-hot',
      'child_health_activity_register' => 'bx bx-calendar-check',
      'laboratory' => 'bx bx-test-tube',
      'prescriptions' => 'bx bx-capsule',
      'invoices' => 'bx bx-receipt',
      'referrals' => 'bx bx-transfer',
      'reminders' => 'bx bx-bell',
      'family_planning' => 'bx bx-group',
      'health_insurance' => 'bx bx-shield',
    ];

    return $icons[$sectionKey] ?? 'bx bx-grid-alt';
  }
}
