<?php

namespace App\Livewire\Workspaces\Modules\Clinical;

use App\Models\Activity;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Referral;
use App\Models\Registrations\DinActivation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dataOfficerLayout')]
class Referrals extends Component
{
  public const SERVICE_OPTIONS = [
    'svc1' => 'Adherence counselling & treatment support',
    'svc2' => 'Antiretroviral therapy',
    'svc3' => 'Child care',
    'svc4' => 'Clinical care',
    'svc5' => 'Education / schooling',
    'svc6' => 'Family planning',
    'svc7' => 'Financial, material & microfinance',
    'svc8' => 'Food support & services',
    'svc9' => 'Shelter',
    'svc10' => 'HIV counselling and testing',
    'svc11' => 'Home-based care',
    'svc12' => 'Legal support',
    'svc13' => 'Nutrition counselling',
    'svc14' => 'OB GYN services',
    'svc15' => 'Fistula repair',
    'svc16' => 'PEP services',
    'svc17' => 'Pharmacy',
    'svc18' => 'PLHA support',
    'svc19' => 'PITCT services',
    'svc20' => 'Prevention services (peer counselling)',
    'svc21' => 'Psychosocial support',
    'svc22' => 'Social services',
    'svc23' => 'Spiritual support',
    'svc24' => 'STI services',
    'svc25' => 'TB services',
    'svc26' => 'Others',
  ];

  public const TRANSPORT_OPTIONS = [
    'ambulance' => 'Ambulance',
    'ets' => 'ETS - Emergency Transport Scheme',
    'others' => 'Others',
  ];

  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;

  public $record_id;
  public $referral_date, $month_year;
  public $referred_from, $referred_to, $requested_service_code;
  public $services_selected = [], $services_other;
  public $service_provided, $date_completed, $follow_up_needed;
  public $transport_mode, $time_in, $time_out;
  public $completed_by, $completed_designation, $completed_date;
  public $focal_person, $focal_date;

