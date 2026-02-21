<?php

namespace App\Livewire\Workspaces\Modules\Child;

use App\Models\Activity;
use App\Models\Facility;
use App\Models\Lga;
use App\Models\LinkedChild;
use App\Models\NutritionRecord;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use App\Models\State;
use App\Models\Ward;
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
class Nutrition extends Component
{
  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;

  public $record_id, $modal_flag = false;

  public $linked_child_id;
  public $age_group;
  public $visit_date, $month_year;
  public $infant_feeding, $complementary_feeding;
  public $counselling_topics = [];
  public $support_group_referred = false;
  public $height_cm, $weight_kg, $oedema;
  public $muac_value_mm, $muac_class, $growth_status;
  public $supplementary_feeding_groups = [];
  public $mnp_given = false;
  public $otp_provider, $admission_status, $outcome_status;
  public $remarks;

  protected $rules = [
    'patientId' => 'required',
    'facility_id' => 'required|exists:facilities,id',
    'linked_child_id' => 'required|exists:linked_children,id',
    'visit_date' => 'required|date|before_or_equal:today',
    'month_year' => 'required|date',
    'age_group' => 'required|in:0-5 months,6-23 months,24-59 months',
    'infant_feeding' => 'nullable|in:Exclusive BF,BF + Water,BF with other foods,Not BF',
    'complementary_feeding' => 'nullable|in:BF + Other foods,Other foods only,Not started CF',
    'counselling_topics' => 'nullable|array',
    'support_group_referred' => 'boolean',
    'height_cm' => 'nullable|numeric|min:25|max:250',
    'weight_kg' => 'nullable|numeric|min:1|max:60',
    'oedema' => 'nullable|in:0,+,++,+++',
    'muac_value_mm' => 'nullable|integer|min:80|max:250',
    'muac_class' => 'nullable|in:Red,Yellow,Green',
    'growth_status' => 'nullable|in:Growing Well,Not Growing Well',
    'supplementary_feeding_groups' => 'nullable|array',
    'mnp_given' => 'boolean',
    'otp_provider' => 'nullable|in:Self,HH,Not Providing OTP,Community Volunteer/CHIPS',
    'admission_status' => 'nullable|in:Admitted HP OTP,Transferred in from another OTP/SC,Referred to SC,Does not meet OTP Admission Criteria',
    'outcome_status' => 'nullable|in:Transferred out to another OTP/SC,Recovered,Defaulted,Died,Non-recovered',
    'remarks' => 'nullable|string|max:2000',
  ];

