<?php

namespace App\Livewire\Registers;

use Exception;
use App\Models\Patient;
use App\Models\Facility;
use App\Models\TetanusVaccination;
use App\Models\DinActivation;
use App\Models\Registrations\AntenatalRegistration;
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
class TetanusRegister extends Component
{
  // ============================================
  // PATIENT CONTEXT (from route parameter)
  // ============================================
  public $patientId;
  public $patient;
  public $antenatalRegistration;

  // ============================================
  // PATIENT INFO (loaded from patient record)
  // ============================================
  public $patient_din;
  public $first_name;
  public $last_name;
  public $middle_name;
  public $patient_phone;
  public $patient_dob;
  public $patient_age;
  public $patient_gender;

  // ============================================
  // PREGNANCY INFO
  // ============================================
  public $antenatal_registration_id;
  public $pregnancy_number;
  public $gestational_age;
  public $edd;
  public $lmp;

  // ============================================
  // VACCINATION HISTORY
  // ============================================
  public $vaccination_history = [];
  public $last_dose_info;
  public $next_dose_number;
  public $next_dose_label;
  public $has_completed_all_doses = false;

  // ============================================
  // FORM PROPERTIES - VACCINATION
  // ============================================
  public $vaccination_id;
  public $visit_date;
  public $current_tt_dose;
  public $dose_date;
  public $dose_number;
  public $protection_status;
  public $dose_interval;
  public $next_appointment_date;
  public $vaccination_site;
  public $batch_number;
  public $expiry_date;
  public $adverse_event = 'None';
  public $adverse_event_details;
  public $notes;

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
  // VACCINATIONS LIST
  // ============================================
  public $vaccinations = [];

  // ============================================
  // ACCESS CONTROL FLAGS
  // ============================================
  public $hasAccess = false;
  public $accessError = '';

  // ============================================
  // VALIDATION RULES
  // ============================================
  protected function rules()
  {
    return [
      'visit_date' => 'required|date|before_or_equal:today',
      'current_tt_dose' => 'required|in:TT1,TT2,TT3,TT4,TT5',
      'dose_date' => 'required|date|before_or_equal:today',
      'dose_number' => 'required|integer|min:1|max:5',
      'protection_status' => 'required|in:Not Protected,Partially Protected,Protected,Fully Protected',
      'vaccination_site' => 'nullable|in:Left Upper Arm,Right Upper Arm,Left Thigh,Right Thigh',
      'batch_number' => 'nullable|string|max:50',
      'expiry_date' => 'nullable|date|after:today',
      'adverse_event' => 'required|in:None,Mild Pain,Swelling,Fever,Other',
      'adverse_event_details' => 'nullable|string|max:500',
      'notes' => 'nullable|string|max:1000',
      'next_appointment_date' => 'nullable|date|after:dose_date',
    ];
  }

  protected function messages()
  {
    return [
      'visit_date.required' => 'Visit date is required',
      'visit_date.before_or_equal' => 'Visit date cannot be in the future',
      'current_tt_dose.required' => 'TT dose is required',
      'dose_date.required' => 'Dose date is required',
      'dose_date.before_or_equal' => 'Dose date cannot be in the future',
      'dose_number.required' => 'Dose number is required',
      'protection_status.required' => 'Protection status is required',
      'expiry_date.after' => 'Vaccine expiry date must be in the future',
    ];
  }

  // ============================================
  // MOUNT - Receive patientId from route
  // ============================================
  public function mount($patientId)
  {
    $this->patientId = $patientId;

    // Load officer and facility info
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

    // Set default dates
    $this->visit_date = now()->format('Y-m-d');
    $this->dose_date = now()->format('Y-m-d');

    // Validate patient access
    $this->validatePatientAccess();

    // Load patient data if access granted
    if ($this->hasAccess) {
      $this->loadPatientData();
      $this->loadVaccinationHistory();
      $this->prepareNextDose();
      $this->loadVaccinations();
    }
  }

