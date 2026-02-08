<?php

namespace App\Livewire\Workspaces\Modules;

use App\Models\Activity;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Carbon\Carbon;
use Livewire\WithPagination;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class Activities extends Component
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

  private function validatePatientAccess()
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

  private function loadPatientData()
  {
    if (!$this->patient) return;

    $this->patient_din = $this->patient->din;
    $this->first_name = $this->patient->first_name;
    $this->last_name = $this->patient->last_name;
    $this->middle_name = $this->patient->middle_name;
    $this->patient_phone = $this->patient->phone;
    $this->patient_dob = $this->patient->date_of_birth?->format('d M Y');
    $this->patient_gender = $this->patient->gender;
    $this->patient_age = $this->patient->date_of_birth ? $this->patient->date_of_birth->age : null;
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
    $activitiesQuery = Activity::where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->latest();

    $activities = $activitiesQuery->paginate(15);
    $totalActivities = (clone $activitiesQuery)->count();

    return view('livewire.workspaces.modules.activities.index', [
      'activities' => $activities,
      'totalActivities' => $totalActivities,
    ]);
  }
}
