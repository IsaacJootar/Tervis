<?php

namespace App\Livewire\Registers;

use Exception;
use App\Models\Lga;
use App\Models\Ward;
use App\Models\State;
use App\Models\Patient;
use Livewire\Component;
use App\Models\Facility;
use App\Models\Registrations\GeneralPatientsRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class GeneralPatientsRegister extends Component
{
  public $din;
  public $first_name;
  public $middle_name;
  public $last_name;
  public $gender;
  public $date_of_birth;
  public $age;
  public $calculated_age;
  public $phone;
  public $email;
  public $state_id;
  public $lga_id;
  public $ward_id;

  public $is_nhis_subscriber = false;
  public $nhis_number;
  public $nhis_provider;
  public $nhis_expiry_date;
  public $nhis_plan_type;
  public $nhis_principal_name;
  public $nhis_principal_number;

  public $marital_status;
  public $occupation;
  public $religion;
  public $place_of_origin;
  public $tribe;
  public $home_address;
  public $town;
  public $landmark;
  public $po_box_no;

  public $nok_name;
  public $nok_relationship;
  public $nok_phone;
  public $nok_address;

  public $xray_no;

  public $modal_flag = false;
  public $registration_id;

  public $states;
  public $lgas;
  public $wards;

  public $facility_name;
  public $facility_state;
  public $facility_lga;
  public $facility_ward;
  public $facility_id;
  public $officer_name;
  public $officer_role;
  public $officer_designation;

  // NEW: DIN Verification properties
  public $isPatientVerified = false;
  public $isNewPatient = false;
  public $hasOpdRegistration = false;
  public $din_modal_flag = false;
  public $patient_id = null;
  public $patient_registration_facility = '';

  protected function rules()
  {
    $rules = [
      'first_name' => 'required|string|max:100',
      'last_name' => 'required|string|max:100',
      'middle_name' => 'nullable|string|max:100',
      'gender' => 'required|in:Male,Female',
      'date_of_birth' => 'required|date|before:today',
      'phone' => 'required|string|max:20',
      'email' => 'nullable|email|max:150',
      'state_id' => 'required|exists:states,id',
      'lga_id' => 'required|exists:lgas,id',
      'ward_id' => 'nullable|exists:wards,id',
      'is_nhis_subscriber' => 'boolean',
      'marital_status' => 'nullable|in:Single,Married,Widowed,Divorced',
      'occupation' => 'nullable|string|max:255',
      'religion' => 'nullable|string|max:255',
      'place_of_origin' => 'nullable|string|max:255',
      'tribe' => 'nullable|string|max:255',
      'home_address' => 'nullable|string|max:500',
      'town' => 'nullable|string|max:255',
      'landmark' => 'nullable|string|max:255',
      'po_box_no' => 'nullable|string|max:50',
      'nok_name' => 'nullable|string|max:255',
      'nok_relationship' => 'nullable|string|max:255',
      'nok_phone' => 'nullable|string|max:20',
      'nok_address' => 'nullable|string|max:500',
      'xray_no' => 'nullable|string|max:50',
    ];

    if ($this->is_nhis_subscriber) {
      $rules['nhis_number'] = 'required|string|max:50';
      $rules['nhis_provider'] = 'required|string|max:255';
      $rules['nhis_expiry_date'] = 'required|date|after:today';
      $rules['nhis_plan_type'] = 'required|in:Individual,Family,Corporate';
    }
    return $rules;
  }

  public function mount()
  {
    $user = Auth::user();
    $this->facility_id = $user->facility_id;
    $this->officer_name = $user->full_name ?? ($user->first_name . ' ' . $user->last_name);
    $this->officer_role = $user->role ?? $user->designation ?? 'Staff';
    $this->officer_designation = $user->designation ?? 'N/A';

    if ($this->facility_id) {
      $facility = Facility::find($this->facility_id);
      if ($facility) {
        $this->facility_name = $facility->name;
        $this->facility_state = $facility->state ?? 'N/A';
        $this->facility_lga = $facility->lga ?? 'N/A';
        $this->facility_ward = $facility->ward ?? 'N/A';
      }
    }

    $this->states = State::orderBy('name')->get();
    $this->lgas = collect();
    $this->wards = collect();
  }

  public function openDinModal()
  {
    $this->reset(['din', 'patient_id', 'first_name', 'middle_name', 'last_name', 'isPatientVerified', 'isNewPatient', 'hasOpdRegistration', 'patient_registration_facility']);
    $this->din_modal_flag = true;
  }

  public function verifyPatient()
  {
    if (strlen($this->din) !== 8 || !ctype_digit($this->din)) {
      toastr()->error('Please enter a valid 8-digit DIN');
      $this->isPatientVerified = false;
      $this->isNewPatient = false;
      $this->hasOpdRegistration = false;
      return;
    }

    $patient = Patient::where('din', $this->din)
      ->with(['generalRegistration', 'facility'])
      ->first();

    if (!$patient) {
      toastr()->error('DIN not found. If this is new patient, proceed with new registration.');
      $this->isPatientVerified = false;
      $this->isNewPatient = true;
      $this->patient_id = null;
      $this->patient_registration_facility = 'New Patient';
      $this->din_modal_flag = true;
      return;
    }

    if ($patient->generalRegistration) {
      toastr()->info('Patient found & already has OPD registration, proceed to dashboard.');
      $this->isPatientVerified = false;
      $this->isNewPatient = false;
      $this->hasOpdRegistration = true;
      $this->patient_id = $patient->id;
      $this->first_name = $patient->first_name;
      $this->last_name = $patient->last_name;
      $this->patient_registration_facility = $patient->facility->name ?? 'N/A';
      $this->din_modal_flag = true;
      return;
    }

    // ========================================================================
    // PRE-FILL: Patient found with DIN but no General Registration yet
    // Pre-fill all patient demographics from existing patient record
    // This is the MOST EXTENSIVE pre-fill because General Registration is
    // the foundational demographic registration (one per patient)
    // ========================================================================

    $this->patient_id = $patient->id;

    // PRE-FILL: Basic patient information
    $this->first_name = $patient->first_name;
    $this->middle_name = $patient->middle_name;
    $this->last_name = $patient->last_name;
    $this->gender = $patient->gender;
    $this->date_of_birth = $patient->date_of_birth->format('Y-m-d');
    $this->phone = $patient->phone;
    $this->email = $patient->email;

    // PRE-FILL: Location data
    $this->state_id = $patient->state_id;
    $this->lga_id = $patient->lga_id;
    $this->ward_id = $patient->ward_id;

    // PRE-FILL: Populate dependent dropdowns based on location
    if ($this->state_id) {
      $this->lgas = Lga::where('state_id', $this->state_id)->orderBy('name')->get();
    }
    if ($this->lga_id) {
      $this->wards = Ward::where('lga_id', $this->lga_id)->orderBy('name')->get();
    }

    // PRE-FILL: NHIS information
    $this->is_nhis_subscriber = $patient->is_nhis_subscriber;
    $this->nhis_number = $patient->nhis_number;
    $this->nhis_provider = $patient->nhis_provider;
    $this->nhis_expiry_date = $patient->nhis_expiry_date?->format('Y-m-d');
    $this->nhis_plan_type = $patient->nhis_plan_type;
    $this->nhis_principal_name = $patient->nhis_principal_name;
    $this->nhis_principal_number = $patient->nhis_principal_number;

    // ========================================================================
    // END PRE-FILL: User will only need to fill OPD-specific fields:
    // marital_status, occupation, religion, place_of_origin, tribe,
    // home_address, town, landmark, po_box_no, NOK details, xray_no
    // ========================================================================

    $this->patient_registration_facility = $patient->facility->name ?? 'N/A';
    $this->isPatientVerified = true;
    $this->isNewPatient = false;
    $this->hasOpdRegistration = false;
    $this->din_modal_flag = true;

    toastr()->info('Patient verified! Proceed to OPD registration.');
  }

  // UPDATED: Allow direct opening for new patients without DIN
  public function openRegistrationModal()
  {
    // Reset patient_id to null for direct new patient registration (no DIN)
    if (!$this->isPatientVerified) {
      $this->patient_id = null;
    }

    $this->din_modal_flag = false;
    $this->modal_flag = false;
    $this->dispatch('open-main-modal');
  }

  public function updatedDateOfBirth()
  {
    if ($this->date_of_birth) {
      $dob = Carbon::parse($this->date_of_birth);
      $this->calculated_age = $dob->age;
    }
  }

  public function updatedStateId($value)
  {
    $this->lgas = Lga::where('state_id', $value)->orderBy('name')->get();
    $this->lga_id = null;
    $this->wards = collect();
  }

  public function updatedLgaId($value)
  {
    $this->wards = Ward::where('lga_id', $value)->orderBy('name')->get();
    $this->ward_id = null;
  }

  public function store()
  {
    DB::beginTransaction();
    try {
      $this->validate();

      if ($this->patient_id) {
        $patient = Patient::findOrFail($this->patient_id);
      } else {
        $patientData = [
          'din' => Patient::generateDIN(),
          'first_name' => $this->first_name,
          'middle_name' => $this->middle_name,
          'last_name' => $this->last_name,
          'gender' => $this->gender,
          'date_of_birth' => $this->date_of_birth,
          'phone' => $this->phone,
          'state_id' => $this->state_id,
          'lga_id' => $this->lga_id,
          'facility_id' => $this->facility_id,
          'is_nhis_subscriber' => $this->is_nhis_subscriber,
        ];

        if ($this->email) {
          $patientData['email'] = $this->email;
        }
        if ($this->ward_id) {
          $patientData['ward_id'] = $this->ward_id;
        }

        if ($this->is_nhis_subscriber) {
          if ($this->nhis_number) $patientData['nhis_number'] = $this->nhis_number;
          if ($this->nhis_provider) $patientData['nhis_provider'] = $this->nhis_provider;
          if ($this->nhis_expiry_date) $patientData['nhis_expiry_date'] = $this->nhis_expiry_date;
          if ($this->nhis_plan_type) $patientData['nhis_plan_type'] = $this->nhis_plan_type;
          if ($this->nhis_principal_name) $patientData['nhis_principal_name'] = $this->nhis_principal_name;
          if ($this->nhis_principal_number) $patientData['nhis_principal_number'] = $this->nhis_principal_number;
        }

        $patient = Patient::create($patientData);
      }

      $registrationData = [
        'patient_id' => $patient->id,
        'facility_id' => $this->facility_id,
        'registration_date' => now(),
        'officer_name' => $this->officer_name,
        'marital_status' => $this->marital_status,
        'occupation' => $this->occupation,
      ];

      if ($this->officer_role) $registrationData['officer_role'] = $this->officer_role;
      if ($this->officer_designation) $registrationData['officer_designation'] = $this->officer_designation;
      if ($this->religion) $registrationData['religion'] = $this->religion;
      if ($this->place_of_origin) $registrationData['place_of_origin'] = $this->place_of_origin;
      if ($this->tribe) $registrationData['tribe'] = $this->tribe;
      if ($this->home_address) $registrationData['home_address'] = $this->home_address;
      if ($this->town) $registrationData['town'] = $this->town;
      if ($this->landmark) $registrationData['landmark'] = $this->landmark;
      if ($this->po_box_no) $registrationData['po_box_no'] = $this->po_box_no;
      if ($this->nok_name) $registrationData['nok_name'] = $this->nok_name;
      if ($this->nok_relationship) $registrationData['nok_relationship'] = $this->nok_relationship;
      if ($this->nok_phone) $registrationData['nok_phone'] = $this->nok_phone;
      if ($this->nok_address) $registrationData['nok_address'] = $this->nok_address;
      if ($this->xray_no) $registrationData['xray_no'] = $this->xray_no;

      GeneralPatientsRegistration::create($registrationData);

      $this->clearCaches();
      DB::commit();

      toastr()->info("Registration successful! DIN: {$patient->din}");
      $this->resetForm();
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
      toastr()->error('An error occurred while creating the patient registration.');
      throw $e;
    }
  }

  public function edit($id)
  {
    $registration = GeneralPatientsRegistration::with('patient')->findOrFail($id);

    $this->registration_id = $id;
    $patient = $registration->patient;

    $this->din = $patient->din;
    $this->first_name = $patient->first_name;
    $this->middle_name = $patient->middle_name;
    $this->last_name = $patient->last_name;
    $this->gender = $patient->gender;
    $this->date_of_birth = $patient->date_of_birth?->format('Y-m-d');
    $this->phone = $patient->phone;
    $this->email = $patient->email;
    $this->state_id = $patient->state_id;
    $this->lga_id = $patient->lga_id;
    $this->ward_id = $patient->ward_id;

    if ($this->date_of_birth) {
      try {
        $dob = Carbon::parse($this->date_of_birth);
        $ageYears = $dob->age;
        $this->age = $ageYears . ' years';
        $this->calculated_age = $ageYears;
      } catch (Exception $e) {
        $this->age = '';
        $this->calculated_age = '';
      }
    }

    $this->is_nhis_subscriber = $patient->is_nhis_subscriber;
    $this->nhis_number = $patient->nhis_number;
    $this->nhis_provider = $patient->nhis_provider;
    $this->nhis_expiry_date = $patient->nhis_expiry_date?->format('Y-m-d');
    $this->nhis_plan_type = $patient->nhis_plan_type;
    $this->nhis_principal_name = $patient->nhis_principal_name;
    $this->nhis_principal_number = $patient->nhis_principal_number;

    $this->marital_status = $registration->marital_status;
    $this->occupation = $registration->occupation;
    $this->religion = $registration->religion;
    $this->place_of_origin = $registration->place_of_origin;
    $this->tribe = $registration->tribe;
    $this->home_address = $registration->home_address;
    $this->town = $registration->town;
    $this->landmark = $registration->landmark;
    $this->po_box_no = $registration->po_box_no;
    $this->nok_name = $registration->nok_name;
    $this->nok_relationship = $registration->nok_relationship;
    $this->nok_phone = $registration->nok_phone;
    $this->nok_address = $registration->nok_address;
    $this->xray_no = $registration->xray_no;

    $this->officer_name = $registration->officer_name ?? $this->officer_name;
    $this->officer_role = $registration->officer_role ?? $this->officer_role;
    $this->officer_designation = $registration->officer_designation ?? $this->officer_designation;

    if ($this->state_id) {
      $this->lgas = Lga::where('state_id', $this->state_id)->orderBy('name')->get();
    }

    if ($this->lga_id) {
      $this->wards = Ward::where('lga_id', $this->lga_id)->orderBy('name')->get();
    }

    $this->modal_flag = true;
  }

  public function update()
  {
    DB::beginTransaction();
    try {
      $this->validate();

      $registration = GeneralPatientsRegistration::findOrFail($this->registration_id);
      $patient = $registration->patient;

      $patientData = [
        'first_name' => $this->first_name,
        'middle_name' => $this->middle_name,
        'last_name' => $this->last_name,
        'gender' => $this->gender,
        'date_of_birth' => $this->date_of_birth,
        'phone' => $this->phone,
        'state_id' => $this->state_id,
        'lga_id' => $this->lga_id,
        'is_nhis_subscriber' => $this->is_nhis_subscriber,
      ];

      $patientData['email'] = $this->email;
      $patientData['ward_id'] = $this->ward_id;

      if ($this->is_nhis_subscriber) {
        $patientData['nhis_number'] = $this->nhis_number;
        $patientData['nhis_provider'] = $this->nhis_provider;
        $patientData['nhis_expiry_date'] = $this->nhis_expiry_date;
        $patientData['nhis_plan_type'] = $this->nhis_plan_type;
        $patientData['nhis_principal_name'] = $this->nhis_principal_name;
        $patientData['nhis_principal_number'] = $this->nhis_principal_number;
      } else {
        $patientData['nhis_number'] = null;
        $patientData['nhis_provider'] = null;
        $patientData['nhis_expiry_date'] = null;
        $patientData['nhis_plan_type'] = null;
        $patientData['nhis_principal_name'] = null;
        $patientData['nhis_principal_number'] = null;
      }

      $patient->update($patientData);

      $registrationData = [
        'marital_status' => $this->marital_status,
        'occupation' => $this->occupation,
        'religion' => $this->religion,
        'place_of_origin' => $this->place_of_origin,
        'tribe' => $this->tribe,
        'home_address' => $this->home_address,
        'town' => $this->town,
        'landmark' => $this->landmark,
        'po_box_no' => $this->po_box_no,
        'nok_name' => $this->nok_name,
        'nok_relationship' => $this->nok_relationship,
        'nok_phone' => $this->nok_phone,
        'nok_address' => $this->nok_address,
        'xray_no' => $this->xray_no,
        'officer_name' => $this->officer_name,
      ];

      if ($this->officer_role) $registrationData['officer_role'] = $this->officer_role;
      if ($this->officer_designation) $registrationData['officer_designation'] = $this->officer_designation;

      $registration->update($registrationData);

      $this->clearCaches();
      DB::commit();

      toastr()->info('Updated successfully!');
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
      toastr()->error('An error occurred while updating the patient registration.');
      throw $e;
    }
  }

  public function delete($id)
  {
    DB::beginTransaction();
    try {
      $registration = GeneralPatientsRegistration::findOrFail($id);
      $registration->delete();

      $this->clearCaches();
      DB::commit();

      toastr()->info('Registration deleted successfully!');
      $this->js('setTimeout(() => window.location.reload(), 1000)');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('Delete failed. Please try again.');
    }
  }

  public function exit()
  {
    $this->resetForm();
    $this->closeModalAndRefresh();
  }

  public function closeModalAndRefresh()
  {
    $this->dispatch('close-modals');
    $this->js('window.location.reload()');
  }

  public function resetForm()
  {
    $this->reset([
      'registration_id',
      'first_name',
      'middle_name',
      'last_name',
      'phone',
      'email',
      'calculated_age',
      'date_of_birth',
      'gender',
      'state_id',
      'lga_id',
      'ward_id',
      'is_nhis_subscriber',
      'nhis_number',
      'nhis_provider',
      'nhis_expiry_date',
      'nhis_plan_type',
      'nhis_principal_name',
      'nhis_principal_number',
      'marital_status',
      'occupation',
      'religion',
      'place_of_origin',
      'tribe',
      'home_address',
      'town',
      'landmark',
      'po_box_no',
      'nok_name',
      'nok_relationship',
      'nok_phone',
      'nok_address',
      'xray_no',
      'isPatientVerified',
      'patient_id',
      'patient_registration_facility'
    ]);
    $this->lgas = collect();
    $this->wards = collect();
    $this->modal_flag = false;
  }

  public function placeholder()
  {
    return view('placeholder');
  }

  private function clearCaches()
  {
    Cache::forget('general_patient_registrations_' . $this->facility_id);
  }

  public function render()
  {
    $registrations = GeneralPatientsRegistration::with(['patient.state'])
      ->where('facility_id', $this->facility_id)
      ->latest('registration_date')
      ->get();

    return view('livewire.registers.general-patients-register', [
      'registrations' => $registrations,
      'states' => $this->states,
      'lgas' => $this->lgas,
      'wards' => $this->wards,
      'facility_name' => $this->facility_name,
    ]);
  }
}
