<?php

namespace App\Livewire\Workspaces\Modules\Clinical;

use App\Models\Activity;
use App\Models\DoctorAssessment;
use App\Models\Facility;
use App\Models\LabTestOrder;
use App\Models\Patient;
use App\Models\Prescription;
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
  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;

  public $record_id;
  public $visit_date, $month_year, $next_appointment_date;

  public $final_diagnosis;
  public $assessment_note, $management_plan;

  public $test_orders = [];
  public $drug_orders = [];

  public $test_entry_name, $test_entry_specimen;
  public $drug_entry_name, $drug_entry_dosage, $drug_entry_frequency, $drug_entry_duration, $drug_entry_route, $drug_entry_instructions, $drug_entry_quantity_prescribed;

  protected $rules = [
    'patientId' => 'required',
    'facility_id' => 'required|exists:facilities,id',
    'visit_date' => 'required|date',
    'next_appointment_date' => 'nullable|date',
    'month_year' => 'required|date',
    'final_diagnosis' => 'nullable|string|max:255',
    'assessment_note' => 'required|string|max:25000',
    'management_plan' => 'nullable|string|max:12000',
    'test_orders' => 'nullable|array',
    'test_orders.*.test_name' => 'required_with:test_orders|string|max:150',
    'test_orders.*.specimen' => 'nullable|string|max:120',
    'drug_orders' => 'nullable|array',
    'drug_orders.*.drug_name' => 'required_with:drug_orders|string|max:150',
    'drug_orders.*.dosage' => 'nullable|string|max:120',
    'drug_orders.*.frequency' => 'nullable|string|max:120',
    'drug_orders.*.duration' => 'nullable|string|max:120',
    'drug_orders.*.route' => 'nullable|string|max:80',
    'drug_orders.*.instructions' => 'nullable|string|max:1200',
    'drug_orders.*.quantity_prescribed' => 'nullable|numeric|min:0',
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
    ];

    $this->reset(['test_entry_name', 'test_entry_specimen']);
  }

  public function removeTestOrder(int $index): void
  {
    if (!isset($this->test_orders[$index])) {
      return;
    }

    unset($this->test_orders[$index]);
    $this->test_orders = array_values($this->test_orders);
  }

  public function addDrugOrder(): void
  {
    $name = trim((string) $this->drug_entry_name);
    if ($name === '') {
      toastr()->error('Drug name is required.');
      return;
    }

    $quantity = trim((string) $this->drug_entry_quantity_prescribed);
    if ($quantity !== '' && !is_numeric($quantity)) {
      toastr()->error('Quantity prescribed must be numeric.');
      return;
    }

    $this->drug_orders[] = [
      'drug_name' => $name,
      'dosage' => trim((string) $this->drug_entry_dosage) ?: null,
      'frequency' => trim((string) $this->drug_entry_frequency) ?: null,
      'duration' => trim((string) $this->drug_entry_duration) ?: null,
      'route' => trim((string) $this->drug_entry_route) ?: null,
      'instructions' => trim((string) $this->drug_entry_instructions) ?: null,
      'quantity_prescribed' => $quantity === '' ? null : (float) $quantity,
    ];

    $this->reset([
      'drug_entry_name',
      'drug_entry_dosage',
      'drug_entry_frequency',
      'drug_entry_duration',
      'drug_entry_route',
      'drug_entry_instructions',
      'drug_entry_quantity_prescribed',
    ]);
  }

  public function removeDrugOrder(int $index): void
  {
    if (!isset($this->drug_orders[$index])) {
      return;
    }

    unset($this->drug_orders[$index]);
    $this->drug_orders = array_values($this->drug_orders);
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
      'next_appointment_date' => $this->next_appointment_date ?: null,
      'final_diagnosis' => $this->final_diagnosis,
      'assessment_note' => $this->assessment_note,
      'management_plan' => $this->management_plan,
      'requires_lab_tests' => count($this->test_orders) > 0,
      'requires_drugs' => count($this->drug_orders) > 0,
      'summary_map' => [
        'test_orders_count' => count($this->test_orders),
        'drug_orders_count' => count($this->drug_orders),
      ],
      'officer_name' => $this->officer_name,
      'officer_role' => $this->officer_role,
      'officer_designation' => $this->officer_designation,
    ];
  }

  private function syncLabOrders(int $assessmentId): void
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
        'priority' => 'Routine',
        'instructions' => null,
        'status' => 'pending',
        'requested_by' => $this->officer_name,
        'requested_at' => now(),
      ]);
    }
  }

  private function syncPrescriptionOrders(int $assessmentId): void
  {
    Prescription::where('doctor_assessment_id', $assessmentId)
      ->where('status', 'pending')
      ->delete();

    foreach ((array) $this->drug_orders as $entry) {
      Prescription::create([
        'doctor_assessment_id' => $assessmentId,
        'patient_id' => $this->patientId,
        'facility_id' => $this->facility_id,
        'state_id' => $this->state_id,
        'lga_id' => $this->lga_id,
        'ward_id' => $this->ward_id,
        'month_year' => $this->month_year,
        'prescribed_date' => $this->visit_date,
        'drug_name' => $entry['drug_name'],
        'dosage' => $entry['dosage'] ?? null,
        'frequency' => $entry['frequency'] ?? null,
        'duration' => $entry['duration'] ?? null,
        'route' => $entry['route'] ?? null,
        'instructions' => $entry['instructions'] ?? null,
        'quantity_prescribed' => $entry['quantity_prescribed'] ?? null,
        'status' => 'pending',
        'prescribed_by' => $this->officer_name,
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
      $this->syncLabOrders((int) $record->id);
      $this->syncPrescriptionOrders((int) $record->id);

      DB::commit();
      $this->logActivity('create', 'Recorded doctor assessment with pending lab and prescription orders');
      $this->edit($record->id);
      toastr()->success('Doctor assessment saved. Pending lab and prescription orders routed.');
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
    $this->next_appointment_date = $record->next_appointment_date?->format('Y-m-d');

    $this->final_diagnosis = $record->final_diagnosis;
    $this->assessment_note = $record->assessment_note;
    $this->management_plan = $record->management_plan;

    $this->test_orders = LabTestOrder::where('doctor_assessment_id', $record->id)
      ->where('status', 'pending')
      ->orderBy('id')
      ->get(['test_name', 'specimen'])
      ->map(fn($entry) => $entry->toArray())
      ->values()
      ->toArray();

    $this->drug_orders = Prescription::where('doctor_assessment_id', $record->id)
      ->where('status', 'pending')
      ->orderBy('id')
      ->get(['drug_name', 'dosage', 'frequency', 'duration', 'route', 'instructions', 'quantity_prescribed'])
      ->map(function ($entry) {
        $payload = $entry->toArray();
        $payload['quantity_prescribed'] = $payload['quantity_prescribed'] !== null ? (float) $payload['quantity_prescribed'] : null;
        return $payload;
      })
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
      $this->syncLabOrders((int) $record->id);
      $this->syncPrescriptionOrders((int) $record->id);

      DB::commit();
      $this->logActivity('update', 'Updated doctor assessment with refreshed pending lab/prescription orders');
      $this->edit($record->id);
      toastr()->success('Doctor assessment updated. Pending lab and prescription orders refreshed.');
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

      Prescription::where('doctor_assessment_id', $record->id)
        ->where('status', 'pending')
        ->update([
          'status' => 'cancelled',
          'dispense_notes' => 'Cancelled because doctor assessment record was removed.',
        ]);

      $record->delete();

      DB::commit();
      $this->logActivity('delete', 'Deleted doctor assessment record');
      if ($this->record_id === (int) $id) {
        $this->openCreate();
      }
      toastr()->success('Doctor assessment deleted. Pending lab and prescription orders cancelled.');
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
          'next_appointment_date' => $this->next_appointment_date,
          'final_diagnosis' => $this->final_diagnosis,
          'test_orders_count' => count($this->test_orders),
          'drug_orders_count' => count($this->drug_orders),
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
      'next_appointment_date',
      'final_diagnosis',
      'assessment_note',
      'management_plan',
      'test_orders',
      'drug_orders',
      'test_entry_name',
      'test_entry_specimen',
      'drug_entry_name',
      'drug_entry_dosage',
      'drug_entry_frequency',
      'drug_entry_duration',
      'drug_entry_route',
      'drug_entry_instructions',
      'drug_entry_quantity_prescribed',
    ]);

    $this->visit_date = now()->format('Y-m-d');
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
        'prescriptions as pending_prescriptions_count' => fn($q) => $q->where('status', 'pending'),
      ])
      ->latest('visit_date')
      ->latest('id')
      ->get();

    return view('livewire.workspaces.modules.clinical.doctor-assessments', [
      'records' => $records,
    ]);
  }
}
