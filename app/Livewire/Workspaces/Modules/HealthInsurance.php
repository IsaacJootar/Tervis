<?php

namespace App\Livewire\Workspaces\Modules;

use App\Models\Activity;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class HealthInsurance extends Component
{
  use WithPagination;

  protected $paginationTheme = 'bootstrap';

  public $patientId;
  public $patient;

  public $patient_din;
  public $first_name;
  public $last_name;
  public $middle_name;
  public $patient_phone;
  public $patient_email;
  public $patient_dob;
  public $patient_age;
  public $patient_gender;

  public $facility_id;
  public $facility_name;
  public $facility_state;
  public $facility_lga;
  public $facility_ward;
  public $officer_name;
  public $officer_role;

  public $hasAccess = false;
  public $accessError = '';
  public $activation_time;

  // Insurance fields managed on patient profile.
  public $is_nhis_subscriber = false;
  public $nhis_number;
  public $nhis_provider;
  public $nhis_expiry_date;
  public $nhis_plan_type;
  public $nhis_principal_name;
  public $nhis_principal_number;

  public function mount($patientId)
  {
    $this->patientId = $patientId;

    $user = Auth::user();
    if (!$user || !in_array($user->role, ['Data Officer', 'Doctor', 'Medical Officer'], true)) {
      abort(403, 'Unauthorized: Access denied for this role.');
    }

    $this->facility_id = $user->facility_id;
    $this->officer_name = $user->full_name ?? ($user->first_name . ' ' . $user->last_name);
    $this->officer_role = $user->role;

    if ($this->facility_id) {
      $facility = Facility::with(['stateRelation', 'lgaRelation'])->find($this->facility_id);
      $this->facility_name = $facility?->name ?? 'Unknown Facility';
      $this->facility_state = $facility?->stateRelation?->name ?? 'N/A';
      $this->facility_lga = $facility?->lgaRelation?->name ?? 'N/A';
      $this->facility_ward = $facility?->ward ?? 'N/A';
    }

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
      $this->loadInsuranceData();
    }
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
    $this->patient_email = $this->patient->email;
    $this->patient_dob = $this->patient->date_of_birth?->format('d M Y');
    $this->patient_gender = $this->patient->gender;
    $this->patient_age = $this->patient->date_of_birth ? $this->patient->date_of_birth->age : null;
  }

  private function loadInsuranceData(): void
  {
    if (!$this->patient) {
      return;
    }

    $this->is_nhis_subscriber = (bool) $this->patient->is_nhis_subscriber;
    $this->nhis_number = $this->patient->nhis_number;
    $this->nhis_provider = $this->patient->nhis_provider;
    $this->nhis_expiry_date = $this->patient->nhis_expiry_date?->format('Y-m-d');
    $this->nhis_plan_type = $this->patient->nhis_plan_type;
    $this->nhis_principal_name = $this->patient->nhis_principal_name;
    $this->nhis_principal_number = $this->patient->nhis_principal_number;
  }

  public function updatedNhisPlanType($value): void
  {
    if (!in_array($value, ['Family', 'Corporate'], true)) {
      $this->nhis_principal_name = null;
      $this->nhis_principal_number = null;
    }
  }

  private function insuranceRules(): array
  {
    $rules = [
      'is_nhis_subscriber' => 'boolean',
    ];

    if ($this->is_nhis_subscriber) {
      $rules['nhis_number'] = 'required|string|max:50';
      $rules['nhis_provider'] = 'required|string|max:255';
      $rules['nhis_expiry_date'] = 'required|date';
      $rules['nhis_plan_type'] = 'required|in:Individual,Family,Corporate';

      if (in_array($this->nhis_plan_type, ['Family', 'Corporate'], true)) {
        $rules['nhis_principal_name'] = 'required|string|max:255';
        $rules['nhis_principal_number'] = 'required|string|max:50';
      }
    }

    return $rules;
  }

  private function patientInsuranceSnapshot(Patient $patient): array
  {
    return [
      'is_nhis_subscriber' => (bool) $patient->is_nhis_subscriber,
      'nhis_number' => $patient->nhis_number,
      'nhis_provider' => $patient->nhis_provider,
      'nhis_expiry_date' => $patient->nhis_expiry_date?->format('Y-m-d'),
      'nhis_plan_type' => $patient->nhis_plan_type,
      'nhis_principal_name' => $patient->nhis_principal_name,
      'nhis_principal_number' => $patient->nhis_principal_number,
    ];
  }

  public function saveInsurance(): void
  {
    if (!$this->hasAccess || !$this->patient) {
      toastr()->error('No access to update insurance.');
      return;
    }

    DB::beginTransaction();
    try {
      $this->validate($this->insuranceRules());

      $before = $this->patientInsuranceSnapshot($this->patient);

      $payload = [
        'is_nhis_subscriber' => (bool) $this->is_nhis_subscriber,
      ];

      if ($payload['is_nhis_subscriber']) {
        $payload['nhis_number'] = $this->nhis_number;
        $payload['nhis_provider'] = $this->nhis_provider;
        $payload['nhis_expiry_date'] = $this->nhis_expiry_date;
        $payload['nhis_plan_type'] = $this->nhis_plan_type;
        $payload['nhis_principal_name'] = in_array($this->nhis_plan_type, ['Family', 'Corporate'], true)
          ? $this->nhis_principal_name
          : null;
        $payload['nhis_principal_number'] = in_array($this->nhis_plan_type, ['Family', 'Corporate'], true)
          ? $this->nhis_principal_number
          : null;
      } else {
        $payload['nhis_number'] = null;
        $payload['nhis_provider'] = null;
        $payload['nhis_expiry_date'] = null;
        $payload['nhis_plan_type'] = null;
        $payload['nhis_principal_name'] = null;
        $payload['nhis_principal_number'] = null;
      }

      $this->patient->update($payload);
      $this->patient->refresh();

      $after = $this->patientInsuranceSnapshot($this->patient);

      $action = 'update';
      if (!$before['is_nhis_subscriber'] && $after['is_nhis_subscriber']) {
        $action = 'activate';
      } elseif ($before['is_nhis_subscriber'] && !$after['is_nhis_subscriber']) {
        $action = 'deactivate';
      }

      $this->logInsuranceActivity($action, $before, $after);

      DB::commit();

      $this->loadPatientData();
      $this->loadInsuranceData();
      $this->resetPage('history_page');
      toastr()->success('Health insurance details saved.');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
      throw $e;
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while saving insurance details.');
      throw $e;
    }
  }

  public function deactivateCoverage(): void
  {
    if (!$this->is_nhis_subscriber) {
      toastr()->info('Patient is already marked as non-subscriber.');
      return;
    }

    $this->is_nhis_subscriber = false;
    $this->nhis_number = null;
    $this->nhis_provider = null;
    $this->nhis_expiry_date = null;
    $this->nhis_plan_type = null;
    $this->nhis_principal_name = null;
    $this->nhis_principal_number = null;

    $this->saveInsurance();
  }

  private function logInsuranceActivity(string $action, array $before, array $after): void
  {
    $description = match ($action) {
      'activate' => 'Activated NHIS coverage.',
      'deactivate' => 'Deactivated NHIS coverage.',
      default => 'Updated NHIS coverage details.',
    };

    Activity::query()->create([
      'patient_id' => $this->patientId,
      'facility_id' => $this->facility_id,
      'module' => 'health_insurance',
      'action' => $action,
      'description' => $description,
      'performed_by' => $this->officer_name,
      'meta' => [
        'before' => $before,
        'after' => $after,
      ],
    ]);
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function placeholder()
  {
    return view('placeholder');
  }

  public function render()
  {
    $historyQuery = Activity::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->where('module', 'health_insurance');

    $summary = [
      'total_changes' => (clone $historyQuery)->count(),
      'activations' => (clone $historyQuery)->where('action', 'activate')->count(),
      'updates' => (clone $historyQuery)->where('action', 'update')->count(),
      'deactivations' => (clone $historyQuery)->where('action', 'deactivate')->count(),
    ];

    $history = (clone $historyQuery)
      ->latest('id')
      ->paginate(10, ['*'], 'history_page');

    return view('livewire.workspaces.modules.health-insurance.index', [
      'summary' => $summary,
      'history' => $history,
    ]);
  }
}
