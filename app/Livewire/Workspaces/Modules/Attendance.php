<?php

namespace App\Livewire\Workspaces\Modules;

use App\Models\Patient;
use App\Models\Facility;
use App\Models\Registrations\DinActivation;
use App\Models\Activity;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class Attendance extends Component
{
  // ============================================
  // PATIENT CONTEXT (from route parameter)
  // ============================================
  public $patientId;
  public $patient;

  // ============================================
  // PATIENT INFO
  // ============================================
  public $patient_din;
  public $first_name;
  public $last_name;
  public $middle_name;
  public $patient_phone;
  public $patient_dob;
  public $patient_age;
  public $patient_gender;
  public $patient_nhis_status;

  // ============================================
  // FACILITY AND OFFICER INFO
  // ============================================
  public $facility_id;
  public $facility_name;
  public $facility_state;
  public $facility_lga;
  public $facility_ward;
  public $officer_name;

  // ============================================
  // ACCESS CONTROL
  // ============================================
  public $hasAccess = false;
  public $accessError = '';
  public $activation_time;

  // ============================================
  // ATTENDANCE DATA
  // ============================================
  public $activations = [];
  public $total_visits = 0;
  public $last_visit_date = 'N/A';
  public $visits_this_month = 0;

  // ============================================
  // MOUNT
  // ============================================
  public function mount($patientId)
  {
    $this->patientId = $patientId;

    // Load officer and facility info
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

    // Validate patient access
    $this->validatePatientAccess();

    // Load patient data if access granted
    if ($this->hasAccess) {
      $this->loadPatientData();
      $this->loadAttendanceData();
      $this->logViewActivity();
    }
  }

  // ============================================
  // VALIDATE PATIENT ACCESS
  // ============================================
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

  // ============================================
  // LOAD PATIENT DATA
  // ============================================
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
    $this->patient_nhis_status = $this->patient->nhis_status;
  }

  // ============================================
  // LOAD ATTENDANCE DATA
  // ============================================
  private function loadAttendanceData()
  {
    $this->activations = DinActivation::where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->latestFirst()
      ->get();

    $this->total_visits = $this->activations->count();
    $latest = $this->activations->first();
    $this->last_visit_date = $latest?->formatted_visit_date ?? 'N/A';

    $this->visits_this_month = DinActivation::where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereBetween('visit_date', [now()->startOfMonth(), now()->endOfMonth()])
      ->count();
  }

  // ============================================
  // ACTIVITY LOGGING (VIEW)
  // ============================================
  private function logViewActivity(): void
  {
    try {
      $cacheKey = "activity:view:attendance:{$this->facility_id}:{$this->patientId}:" . now()->toDateString();
      if (!Cache::add($cacheKey, true, now()->addHours(6))) {
        return;
      }

      Activity::create([
        'patient_id' => $this->patientId,
        'facility_id' => $this->facility_id,
        'module' => 'attendance',
        'action' => 'view',
        'description' => 'Viewed attendance history',
        'performed_by' => $this->officer_name,
      ]);
    } catch (\Exception $e) {
      // Activity logging should never break the main workflow.
    }
  }

  // ============================================
  // BACK TO DASHBOARD
  // ============================================
  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  // ============================================
  // PLACEHOLDER FOR LAZY LOADING
  // ============================================
  public function placeholder()
  {
    return view('placeholder');
  }

  // ============================================
  // RENDER
  // ============================================
  public function render()
  {
    return view('livewire.workspaces.modules.attendance.index');
  }
}
