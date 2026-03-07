<?php

namespace App\Livewire\Workspaces\Modules\Clinical;

use App\Models\Activity;
use App\Models\DoctorAssessment;
use App\Models\Facility;
use App\Models\LabTestOrder;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dataOfficerLayout')]
class DoctorAssessments extends Component
{
  public const PRIORITY_OPTIONS = ['Routine', 'Urgent', 'STAT'];

  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;

  public $record_id;
  public $visit_date, $month_year;

  public $chief_complaints, $history_of_present_illness, $vital_signs, $physical_examination, $clinical_findings;
  public $provisional_diagnosis, $final_diagnosis;
  public $assessment_note, $management_plan, $follow_up_instructions, $referral_note, $advice_to_patient;

  public $test_orders = [];

  public $test_entry_name, $test_entry_specimen, $test_entry_priority = 'Routine', $test_entry_instructions;

  protected $rules = [
    'patientId' => 'required',
    'facility_id' => 'required|exists:facilities,id',
    'visit_date' => 'required|date',
    'month_year' => 'required|date',
    'chief_complaints' => 'nullable|string|max:4000',
    'history_of_present_illness' => 'nullable|string|max:12000',
    'vital_signs' => 'nullable|string|max:3000',
    'physical_examination' => 'nullable|string|max:12000',
    'clinical_findings' => 'nullable|string|max:12000',
    'provisional_diagnosis' => 'nullable|string|max:255',
    'final_diagnosis' => 'nullable|string|max:255',
    'assessment_note' => 'required|string|max:25000',
    'management_plan' => 'nullable|string|max:12000',
    'follow_up_instructions' => 'nullable|string|max:12000',
    'referral_note' => 'nullable|string|max:12000',
    'advice_to_patient' => 'nullable|string|max:12000',
    'test_orders' => 'nullable|array',
    'test_orders.*.test_name' => 'required_with:test_orders|string|max:150',
    'test_orders.*.specimen' => 'nullable|string|max:120',
    'test_orders.*.priority' => 'required_with:test_orders|in:Routine,Urgent,STAT',
    'test_orders.*.instructions' => 'nullable|string|max:1000',
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

    $this->visit_date = now()->format('Y-m-d');
    $this->autoFillMonthYear();

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
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

  public function updatedVisitDate(): void
  {
    $this->autoFillMonthYear();
  }

  private function autoFillMonthYear(): void
  {
    if ($this->visit_date) {
      $this->month_year = Carbon::parse($this->visit_date)->startOfMonth()->format('Y-m-d');
    }
  }

  public function addTestOrder(): void
  {
    $name = trim((string) $this->test_entry_name);
    if ($name === '') {
      toastr()->error('Test name is required.');
      return;
    }

    $this->test_orders[] = [
      'test_name' => $name,
      'specimen' => trim((string) $this->test_entry_specimen) ?: null,
      'priority' => in_array($this->test_entry_priority, self::PRIORITY_OPTIONS, true) ? $this->test_entry_priority : 'Routine',
      'instructions' => trim((string) $this->test_entry_instructions) ?: null,
    ];

    $this->reset(['test_entry_name', 'test_entry_specimen', 'test_entry_instructions']);
    $this->test_entry_priority = 'Routine';
  }

  public function removeTestOrder(int $index): void
  {
    if (!isset($this->test_orders[$index])) {
      return;
    }

    unset($this->test_orders[$index]);
    $this->test_orders = array_values($this->test_orders);
  }

  private function payload(): array
  {
    return [
      'patient_id' => $this->patientId,
      'facility_id' => $this->facility_id,
      'state_id' => $this->state_id,
      'lga_id' => $this->lga_id,
      'ward_id' => $this->ward_id,
      'doctor_user_id' => Auth::id(),
      'month_year' => $this->month_year,
      'visit_date' => $this->visit_date,
      'chief_complaints' => $this->chief_complaints,
      'history_of_present_illness' => $this->history_of_present_illness,
      'vital_signs' => $this->vital_signs,
      'physical_examination' => $this->physical_examination,
      'clinical_findings' => $this->clinical_findings,
      'provisional_diagnosis' => $this->provisional_diagnosis,
      'final_diagnosis' => $this->final_diagnosis,
      'assessment_note' => $this->assessment_note,
      'management_plan' => $this->management_plan,
      'follow_up_instructions' => $this->follow_up_instructions,
      'referral_note' => $this->referral_note,
      'advice_to_patient' => $this->advice_to_patient,
      'requires_lab_tests' => count($this->test_orders) > 0,
      'requires_drugs' => false,
      'summary_map' => [
        'test_orders_count' => count($this->test_orders),
        'drug_orders_count' => 0,
      ],
      'officer_name' => $this->officer_name,
      'officer_role' => $this->officer_role,
      'officer_designation' => $this->officer_designation,
    ];
  }

  private function syncOrders(int $assessmentId): void
  {
    LabTestOrder::where('doctor_assessment_id', $assessmentId)
      ->where('status', 'pending')
      ->delete();

    foreach ((array) $this->test_orders as $entry) {
      LabTestOrder::create([
        'doctor_assessment_id' => $assessmentId,
        'patient_id' => $this->patientId,
        'facility_id' => $this->facility_id,
        'state_id' => $this->state_id,
        'lga_id' => $this->lga_id,
        'ward_id' => $this->ward_id,
        'month_year' => $this->month_year,
        'visit_date' => $this->visit_date,
        'test_name' => $entry['test_name'],
        'specimen' => $entry['specimen'] ?? null,
        'priority' => $entry['priority'] ?? 'Routine',
        'instructions' => $entry['instructions'] ?? null,
        'status' => 'pending',
        'requested_by' => $this->officer_name,
        'requested_at' => now(),
      ]);
    }
  }

  public function store(): void
  {
    DB::beginTransaction();
    try {
      $this->autoFillMonthYear();
      $this->validate();

      $record = DoctorAssessment::create($this->payload());
      $this->syncOrders($record->id);

      DB::commit();
      $this->logActivity('create', 'Recorded doctor assessment and test orders');
      $this->edit($record->id);
      toastr()->success('Doctor assessment saved. Pending tests routed to laboratory.');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while saving doctor assessment.');
      throw $e;
    }
  }

  public function edit($id): void
  {
    $record = DoctorAssessment::where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->findOrFail($id);

    $this->record_id = $record->id;
    $this->visit_date = $record->visit_date?->format('Y-m-d');
    $this->month_year = $record->month_year?->format('Y-m-d');

    $this->chief_complaints = $record->chief_complaints;
    $this->history_of_present_illness = $record->history_of_present_illness;
    $this->vital_signs = $record->vital_signs;
    $this->physical_examination = $record->physical_examination;
    $this->clinical_findings = $record->clinical_findings;
    $this->provisional_diagnosis = $record->provisional_diagnosis;
    $this->final_diagnosis = $record->final_diagnosis;
    $this->assessment_note = $record->assessment_note;
    $this->management_plan = $record->management_plan;
    $this->follow_up_instructions = $record->follow_up_instructions;
    $this->referral_note = $record->referral_note;
    $this->advice_to_patient = $record->advice_to_patient;

    $this->test_orders = LabTestOrder::where('doctor_assessment_id', $record->id)
      ->where('status', 'pending')
      ->orderBy('id')
      ->get(['test_name', 'specimen', 'priority', 'instructions'])
      ->map(fn($entry) => $entry->toArray())
      ->values()
      ->toArray();
  }

  public function update(): void
  {
    DB::beginTransaction();
    try {
      $rules = array_diff_key($this->rules, ['patientId' => '']);
      $this->autoFillMonthYear();
      $this->validate($rules);

      $record = DoctorAssessment::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($this->record_id);

      $record->update($this->payload());
      $this->syncOrders($record->id);

      DB::commit();
      $this->logActivity('update', 'Updated doctor assessment and test orders');
      $this->edit($record->id);
      toastr()->success('Doctor assessment updated. Pending test orders refreshed.');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating doctor assessment.');
      throw $e;
    }
  }

  public function delete($id): void
  {
    DB::beginTransaction();
    try {
      $record = DoctorAssessment::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($id);

      LabTestOrder::where('doctor_assessment_id', $record->id)
        ->where('status', 'pending')
        ->update([
          'status' => 'cancelled',
          'completion_notes' => 'Cancelled because doctor assessment record was removed.',
        ]);

      $record->delete();

      DB::commit();
      $this->logActivity('delete', 'Deleted doctor assessment record');
      if ($this->record_id === (int) $id) {
        $this->openCreate();
      }
      toastr()->success('Doctor assessment deleted. Pending test orders cancelled.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting doctor assessment.');
      throw $e;
    }
  }

  private function logActivity(string $action, string $description): void
  {
    try {
      Activity::create([
        'patient_id' => $this->patientId,
        'facility_id' => $this->facility_id,
        'module' => 'doctor-assessment',
        'action' => $action,
        'description' => $description,
        'performed_by' => $this->officer_name,
        'meta' => [
          'visit_date' => $this->visit_date,
          'provisional_diagnosis' => $this->provisional_diagnosis,
          'final_diagnosis' => $this->final_diagnosis,
          'test_orders_count' => count($this->test_orders),
        ],
      ]);
    } catch (Exception $e) {
      // Activity logging must not block workflow.
    }
  }

  public function openCreate(): void
  {
    $this->reset([
      'record_id',
      'visit_date',
      'month_year',
      'chief_complaints',
      'history_of_present_illness',
      'vital_signs',
      'physical_examination',
      'clinical_findings',
      'provisional_diagnosis',
      'final_diagnosis',
      'assessment_note',
      'management_plan',
      'follow_up_instructions',
      'referral_note',
      'advice_to_patient',
      'test_orders',
      'test_entry_name',
      'test_entry_specimen',
      'test_entry_instructions',
    ]);

    $this->visit_date = now()->format('Y-m-d');
    $this->test_entry_priority = 'Routine';
    $this->autoFillMonthYear();
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function render()
  {
    $records = DoctorAssessment::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->withCount([
        'labTestOrders as pending_tests_count' => fn($q) => $q->where('status', 'pending'),
      ])
      ->latest('visit_date')
      ->latest('id')
      ->get();

    return view('livewire.workspaces.modules.clinical.doctor-assessments', [
      'records' => $records,
      'priorityOptions' => self::PRIORITY_OPTIONS,
    ]);
  }
}