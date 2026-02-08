<?php

namespace App\Livewire\Registers;

use Exception;
use Carbon\Carbon;
use App\Models\Lga;
use App\Models\User;
use App\Models\Ward;
use App\Models\State;
use Livewire\Component;
use App\Models\Facility;
use App\Models\Antenatal;
use App\Models\DailyAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\MassAssignmentException;

class DailyAttendanceRegister extends Component
{
  public $din, $user_id, $first_name, $last_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name, $facility_id, $month_year, $visit_date, $date_of_birth, $gender, $age_group, $address, $state_of_origin_id, $phone, $first_contact;
  public $next_of_kin_name, $next_of_kin_relation, $next_of_kin_address, $next_of_kin_phone;
  public $officer_name, $officer_role, $officer_designation;



  public $modal_flag = false;
  public $din_modal_flag = false;
  public $isPatientVerified = false;
  public $daily_id;
  public $states;
  public  $patient_registration_facility; // patient -registration_facility_name

  protected $rules = [
    'din' => 'required|string|max:255',
    'state_id' => 'required|exists:states,id',
    'lga_id' => 'nullable|exists:lgas,id',
    'ward_id' => 'nullable|exists:wards,id',
    'facility_id' => 'required|exists:facilities,id',
    'month_year' => 'nullable|date',
    'visit_date' => 'nullable|date',
    'date_of_birth' => 'required|date|before_or_equal:today',
    'gender' => 'required|in:Male,Female',
    'age_group' => 'nullable|in:11 - 14 years,15 - 19 years,20 - 24 years,25 - 29 years,30 - 34 years,35 - 49 years,50 + years',
    'address' => 'nullable|string|max:255',
    'state_of_origin_id' => 'nullable|exists:states,id',
    'phone' => 'nullable|string|max:20',
    'first_contact' => 'nullable|boolean',
    'next_of_kin_name' => 'nullable|string|max:255',
    'next_of_kin_relation' => 'nullable|string|max:255',
    'next_of_kin_address' => 'nullable|string|max:255',
    'next_of_kin_phone' => 'nullable|string|max:20',
    'officer_name' => 'string|max:255',
    'officer_role' => 'string|max:255',
    'officer_designation' => 'string|max:255',
  ];

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
    $this->state_id = State::where('name', $facility->state)->first()->id ?? null;
    $this->state_name = $facility->state;
    $this->lga_id = Lga::where('name', $facility->lga)->where('state_id', $this->state_id)->first()->id ?? null;
    $this->lga_name = $facility->lga;
    $this->ward_id = Ward::where('name', $facility->ward)->where('lga_id', $this->lga_id)->first()->id ?? null;
    $this->ward_name = $facility->ward;
    $this->month_year = Carbon::now()->format('Y-m-d');

    // Fix this line - add error handling
    try {
      $this->states = State::orderBy('name')->get();
    } catch (Exception $e) {
      $this->states = collect(); // Empty collection as fallback
    }

