<?php

namespace App\Livewire\Registers;

use Exception;
use App\Models\Lga;
use App\Models\Ward;
use App\Models\State;
use App\Models\Patient;
use App\Models\Facility;
use Livewire\Component;
use App\Models\Registrations\AntenatalRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class AntenatalRegister extends Component
{
  // DIN Verification properties
  public $din;
  public $isPatientVerified = false;
  public $isNewPatient = false;
  public $hasActiveAncRegistration = false;
  public $din_modal_flag = false;
  public $patient_registration_facility = '';

  // Patient Info (auto-populated from DIN verification OR new registration)
  public $patient_id;
  public $first_name;
  public $last_name;
  public $middle_name;
  public $patient_phone;
  public $patient_email;
  public $patient_dob;
  public $patient_age;
  public $patient_gender;
  public $marital_status;
  public $address;
  public $state_id;
  public $lga_id;
  public $ward_id;
  public $is_nhis_subscriber;
  public $nhis_number;

  // Multiple Pregnancy Tracking
  public $pregnancy_number = 1;
  public $previous_registration_id = null;
  public $suggested_gravida;
  public $suggested_parity;
  public $lastRegistration = null;

  // Registration Details
  public $registration_id;
  public $registration_date;
  public $date_of_booking;
  public $indication_for_booking;

  // Biographical Information
  public $xray_no;
  public $unit_no;
  public $ethnic_group;
  public $occupation;
  public $speaks_english = true;
  public $literate = true;
  public $special_points;
  public $consultant;

  // Husband/Partner Information
  public $husband_name;
  public $husband_occupation;
  public $husband_employer;

  // Current Pregnancy Details
  public $lmp;
  public $edd;
  public $gestational_age_weeks;
  public $gestational_age_days;
  public $gestational_age_display;
  public $booking_trimester;

  // Obstetric History
  public $gravida;
  public $parity;
  public $total_births;
  public $living_children;
  public $abortions;

  // Previous Pregnancy History (5 pregnancies)
  public $preg_0_dob, $preg_0_dur, $preg_0_outcome, $preg_0_weight, $preg_0_nndd;
  public $preg_1_dob, $preg_1_dur, $preg_1_outcome, $preg_1_weight, $preg_1_nndd;
  public $preg_2_dob, $preg_2_dur, $preg_2_outcome, $preg_2_weight, $preg_2_nndd;
  public $preg_3_dob, $preg_3_dur, $preg_3_outcome, $preg_3_weight, $preg_3_nndd;
  public $preg_4_dob, $preg_4_dur, $preg_4_outcome, $preg_4_weight, $preg_4_nndd;

  // Medical History
  public $heart_disease = false;
  public $chest_disease = false;
  public $kidney_disease = false;
  public $blood_transfusion = false;
  public $other_medical_history;

  // Family History
  public $family_multiple_pregnancy = false;
  public $family_tuberculosis = false;
  public $family_hypertension = false;
  public $family_heart_disease = false;
  public $other_family_history;

  // Blood Tests
  public $genotype;
  public $blood_group_rhesus;
  public $vdrl = false;
  public $hiv_test_done = false;
  public $hiv_result;
  public $hbsag = false;

  // Facility and Officer Info
  public $facility_id;
  public $facility_name;
  public $facility_state;
  public $facility_lga;
  public $facility_ward;
  public $officer_name;
  public $officer_role;
  public $officer_designation;

  public $antenatals = [];
  public $states = [];
  public $lgas = [];
  public $wards = [];

  protected function rules()
  {
    $rules = [
      'lmp' => 'required|date|before_or_equal:today',
      'edd' => 'nullable|date|after:lmp',
      'gravida' => 'required|integer|min:1',
      'parity' => 'required|integer|min:0',
      'date_of_booking' => 'required|date',
      'unit_no' => 'nullable|string|max:50',
      'occupation' => 'nullable|string|max:255',
      'ethnic_group' => 'nullable|string|max:255',
      'genotype' => 'nullable|in:AA,AS,SS,AC,SC,CC',
      'blood_group_rhesus' => 'nullable|string|max:10',
    ];

    // If creating new patient (no patient_id), add patient validation
    if (!$this->patient_id) {
      $rules['first_name'] = 'required|string|max:255';
      $rules['last_name'] = 'required|string|max:255';
      $rules['patient_gender'] = 'required|in:Male,Female';
      $rules['patient_dob'] = 'required|date|before:today';
      $rules['patient_phone'] = 'required|string|max:20';
      $rules['state_id'] = 'required|exists:states,id';
      $rules['lga_id'] = 'required|exists:lgas,id';
      $rules['address'] = 'required|string|max:500';
    } else {
      $rules['patient_id'] = 'required|exists:patients,id';
    }

    return $rules;
  }

  protected function messages()
  {
    return [
      // Patient fields
      'first_name.required' => 'First name is required',
      'last_name.required' => 'Last name is required',
      'patient_gender.required' => 'Gender is required',
      'patient_dob.required' => 'Date of birth is required',
      'patient_dob.before' => 'Date of birth must be in the past',
      'patient_phone.required' => 'Phone number is required',
      'state_id.required' => 'State is required',
      'lga_id.required' => 'LGA is required',
      'address.required' => 'Residential address is required',

      // ANC fields
      'lmp.required' => 'Last Menstrual Period (LMP) is required',
      'lmp.before_or_equal' => 'LMP cannot be a future date',
      'date_of_booking.required' => 'Booking date is required',
      'unit_no.required' => 'Unit number is required',
      'gravida.required' => 'Gravida is required',
      'gravida.min' => 'Gravida must be at least 1',
      'parity.required' => 'Parity is required',
      'parity.min' => 'Parity must be at least 0',
    ];
  }

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

    // Load states, lgas, wards for new patient registration
    $this->states = State::orderBy('name')->get();
    $this->lgas = [];
    $this->wards = [];

    $this->registration_date = now()->format('Y-m-d');
    $this->date_of_booking = now()->format('Y-m-d');

    $this->loadRegistrations();
  }

  /**
   * Open DIN Modal
   */
  public function openDinModal()
  {
    $this->reset([
      'din',
      'patient_id',
      'first_name',
      'last_name',
      'isPatientVerified',
      'isNewPatient',
      'hasActiveAncRegistration',
      'patient_registration_facility'
    ]);
    $this->din_modal_flag = true;
  }

  /**
   * Verify Patient by DIN (ANC-specific logic)
   */
  public function verifyPatient()
  {
    // Validate DIN
    if (strlen($this->din) !== 8 || !ctype_digit($this->din)) {
      toastr()->error('Please enter a valid 8-digit DIN');
      $this->isPatientVerified = false;
      $this->isNewPatient = false;
      $this->hasActiveAncRegistration = false;
      return;
    }

    // Search for patient
    $patient = Patient::where('din', $this->din)
      ->with([
        'antenatalRegistrations' => function ($query) {
          $query->orderBy('registration_date', 'desc');
        },
        'facility'
      ])
      ->first();

    // SCENARIO 1: Patient NOT found (NEW PATIENT - Can register in ANC as entry point)
    if (!$patient) {
      toastr()->warning('DIN not found. You can proceed to register a new patient in ANC.');
      $this->isPatientVerified = false;
      $this->isNewPatient = true;
      $this->hasActiveAncRegistration = false;
      $this->patient_id = null;
      $this->patient_registration_facility = 'New Patient';
      $this->din_modal_flag = true;
      return;
    }

    // SCENARIO 2: Patient found - check if has ACTIVE ANC registration
    $activeAncRegistration = $patient->antenatalRegistrations()->where('is_active', true)->first();

    if ($activeAncRegistration) {
      // Has ACTIVE ANC registration - show dashboard button
      toastr()->info('Patient Found & has an active ANC registration (Pregnancy in progress).');
      $this->isPatientVerified = false;
      $this->isNewPatient = false;
      $this->hasActiveAncRegistration = true;
      $this->patient_id = $patient->id;
      $this->first_name = $patient->first_name;
      $this->last_name = $patient->last_name;
      $this->patient_registration_facility = $patient->facility->name ?? 'N/A';
      $this->pregnancy_number = $patient->antenatalRegistrations()->count() + 1;
      $this->din_modal_flag = true;
      return;
    }

    // ========================================================================
    // PRE-FILL: Patient found and NO active ANC - can register new pregnancy
    // This is MODERATE pre-fill - includes patient info + stable pregnancy data
    // Pre-fills data that remains constant across pregnancies for efficiency
    // ========================================================================

    $this->patient_id = $patient->id;

    // PRE-FILL: Patient basic information
    $this->first_name = $patient->first_name;
    $this->last_name = $patient->last_name;
    $this->patient_gender = $patient->gender;
    $this->patient_dob = $patient->date_of_birth->format('Y-m-d');
    $this->patient_age = $patient->age;
    $this->patient_phone = $patient->phone;
    $this->patient_email = $patient->email;

    // Get previous ANC registrations (use local variable)
    $previousRegistrations = $patient->antenatalRegistrations;
    $this->lastRegistration = $previousRegistrations->first();

    // PRE-FILL: Calculate pregnancy number from history
    $this->pregnancy_number = $patient->antenatalRegistrations()->count() + 1;

    // PRE-FILL: Auto-populate stable fields from previous pregnancy
    // (occupation, ethnic group, husband info, genotype, blood group, etc.)
    $this->autoPopulateFields();

    // PRE-FILL: Suggest gravida/parity based on obstetric history
    $suggestions = AntenatalRegistration::suggestGravidaParity($patient->id);
    $this->suggested_gravida = $suggestions['gravida'];
    $this->suggested_parity = $suggestions['parity'];
    $this->gravida = $this->suggested_gravida;
    $this->parity = $this->suggested_parity;

    // ========================================================================
    // END PRE-FILL: User will fill current pregnancy-specific fields:
    // LMP, EDD, date_of_booking, indication, gestational age, medical history,
    // current obstetric findings, examination results, etc.
    // ========================================================================

    $this->patient_registration_facility = $patient->facility->name ?? 'N/A';
    $this->isPatientVerified = true;
    $this->isNewPatient = false;
    $this->hasActiveAncRegistration = false;
    $this->din_modal_flag = true;

    toastr()->info("Patient verified! This will be Pregnancy #{$this->pregnancy_number}.");
  }

  /**
   * Open Registration Modal
   */
  public function openRegistrationModal()
  {
    // Allow opening for verified patients OR direct new patient registration
    // Reset patient_id to null for direct new patient registration (no DIN)
    if (!$this->isPatientVerified) {
      $this->patient_id = null;
    }

    $this->din_modal_flag = false;
    $this->dispatch('open-main-modal');
  }

  /**
   * When state changes, load LGAs
   */
  public function updatedStateId($value)
  {
    $this->lgas = $value ? Lga::where('state_id', $value)->orderBy('name')->get() : [];
    $this->lga_id = null;
    $this->ward_id = null;
    $this->wards = [];
  }

  /**
   * When LGA changes, load wards
   */
  public function updatedLgaId($value)
  {
    $this->wards = $value ? Ward::where('lga_id', $value)->orderBy('name')->get() : [];
    $this->ward_id = null;
  }

  /**
   * Auto-populate fields from previous records
   */
  /**
   * AUTO-POPULATE FIELDS FROM PREVIOUS PREGNANCY
   *
   * This method pre-fills data that remains stable across pregnancies:
   * - Personal characteristics (occupation, ethnicity, literacy)
   * - Husband/partner information (unlikely to change)
   * - Biological constants (genotype, blood group/Rhesus factor)
   *
   * This saves data entry time and ensures consistency across pregnancy records
   */
  private function autoPopulateFields()
  {
    // Try to get data from last ANC registration first
    if ($this->lastRegistration) {
      // PRE-FILL: Personal characteristics
      $this->occupation = $this->lastRegistration->occupation;
      $this->ethnic_group = $this->lastRegistration->ethnic_group;
      $this->speaks_english = $this->lastRegistration->speaks_english;
      $this->literate = $this->lastRegistration->literate;

      // PRE-FILL: Husband/partner information
      $this->husband_name = $this->lastRegistration->husband_name;
      $this->husband_occupation = $this->lastRegistration->husband_occupation;
      $this->husband_employer = $this->lastRegistration->husband_employer;

      // PRE-FILL: Biological constants (never change)
      $this->genotype = $this->lastRegistration->genotype;
      $this->blood_group_rhesus = $this->lastRegistration->blood_group_rhesus;

      // Link to previous pregnancy for tracking
      $this->previous_registration_id = $this->lastRegistration->id;
    }
  }

  /**
   * Calculate EDD when LMP changes
   */
  public function updatedLmp()
  {
    if ($this->lmp) {
      $this->calculateEDD();
      $this->calculateGestationalAge();
    }
  }

  /**
   * Calculate Expected Delivery Date (Naegele's Rule: LMP + 280 days)
   */
  private function calculateEDD()
  {
    if ($this->lmp) {
      $lmpDate = Carbon::parse($this->lmp);
      $edd = $lmpDate->addDays(280);
      $this->edd = $edd->format('Y-m-d');
    }
  }

  /**
   * Calculate Gestational Age
   */
  private function calculateGestationalAge()
  {
    if ($this->lmp) {
      $lmpDate = Carbon::parse($this->lmp);
      $today = Carbon::now();
      $diffInDays = $lmpDate->diffInDays($today);

      $this->gestational_age_weeks = floor($diffInDays / 7);
      $this->gestational_age_days = $diffInDays % 7;
      $this->gestational_age_display = "{$this->gestational_age_weeks} weeks, {$this->gestational_age_days} days";

      // Determine booking trimester (must match ENUM: 'First', 'Second', 'Third')
      if ($this->gestational_age_weeks < 13) {
        $this->booking_trimester = 'First';
      } elseif ($this->gestational_age_weeks < 27) {
        $this->booking_trimester = 'Second';
      } else {
        $this->booking_trimester = 'Third';
      }
    }
  }

  /**
   * Store new ANC registration
   */
  public function store()
  {
    DB::beginTransaction();
    try {
      // Validate form first - errors will show inline
      $this->validate();

      // If no patient_id, create new patient first
      if (!$this->patient_id) {
        $patientData = [
          'din' => Patient::generateDIN(),
          'first_name' => $this->first_name,
          'last_name' => $this->last_name,
          'gender' => $this->patient_gender,
          'date_of_birth' => $this->patient_dob,
          'phone' => $this->patient_phone ?? '',
          'state_id' => $this->state_id,
          'lga_id' => $this->lga_id,
          'facility_id' => $this->facility_id,
          'is_nhis_subscriber' => $this->is_nhis_subscriber ?? false,
        ];

        // Optional fields - only add if present
        if ($this->middle_name) {
          $patientData['middle_name'] = $this->middle_name;
        }
        if ($this->patient_email) {
          $patientData['email'] = $this->patient_email;
        }
        if ($this->ward_id) {
          $patientData['ward_id'] = $this->ward_id;
        }
        if ($this->marital_status) {
          $patientData['marital_status'] = $this->marital_status;
        }
        if ($this->address) {
          $patientData['address'] = $this->address;
        }

        if ($this->is_nhis_subscriber) {
          if ($this->nhis_number) $patientData['nhis_number'] = $this->nhis_number;
        }

        $patient = Patient::create($patientData);
        $this->patient_id = $patient->id;
        $this->pregnancy_number = 1; // First pregnancy for new patient
      }

      $this->validate();

      $registrationData = [
        'patient_id' => $this->patient_id,
        'facility_id' => $this->facility_id,
        'pregnancy_number' => $this->pregnancy_number,
        'registration_date' => $this->registration_date,
        'date_of_booking' => $this->date_of_booking,
        'lmp' => $this->lmp,
        'edd' => $this->edd,
        'gravida' => $this->gravida,
        'parity' => $this->parity,
        'is_active' => true, // New pregnancy is active
        'officer_name' => $this->officer_name,
      ];

      // Add gestational age fields only if calculated
      if ($this->gestational_age_weeks !== null) {
        $registrationData['gestational_age_weeks'] = $this->gestational_age_weeks;
      }
      if ($this->gestational_age_days !== null) {
        $registrationData['gestational_age_days'] = $this->gestational_age_days;
      }
      if ($this->booking_trimester) {
        $registrationData['booking_trimester'] = $this->booking_trimester;
      }

      // Add optional fields
      if ($this->occupation) $registrationData['occupation'] = $this->occupation;
      if ($this->ethnic_group) $registrationData['ethnic_group'] = $this->ethnic_group;
      if ($this->unit_no) $registrationData['unit_no'] = $this->unit_no;
      if ($this->husband_name) $registrationData['husband_name'] = $this->husband_name;
      if ($this->husband_occupation) $registrationData['husband_occupation'] = $this->husband_occupation;
      if ($this->husband_employer) $registrationData['husband_employer'] = $this->husband_employer;
      if ($this->genotype) $registrationData['genotype'] = $this->genotype;
      if ($this->blood_group_rhesus) $registrationData['blood_group_rhesus'] = $this->blood_group_rhesus;
      if ($this->living_children !== null) $registrationData['living_children'] = $this->living_children;
      if ($this->abortions !== null) $registrationData['abortions'] = $this->abortions;

      $registrationData['speaks_english'] = $this->speaks_english;
      $registrationData['literate'] = $this->literate;

      AntenatalRegistration::create($registrationData);

      $this->clearCaches();
      DB::commit();

      toastr()->info("ANC Registration successful! Pregnancy #{$this->pregnancy_number}");
      $this->resetForm();
      $this->loadRegistrations();
    } catch (ValidationException $e) {
      DB::rollBack();
      // Show all validation errors in toastr
      $errors = $e->validator->errors()->all();
      foreach ($errors as $error) {
        toastr()->error($error);
      }
      // Let Livewire handle form validation errors automatically
      // They will show up inline in the form
      throw $e;
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while creating the ANC registration.');
      throw $e;
    }
  }

  /**
   * Edit existing registration
   */
  public function edit($id)
  {
    $registration = AntenatalRegistration::with('patient')->findOrFail($id);

    $this->registration_id = $id;
    $patient = $registration->patient;

    // Load patient data
    $this->patient_id = $patient->id;
    $this->first_name = $patient->first_name;
    $this->last_name = $patient->last_name;
    $this->patient_gender = $patient->gender;
    $this->patient_dob = $patient->date_of_birth->format('Y-m-d');
    $this->patient_phone = $patient->phone;
    $this->patient_email = $patient->email;

    // Load registration data
    $this->pregnancy_number = $registration->pregnancy_number;
    $this->date_of_booking = $registration->date_of_booking?->format('Y-m-d');
    $this->lmp = $registration->lmp?->format('Y-m-d');
    $this->edd = $registration->edd?->format('Y-m-d');
    $this->gestational_age_weeks = $registration->gestational_age_weeks;
    $this->gestational_age_days = $registration->gestational_age_days;
    $this->booking_trimester = $registration->booking_trimester;
    $this->gravida = $registration->gravida;
    $this->parity = $registration->parity;
    $this->occupation = $registration->occupation;
    $this->ethnic_group = $registration->ethnic_group;
    $this->unit_no = $registration->unit_no;
    $this->husband_name = $registration->husband_name;
    $this->husband_occupation = $registration->husband_occupation;
    $this->husband_employer = $registration->husband_employer;
    $this->genotype = $registration->genotype;
    $this->blood_group_rhesus = $registration->blood_group_rhesus;
    $this->living_children = $registration->living_children;
    $this->abortions = $registration->abortions;
    $this->speaks_english = $registration->speaks_english;
    $this->literate = $registration->literate;

    $this->din_modal_flag = false;
  }

  /**
   * Update existing registration
   */
  public function update()
  {
    DB::beginTransaction();
    try {
      $this->validate();

      $registration = AntenatalRegistration::findOrFail($this->registration_id);

      $registrationData = [
        'date_of_booking' => $this->date_of_booking,
        'lmp' => $this->lmp,
        'edd' => $this->edd,
        'gravida' => $this->gravida,
        'parity' => $this->parity,
        'occupation' => $this->occupation,
        'ethnic_group' => $this->ethnic_group,
        'unit_no' => $this->unit_no,
        'husband_name' => $this->husband_name,
        'husband_occupation' => $this->husband_occupation,
        'husband_employer' => $this->husband_employer,
        'genotype' => $this->genotype,
        'blood_group_rhesus' => $this->blood_group_rhesus,
        'living_children' => $this->living_children,
        'abortions' => $this->abortions,
        'speaks_english' => $this->speaks_english,
        'literate' => $this->literate,
      ];

      // Add gestational age fields only if calculated
      if ($this->gestational_age_weeks !== null) {
        $registrationData['gestational_age_weeks'] = $this->gestational_age_weeks;
      }
      if ($this->gestational_age_days !== null) {
        $registrationData['gestational_age_days'] = $this->gestational_age_days;
      }
      if ($this->booking_trimester) {
        $registrationData['booking_trimester'] = $this->booking_trimester;
      }

      $registration->update($registrationData);

      $this->clearCaches();
      DB::commit();

      toastr()->info('ANC Registration updated successfully!');
      $this->resetForm();
      $this->loadRegistrations();
    } catch (ValidationException $e) {
      DB::rollBack();
      // Show all validation errors in toastr
      $errors = $e->validator->errors()->all();
      foreach ($errors as $error) {
        toastr()->error($error);
      }
      // Let Livewire handle form validation errors automatically
      // They will show up inline in the form
      throw $e;
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the ANC registration.');
      throw $e;
    }
  }

  /**
   * Delete registration
   */
  public function delete($id)
  {
    DB::beginTransaction();
    try {
      $registration = AntenatalRegistration::findOrFail($id);
      $registration->delete();

      $this->clearCaches();
      DB::commit();

      toastr()->info('ANC Registration deleted successfully!');
      $this->loadRegistrations();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('Delete failed: ' . $e->getMessage());
    }
  }

  /**
   * Exit and close modals
   */
  public function exit()
  {
    $this->resetForm();
    $this->closeModalAndRefresh();
  }

  /**
   * Close modal and refresh page
   */
  public function closeModalAndRefresh()
  {
    $this->dispatch('close-modals');
    $this->js('window.location.reload()');
  }

  /**
   * Load registrations
   */
  private function loadRegistrations()
  {
    $this->antenatals = AntenatalRegistration::with('patient')
      ->where('facility_id', $this->facility_id)
      ->orderBy('created_at', 'desc')
      ->get();
  }

  /**
   * Clear caches
   */
  private function clearCaches()
  {
    Cache::forget("antenatal_registrations_{$this->facility_id}");
  }

  /**
   * Reset form
   */
  private function resetForm()
  {
    $this->reset([
      'registration_id',
      'patient_id',
      'din',
      'first_name',
      'last_name',
      'lmp',
      'edd',
      'gestational_age_weeks',
      'gestational_age_days',
      'booking_trimester',
      'gravida',
      'parity',
      'occupation',
      'ethnic_group',
      'unit_no',
      'husband_name',
      'genotype',
      'blood_group_rhesus',
      'isPatientVerified',
      'isNewPatient',
      'hasActiveAncRegistration'
    ]);
    $this->registration_date = now()->format('Y-m-d');
    $this->date_of_booking = now()->format('Y-m-d');
    $this->pregnancy_number = 1;
  }

  /**
   * Placeholder for lazy loading
   */
  public function placeholder()
  {
    return view('placeholder');
  }

  /**
   * Render component
   */
  public function render()
  {
    return view('livewire.registers.antenatal-register', [
      'antenatals' => $this->antenatals,
      'facility_name' => $this->facility_name,
      'facility_state' => $this->facility_state,
      'facility_lga' => $this->facility_lga,
      'facility_ward' => $this->facility_ward,
    ]);
  }
}