  // ============================================
  // VALIDATE PATIENT ACCESS
  // ============================================
  private function validatePatientAccess()
  {
    // 1. Check if patient exists
    $patient = Patient::with(['facility', 'activeAntenatalRegistration'])->find($this->patientId);

    if (!$patient) {
      $this->hasAccess = false;
      $this->accessError = 'Patient not found in the system.';
      return;
    }

    // 2. Check if patient is activated for today at this facility
    $todaysActivation = DinActivation::where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereDate('visit_date', today())
      ->exists();

    if (!$todaysActivation) {
      $this->hasAccess = false;
      $this->accessError = 'Patient has not been checked in today. Please activate DIN first.';
      return;
    }

    // 3. Check if patient has active antenatal registration
    $activePregnancy = $patient->activeAntenatalRegistration;

    if (!$activePregnancy) {
      $this->hasAccess = false;
      $this->accessError = 'Patient has no active pregnancy. Please register for ANC first.';
      return;
    }

    // All checks passed
    $this->hasAccess = true;
    $this->patient = $patient;
    $this->antenatalRegistration = $activePregnancy;
  }

  // ============================================
  // LOAD PATIENT DATA
  // ============================================
  private function loadPatientData()
  {
    if (!$this->patient) return;

    // Patient info
    $this->patient_din = $this->patient->din;
    $this->first_name = $this->patient->first_name;
    $this->last_name = $this->patient->last_name;
    $this->middle_name = $this->patient->middle_name;
    $this->patient_phone = $this->patient->phone;
    $this->patient_dob = $this->patient->date_of_birth?->format('Y-m-d');
    $this->patient_gender = $this->patient->gender;
    $this->patient_age = $this->patient->date_of_birth ? $this->patient->date_of_birth->age : null;

    // Pregnancy info
    $this->antenatal_registration_id = $this->antenatalRegistration->id;
    $this->pregnancy_number = $this->antenatalRegistration->pregnancy_number;
    $this->edd = $this->antenatalRegistration->edd?->format('Y-m-d');
    $this->lmp = $this->antenatalRegistration->lmp?->format('Y-m-d');
    $this->gestational_age = $this->antenatalRegistration->current_gestational_age['display'] ?? 'N/A';
  }

  // ============================================
  // LOAD VACCINATION HISTORY FOR CURRENT PREGNANCY
  // ============================================
  private function loadVaccinationHistory()
  {
    if (!$this->antenatal_registration_id) return;

    $this->vaccination_history = TetanusVaccination::where('antenatal_registration_id', $this->antenatal_registration_id)
      ->orderBy('dose_number', 'asc')
      ->get();

    $this->last_dose_info = $this->vaccination_history->last();
  }

  // ============================================
  // PREPARE NEXT DOSE INFO
  // ============================================
  private function prepareNextDose()
  {
    if (!$this->antenatal_registration_id) return;

    // Determine next dose
    $this->next_dose_number = TetanusVaccination::getNextDoseNumber($this->antenatal_registration_id);
    $this->next_dose_label = TetanusVaccination::getNextDoseLabel($this->antenatal_registration_id);
    $this->has_completed_all_doses = TetanusVaccination::hasCompletedAllDoses($this->antenatal_registration_id);

    // Pre-fill form with next dose info
    if (!$this->has_completed_all_doses) {
      $this->current_tt_dose = $this->next_dose_label;
      $this->dose_number = $this->next_dose_number;
      $this->protection_status = TetanusVaccination::calculateProtectionStatus($this->next_dose_number);
      $this->calculateNextAppointment();
    }
  }

  // ============================================
  // CALCULATE NEXT APPOINTMENT DATE
  // ============================================
  public function calculateNextAppointment()
  {
    if ($this->dose_number && $this->dose_date) {
      $nextDate = TetanusVaccination::calculateNextAppointmentDate($this->dose_number, $this->dose_date);
      $this->next_appointment_date = $nextDate?->format('Y-m-d');
      $this->dose_interval = TetanusVaccination::getRecommendedInterval($this->dose_number);
    }
  }

