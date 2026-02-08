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
use App\Models\ClinicalNote;
use App\Models\DailyAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class ClinicalNotesRegister extends Component
{
  public $din, $user_id, $first_name, $last_name, $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $month_year, $date_of_visit, $section, $note, $phone, $officer_name, $officer_role, $officer_designation;
  public $note_id, $modal_flag = false, $din_modal_flag = false, $isPatientVerified = false;
  public $states, $lgas, $wards, $patient_registration_facility;

  protected $rules = [
    'din' => 'required|string|max:6|min:6',
    'user_id' => 'required|exists:users,id',
    'facility_id' => 'required|exists:facilities,id',
    'state_id' => 'required|exists:states,id',
    'lga_id' => 'nullable|exists:lgas,id',
    'ward_id' => 'nullable|exists:wards,id',
    'month_year' => 'required|date',
    'date_of_visit' => 'required|date',
    'section' => 'required|string|in:Blood Test,Urine Test,Ultrasound,Clinical Note,X-Ray,ECG,Other Lab Tests',
    'note' => 'required|string|max:2000',
    'phone' => 'nullable|string|max:20',
    'officer_name' => 'required|string|max:255',
    'officer_role' => 'required|string|max:255',
    'officer_designation' => 'required|string|max:255',
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
    $this->facility_name = $facility->name;
    $this->state_id = State::where('name', $facility->state)->first()->id ?? null;
    $this->state_name = $facility->state;
    $this->lga_id = Lga::where('name', $facility->lga)->where('state_id', $this->state_id)->first()->id ?? null;
    $this->lga_name = $facility->lga;
    $this->ward_id = Ward::where('name', $facility->ward)->where('lga_id', $this->lga_id)->first()->id ?? null;
    $this->ward_name = $facility->ward;
    $this->month_year = Carbon::now()->format('Y-m-d');
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
    $this->reset(['din', 'user_id', 'first_name', 'last_name', 'phone', 'isPatientVerified', 'note_id', 'modal_flag', 'patient_registration_facility']);
    $this->din_modal_flag = true;
  }

  public function verifyPatient()
  {
    $this->validateOnly('din');
    $patient = User::where('DIN', $this->din)->first();
    if (!$patient) {
      toastr()->error('Patient Profile not found. If patient is new please create an antenatal register first');
      $this->isPatientVerified = false;
      $this->din_modal_flag = true;
      return;
    }

    $this->user_id = $patient->id;
    $this->first_name = $patient->first_name;
    $this->last_name = $patient->last_name;

    $patient = User::with('antenatal.registrationFacility')->find($this->user_id);
    $this->patient_registration_facility = $patient->antenatal->registrationFacility->name ?? 'N/A';
    $this->phone = $patient->phone ?? null;

    // Check if patient has daily attendance for today
    $todayAttendance = DailyAttendance::where('user_id', $this->user_id)
      ->whereDate('visit_date', Carbon::today())
      ->first();

    if (!$todayAttendance) {
      toastr()->error('Patient must have daily attendance recorded for today before clinical notes can be created. Please record daily attendance first.');
      $this->isPatientVerified = false;
      $this->din_modal_flag = true;
      return;
    }

    $this->isPatientVerified = true;
    $this->din_modal_flag = true;
  }

  public function openClinicalNoteModal()
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

      $existingNote = ClinicalNote::where('user_id', $this->user_id)
        ->whereDate('date_of_visit', Carbon::parse($this->date_of_visit)->format('Y-m-d'))
        ->where('section', $this->section)
        ->first();

      if ($existingNote) {
        DB::rollBack();
        toastr()->error('Clinical note for this patient, date, and section already exists.');
        return;
      }

      $data = array_diff_key($this->all(), [
        'din' => '',
        'first_name' => '',
        'last_name' => '',
        'state_name' => '',
        'lga_name' => '',
        'ward_name' => '',
        'facility_name' => '',
        'states' => '',
        'lgas' => '',
        'wards' => '',
        'isPatientVerified' => '',
        'patient_registration_facility' => ''
      ]);

      ClinicalNote::create($data);

      DB::commit();
      toastr()->success('Clinical note created successfully.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      toastr()->error('Database error occurred while creating the clinical note.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while creating the clinical note.');
      throw $e;
    }
  }

  public function edit($id)
  {
    DB::beginTransaction();
    try {
      $note = ClinicalNote::findOrFail($id);
      $this->note_id = $id;
      $this->user_id = $note->user_id;
      $this->first_name = $note->user->first_name ?? 'N/A';
      $this->last_name = $note->user->last_name ?? 'N/A';
      $this->patient_registration_facility = User::with('antenatal.registrationFacility')->find($note->user_id)->antenatal->registrationFacility->name ?? 'N/A';
      $this->facility_id = $note->facility_id;
      $this->facility_name = Facility::find($note->facility_id)->name ?? 'N/A';
      $this->state_id = $note->state_id;
      $this->state_name = State::find($note->state_id)->name ?? 'N/A';
      $this->lga_id = $note->lga_id;
      $this->lga_name = Lga::find($note->lga_id)->name ?? 'N/A';
      $this->ward_id = $note->ward_id;
      $this->ward_name = Ward::find($note->ward_id)->name ?? 'N/A';
      $this->month_year = $note->month_year ? Carbon::parse($note->month_year)->format('Y-m-d') : null;
      $this->date_of_visit = $note->date_of_visit ? Carbon::parse($note->date_of_visit)->format('Y-m-d') : null;
      $this->section = $note->section;
      $this->note = $note->note;
      $this->phone = $note->phone;
      $this->officer_name = $note->officer_name;
      $this->officer_role = $note->officer_role;
      $this->officer_designation = $note->officer_designation;
      $this->din_modal_flag = false;
      $this->modal_flag = true;
      $this->isPatientVerified = false;
      $this->dispatch('open-main-modal');

      DB::commit();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while loading the clinical note.');
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

      $note = ClinicalNote::findOrFail($this->note_id);
      $note->update(array_diff_key($this->all(), [
        'din' => '',
        'first_name' => '',
        'last_name' => '',
        'state_name' => '',
        'lga_name' => '',
        'ward_name' => '',
        'facility_name' => '',
        'states' => '',
        'lgas' => '',
        'wards' => '',
        'isPatientVerified' => '',
        'patient_registration_facility' => ''
      ]));

      DB::commit();
      toastr()->success('Clinical note updated successfully.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      toastr()->error('Database error while updating the clinical note.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the clinical note.');
      throw $e;
    }
  }

  public function delete($id)
  {
    DB::beginTransaction();
    try {
      $note = ClinicalNote::findOrFail($id);
      $note->delete();

      DB::commit();
      toastr()->success('Clinical note deleted successfully.');
      $this->resetForm();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting the clinical note.');
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
      'phone',
      'month_year',
      'date_of_visit',
      'section',
      'note',
      'officer_name',
      'officer_role',
      'officer_designation',
      'note_id',
      'modal_flag',
      'din_modal_flag',
      'isPatientVerified',
      'patient_registration_facility'
    ]);
    $this->mount();
  }

  public function render()
  {
    $notes = ClinicalNote::with('user', 'facility')->latest()->take(50)->get();
    return view('livewire.registers.clinical-notes-register', [
      'notes' => $notes,
      'states' => $this->states,
      'lgas' => $this->lgas,
      'wards' => $this->wards,
      'facility_name' => $this->facility_name,
      'state_name' => $this->state_name,
      'lga_name' => $this->lga_name,
      'ward_name' => $this->ward_name
    ])->layout('layouts.dataOfficerLayout');
  }
}
