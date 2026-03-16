<?php

namespace App\Livewire\Workspaces\Modules\FamilyPlanning;

use App\Models\Activity;
use App\Models\Facility;
use App\Models\FamilyPlanningFollowUp;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use App\Models\Registrations\FamilyPlanningRegistration;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dataOfficerLayout')]
class ClientFollowUp extends Component
{
  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;

  public $hasFamilyPlanningRegistration = false;
  public $registration_id;
  public $registration_date;
  public $registration_method;
  public $registration_next_appointment;

  public $record_id;
  public $month_year;
  public $visit_date;
  public $next_appointment_date;
  public $method_change;
  public $method_supplied;
  public $brand_size_quality;
  public $blood_pressure;
  public $weight;
  public $pelvic_exam_performed;
  public $observation_notes;

  protected $rules = [
    'visit_date' => 'required|date',
    'next_appointment_date' => 'nullable|date',
    'method_change' => 'nullable|in:Y,N',
    'method_supplied' => 'nullable|string|max:255',
    'brand_size_quality' => 'nullable|string|max:255',
    'blood_pressure' => 'nullable|string|max:20',
    'weight' => 'nullable|numeric|min:0|max:400',
    'pelvic_exam_performed' => 'nullable|in:Y,N',
    'observation_notes' => 'nullable|string|max:5000',
  ];

  public function mount($patientId)
  {
    $this->patientId = $patientId;

    $user = Auth::user();
    if (!$user || !in_array($user->role, ['Data Officer', 'Doctor', 'Medical Officer'], true)) {
      abort(403, 'Unauthorized: Access denied for this role.');
    }

    $facility = Facility::with(['stateRelation', 'lgaRelation', 'wardRelation'])->find($user->facility_id);
    if (!$facility) {
      abort(403, 'Invalid facility assignment.');
    }

    $this->facility_id = $facility->id;
    $this->facility_name = $facility->name;
    $this->state_id = $facility->state_id;
    $this->state_name = $facility->stateRelation?->name ?? $facility->state;
    $this->lga_id = $facility->lga_id;
    $this->lga_name = $facility->lgaRelation?->name ?? $facility->lga;
    $this->ward_id = $facility->ward_id;
    $this->ward_name = $facility->wardRelation?->name ?? $facility->ward;

    $this->officer_name = $user->first_name . ' ' . $user->last_name;
    $this->officer_role = $user->role;
    $this->officer_designation = $user->designation;

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
      $this->loadRegistrationContext();
    }

