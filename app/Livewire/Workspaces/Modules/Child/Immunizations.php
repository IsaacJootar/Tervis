<?php

namespace App\Livewire\Workspaces\Modules\Child;

use App\Models\Activity;
use App\Models\Facility;
use App\Models\ImmunizationRecord;
use App\Models\LinkedChild;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class Immunizations extends Component
{
  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;

  public $record_id, $modal_flag = false;

  public $linked_child_id;
  public $visit_date, $month_year;
  public $immunization_card_no, $follow_up_address, $follow_up_phone, $comments;

  public $hepb0_date, $opv0_date, $bcg_date, $opv1_date, $penta1_date, $pcv1_date, $rota1_date;
  public $opv2_date, $penta2_date, $pcv2_date, $rota2_date, $ipv1_date;
  public $opv3_date, $penta3_date, $pcv3_date, $mr1_date, $yf_date;
  public $mr2_date, $mena_date, $yf2_date, $slea_date;
  public $vita1_date, $vita2_date, $ipv2_date;

  protected $rules = [
    'patientId' => 'required',
    'facility_id' => 'required|exists:facilities,id',
    'linked_child_id' => 'required|exists:linked_children,id',
    'visit_date' => 'required|date|before_or_equal:today',
    'month_year' => 'required|date',
    'immunization_card_no' => 'nullable|string|max:50',
    'follow_up_address' => 'nullable|string|max:255',
    'follow_up_phone' => 'nullable|string|max:20',
    'hepb0_date' => 'nullable|date|before_or_equal:today',
    'opv0_date' => 'nullable|date|before_or_equal:today',
    'bcg_date' => 'nullable|date|before_or_equal:today',
    'opv1_date' => 'nullable|date|before_or_equal:today',
    'penta1_date' => 'nullable|date|before_or_equal:today',
    'pcv1_date' => 'nullable|date|before_or_equal:today',
    'rota1_date' => 'nullable|date|before_or_equal:today',
    'opv2_date' => 'nullable|date|before_or_equal:today',
    'penta2_date' => 'nullable|date|before_or_equal:today',
    'pcv2_date' => 'nullable|date|before_or_equal:today',
    'rota2_date' => 'nullable|date|before_or_equal:today',
    'ipv1_date' => 'nullable|date|before_or_equal:today',
    'opv3_date' => 'nullable|date|before_or_equal:today',
    'penta3_date' => 'nullable|date|before_or_equal:today',
    'pcv3_date' => 'nullable|date|before_or_equal:today',
    'mr1_date' => 'nullable|date|before_or_equal:today',
    'yf_date' => 'nullable|date|before_or_equal:today',
    'mr2_date' => 'nullable|date|before_or_equal:today',
    'mena_date' => 'nullable|date|before_or_equal:today',
    'yf2_date' => 'nullable|date|before_or_equal:today',
    'slea_date' => 'nullable|date|before_or_equal:today',
    'vita1_date' => 'nullable|date|before_or_equal:today',
    'vita2_date' => 'nullable|date|before_or_equal:today',
    'ipv2_date' => 'nullable|date|before_or_equal:today',
    'comments' => 'nullable|string|max:2000',
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

    $this->visit_date = now()->format('Y-m-d');
    $this->autoFillMonthYear();

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
      $firstChild = $this->getLinkedChildren()->first();
      $this->linked_child_id = $firstChild?->id;
      $this->applyDerivedFromChild();
    }
  }

  private function validatePatientAccess(): void
  {
    $patient = Patient::with(['facility', 'linkedChildren'])->find($this->patientId);

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

    if (!$patient->linkedChildren()->exists()) {
      $this->hasAccess = false;
      $this->accessError = 'No linked child found. Record delivery child details first.';
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

  public function updatedLinkedChildId(): void
  {
    $this->applyDerivedFromChild();
  }

  private function applyDerivedFromChild(): void
  {
    $child = $this->currentChild();
    if (!$child) {
      return;
    }

    if (!$this->immunization_card_no) {
      $this->immunization_card_no = $child->linked_child_id;
    }

    if (!$this->follow_up_phone) {
      $this->follow_up_phone = $this->patient_phone;
    }
  }

  private function autoFillMonthYear(): void
  {
    if ($this->visit_date) {
      $this->month_year = Carbon::parse($this->visit_date)->startOfMonth()->format('Y-m-d');
    }
  }

  private function validateVaccineDatesAgainstDob(): void
  {
    $child = $this->currentChild();
    if (!$child || !$child->date_of_birth) {
      return;
    }

    $dob = $child->date_of_birth->format('Y-m-d');
    foreach (ImmunizationRecord::VACCINE_FIELDS as $field) {
      $value = $this->{$field};
      if ($value && $value < $dob) {
        throw ValidationException::withMessages([
          $field => 'Vaccine date cannot be earlier than child DOB.',
        ]);
      }
    }
  }

  private function buildSummaryMap(): array
  {
    $map = [];
    foreach (ImmunizationRecord::VACCINE_FIELDS as $field) {
      $key = str_replace('_date', '', $field);
      $map[$key] = !empty($this->{$field});
    }

    $requiredForFull = [
      'hepb0_date',
      'opv0_date',
      'bcg_date',
      'opv1_date',
      'penta1_date',
      'pcv1_date',
      'rota1_date',
      'opv2_date',
      'penta2_date',
      'pcv2_date',
      'rota2_date',
      'ipv1_date',
      'opv3_date',
      'penta3_date',
      'pcv3_date',
      'mr1_date',
      'yf_date',
      'mr2_date',
      'mena_date',
    ];

    $map['fully_immunized'] = collect($requiredForFull)->every(fn($field) => !empty($this->{$field}));
    $map['given_vaccines_count'] = collect(ImmunizationRecord::VACCINE_FIELDS)
      ->filter(fn($field) => !empty($this->{$field}))
      ->count();

    return $map;
  }

  private function payload(): array
  {
    return [
      'patient_id' => $this->patientId,
      'linked_child_id' => $this->linked_child_id,
      'facility_id' => $this->facility_id,
      'state_id' => $this->state_id,
      'lga_id' => $this->lga_id,
      'ward_id' => $this->ward_id,
      'month_year' => $this->month_year,
      'visit_date' => $this->visit_date,
      'immunization_card_no' => $this->immunization_card_no,
      'follow_up_address' => $this->follow_up_address,
      'follow_up_phone' => $this->follow_up_phone,
      'hepb0_date' => $this->hepb0_date,
      'opv0_date' => $this->opv0_date,
      'bcg_date' => $this->bcg_date,
      'opv1_date' => $this->opv1_date,
      'penta1_date' => $this->penta1_date,
      'pcv1_date' => $this->pcv1_date,
      'rota1_date' => $this->rota1_date,
      'opv2_date' => $this->opv2_date,
      'penta2_date' => $this->penta2_date,
      'pcv2_date' => $this->pcv2_date,
      'rota2_date' => $this->rota2_date,
      'ipv1_date' => $this->ipv1_date,
      'opv3_date' => $this->opv3_date,
      'penta3_date' => $this->penta3_date,
      'pcv3_date' => $this->pcv3_date,
      'mr1_date' => $this->mr1_date,
      'yf_date' => $this->yf_date,
      'mr2_date' => $this->mr2_date,
      'mena_date' => $this->mena_date,
      'yf2_date' => $this->yf2_date,
      'slea_date' => $this->slea_date,
      'vita1_date' => $this->vita1_date,
      'vita2_date' => $this->vita2_date,
      'ipv2_date' => $this->ipv2_date,
      'comments' => $this->comments,
      'summary_map' => $this->buildSummaryMap(),
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
      $this->applyDerivedFromChild();
      $this->validate();
      $this->validateVaccineDatesAgainstDob();

      ImmunizationRecord::create($this->payload());

      DB::commit();
      $this->logActivity('create', 'Recorded child immunization visit');
      toastr()->success('Immunization record saved.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      if (str_contains($e->getMessage(), 'immunization_child_visit_unique')) {
        toastr()->error('This child already has an immunization entry for the selected visit date.');
        return;
      }
      toastr()->error('Database error occurred while saving record.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while saving record.');
      throw $e;
    }
  }

  public function edit($id): void
  {
    $record = ImmunizationRecord::where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->findOrFail($id);

    $this->record_id = $record->id;
    $this->linked_child_id = $record->linked_child_id;
    $this->visit_date = $record->visit_date?->format('Y-m-d');
    $this->month_year = $record->month_year?->format('Y-m-d');
    $this->immunization_card_no = $record->immunization_card_no;
    $this->follow_up_address = $record->follow_up_address;
    $this->follow_up_phone = $record->follow_up_phone;
    $this->hepb0_date = $record->hepb0_date?->format('Y-m-d');
    $this->opv0_date = $record->opv0_date?->format('Y-m-d');
    $this->bcg_date = $record->bcg_date?->format('Y-m-d');
    $this->opv1_date = $record->opv1_date?->format('Y-m-d');
    $this->penta1_date = $record->penta1_date?->format('Y-m-d');
    $this->pcv1_date = $record->pcv1_date?->format('Y-m-d');
    $this->rota1_date = $record->rota1_date?->format('Y-m-d');
    $this->opv2_date = $record->opv2_date?->format('Y-m-d');
    $this->penta2_date = $record->penta2_date?->format('Y-m-d');
    $this->pcv2_date = $record->pcv2_date?->format('Y-m-d');
    $this->rota2_date = $record->rota2_date?->format('Y-m-d');
    $this->ipv1_date = $record->ipv1_date?->format('Y-m-d');
    $this->opv3_date = $record->opv3_date?->format('Y-m-d');
    $this->penta3_date = $record->penta3_date?->format('Y-m-d');
    $this->pcv3_date = $record->pcv3_date?->format('Y-m-d');
    $this->mr1_date = $record->mr1_date?->format('Y-m-d');
    $this->yf_date = $record->yf_date?->format('Y-m-d');
    $this->mr2_date = $record->mr2_date?->format('Y-m-d');
    $this->mena_date = $record->mena_date?->format('Y-m-d');
    $this->yf2_date = $record->yf2_date?->format('Y-m-d');
    $this->slea_date = $record->slea_date?->format('Y-m-d');
    $this->vita1_date = $record->vita1_date?->format('Y-m-d');
    $this->vita2_date = $record->vita2_date?->format('Y-m-d');
    $this->ipv2_date = $record->ipv2_date?->format('Y-m-d');
    $this->comments = $record->comments;

    $this->modal_flag = true;
    $this->dispatch('open-main-modal');
  }

  public function update(): void
  {
    DB::beginTransaction();
    try {
      $rules = array_diff_key($this->rules, ['patientId' => '']);
      $this->autoFillMonthYear();
      $this->applyDerivedFromChild();
      $this->validate($rules);
      $this->validateVaccineDatesAgainstDob();

      $record = ImmunizationRecord::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($this->record_id);

      $record->update($this->payload());

      DB::commit();
      $this->logActivity('update', 'Updated child immunization visit');
      toastr()->success('Immunization record updated.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating record.');
      throw $e;
    }
  }

  public function delete($id): void
  {
    DB::beginTransaction();
    try {
      $record = ImmunizationRecord::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($id);

      $record->delete();
      DB::commit();

      $this->logActivity('delete', 'Deleted child immunization visit');
      toastr()->success('Immunization record deleted.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting record.');
      throw $e;
    }
  }

  private function logActivity(string $action, string $description): void
  {
    try {
      Activity::create([
        'patient_id' => $this->patientId,
        'facility_id' => $this->facility_id,
        'module' => 'child_immunization',
        'action' => $action,
        'description' => $description,
        'performed_by' => $this->officer_name,
        'meta' => [
          'linked_child_id' => $this->linked_child_id,
          'visit_date' => $this->visit_date,
          'given_vaccines_count' => collect(ImmunizationRecord::VACCINE_FIELDS)
            ->filter(fn($field) => !empty($this->{$field}))
            ->count(),
        ],
      ]);
    } catch (Exception $e) {
      // Activity logging should not block workflow.
    }
  }

  private function resetForm(): void
  {
    $keepChild = $this->linked_child_id;
    $this->reset([
      'record_id',
      'visit_date',
      'month_year',
      'immunization_card_no',
      'follow_up_address',
      'follow_up_phone',
      'hepb0_date',
      'opv0_date',
      'bcg_date',
      'opv1_date',
      'penta1_date',
      'pcv1_date',
      'rota1_date',
      'opv2_date',
      'penta2_date',
      'pcv2_date',
      'rota2_date',
      'ipv1_date',
      'opv3_date',
      'penta3_date',
      'pcv3_date',
      'mr1_date',
      'yf_date',
      'mr2_date',
      'mena_date',
      'yf2_date',
      'slea_date',
      'vita1_date',
      'vita2_date',
      'ipv2_date',
      'comments',
      'modal_flag',
    ]);

    $this->linked_child_id = $keepChild;
    $this->visit_date = now()->format('Y-m-d');
    $this->autoFillMonthYear();
    $this->applyDerivedFromChild();
  }

  private function getLinkedChildren()
  {
    return LinkedChild::where('parent_patient_id', $this->patientId)
      ->where('is_active', true)
      ->orderBy('date_of_birth', 'desc')
      ->get();
  }

  private function currentChild(): ?LinkedChild
  {
    if (!$this->linked_child_id) {
      return null;
    }

    return LinkedChild::find($this->linked_child_id);
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function render()
  {
    $records = ImmunizationRecord::with('linkedChild')
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->latest('visit_date')
      ->latest('id')
      ->get();

    return view('livewire.workspaces.modules.child.immunizations', [
      'records' => $records,
      'linkedChildren' => $this->getLinkedChildren(),
      'currentChild' => $this->currentChild(),
      'hasAccess' => $this->hasAccess,
      'accessError' => $this->accessError,
    ])->layout('layouts.dataOfficerLayout');
  }

  public function placeholder()
  {
    return view('placeholder');
  }
}
