<?php

namespace App\Livewire\Workspaces\Modules\Clinical;

use App\Models\Activity;
use App\Models\Facility;
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
class Prescriptions extends Component
{
  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;

  public $active_dispense_id;
  public $dispensed_date;
  public $quantity_dispensed;
  public $dispense_notes;

  protected $rules = [
    'active_dispense_id' => 'required|exists:prescriptions,id',
    'dispensed_date' => 'required|date',
    'quantity_dispensed' => 'nullable|numeric|min:0',
    'dispense_notes' => 'nullable|string|max:1000',
  ];

  public function mount($patientId)
  {
    $this->patientId = $patientId;

    $user = Auth::user();
    if (!$user || $user->role !== 'Data Officer') {
      abort(403, 'Unauthorized: Only Data Officers can access this page.');
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
    }

    $this->dispensed_date = now()->format('Y-m-d');
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

  public function startDispense($id): void
  {
    $record = Prescription::where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->where('status', 'pending')
      ->findOrFail($id);

    $this->active_dispense_id = $record->id;
    $this->dispensed_date = now()->format('Y-m-d');
    $this->quantity_dispensed = $record->quantity_prescribed;
    $this->dispense_notes = null;
  }

  public function clearDispense(): void
  {
    $this->reset(['active_dispense_id', 'quantity_dispensed', 'dispense_notes']);
    $this->dispensed_date = now()->format('Y-m-d');
  }

  public function dispense(): void
  {
    DB::beginTransaction();
    try {
      $this->validate();

      $record = Prescription::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->where('status', 'pending')
        ->findOrFail($this->active_dispense_id);

      $record->update([
        'status' => 'dispensed',
        'quantity_dispensed' => $this->quantity_dispensed === '' ? null : $this->quantity_dispensed,
        'dispensed_by' => $this->officer_name,
        'dispensed_date' => $this->dispensed_date,
        'dispense_notes' => $this->dispense_notes,
      ]);

      DB::commit();
      $this->logActivity('dispense', 'Dispensed prescribed drug: ' . $record->drug_name);
      $this->clearDispense();
      toastr()->success('Prescription dispensed successfully.');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while dispensing prescription.');
      throw $e;
    }
  }

  public function cancelPending($id): void
  {
    $record = Prescription::where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->where('status', 'pending')
      ->findOrFail($id);

    $record->update([
      'status' => 'cancelled',
      'dispense_notes' => 'Cancelled by pharmacy desk.',
    ]);

    $this->logActivity('cancel', 'Cancelled pending prescription: ' . $record->drug_name);
    toastr()->success('Pending prescription cancelled.');
  }

  private function logActivity(string $action, string $description): void
  {
    try {
      Activity::create([
        'patient_id' => $this->patientId,
        'facility_id' => $this->facility_id,
        'module' => 'prescriptions',
        'action' => $action,
        'description' => $description,
        'performed_by' => $this->officer_name,
      ]);
    } catch (Exception $e) {
      // Ignore logging errors in workflow.
    }
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function render()
  {
    $pendingPrescriptions = Prescription::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->where('status', 'pending')
      ->latest('prescribed_date')
      ->latest('id')
      ->get();

    $history = Prescription::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereIn('status', ['dispensed', 'cancelled'])
      ->latest('dispensed_date')
      ->latest('id')
      ->limit(100)
      ->get();

    $activeRecord = null;
    if ($this->active_dispense_id) {
      $activeRecord = $pendingPrescriptions->firstWhere('id', (int) $this->active_dispense_id);
      if (!$activeRecord) {
        $activeRecord = Prescription::where('id', $this->active_dispense_id)->first();
      }
    }

    return view('livewire.workspaces.modules.clinical.prescriptions', [
      'pendingPrescriptions' => $pendingPrescriptions,
      'history' => $history,
      'activeRecord' => $activeRecord,
    ]);
  }
}