  // ============================================
  // UPDATED HOOKS
  // ============================================
  public function updatedDoseDate()
  {
    $this->calculateNextAppointment();
  }

  public function updatedDoseNumber()
  {
    $this->current_tt_dose = "TT{$this->dose_number}";
    $this->protection_status = TetanusVaccination::calculateProtectionStatus($this->dose_number);
    $this->calculateNextAppointment();
  }

  // ============================================
  // STORE NEW VACCINATION
  // ============================================
  public function store()
  {
    if (!$this->hasAccess) {
      toastr()->error('Access denied. Patient not verified.');
      return;
    }

    if ($this->has_completed_all_doses) {
      toastr()->warning('Patient has already completed all 5 TT doses for this pregnancy.');
      return;
    }

    DB::beginTransaction();
    try {
      $this->validate();

      // Check if this dose already exists for this pregnancy
      $existingDose = TetanusVaccination::where('antenatal_registration_id', $this->antenatal_registration_id)
        ->where('current_tt_dose', $this->current_tt_dose)
        ->exists();

      if ($existingDose) {
        toastr()->error("{$this->current_tt_dose} has already been administered for this pregnancy.");
        DB::rollBack();
        return;
      }

      TetanusVaccination::create([
        // Relationships
        'patient_id' => $this->patientId,
        'antenatal_registration_id' => $this->antenatal_registration_id,
        'facility_id' => $this->facility_id,

        // Visit info
        'visit_date' => $this->visit_date,

        // Vaccination info
        'current_tt_dose' => $this->current_tt_dose,
        'dose_date' => $this->dose_date,
        'dose_number' => $this->dose_number,
        'protection_status' => $this->protection_status,
        'dose_interval' => $this->dose_interval,
        'next_appointment_date' => $this->next_appointment_date,

        // Vaccine details
        'vaccination_site' => $this->vaccination_site,
        'batch_number' => $this->batch_number,
        'expiry_date' => $this->expiry_date,

        // Safety
        'adverse_event' => $this->adverse_event,
        'adverse_event_details' => $this->adverse_event_details,
        'notes' => $this->notes,

        // Patient snapshot
        'patient_din' => $this->patient_din,
        'patient_first_name' => $this->first_name,
        'patient_middle_name' => $this->middle_name,
        'patient_last_name' => $this->last_name,
        'patient_phone' => $this->patient_phone,
        'patient_age' => $this->patient_age,
        'patient_gender' => $this->patient_gender,

        // Officer info
        'officer_name' => $this->officer_name,
        'officer_role' => $this->officer_role,
        'officer_designation' => $this->officer_designation,
      ]);

      $this->clearCaches();
      DB::commit();

      toastr()->success("{$this->current_tt_dose} vaccination recorded successfully!");

      // Refresh data
      $this->resetForm();
      $this->loadVaccinationHistory();
      $this->prepareNextDose();
      $this->loadVaccinations();

      $this->dispatch('close-modal');
    } catch (ValidationException $e) {
      DB::rollBack();
      $errors = $e->validator->errors()->all();
      foreach ($errors as $error) {
        toastr()->error($error);
      }
      throw $e;
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while recording the vaccination.');
      throw $e;
    }
  }

  // ============================================
  // EDIT VACCINATION
  // ============================================
  public function edit($id)
  {
    $vaccination = TetanusVaccination::where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->findOrFail($id);

    $this->vaccination_id = $id;

    $this->visit_date = $vaccination->visit_date?->format('Y-m-d');
    $this->current_tt_dose = $vaccination->current_tt_dose;
    $this->dose_date = $vaccination->dose_date?->format('Y-m-d');
    $this->dose_number = $vaccination->dose_number;
    $this->protection_status = $vaccination->protection_status;
    $this->dose_interval = $vaccination->dose_interval;
    $this->next_appointment_date = $vaccination->next_appointment_date?->format('Y-m-d');
    $this->vaccination_site = $vaccination->vaccination_site;
    $this->batch_number = $vaccination->batch_number;
    $this->expiry_date = $vaccination->expiry_date?->format('Y-m-d');
    $this->adverse_event = $vaccination->adverse_event;
    $this->adverse_event_details = $vaccination->adverse_event_details;
    $this->notes = $vaccination->notes;

    $this->dispatch('open-modal');
  }

