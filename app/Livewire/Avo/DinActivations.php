<?php

namespace App\Livewire\Avo;

use Exception;
use App\Models\Patient;
use App\Models\Facility;
use App\Models\Registrations\DinActivation;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;

#[Layout('layouts.avoOfficerLayout')]
#[Lazy]
class DinActivations extends Component
{
  // ============================================
  // DIN VERIFICATION PROPERTIES
  // ============================================
  public $din;
  public $isPatientVerified = false;
  public $isPatientNotFound = false;
  public $isAlreadyActivatedToday = false;
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
  public $existing_activation_time;
  public $existing_activation_id;

  // ============================================
  // FACILITY AND OFFICER INFO
  // ============================================
  public $facility_id;
  public $facility_name;
  public $facility_state;
  public $facility_lga;
  public $facility_ward;
  public $officer_name;
  public $officer_role;
  public $officer_designation;

  // ============================================
  // ACTIVATIONS LIST
  // ============================================
  public $activations = [];

  // ============================================
  // MOUNT
  // ============================================
  public function mount()
  {
    $user = Auth::user();
    $this->facility_id = $user->facility_id;
    $this->officer_name = $user->full_name ?? ($user->first_name . ' ' . $user->last_name);
    $this->officer_role = $user->role ?? $user->designation ?? 'Staff';
    $this->officer_designation = $user->designation ?? 'N/A';

    if ($this->facility_id) {
      $facility = Facility::with(['stateRelation', 'lgaRelation'])->find($this->facility_id);

      $this->facility_name = $facility?->name ?? 'Unknown Facility';
      $this->facility_state = $facility?->stateRelation?->name ?? 'N/A';
      $this->facility_lga = $facility?->lgaRelation?->name ?? 'N/A';
      $this->facility_ward = $facility?->ward ?? 'N/A';
    }

    $this->loadActivations();
  }