  public function mount($patientId)
  {
    $this->patientId = $patientId;
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

  public function updatedMuacValueMm(): void
  {
    $this->muac_class = $this->deriveMuacClass($this->muac_value_mm);
  }

  private function applyDerivedFromChild(): void
  {
    $child = $this->currentChild();
    if (!$child) {
      return;
    }

    $this->age_group = $this->deriveAgeGroup($child->date_of_birth);
  }

  private function autoFillMonthYear(): void
  {
    if ($this->visit_date) {
      $this->month_year = Carbon::parse($this->visit_date)->startOfMonth()->format('Y-m-d');
    }
  }

  private function deriveAgeGroup($dob): ?string
  {
    if (!$dob) {
      return null;
    }

    $months = Carbon::parse($dob)->diffInMonths(now());
    if ($months <= 5) {
      return '0-5 months';
    }
    if ($months <= 23) {
      return '6-23 months';
    }
    if ($months <= 59) {
      return '24-59 months';
    }
    return null;
  }

  private function deriveMuacClass($muac): ?string
  {
    if (!$muac) {
      return null;
    }
    if ($muac < 115) {
      return 'Red';
    }
    if ($muac <= 124) {
      return 'Yellow';
    }
    return 'Green';
  }

  private function normalizeAgeFeedingRules(): void
  {
    if ($this->age_group === '0-5 months') {
      $this->complementary_feeding = null;
      $this->supplementary_feeding_groups = [];
    }

    if (in_array($this->age_group, ['6-23 months', '24-59 months'], true)) {
      $this->infant_feeding = null;
    }
  }

  private function buildSummaryMap(): array
  {
    // Monthly summary anchors (Child Health & Nutrition section).
    return [
      'exclusive_breastfeeding' => $this->age_group === '0-5 months' && $this->infant_feeding === 'Exclusive BF',
      'sam_admissions' => $this->muac_class === 'Red' && $this->admission_status === 'Admitted HP OTP',
      'mam_cases' => $this->muac_class === 'Yellow',
      'mnp_given' => (bool) $this->mnp_given,
      'support_group_referred' => (bool) $this->support_group_referred,
      'not_growing_well' => $this->growth_status === 'Not Growing Well',
    ];
  }

  public function store(): void
  {
    DB::beginTransaction();
    try {
      $this->autoFillMonthYear();
      $this->applyDerivedFromChild();
      $this->muac_class = $this->deriveMuacClass($this->muac_value_mm);
      $this->normalizeAgeFeedingRules();
      $this->validate();

      NutritionRecord::create([
        'patient_id' => $this->patientId,
        'linked_child_id' => $this->linked_child_id,
        'facility_id' => $this->facility_id,
        'state_id' => $this->state_id,
        'lga_id' => $this->lga_id,
        'ward_id' => $this->ward_id,
        'month_year' => $this->month_year,
        'visit_date' => $this->visit_date,
        'age_group' => $this->age_group,
        'infant_feeding' => $this->infant_feeding,
        'complementary_feeding' => $this->complementary_feeding,
        'counselling_topics' => $this->counselling_topics,
        'support_group_referred' => $this->support_group_referred,
        'height_cm' => $this->height_cm,
        'weight_kg' => $this->weight_kg,
        'oedema' => $this->oedema,
        'muac_value_mm' => $this->muac_value_mm,
        'muac_class' => $this->muac_class,
        'growth_status' => $this->growth_status,
        'supplementary_feeding_groups' => $this->supplementary_feeding_groups,
        'mnp_given' => $this->mnp_given,
        'otp_provider' => $this->otp_provider,
        'admission_status' => $this->admission_status,
        'outcome_status' => $this->outcome_status,
        'remarks' => $this->remarks,
        'summary_map' => $this->buildSummaryMap(),
        'officer_name' => $this->officer_name,
        'officer_role' => $this->officer_role,
        'officer_designation' => $this->officer_designation,
      ]);

      DB::commit();
      $this->logActivity('create', 'Recorded child nutrition assessment');
      toastr()->success('Nutrition record saved.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      if (str_contains($e->getMessage(), 'nutrition_child_visit_unique')) {
        toastr()->error('This child already has a nutrition record for the selected visit date.');
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
    $record = NutritionRecord::where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->findOrFail($id);

    $this->record_id = $record->id;
    $this->linked_child_id = $record->linked_child_id;
    $this->visit_date = $record->visit_date?->format('Y-m-d');
    $this->month_year = $record->month_year?->format('Y-m-d');
    $this->age_group = $record->age_group;
    $this->infant_feeding = $record->infant_feeding;
    $this->complementary_feeding = $record->complementary_feeding;
    $this->counselling_topics = $record->counselling_topics ?? [];
    $this->support_group_referred = (bool) $record->support_group_referred;
    $this->height_cm = $record->height_cm;
    $this->weight_kg = $record->weight_kg;
    $this->oedema = $record->oedema;
    $this->muac_value_mm = $record->muac_value_mm;
    $this->muac_class = $record->muac_class;
    $this->growth_status = $record->growth_status;
    $this->supplementary_feeding_groups = $record->supplementary_feeding_groups ?? [];
    $this->mnp_given = (bool) $record->mnp_given;
    $this->otp_provider = $record->otp_provider;
    $this->admission_status = $record->admission_status;
    $this->outcome_status = $record->outcome_status;
    $this->remarks = $record->remarks;

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
      $this->muac_class = $this->deriveMuacClass($this->muac_value_mm);
      $this->normalizeAgeFeedingRules();
      $this->validate($rules);

      $record = NutritionRecord::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($this->record_id);

      $record->update([
        'linked_child_id' => $this->linked_child_id,
        'month_year' => $this->month_year,
        'visit_date' => $this->visit_date,
        'age_group' => $this->age_group,
        'infant_feeding' => $this->infant_feeding,
        'complementary_feeding' => $this->complementary_feeding,
        'counselling_topics' => $this->counselling_topics,
        'support_group_referred' => $this->support_group_referred,
        'height_cm' => $this->height_cm,
        'weight_kg' => $this->weight_kg,
        'oedema' => $this->oedema,
        'muac_value_mm' => $this->muac_value_mm,
        'muac_class' => $this->muac_class,
        'growth_status' => $this->growth_status,
        'supplementary_feeding_groups' => $this->supplementary_feeding_groups,
        'mnp_given' => $this->mnp_given,
        'otp_provider' => $this->otp_provider,
        'admission_status' => $this->admission_status,
        'outcome_status' => $this->outcome_status,
        'remarks' => $this->remarks,
        'summary_map' => $this->buildSummaryMap(),
        'officer_name' => $this->officer_name,
        'officer_role' => $this->officer_role,
        'officer_designation' => $this->officer_designation,
      ]);

      DB::commit();
      $this->logActivity('update', 'Updated child nutrition assessment');
      toastr()->success('Nutrition record updated.');
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
      $record = NutritionRecord::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($id);

      $record->delete();
      DB::commit();

      $this->logActivity('delete', 'Deleted child nutrition assessment');
      toastr()->success('Nutrition record deleted.');
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
        'module' => 'child_nutrition',
        'action' => $action,
        'description' => $description,
        'performed_by' => $this->officer_name,
        'meta' => [
          'linked_child_id' => $this->linked_child_id,
          'visit_date' => $this->visit_date,
          'muac_class' => $this->muac_class,
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
      'age_group',
      'visit_date',
      'month_year',
      'infant_feeding',
      'complementary_feeding',
      'counselling_topics',
      'support_group_referred',
      'height_cm',
      'weight_kg',
      'oedema',
      'muac_value_mm',
      'muac_class',
      'growth_status',
      'supplementary_feeding_groups',
      'mnp_given',
      'otp_provider',
      'admission_status',
      'outcome_status',
      'remarks',
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
    $records = NutritionRecord::with('linkedChild')
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->latest('visit_date')
      ->latest('id')
      ->get();

    return view('livewire.workspaces.modules.child.nutrition', [
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
