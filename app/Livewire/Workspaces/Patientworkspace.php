<?php

namespace App\Livewire\Workspaces;

use Exception;
use App\Models\Patient;
use App\Models\Facility;
use App\Models\Registrations\DinActivation;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class PatientWorkspace extends Component
{
  // ============================================
  // DIN VERIFICATION PROPERTIES
  // ============================================
  public $din;
  public $isPatientVerified = false;
  public $isPatientNotFound = false;
  public $isNotActivatedToday = false;
  public $din_modal_flag = false;
  public $patient_registration_facility = '';

  // ============================================
  // PATIENT INFO (auto-populated from DIN verification)
  // ============================================
  public $patient_id;
  public $first_name;
  public $last_name;
  public $middle_name;
  public $patient_phone;
  public $patient_dob;
  public $patient_age;
  public $patient_gender;
  public $patient_din;

  // ============================================
  // ACTIVATION INFO
  // ============================================
  public $activation_time;
  public $activation_id;

  // ============================================
  // PATIENT ENTRY POINTS (Programs registered)
  // ============================================
  public $has_general_registration = false;
  public $has_antenatal_registration = false;
  public $has_family_planning_registration = false;
  public $entry_points = [];

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
  // MOUNT
  // ============================================
  public function mount()
  {
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
  }

  // ============================================
  // OPEN DIN MODAL
  // ============================================
  public function openDinModal()
  {
    $this->resetVerificationState();
    $this->din_modal_flag = true;
  }

  // ============================================
  // RESET VERIFICATION STATE
  // ============================================
  private function resetVerificationState()
  {
    $this->reset([
      'din',
      'patient_id',
      'first_name',
      'last_name',
      'middle_name',
      'patient_phone',
      'patient_dob',
      'patient_age',
      'patient_gender',
      'patient_din',
      'activation_time',
      'activation_id',
      'has_general_registration',
      'has_antenatal_registration',
      'has_family_planning_registration',
      'entry_points',
      'isPatientVerified',
      'isPatientNotFound',
      'isNotActivatedToday',
      'patient_registration_facility'
    ]);
  }

  // ============================================
  // VERIFY PATIENT BY DIN
  // ============================================
  public function verifyPatient()
  {
    // Reset verification states
    $this->isPatientVerified = false;
    $this->isPatientNotFound = false;
    $this->isNotActivatedToday = false;

    // Validate DIN format (must be exactly 8 digits)
    if (strlen($this->din) !== 8 || !ctype_digit($this->din)) {
      toastr()->error('Please enter a valid 8-digit DIN');
      return;
    }

    // Search for patient
    $patient = Patient::where('din', $this->din)
      ->with(['facility', 'generalRegistration', 'activeAntenatalRegistration', 'familyPlanningRegistration'])
      ->first();

    // ================================================================
    // SCENARIO 1: Patient NOT found in system
    // ================================================================
    if (!$patient) {
      toastr()->error('DIN not found. Patient is not registered in the system.');
      $this->isPatientNotFound = true;
      $this->isPatientVerified = false;
      $this->isNotActivatedToday = false;
      $this->patient_id = null;
      $this->din_modal_flag = true;
      return;
    }

    // ================================================================
    // SCENARIO 2: Patient found - check if ACTIVATED TODAY
    // ================================================================
    $todaysActivation = DinActivation::where('patient_id', $patient->id)
      ->where('facility_id', $this->facility_id)
      ->whereDate('visit_date', today())
      ->first();

    if (!$todaysActivation) {
      // NOT activated today
      toastr()->warning('Patient has not been checked in today. Please activate DIN first.');
      $this->isPatientVerified = false;
      $this->isPatientNotFound = false;
      $this->isNotActivatedToday = true;
      $this->patient_id = $patient->id;
      $this->first_name = $patient->first_name;
      $this->last_name = $patient->last_name;
      $this->middle_name = $patient->middle_name;
      $this->patient_din = $patient->din;
      $this->patient_registration_facility = $patient->facility->name ?? 'N/A';
      $this->din_modal_flag = true;
      return;
    }

    // ================================================================
    // SCENARIO 3: Patient found AND activated today - Ready to open workspace
    // ================================================================
    $this->patient_id = $patient->id;
    $this->patient_din = $patient->din;
    $this->first_name = $patient->first_name;
    $this->last_name = $patient->last_name;
    $this->middle_name = $patient->middle_name;
    $this->patient_phone = $patient->phone;
    $this->patient_dob = $patient->date_of_birth?->format('Y-m-d');
    $this->patient_gender = $patient->gender;

    // Recalculate age from date of birth
    $this->patient_age = $patient->date_of_birth ? $patient->date_of_birth->age : null;

    // Activation info
    $this->activation_time = Carbon::parse($todaysActivation->check_in_time)->format('h:i A');
    $this->activation_id = $todaysActivation->id;

    // Check entry points (registered programs)
    $this->has_general_registration = $patient->hasGeneralRegistration();
    $this->has_antenatal_registration = $patient->hasActiveAntenatalRegistration();
    $this->has_family_planning_registration = $patient->hasFamilyPlanningRegistration();
    $this->entry_points = $patient->entry_points;

    $this->patient_registration_facility = $patient->facility->name ?? 'N/A';
    $this->isPatientVerified = true;
    $this->isPatientNotFound = false;
    $this->isNotActivatedToday = false;
    $this->din_modal_flag = true;

    toastr()->success('Patient verified! Checked in at ' . $this->activation_time . '. Click "Open Workspace" to proceed.');
  }

  // ============================================
  // OPEN PATIENT WORKSPACE (Redirect to Dashboard)
  // ============================================
  public function openWorkspace()
  {
    if (!$this->isPatientVerified || !$this->patient_id) {
      toastr()->error('Please verify patient first');
      return;
    }

    // Redirect to Patient Dashboard with patient ID
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patient_id]);
  }

  // ============================================
  // RESET FOR NEXT PATIENT
  // ============================================
  public function resetForNextPatient()
  {
    $this->resetVerificationState();
    $this->dispatch('clear-din-inputs');
  }

  // ============================================
  // EXIT AND CLOSE MODAL
  // ============================================
  public function exit()
  {
    $this->resetVerificationState();
    $this->dispatch('close-modals');
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
    return view('livewire.workspaces.patient-workspace', [
      'facility_name' => $this->facility_name,
      'facility_state' => $this->facility_state,
      'facility_lga' => $this->facility_lga,
      'facility_ward' => $this->facility_ward,
    ]);
  }
}