  // ============================================
  // OPEN DIN MODAL
  // ============================================
  public function openDinModal()
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
      'isPatientVerified',
      'isPatientNotFound',
      'isAlreadyActivatedToday',
      'existing_activation_time',
      'existing_activation_id',
      'patient_registration_facility'
    ]);
    $this->din_modal_flag = true;
  }

  // ============================================
  // VERIFY PATIENT BY DIN
  // ============================================
  public function verifyPatient()
  {
    // Reset verification states
    $this->isPatientVerified = false;
    $this->isPatientNotFound = false;
    $this->isAlreadyActivatedToday = false;

    // Validate DIN format (must be exactly 8 digits)
    if (strlen($this->din) !== 8 || !ctype_digit($this->din)) {
      toastr()->error('Please enter a valid 8-digit DIN');
      return;
    }

    // Search for patient in this facility's records or any facility
    $patient = Patient::where('din', $this->din)
      ->with(['facility'])
      ->first();

    // ================================================================
    // SCENARIO 1: Patient NOT found
    // ================================================================
    if (!$patient) {
      toastr()->error('DIN not found. Patient does not exist in the system.');
      $this->isPatientNotFound = true;
      $this->isPatientVerified = false;
      $this->isAlreadyActivatedToday = false;
      $this->patient_id = null;
      $this->din_modal_flag = true;
      return;
    }

    // ================================================================
    // SCENARIO 2: Patient found - check if ALREADY ACTIVATED TODAY
    // ================================================================
    $todaysActivation = DinActivation::where('patient_id', $patient->id)
      ->where('facility_id', $this->facility_id)
      ->whereDate('visit_date', today())
      ->first();

    if ($todaysActivation) {
      // Already activated today - show info
      toastr()->info('Patient already checked in today at ' . Carbon::parse($todaysActivation->check_in_time)->format('h:i A'));
      $this->isPatientVerified = false;
      $this->isPatientNotFound = false;
      $this->isAlreadyActivatedToday = true;
      $this->patient_id = $patient->id;
      $this->first_name = $patient->first_name;
      $this->last_name = $patient->last_name;
      $this->middle_name = $patient->middle_name;
      $this->patient_registration_facility = $patient->facility->name ?? 'N/A';
      $this->existing_activation_time = Carbon::parse($todaysActivation->check_in_time)->format('h:i A');
      $this->existing_activation_id = $todaysActivation->id;
      $this->din_modal_flag = true;
      return;
    }

    // ================================================================
    // SCENARIO 3: Patient found + NOT activated today - Ready to activate
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

    $this->patient_registration_facility = $patient->facility->name ?? 'N/A';
    $this->isPatientVerified = true;
    $this->isPatientNotFound = false;
    $this->isAlreadyActivatedToday = false;
    $this->din_modal_flag = true;

    toastr()->success('Patient verified! Click "Activate DIN & Check-In" to record attendance.');
  }

  // ============================================
  // ACTIVATE DIN (Create Attendance Record)
  // ============================================
  public function activateDin()
  {
    DB::beginTransaction();
    try {
      // Ensure patient is verified
      if (!$this->isPatientVerified || !$this->patient_id) {
        toastr()->error('Please verify patient first');
        DB::rollBack();
        return;
      }

      // Double-check not already activated today
      $existingActivation = DinActivation::where('patient_id', $this->patient_id)
        ->where('facility_id', $this->facility_id)
        ->whereDate('visit_date', today())
        ->exists();

      if ($existingActivation) {
        toastr()->warning('Patient has already been activated today');
        DB::rollBack();
        $this->isAlreadyActivatedToday = true;
        $this->isPatientVerified = false;
        return;
      }

      // Create the activation record
      $activation = DinActivation::create([
        'patient_id' => $this->patient_id,
        'facility_id' => $this->facility_id,
        'visit_date' => today(),
        'check_in_time' => now()->format('H:i:s'),

        // Copy patient data (snapshot at activation time)
        'patient_din' => $this->patient_din,
        'patient_first_name' => $this->first_name,
        'patient_middle_name' => $this->middle_name,
        'patient_last_name' => $this->last_name,
        'patient_phone' => $this->patient_phone,
        'patient_age' => $this->patient_age,
        'patient_gender' => $this->patient_gender,

        // Officer information
        'officer_name' => $this->officer_name,
        'officer_role' => $this->officer_role,
        'officer_designation' => $this->officer_designation,
      ]);

      $this->clearCaches();
      DB::commit();

      toastr()->info('DIN Activated! Patient checked in successfully at ' . now()->format('h:i A'));

      // Update state to show success
      $this->isPatientVerified = false;
      $this->isAlreadyActivatedToday = true;
      $this->existing_activation_time = now()->format('h:i A');
      $this->existing_activation_id = $activation->id;

      // Reload the activations list
      $this->loadActivations();
    } catch (ValidationException $e) {
      DB::rollBack();
      $errors = $e->validator->errors()->all();
      foreach ($errors as $error) {
        toastr()->error($error);
      }
      throw $e;
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while activating DIN.');
      throw $e;
    }
  }

  // ============================================
  // DELETE ACTIVATION
  // ============================================
  public function delete($id)
  {
    DB::beginTransaction();
    try {
      $activation = DinActivation::where('facility_id', $this->facility_id)
        ->findOrFail($id);

      $activation->delete();

      $this->clearCaches();
      DB::commit();

      toastr()->info('Activation record deleted successfully!');
      $this->loadActivations();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('Delete failed: ' . $e->getMessage());
    }
  }

  // ============================================
  // EXIT AND CLOSE MODAL
  // ============================================
  public function exit()
  {
    $this->resetForm();
    $this->closeModalAndRefresh();
  }

  // ============================================
  // CLOSE MODAL AND REFRESH
  // ============================================
  public function closeModalAndRefresh()
  {
    $this->dispatch('close-modals');
    $this->js('window.location.reload()');
  }

  // ============================================
  // RESET FOR NEXT PATIENT (Stay on modal)
  // ============================================
  public function resetForNextPatient()
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
      'isPatientVerified',
      'isPatientNotFound',
      'isAlreadyActivatedToday',
      'existing_activation_time',
      'existing_activation_id',
      'patient_registration_facility'
    ]);

    // Dispatch event to clear DIN input fields in JavaScript
    $this->dispatch('clear-din-inputs');
  }

  // ============================================
  // LOAD ACTIVATIONS (Today's activations for this facility)
  // ============================================
  private function loadActivations()
  {
    $this->activations = DinActivation::where('facility_id', $this->facility_id)
      ->whereDate('visit_date', today())
      ->orderBy('check_in_time', 'desc')
      ->get();
  }

  // ============================================
  // CLEAR CACHES
  // ============================================
  private function clearCaches()
  {
    Cache::forget("din_activations_{$this->facility_id}");
    Cache::forget("din_activations_today_{$this->facility_id}");
  }

  // ============================================
  // RESET FORM
  // ============================================
  private function resetForm()
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
      'isPatientVerified',
      'isPatientNotFound',
      'isAlreadyActivatedToday',
      'existing_activation_time',
      'existing_activation_id',
      'patient_registration_facility'
    ]);
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
    return view('livewire.avo.din-activations', [
      'activations' => $this->activations,
      'facility_name' => $this->facility_name,
      'facility_state' => $this->facility_state,
      'facility_lga' => $this->facility_lga,
      'facility_ward' => $this->facility_ward,
      'todayCount' => count($this->activations),
    ]);
  }
}