    $this->openCreate();
  }

  private function validatePatientAccess(): void
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

  private function loadPatientData(): void
  {
    if (!$this->patient) {
      return;
    }

    $this->patient_din = $this->patient->din;
    $this->first_name = $this->patient->first_name;
    $this->last_name = $this->patient->last_name;
    $this->middle_name = $this->patient->middle_name;
    $this->patient_phone = $this->patient->phone;
    $this->patient_dob = $this->patient->date_of_birth?->format('d M Y');
    $this->patient_gender = $this->patient->gender;
    $this->patient_age = $this->patient->date_of_birth ? $this->patient->date_of_birth->age : null;
  }

  private function loadRegistrationContext(): void
  {
    $registration = FamilyPlanningRegistration::query()
      ->where('patient_id', $this->patientId)
      ->latest('registration_date')
      ->latest('id')
      ->first();

    $this->hasFamilyPlanningRegistration = !is_null($registration);
    $this->registration_id = $registration?->id;
    $this->registration_date = $registration?->registration_date?->format('Y-m-d');
    $this->registration_method = $registration?->contraceptive_selected;
    $this->registration_next_appointment = $registration?->next_appointment?->format('Y-m-d');
  }

  public function updatedVisitDate(): void
  {
    $this->month_year = $this->visit_date
      ? Carbon::parse($this->visit_date)->startOfMonth()->format('Y-m-d')
      : null;
  }

  private function payload(): array
  {
    return [
      'patient_id' => $this->patientId,
      'facility_id' => $this->facility_id,
      'family_planning_registration_id' => $this->registration_id,
      'state_id' => $this->state_id,
      'lga_id' => $this->lga_id,
      'ward_id' => $this->ward_id,
      'month_year' => $this->month_year,
      'visit_date' => $this->visit_date,
      'next_appointment_date' => $this->next_appointment_date,
      'method_change' => $this->method_change,
      'method_supplied' => $this->method_supplied,
      'brand_size_quality' => $this->brand_size_quality,
      'blood_pressure' => $this->blood_pressure,
      'weight' => $this->weight,
      'pelvic_exam_performed' => $this->pelvic_exam_performed,
      'observation_notes' => $this->observation_notes,
      'summary_map' => [
        'method_changed' => $this->method_change === 'Y',
        'has_observation' => !empty($this->observation_notes),
      ],
      'officer_name' => $this->officer_name,
      'officer_role' => $this->officer_role,
      'officer_designation' => $this->officer_designation,
    ];
  }

  public function store(): void
  {
    if (!$this->hasFamilyPlanningRegistration) {
      toastr()->error('Family Planning registration is required before follow-up visits.');
      return;
    }

    DB::beginTransaction();
    try {
      $this->updatedVisitDate();
      $this->validate();

      if ($this->method_change === 'Y' && !$this->method_supplied) {
        throw ValidationException::withMessages([
          'method_supplied' => 'Method supplied is required when method change is Yes.',
        ]);
      }

      $record = FamilyPlanningFollowUp::query()->create($this->payload());

      DB::commit();
      $this->logActivity('create', 'Recorded family planning follow-up visit');
      $this->edit($record->id);
      $this->loadRegistrationContext();
      toastr()->success('Family planning follow-up saved.');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while saving family planning follow-up.');
      throw $e;
    }
  }

  public function edit($id): void
  {
    $record = FamilyPlanningFollowUp::query()
      ->where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->findOrFail($id);

    $this->record_id = $record->id;
    $this->month_year = $record->month_year?->format('Y-m-d');
    $this->visit_date = $record->visit_date?->format('Y-m-d');
    $this->next_appointment_date = $record->next_appointment_date?->format('Y-m-d');
    $this->method_change = $record->method_change;
    $this->method_supplied = $record->method_supplied;
    $this->brand_size_quality = $record->brand_size_quality;
    $this->blood_pressure = $record->blood_pressure;
    $this->weight = $record->weight;
    $this->pelvic_exam_performed = $record->pelvic_exam_performed;
    $this->observation_notes = $record->observation_notes;
  }

  public function update(): void
  {
    if (!$this->record_id) {
      toastr()->error('No follow-up record selected for update.');
      return;
    }

    DB::beginTransaction();
    try {
      $this->updatedVisitDate();
      $this->validate();

      if ($this->method_change === 'Y' && !$this->method_supplied) {
        throw ValidationException::withMessages([
          'method_supplied' => 'Method supplied is required when method change is Yes.',
        ]);
      }

      $record = FamilyPlanningFollowUp::query()
        ->where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($this->record_id);

      $record->update($this->payload());

      DB::commit();
      $this->logActivity('update', 'Updated family planning follow-up visit');
      $this->edit($record->id);
      $this->loadRegistrationContext();
      toastr()->success('Family planning follow-up updated.');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating family planning follow-up.');
      throw $e;
    }
  }

  public function delete($id): void
  {
    DB::beginTransaction();
    try {
      $record = FamilyPlanningFollowUp::query()
        ->where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($id);

      $record->delete();

      DB::commit();
      $this->logActivity('delete', 'Deleted family planning follow-up visit');
      if ($this->record_id === (int) $id) {
        $this->openCreate();
      }
      toastr()->success('Family planning follow-up deleted.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting family planning follow-up.');
      throw $e;
    }
  }

  private function logActivity(string $action, string $description): void
  {
    try {
      Activity::query()->create([
        'patient_id' => $this->patientId,
        'facility_id' => $this->facility_id,
        'module' => 'family_planning',
        'action' => $action,
        'description' => $description,
        'performed_by' => $this->officer_name,
        'meta' => [
          'visit_date' => $this->visit_date,
          'next_appointment_date' => $this->next_appointment_date,
          'method_change' => $this->method_change,
        ],
      ]);
    } catch (Exception $e) {
      // Activity logging should not block workflow.
    }
  }

  public function openCreate(): void
  {
    $this->reset([
      'record_id',
      'month_year',
      'visit_date',
      'next_appointment_date',
      'method_change',
      'method_supplied',
      'brand_size_quality',
      'blood_pressure',
      'weight',
      'pelvic_exam_performed',
      'observation_notes',
    ]);

    $this->visit_date = now()->format('Y-m-d');
    $this->next_appointment_date = $this->registration_next_appointment;
    $this->method_supplied = $this->registration_method;
    $this->brand_size_quality = FamilyPlanningRegistration::query()
      ->where('id', $this->registration_id)
      ->value('brand_size_model');
    $this->blood_pressure = FamilyPlanningRegistration::query()
      ->where('id', $this->registration_id)
      ->value('blood_pressure');
    $this->weight = FamilyPlanningRegistration::query()
      ->where('id', $this->registration_id)
      ->value('weight');

    $this->updatedVisitDate();
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function render()
  {
    $records = FamilyPlanningFollowUp::query()
      ->where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->latest('visit_date')
      ->latest('id')
      ->get();

    return view('livewire.workspaces.modules.family-planning.client-follow-up', [
      'records' => $records,
    ]);
  }
}