  protected $rules = [
    'patientId' => 'required',
    'facility_id' => 'required|exists:facilities,id',
    'referral_date' => 'required|date',
    'month_year' => 'required|date',
    'referred_from' => 'nullable|string|max:255',
    'referred_to' => 'nullable|string|max:255',
    'requested_service_code' => 'nullable|string|max:120',
    'services_selected' => 'nullable|array',
    'services_selected.*' => 'string|in:svc1,svc2,svc3,svc4,svc5,svc6,svc7,svc8,svc9,svc10,svc11,svc12,svc13,svc14,svc15,svc16,svc17,svc18,svc19,svc20,svc21,svc22,svc23,svc24,svc25,svc26',
    'services_other' => 'nullable|string|max:500',
    'service_provided' => 'nullable|in:Yes,No',
    'date_completed' => 'nullable|date',
    'follow_up_needed' => 'nullable|in:Yes,No',
    'transport_mode' => 'nullable|in:ambulance,ets,others',
    'time_in' => 'nullable|date_format:H:i',
    'time_out' => 'nullable|date_format:H:i',
    'completed_by' => 'nullable|string|max:120',
    'completed_designation' => 'nullable|string|max:120',
    'completed_date' => 'nullable|date',
    'focal_person' => 'nullable|string|max:120',
    'focal_date' => 'nullable|date',
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

  public function updatedReferralDate(): void
  {
    $this->autoFillMonthYear();
  }

  private function autoFillMonthYear(): void
  {
    if ($this->referral_date) {
      $this->month_year = Carbon::parse($this->referral_date)->startOfMonth()->format('Y-m-d');
    }
  }

  private function payload(): array
  {
    $selectedServices = collect((array) $this->services_selected)
      ->filter(fn($value) => isset(self::SERVICE_OPTIONS[$value]))
      ->values()
      ->toArray();

    return [
      'patient_id' => $this->patientId,
      'facility_id' => $this->facility_id,
      'state_id' => $this->state_id,
      'lga_id' => $this->lga_id,
      'ward_id' => $this->ward_id,
      'month_year' => $this->month_year,
      'referral_date' => $this->referral_date,
      'referred_from' => $this->referred_from,
      'referred_to' => $this->referred_to,
      'requested_service_code' => $this->requested_service_code,
      'services_selected' => $selectedServices,
      'services_other' => $this->services_other,
      'service_provided' => $this->service_provided,
      'date_completed' => $this->date_completed,
      'follow_up_needed' => $this->follow_up_needed,
      'transport_mode' => $this->transport_mode,
      'time_in' => $this->time_in,
      'time_out' => $this->time_out,
      'completed_by' => $this->completed_by,
      'completed_designation' => $this->completed_designation,
      'completed_date' => $this->completed_date,
      'focal_person' => $this->focal_person,
      'focal_date' => $this->focal_date,
      'summary_map' => [
        'services_count' => count($selectedServices),
        'has_follow_up' => $this->follow_up_needed === 'Yes',
      ],
      'officer_name' => $this->officer_name,
      'officer_role' => $this->officer_role,
      'officer_designation' => $this->officer_designation,
    ];
  }

  public function store(): void
  {
    DB::beginTransaction();
    try {
      $this->autoFillMonthYear();
      $this->validate();

      $record = Referral::create($this->payload());

      DB::commit();
      $this->logActivity('create', 'Recorded referral form entry');
      $this->edit($record->id);
      toastr()->success('Referral record saved.');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while saving referral record.');
      throw $e;
    }
  }

  public function edit($id): void
  {
    $record = Referral::where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->findOrFail($id);

    $this->record_id = $record->id;
    $this->referral_date = $record->referral_date?->format('Y-m-d');
    $this->month_year = $record->month_year?->format('Y-m-d');
    $this->referred_from = $record->referred_from;
    $this->referred_to = $record->referred_to;
    $this->requested_service_code = $record->requested_service_code;
    $this->services_selected = (array) ($record->services_selected ?? []);
    $this->services_other = $record->services_other;
    $this->service_provided = $record->service_provided;
    $this->date_completed = $record->date_completed?->format('Y-m-d');
    $this->follow_up_needed = $record->follow_up_needed;
    $this->transport_mode = $record->transport_mode;
    $this->time_in = $record->time_in ? substr((string) $record->time_in, 0, 5) : null;
    $this->time_out = $record->time_out ? substr((string) $record->time_out, 0, 5) : null;
    $this->completed_by = $record->completed_by;
    $this->completed_designation = $record->completed_designation;
    $this->completed_date = $record->completed_date?->format('Y-m-d');
    $this->focal_person = $record->focal_person;
    $this->focal_date = $record->focal_date?->format('Y-m-d');
  }

  public function update(): void
  {
    DB::beginTransaction();
    try {
      $rules = array_diff_key($this->rules, ['patientId' => '']);
      $this->autoFillMonthYear();
      $this->validate($rules);

      $record = Referral::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($this->record_id);

      $record->update($this->payload());

      DB::commit();
      $this->logActivity('update', 'Updated referral form entry');
      $this->edit($record->id);
      toastr()->success('Referral record updated.');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating referral record.');
      throw $e;
    }
  }

  public function delete($id): void
  {
    DB::beginTransaction();
    try {
      $record = Referral::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($id);

      $record->delete();

      DB::commit();
      $this->logActivity('delete', 'Deleted referral record');
      if ($this->record_id === (int) $id) {
        $this->openCreate();
      }
      toastr()->success('Referral record deleted.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting referral record.');
      throw $e;
    }
  }

  private function logActivity(string $action, string $description): void
  {
    try {
      Activity::create([
        'patient_id' => $this->patientId,
        'facility_id' => $this->facility_id,
        'module' => 'referrals',
        'action' => $action,
        'description' => $description,
        'performed_by' => $this->officer_name,
        'meta' => [
          'referral_date' => $this->referral_date,
          'referred_to' => $this->referred_to,
          'service_provided' => $this->service_provided,
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
      'referral_date',
      'month_year',
      'referred_from',
      'referred_to',
      'requested_service_code',
      'services_selected',
      'services_other',
      'service_provided',
      'date_completed',
      'follow_up_needed',
      'transport_mode',
      'time_in',
      'time_out',
      'completed_by',
      'completed_designation',
      'completed_date',
      'focal_person',
      'focal_date',
    ]);

    $this->referral_date = now()->format('Y-m-d');
    $this->autoFillMonthYear();
    $this->referred_from = $this->facility_name;
    $this->completed_by = $this->officer_name;
    $this->completed_designation = $this->officer_designation;
    $this->completed_date = now()->format('Y-m-d');
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function render()
  {
    $records = Referral::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->latest('referral_date')
      ->latest('id')
      ->get();

    return view('livewire.workspaces.modules.clinical.referrals', [
      'records' => $records,
      'serviceOptions' => self::SERVICE_OPTIONS,
      'transportOptions' => self::TRANSPORT_OPTIONS,
    ]);
  }
}