    $this->officer_name = $user->first_name . ' ' . $user->last_name;
    $this->officer_role = $user->role;
    $this->officer_designation = $user->designation;
  }
  public function openDinModal()
  {
    $this->reset(['din', 'user_id', 'first_name', 'last_name', 'date_of_birth', 'gender', 'isPatientVerified', 'daily_id', 'modal_flag']);
    $this->din_modal_flag = true;
    $this->modal_flag = false;
  }

  public function verifyPatient()
  {
    $this->validateOnly('din');
    $patient = User::where('DIN', $this->din)->first();
    if (!$patient) {
      toastr()->error('Patient Profile not found. If patinet is new please create an antenatal register first');
      $this->isPatientVerified = false;
      $this->din_modal_flag = true;
      return;
    }
    // after verification, get some data to preload for the daily attendance
    $this->user_id = $patient->id;
    $this->first_name = $patient->first_name;
    $this->last_name = $patient->last_name;

    $patient = User::with('antenatal.registrationFacility')->find($this->user_id);
    $this->patient_registration_facility = $patient->antenatal->registrationFacility->name;

    $latestAttendance = DailyAttendance::where('user_id', $patient->id)->latest()->first();
    if ($latestAttendance) {
      $this->date_of_birth = $latestAttendance->date_of_birth;
      $this->gender = $latestAttendance->gender;
      //$this->state_id = $latestAttendance->state_id;
      //$this->state_name = $latestAttendance->state_id ? State::find($latestAttendance->state_id)->name ?? $this->state_name : $this->state_name;
    }

    $this->isPatientVerified = true;
    $this->din_modal_flag = true;
  }

  public function openAttendanceModal()
  {
    if ($this->isPatientVerified) {
      $this->din_modal_flag = false;
      $this->modal_flag = false;
      $this->dispatch('open-main-modal');
    }
  }

  public function store()
  {
    DB::beginTransaction();
    try {
      $this->validate();

      // Normalize visit_date to date-only format
      $visitDate = Carbon::parse($this->visit_date)->format('Y-m-d');

      // Check for existing attendance on the same date
      $existingAttendance = DailyAttendance::where('user_id', $this->user_id)
        ->whereDate('visit_date', $visitDate)
        ->first();

      if ($existingAttendance) {
        DB::rollBack();
        toastr()->error('Attendance for this patient on this date has already been taken.');
        return;
      }

      $data = array_merge(
        array_diff_key($this->all(), ['din' => '', 'first_name' => '', 'last_name' => '', 'state_name' => '', 'lga_name' => '', 'ward_name' => '', 'facility_name' => '', 'state_of_origin_name' => '', 'states' => '', 'isPatientVerified' => '']),
        [
          'user_id' => $this->user_id,
          'facility_id' => $this->facility_id,
          'visit_date' => $visitDate,
        ]
      );

      $attendance = new DailyAttendance();
      $attendance->user_id = $this->user_id;
      $attendance->facility_id = $this->facility_id;
      $attendance->state_id = $data['state_id'];
      $attendance->lga_id = $data['lga_id'] ?? null;
      $attendance->ward_id = $data['ward_id'] ?? null;
      $attendance->month_year = $data['month_year'] ?? null;
      $attendance->visit_date = $data['visit_date'] ?? null;
      $attendance->date_of_birth = $data['date_of_birth'];
      $attendance->gender = $data['gender'];
      $attendance->age_group = $data['age_group'] ?? null;
      $attendance->address = $data['address'] ?? null;
      $attendance->state_of_origin_id = $data['state_of_origin_id'] ?? null;
      $attendance->phone = $data['phone'] ?? null;
      $attendance->first_contact = $data['first_contact'] ?? null;
      $attendance->next_of_kin_name = $data['next_of_kin_name'] ?? null;
      $attendance->next_of_kin_relation = $data['next_of_kin_relation'] ?? null;
      $attendance->next_of_kin_address = $data['next_of_kin_address'] ?? null;
      $attendance->next_of_kin_phone = $data['next_of_kin_phone'] ?? null;
      $attendance->officer_name = $data['officer_name'] ?? null;
      $attendance->officer_role = $data['officer_role'] ?? null;
      $attendance->officer_designation = $data['officer_designation'] ?? null;
      $attendance->save();

      DB::commit();
      toastr()->info('Daily attendance record created successfully.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      if (str_contains($e->getMessage(), "Field 'user_id' doesn't have a default value")) {
        toastr()->error('Failed to save attendance: database schema issue with user ID. Please contact support.');
      } else {
        toastr()->error('Database error occurred while creating the attendance record.');
      }
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while creating the attendance record.');
      throw $e;
    }
  }

  public function edit($id)
  {
    DB::beginTransaction();
    try {
      $daily = DailyAttendance::findOrFail($id);
      $this->daily_id = $id;
      $this->user_id = $daily->user_id;
      $this->first_name = $daily->user->first_name;
      $this->last_name = $daily->user->last_name;
      $this->state_id = $daily->state_id;
      $this->state_name = $daily->state->name ?? 'N/A';
      $this->lga_id = $daily->lga_id;
      $this->lga_name = $daily->lga->name ?? 'N/A';
      $this->ward_id = $daily->ward_id;
      $this->ward_name = $daily->ward->name ?? 'N/A';
      $this->facility_id = $daily->facility_id;
      $this->month_year = $daily->month_year ? Carbon::parse($daily->month_year)->format('Y-m-d') : null;
      $this->visit_date = $daily->visit_date;
      $this->date_of_birth = $daily->date_of_birth;
      $this->gender = $daily->gender;
      $this->age_group = $daily->age_group;
      $this->address = $daily->address;
      $this->state_of_origin_id = $daily->state_of_origin_id;
      $this->phone = $daily->phone;
      $this->first_contact = $daily->first_contact;
      $this->next_of_kin_name = $daily->next_of_kin_name;
      $this->next_of_kin_relation = $daily->next_of_kin_relation;
      $this->next_of_kin_address = $daily->next_of_kin_address;
      $this->next_of_kin_phone = $daily->next_of_kin_phone;
      $this->officer_name = $daily->officer_name;
      $this->officer_role = $daily->officer_role;
      $this->officer_designation = $daily->officer_designation;
      $this->din_modal_flag = false;
      $this->modal_flag = true;
      $this->isPatientVerified = false;
      $this->dispatch('open-main-modal');

      DB::commit();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while loading the attendance record.');
      throw $e;
    }
  }

  public function update()
  {
    DB::beginTransaction();
    try {
      $rules = array_diff_key($this->rules, [
        'din' => '',
        'first_name' => '',
        'last_name' => '',
      ]);
      $this->validate($rules);

      $daily = DailyAttendance::findOrFail($this->daily_id);
      $daily->update(array_diff_key($this->all(), [
        'din' => '',
        'first_name' => '',
        'last_name' => '',
        'state_name' => '',
        'lga_name' => '',
        'ward_name' => '',
        'facility_name' => '',
        'states' => '',
        'isPatientVerified' => '',
      ]));

      DB::commit();
      toastr()->info('Daily attendance record updated successfully.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      toastr()->error('Database error while updating the attendance record.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the attendance record.');
      throw $e;
    }
  }

  public function delete($id)
  {
    DB::beginTransaction();
    try {
      $daily = DailyAttendance::findOrFail($id);
      $daily->delete();

      DB::commit();
      toastr()->info('Daily attendance record deleted successfully.');
      $this->resetForm();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting the attendance record.');
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

  private function resetForm()
  {
    $this->reset([
      'din',
      'user_id',
      'first_name',
      'last_name',
      'date_of_birth',
      'gender',
      'isPatientVerified',
      'daily_id',
      'modal_flag',
      'din_modal_flag',
      'visit_date',
      'age_group',
      'address',
      'state_of_origin_id',
      'phone',
      'first_contact',
      'next_of_kin_name',
      'next_of_kin_relation',
      'next_of_kin_address',
      'next_of_kin_phone'
    ]);
  }

  public function render()
  {
    $dailies = DailyAttendance::with('user', 'facility', 'state', 'lga', 'ward', 'stateOfOrigin')->latest()->take(10)->get();
    $states = $this->states ?? collect();

    $facility_name = Facility::find($this->facility_id)->name ?? 'N/A';

    return view('livewire.registers.daily-attendance-register', [
      'dailies' => $dailies,
      'states' => $states,
      'facility_name' => $facility_name,
    ])->layout('layouts.dataOfficerLayout');
  }
}