  // ============================================
  // UPDATE VACCINATION
  // ============================================
  public function update()
  {
    if (!$this->hasAccess) {
      toastr()->error('Access denied. Patient not verified.');
      return;
    }

    DB::beginTransaction();
    try {
      $this->validate();

      $vaccination = TetanusVaccination::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($this->vaccination_id);

      $vaccination->update([
        'visit_date' => $this->visit_date,
        'dose_date' => $this->dose_date,
        'protection_status' => $this->protection_status,
        'dose_interval' => $this->dose_interval,
        'next_appointment_date' => $this->next_appointment_date,
        'vaccination_site' => $this->vaccination_site,
        'batch_number' => $this->batch_number,
        'expiry_date' => $this->expiry_date,
        'adverse_event' => $this->adverse_event,
        'adverse_event_details' => $this->adverse_event_details,
        'notes' => $this->notes,
      ]);

      $this->clearCaches();
      DB::commit();

      toastr()->success('Vaccination record updated successfully!');
      $this->resetForm();
      $this->loadVaccinationHistory();
      $this->prepareNextDose();
      $this->loadVaccinations();

      $this->dispatch('close-modal');
    } catch (ValidationException $e) {
      DB::rollBack();
      $errors = $e->validator->errors()->all();
      foreach ($errors as $error) {
        toastr()->error($error);
      }
      throw $e;
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the vaccination.');
      throw $e;
    }
  }

  // ============================================
  // DELETE VACCINATION
  // ============================================
  public function delete($id)
  {
    if (!$this->hasAccess) {
      toastr()->error('Access denied. Patient not verified.');
      return;
    }

    DB::beginTransaction();
    try {
      $vaccination = TetanusVaccination::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($id);

      $vaccination->delete();

      $this->clearCaches();
      DB::commit();

      toastr()->success('Vaccination record deleted successfully!');
      $this->loadVaccinationHistory();
      $this->prepareNextDose();
      $this->loadVaccinations();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('Delete failed: ' . $e->getMessage());
    }
  }

  // ============================================
  // LOAD VACCINATIONS (This patient's vaccinations for this pregnancy)
  // ============================================
  private function loadVaccinations()
  {
    if (!$this->patientId || !$this->antenatal_registration_id) return;

    $this->vaccinations = TetanusVaccination::where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->where('antenatal_registration_id', $this->antenatal_registration_id)
      ->orderBy('dose_number', 'asc')
      ->get();
  }

  // ============================================
  // CLEAR CACHES
  // ============================================
  private function clearCaches()
  {
    Cache::forget("tetanus_vaccinations_{$this->facility_id}_{$this->patientId}");
  }

  // ============================================
  // RESET FORM
  // ============================================
  private function resetForm()
  {
    $this->reset([
      'vaccination_id',
      'vaccination_site',
      'batch_number',
      'expiry_date',
      'adverse_event_details',
      'notes',
    ]);

    $this->visit_date = now()->format('Y-m-d');
    $this->dose_date = now()->format('Y-m-d');
    $this->adverse_event = 'None';
  }

  // ============================================
  // BACK TO PATIENT DASHBOARD
  // ============================================
  public function backToDashboard()
  {
    return redirect()->route('patient.dashboard', ['patientId' => $this->patientId]);
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
    return view('livewire.registers.tetanus-register', [
      'vaccinations' => $this->vaccinations,
      'facility_name' => $this->facility_name,
      'facility_state' => $this->facility_state,
      'facility_lga' => $this->facility_lga,
      'facility_ward' => $this->facility_ward,
      'totalVaccinations' => count($this->vaccinations),
      'hasAccess' => $this->hasAccess,
      'accessError' => $this->accessError,
    ]);
  }
}
