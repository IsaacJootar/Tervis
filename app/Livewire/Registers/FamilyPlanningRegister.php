<?php

namespace App\Livewire\Registers;

use Exception;
use App\Models\Lga;
use App\Models\Ward;
use App\Models\State;
use App\Models\Patient;
use App\Models\Facility;
use Livewire\Component;
use App\Models\Registrations\FamilyPlanningRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class FamilyPlanningRegister extends Component
{
  // DIN Verification properties
  public $din;
  public $isPatientVerified = false;
  public $isNewPatient = false;

  // Patient fields (for NEW patient only)
  public $patient_id;
  public $first_name, $last_name, $middle_name;
  public $patient_gender, $patient_dob, $patient_age;
  public $patient_phone, $patient_email;
  public $address, $marital_status;
  public $education, $religion;

  // FP Registration fields
  public $registration_id;
  public $registration_date, $client_reg_number, $referral_source;

  // Obstetric History
  public $children_born_alive, $children_still_living;
  public $miscarriages_stillbirths_abortions;
  public $last_pregnancy_ended, $last_pregnancy_result;
  public $breastfeeding, $want_more_children;

  // Menstrual History
  public $last_menstrual_period, $menstrual_cycle, $cycle_duration;

  // Medical History
  public $medical_conditions = [];
  public $other_illness_specify, $smoke;
  public $last_pregnancy_complication, $complication_specify;

  // Contraceptive History
  public $prior_contraceptive, $prior_method;

  // Contraceptive Method Selected
  public $contraceptive_selected, $brand_size_model;
  public $source, $quality;

  // Physical Examination
  public $weight, $blood_pressure;
  public $breasts, $uterus_position, $uterus_size;
  public $cervix_tears, $cervix_erosion;
  public $vaginal_discharge, $discharge_colour, $discharge_odor;
  public $cervix_discharge, $liver_enlarged;
  public $laboratory_results, $other_observations;

  // Follow-up
  public $next_appointment;

  // Pregnancy Tracking (after initial visit)
  public $pregnancy1_date_ended, $pregnancy1_outcome, $pregnancy1_complication;
  public $pregnancy2_date_ended, $pregnancy2_outcome, $pregnancy2_complication;

  // Location
  public $facility_id, $facility_name, $facility_state, $facility_lga, $facility_ward;
  public $state_id, $lga_id, $ward_id;
  public $states, $lgas, $wards;

  // Officer Information
  public $officer_name, $officer_role, $officer_designation;

  // Modal flags
  public $modal_flag = false;
  public $patient_registration_facility = '';

  protected function rules()
  {
    $rules = [
      'registration_date' => 'required|date',
      'last_menstrual_period' => 'required|date|before_or_equal:today',
      'contraceptive_selected' => 'required|string',
      'weight' => 'required|numeric|min:30|max:200',
      'blood_pressure' => 'required|string|max:20',
    ];

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
      'first_name.required' => 'First name is required',
      'last_name.required' => 'Last name is required',
      'patient_gender.required' => 'Gender is required',
      'patient_dob.required' => 'Date of birth is required',
      'patient_dob.before' => 'Date of birth must be in the past',
      'patient_phone.required' => 'Phone number is required',
      'state_id.required' => 'State is required',
      'lga_id.required' => 'LGA is required',
      'address.required' => 'Residential address is required',
      'registration_date.required' => 'Registration date is required',
      'last_menstrual_period.required' => 'Last Menstrual Period (LMP) is required',
      'last_menstrual_period.before_or_equal' => 'LMP cannot be a future date',
      'contraceptive_selected.required' => 'Please select a contraceptive method',
      'weight.required' => 'Weight is required',
      'weight.min' => 'Weight must be at least 30 kg',
      'blood_pressure.required' => 'Blood pressure is required',
    ];
  }

  public function mount()
  {
    $user = Auth::user();
    if (!$user || $user->role !== 'Data Officer') {
      abort(403, 'Unauthorized: Only Data Officers can access this page.');
    }

    $facility = Facility::find($user->facility_id);
    if (!$facility) {
      abort(403, 'Invalid facility assignment.');
    }

    $this->facility_id = $facility->id;
    $this->facility_name = $facility->name;
    $this->facility_state = $facility->state;
    $this->facility_lga = $facility->lga;
    $this->facility_ward = $facility->ward;

    $this->state_id = State::where('name', $facility->state)->first()->id ?? null;
    $this->lga_id = Lga::where('name', $facility->lga)->where('state_id', $this->state_id)->first()->id ?? null;
    $this->ward_id = Ward::where('name', $facility->ward)->where('lga_id', $this->lga_id)->first()->id ?? null;

    $this->registration_date = Carbon::now()->format('Y-m-d');
    $this->officer_name = $user->first_name . ' ' . $user->last_name;
    $this->officer_role = $user->role;
    $this->officer_designation = $user->designation;

    try {
      $this->states = Cache::remember('states', 60 * 60 * 24, function () {
        return State::orderBy('name')->get();
      });
      $this->lgas = Cache::remember('lgas_state_' . $this->state_id, 60 * 60 * 24, function () {
        return Lga::where('state_id', $this->state_id)->orderBy('name')->get();
      });
      $this->wards = Cache::remember('wards_lga_' . $this->lga_id, 60 * 60 * 24, function () {
        return Ward::where('lga_id', $this->lga_id)->orderBy('name')->get();
      });
    } catch (Exception $e) {
      $this->states = collect();
      $this->lgas = collect();
      $this->wards = collect();
    }
  }

  public function updatedStateId($value)
  {
    $this->lgas = Cache::remember('lgas_state_' . $value, 60 * 60 * 24, function () use ($value) {
      return Lga::where('state_id', $value)->orderBy('name')->get();
    });
    $this->lga_id = null;
    $this->ward_id = null;
    $this->wards = collect();
  }

  public function updatedLgaId($value)
  {
    $this->wards = Cache::remember('wards_lga_' . $value, 60 * 60 * 24, function () use ($value) {
      return Ward::where('lga_id', $value)->orderBy('name')->get();
    });
    $this->ward_id = null;
  }

  public function openDinModal()
  {
    $this->reset(['din', 'patient_id', 'first_name', 'last_name', 'isPatientVerified', 'isNewPatient', 'registration_id', 'modal_flag', 'patient_registration_facility']);
  }

  public function verifyPatient()
  {
    if (strlen($this->din) !== 8 || !ctype_digit($this->din)) {
      toastr()->error('Please enter a valid 8-digit DIN');
      $this->isPatientVerified = false;
      $this->isNewPatient = false;
      return;
    }

    $patient = Patient::where('din', $this->din)
      ->with(['generalRegistration', 'facility'])
      ->first();

    // SCENARIO 1: Patient NOT found
    if (!$patient) {
      toastr()->info('DIN not found. You can proceed to register a new patient in FP.');
      $this->isPatientVerified = false;
      $this->isNewPatient = true;
      $this->patient_id = null;
      $this->patient_registration_facility = 'New Patient';
      return;
    }

    // SCENARIO 2: Already registered TODAY
    $registeredToday = FamilyPlanningRegistration::where('patient_id', $patient->id)
      ->whereDate('registration_date', Carbon::today())
      ->exists();

    if ($registeredToday) {
      toastr()->warning('Patient already has an FP registration today.');
      $this->isPatientVerified = false;
      $this->isNewPatient = false;
      return;
    }

    // ========================================================================
    // PRE-FILL: Patient verified - pre-fill patient demographic data
    // Similar to Antenatal workflow - pre-fill patient info for efficiency
    // ========================================================================

    toastr()->info('Patient verified successfully!');
    $this->patient_id = $patient->id;

    // PRE-FILL: Patient basic information
    $this->first_name = $patient->first_name;
    $this->middle_name = $patient->middle_name;
    $this->last_name = $patient->last_name;
    $this->patient_gender = $patient->gender;
    $this->patient_dob = $patient->date_of_birth->format('Y-m-d');
    $this->patient_age = $patient->age;
    $this->patient_phone = $patient->phone;
    $this->patient_email = $patient->email;

    // PRE-FILL: Additional demographic data from General Registration if available
    if ($patient->generalRegistration) {
      $this->marital_status = $patient->generalRegistration->marital_status;
      $this->religion = $patient->generalRegistration->religion;
      $this->address = $patient->generalRegistration->home_address;
    }

    // ========================================================================
    // END PRE-FILL: User will fill FP-specific clinical data:
    // obstetric history, menstrual history, medical conditions, contraceptive
    // details, physical examination findings, etc.
    // ========================================================================

    $this->patient_registration_facility = $patient->facility->name ?? 'N/A';
    $this->isPatientVerified = true;
    $this->isNewPatient = false;
  }

  public function openFPModal()
  {
    if ($this->isPatientVerified || $this->isNewPatient) {
      $this->modal_flag = false;
      $this->dispatch('open-main-modal');
    }
  }

  public function store()
  {
    DB::beginTransaction();
    try {
      $this->validate();

      if (!$this->patient_id) {
        $patientData = [
          'din' => Patient::generateDIN(),
          'first_name' => $this->first_name,
          'last_name' => $this->last_name,
          'gender' => $this->patient_gender,
          'date_of_birth' => $this->patient_dob,
          'state_id' => $this->state_id,
          'lga_id' => $this->lga_id,
          'facility_id' => $this->facility_id,
        ];

        if ($this->middle_name) $patientData['middle_name'] = $this->middle_name;
        if ($this->patient_phone) $patientData['phone'] = $this->patient_phone;
        if ($this->patient_email) $patientData['email'] = $this->patient_email;
        if ($this->ward_id) $patientData['ward_id'] = $this->ward_id;
        if ($this->marital_status) $patientData['marital_status'] = $this->marital_status;
        if ($this->address) $patientData['address'] = $this->address;
        if ($this->education) $patientData['education'] = $this->education;
        if ($this->religion) $patientData['religion'] = $this->religion;

        $patient = Patient::create($patientData);
        $this->patient_id = $patient->id;
      }

      $registrationData = [
        'patient_id' => $this->patient_id,
        'facility_id' => $this->facility_id,
        'registration_date' => $this->registration_date,
        'last_menstrual_period' => $this->last_menstrual_period,
        'contraceptive_selected' => $this->contraceptive_selected,
        'weight' => $this->weight,
        'blood_pressure' => $this->blood_pressure,
        'officer_name' => $this->officer_name,
      ];

      // Add optional fields
      if ($this->client_reg_number) $registrationData['client_reg_number'] = $this->client_reg_number;
      if ($this->referral_source) $registrationData['referral_source'] = $this->referral_source;
      if ($this->children_born_alive !== null) $registrationData['children_born_alive'] = $this->children_born_alive;
      if ($this->children_still_living !== null) $registrationData['children_still_living'] = $this->children_still_living;
      if ($this->miscarriages_stillbirths_abortions !== null) $registrationData['miscarriages_stillbirths_abortions'] = $this->miscarriages_stillbirths_abortions;
      if ($this->last_pregnancy_ended) $registrationData['last_pregnancy_ended'] = $this->last_pregnancy_ended;
      if ($this->last_pregnancy_result) $registrationData['last_pregnancy_result'] = $this->last_pregnancy_result;
      if ($this->breastfeeding !== null) $registrationData['breastfeeding'] = $this->breastfeeding;
      if ($this->want_more_children) $registrationData['want_more_children'] = $this->want_more_children;
      if ($this->menstrual_cycle) $registrationData['menstrual_cycle'] = $this->menstrual_cycle;
      if ($this->cycle_duration) $registrationData['cycle_duration'] = $this->cycle_duration;
      if (!empty($this->medical_conditions)) $registrationData['medical_conditions'] = $this->medical_conditions;
      if ($this->other_illness_specify) $registrationData['other_illness_specify'] = $this->other_illness_specify;
      if ($this->smoke !== null) $registrationData['smoke'] = $this->smoke;
      if ($this->last_pregnancy_complication) $registrationData['last_pregnancy_complication'] = $this->last_pregnancy_complication;
      if ($this->complication_specify) $registrationData['complication_specify'] = $this->complication_specify;
      if ($this->prior_contraceptive !== null) $registrationData['prior_contraceptive'] = $this->prior_contraceptive;
      if ($this->prior_method) $registrationData['prior_method'] = $this->prior_method;
      if ($this->brand_size_model) $registrationData['brand_size_model'] = $this->brand_size_model;
      if ($this->source) $registrationData['source'] = $this->source;
      if ($this->quality) $registrationData['quality'] = $this->quality;
      if ($this->breasts) $registrationData['breasts'] = $this->breasts;
      if ($this->uterus_position) $registrationData['uterus_position'] = $this->uterus_position;
      if ($this->uterus_size) $registrationData['uterus_size'] = $this->uterus_size;
      if ($this->cervix_tears !== null) $registrationData['cervix_tears'] = $this->cervix_tears;
      if ($this->cervix_erosion !== null) $registrationData['cervix_erosion'] = $this->cervix_erosion;
      if ($this->vaginal_discharge !== null) $registrationData['vaginal_discharge'] = $this->vaginal_discharge;
      if ($this->discharge_colour) $registrationData['discharge_colour'] = $this->discharge_colour;
      if ($this->discharge_odor) $registrationData['discharge_odor'] = $this->discharge_odor;
      if ($this->cervix_discharge !== null) $registrationData['cervix_discharge'] = $this->cervix_discharge;
      if ($this->liver_enlarged !== null) $registrationData['liver_enlarged'] = $this->liver_enlarged;
      if ($this->laboratory_results) $registrationData['laboratory_results'] = $this->laboratory_results;
      if ($this->other_observations) $registrationData['other_observations'] = $this->other_observations;
      if ($this->next_appointment) $registrationData['next_appointment'] = $this->next_appointment;
      if ($this->pregnancy1_date_ended) $registrationData['pregnancy1_date_ended'] = $this->pregnancy1_date_ended;
      if ($this->pregnancy1_outcome) $registrationData['pregnancy1_outcome'] = $this->pregnancy1_outcome;
      if ($this->pregnancy1_complication) $registrationData['pregnancy1_complication'] = $this->pregnancy1_complication;
      if ($this->pregnancy2_date_ended) $registrationData['pregnancy2_date_ended'] = $this->pregnancy2_date_ended;
      if ($this->pregnancy2_outcome) $registrationData['pregnancy2_outcome'] = $this->pregnancy2_outcome;
      if ($this->pregnancy2_complication) $registrationData['pregnancy2_complication'] = $this->pregnancy2_complication;
      if ($this->officer_role) $registrationData['officer_role'] = $this->officer_role;
      if ($this->officer_designation) $registrationData['officer_designation'] = $this->officer_designation;

      FamilyPlanningRegistration::create($registrationData);

      $this->clearCaches();
      DB::commit();

      toastr()->info('Family Planning Registration successful!');
      $this->resetForm();
      $this->loadRegistrations();
    } catch (ValidationException $e) {
      DB::rollBack();
      $errors = $e->validator->errors()->all();
      foreach ($errors as $error) {
        toastr()->error($error);
      }
      throw $e;
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while creating the FP registration.');
      throw $e;
    }
  }

  public function edit($id)
  {
    $registration = FamilyPlanningRegistration::with('patient')->findOrFail($id);

    $this->registration_id = $id;
    $patient = $registration->patient;

    // Load patient demographic data
    $this->patient_id = $patient->id;
    $this->first_name = $patient->first_name;
    $this->middle_name = $patient->middle_name;
    $this->last_name = $patient->last_name;
    $this->patient_gender = $patient->gender;
    $this->patient_dob = $patient->date_of_birth?->format('Y-m-d');
    $this->patient_age = $patient->age;
    $this->patient_phone = $patient->phone;
    $this->patient_email = $patient->email;
    $this->patient_registration_facility = $patient->facility->name ?? 'N/A';

    // Load FP registration data
    $this->registration_date = $registration->registration_date?->format('Y-m-d');
    $this->client_reg_number = $registration->client_reg_number;
    $this->referral_source = $registration->referral_source;
    $this->children_born_alive = $registration->children_born_alive;
    $this->children_still_living = $registration->children_still_living;
    $this->miscarriages_stillbirths_abortions = $registration->miscarriages_stillbirths_abortions;
    $this->last_pregnancy_ended = $registration->last_pregnancy_ended?->format('Y-m-d');
    $this->last_pregnancy_result = $registration->last_pregnancy_result;
    $this->breastfeeding = $registration->breastfeeding;
    $this->want_more_children = $registration->want_more_children;
    $this->last_menstrual_period = $registration->last_menstrual_period?->format('Y-m-d');
    $this->menstrual_cycle = $registration->menstrual_cycle;
    $this->cycle_duration = $registration->cycle_duration;
    $this->medical_conditions = $registration->medical_conditions ?? [];
    $this->other_illness_specify = $registration->other_illness_specify;
    $this->smoke = $registration->smoke;
    $this->last_pregnancy_complication = $registration->last_pregnancy_complication;
    $this->complication_specify = $registration->complication_specify;
    $this->prior_contraceptive = $registration->prior_contraceptive;
    $this->prior_method = $registration->prior_method;
    $this->contraceptive_selected = $registration->contraceptive_selected;
    $this->brand_size_model = $registration->brand_size_model;
    $this->source = $registration->source;
    $this->quality = $registration->quality;
    $this->weight = $registration->weight;
    $this->blood_pressure = $registration->blood_pressure;
    $this->breasts = $registration->breasts;
    $this->uterus_position = $registration->uterus_position;
    $this->uterus_size = $registration->uterus_size;
    $this->cervix_tears = $registration->cervix_tears;
    $this->cervix_erosion = $registration->cervix_erosion;
    $this->vaginal_discharge = $registration->vaginal_discharge;
    $this->discharge_colour = $registration->discharge_colour;
    $this->discharge_odor = $registration->discharge_odor;
    $this->cervix_discharge = $registration->cervix_discharge;
    $this->liver_enlarged = $registration->liver_enlarged;
    $this->laboratory_results = $registration->laboratory_results;
    $this->other_observations = $registration->other_observations;
    $this->next_appointment = $registration->next_appointment?->format('Y-m-d');
    $this->pregnancy1_date_ended = $registration->pregnancy1_date_ended?->format('Y-m-d');
    $this->pregnancy1_outcome = $registration->pregnancy1_outcome;
    $this->pregnancy1_complication = $registration->pregnancy1_complication;
    $this->pregnancy2_date_ended = $registration->pregnancy2_date_ended?->format('Y-m-d');
    $this->pregnancy2_outcome = $registration->pregnancy2_outcome;
    $this->pregnancy2_complication = $registration->pregnancy2_complication;
    $this->officer_name = $registration->officer_name;
    $this->officer_role = $registration->officer_role;
    $this->officer_designation = $registration->officer_designation;

    $this->modal_flag = true;
    $this->dispatch('open-main-modal');
  }

  public function update()
  {
    DB::beginTransaction();
    try {
      $rules = array_diff_key($this->rules(), [
        'first_name' => '',
        'last_name' => '',
        'patient_gender' => '',
        'patient_dob' => '',
        'patient_phone' => '',
        'state_id' => '',
        'lga_id' => '',
        'address' => '',
      ]);
      $this->validate($rules);

      $registration = FamilyPlanningRegistration::findOrFail($this->registration_id);

      $registrationData = [
        'registration_date' => $this->registration_date,
        'last_menstrual_period' => $this->last_menstrual_period,
        'contraceptive_selected' => $this->contraceptive_selected,
        'weight' => $this->weight,
        'blood_pressure' => $this->blood_pressure,
        'officer_name' => $this->officer_name,
      ];

      // Same optional fields as store
      if ($this->client_reg_number) $registrationData['client_reg_number'] = $this->client_reg_number;
      if ($this->referral_source) $registrationData['referral_source'] = $this->referral_source;
      if ($this->children_born_alive !== null) $registrationData['children_born_alive'] = $this->children_born_alive;
      if ($this->children_still_living !== null) $registrationData['children_still_living'] = $this->children_still_living;
      if ($this->miscarriages_stillbirths_abortions !== null) $registrationData['miscarriages_stillbirths_abortions'] = $this->miscarriages_stillbirths_abortions;
      if ($this->last_pregnancy_ended) $registrationData['last_pregnancy_ended'] = $this->last_pregnancy_ended;
      if ($this->last_pregnancy_result) $registrationData['last_pregnancy_result'] = $this->last_pregnancy_result;
      if ($this->breastfeeding !== null) $registrationData['breastfeeding'] = $this->breastfeeding;
      if ($this->want_more_children) $registrationData['want_more_children'] = $this->want_more_children;
      if ($this->menstrual_cycle) $registrationData['menstrual_cycle'] = $this->menstrual_cycle;
      if ($this->cycle_duration) $registrationData['cycle_duration'] = $this->cycle_duration;
      if (!empty($this->medical_conditions)) $registrationData['medical_conditions'] = $this->medical_conditions;
      if ($this->other_illness_specify) $registrationData['other_illness_specify'] = $this->other_illness_specify;
      if ($this->smoke !== null) $registrationData['smoke'] = $this->smoke;
      if ($this->last_pregnancy_complication) $registrationData['last_pregnancy_complication'] = $this->last_pregnancy_complication;
      if ($this->complication_specify) $registrationData['complication_specify'] = $this->complication_specify;
      if ($this->prior_contraceptive !== null) $registrationData['prior_contraceptive'] = $this->prior_contraceptive;
      if ($this->prior_method) $registrationData['prior_method'] = $this->prior_method;
      if ($this->brand_size_model) $registrationData['brand_size_model'] = $this->brand_size_model;
      if ($this->source) $registrationData['source'] = $this->source;
      if ($this->quality) $registrationData['quality'] = $this->quality;
      if ($this->breasts) $registrationData['breasts'] = $this->breasts;
      if ($this->uterus_position) $registrationData['uterus_position'] = $this->uterus_position;
      if ($this->uterus_size) $registrationData['uterus_size'] = $this->uterus_size;
      if ($this->cervix_tears !== null) $registrationData['cervix_tears'] = $this->cervix_tears;
      if ($this->cervix_erosion !== null) $registrationData['cervix_erosion'] = $this->cervix_erosion;
      if ($this->vaginal_discharge !== null) $registrationData['vaginal_discharge'] = $this->vaginal_discharge;
      if ($this->discharge_colour) $registrationData['discharge_colour'] = $this->discharge_colour;
      if ($this->discharge_odor) $registrationData['discharge_odor'] = $this->discharge_odor;
      if ($this->cervix_discharge !== null) $registrationData['cervix_discharge'] = $this->cervix_discharge;
      if ($this->liver_enlarged !== null) $registrationData['liver_enlarged'] = $this->liver_enlarged;
      if ($this->laboratory_results) $registrationData['laboratory_results'] = $this->laboratory_results;
      if ($this->other_observations) $registrationData['other_observations'] = $this->other_observations;
      if ($this->next_appointment) $registrationData['next_appointment'] = $this->next_appointment;
      if ($this->pregnancy1_date_ended) $registrationData['pregnancy1_date_ended'] = $this->pregnancy1_date_ended;
      if ($this->pregnancy1_outcome) $registrationData['pregnancy1_outcome'] = $this->pregnancy1_outcome;
      if ($this->pregnancy1_complication) $registrationData['pregnancy1_complication'] = $this->pregnancy1_complication;
      if ($this->pregnancy2_date_ended) $registrationData['pregnancy2_date_ended'] = $this->pregnancy2_date_ended;
      if ($this->pregnancy2_outcome) $registrationData['pregnancy2_outcome'] = $this->pregnancy2_outcome;
      if ($this->pregnancy2_complication) $registrationData['pregnancy2_complication'] = $this->pregnancy2_complication;
      if ($this->officer_role) $registrationData['officer_role'] = $this->officer_role;
      if ($this->officer_designation) $registrationData['officer_designation'] = $this->officer_designation;

      $registration->update($registrationData);

      $this->clearCaches();
      DB::commit();

      toastr()->info('Family Planning Registration updated successfully!');
      $this->resetForm();
      $this->loadRegistrations();
    } catch (ValidationException $e) {
      DB::rollBack();
      $errors = $e->validator->errors()->all();
      foreach ($errors as $error) {
        toastr()->error($error);
      }
      throw $e;
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the FP registration.');
      throw $e;
    }
  }

  public function delete($id)
  {
    DB::beginTransaction();
    try {
      $registration = FamilyPlanningRegistration::findOrFail($id);
      $registration->delete();

      $this->clearCaches();
      DB::commit();

      toastr()->info('Family Planning Registration deleted successfully.');
      $this->resetForm();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting the registration.');
      throw $e;
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

  private function clearCaches()
  {
    Cache::forget('fp_registrations_list');
    Cache::forget('fp_registrations_count');
  }

  private function loadRegistrations()
  {
    // Loaded in render
  }

  private function resetForm()
  {
    $this->reset([
      'din',
      'patient_id',
      'first_name',
      'last_name',
      'middle_name',
      'patient_gender',
      'patient_dob',
      'patient_age',
      'patient_phone',
      'patient_email',
      'address',
      'marital_status',
      'education',
      'religion',
      'registration_id',
      'client_reg_number',
      'referral_source',
      'children_born_alive',
      'children_still_living',
      'miscarriages_stillbirths_abortions',
      'last_pregnancy_ended',
      'last_pregnancy_result',
      'breastfeeding',
      'want_more_children',
      'last_menstrual_period',
      'menstrual_cycle',
      'cycle_duration',
      'medical_conditions',
      'other_illness_specify',
      'smoke',
      'last_pregnancy_complication',
      'complication_specify',
      'prior_contraceptive',
      'prior_method',
      'contraceptive_selected',
      'brand_size_model',
      'source',
      'quality',
      'weight',
      'blood_pressure',
      'breasts',
      'uterus_position',
      'uterus_size',
      'cervix_tears',
      'cervix_erosion',
      'vaginal_discharge',
      'discharge_colour',
      'discharge_odor',
      'cervix_discharge',
      'liver_enlarged',
      'laboratory_results',
      'other_observations',
      'next_appointment',
      'pregnancy1_date_ended',
      'pregnancy1_outcome',
      'pregnancy1_complication',
      'pregnancy2_date_ended',
      'pregnancy2_outcome',
      'pregnancy2_complication',
      'modal_flag',
      'isPatientVerified',
      'isNewPatient',
      'patient_registration_facility'
    ]);
    $this->mount();
  }

  public function placeholder()
  {
    return view('placeholder');
  }

  public function render()
  {
    $registrations = Cache::remember('fp_registrations_list', 300, function () {
      return FamilyPlanningRegistration::with(['patient', 'facility'])
        ->latest()
        ->take(50)
        ->get();
    });

    return view('livewire.registers.family-planning-register', [
      'registrations' => $registrations,
      'states' => $this->states,
      'lgas' => $this->lgas,
      'wards' => $this->wards,
      'facility_name' => $this->facility_name,
      'facility_state' => $this->facility_state,
      'facility_lga' => $this->facility_lga,
      'facility_ward' => $this->facility_ward,
    ]);
  }
}
