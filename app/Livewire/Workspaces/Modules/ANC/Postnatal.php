<?php

namespace App\Livewire\Workspaces\Modules\ANC;

use Exception;
use Carbon\Carbon;
use App\Models\Lga;
use App\Models\Ward;
use App\Models\State;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Delivery;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use App\Models\Registrations\DinActivation;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class Postnatal extends Component
{
  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;
  public $month_year, $visit_date, $delivery_date, $days_postpartum, $age_range, $parity_count;
  public $attendance, $associated_problems, $mother_days, $child_days, $child_sex, $nutrition_counseling;
  public $breast_examination, $breastfeeding_status, $family_planning, $female_genital_mutilation, $vaginal_examination;
  public $packed_cell_volume, $urine_test_results, $newborn_care, $kangaroo_mother_care, $visit_outcome;
  public $systolic_bp, $diastolic_bp, $newborn_weight;
  public $lmp, $edd, $pregnancy_number;
  public $post_id, $modal_flag = false;
  public $states, $lgas, $wards;

  protected $rules = [
    'patientId' => 'required',
    'facility_id' => 'required|exists:facilities,id',
    'state_id' => 'required|exists:states,id',
    'lga_id' => 'nullable|exists:lgas,id',
    'ward_id' => 'nullable|exists:wards,id',
    'month_year' => 'required|date',
    'visit_date' => 'required|date',
    'delivery_date' => 'required|date|before_or_equal:visit_date',
    'days_postpartum' => 'nullable|integer|min:0|max:365',
    'age_range' => 'nullable|in:11 - 14 years,15 - 19 years,20 - 24 years,25 - 29 years,30 - 34 years,35 - 49 years,50 + years',
    'parity_count' => 'nullable|integer|min:0|max:20',
    'attendance' => 'nullable|in:1st Visit,2nd Visit,3rd Visit,Other',
    'associated_problems' => 'nullable|string|max:1000',
    'mother_days' => 'nullable|integer|min:0|max:365',
    'child_days' => 'nullable|integer|min:0|max:365',
    'child_sex' => 'nullable|in:Male,Female',
    'nutrition_counseling' => 'nullable|in:Yes,No,Counseled',
    'breast_examination' => 'nullable|in:Normal,Abnormal,Not Done',
    'breastfeeding_status' => 'nullable|in:Exclusive,Mixed,Not Breastfeeding',
    'family_planning' => 'nullable|in:Counseled,Accepted,Declined',
    'female_genital_mutilation' => 'nullable|in:Yes,No,Suspected',
    'vaginal_examination' => 'nullable|in:Normal,Abnormal,Not Done',
    'packed_cell_volume' => 'nullable|string|max:50',
    'urine_test_results' => 'nullable|string|max:255',
    'newborn_care' => 'nullable|in:Provided,Not Provided,Referred',
    'kangaroo_mother_care' => 'nullable|in:Yes,No,Not Applicable',
    'visit_outcome' => 'nullable|in:Stable,Referred,Admitted,Discharged',
    'systolic_bp' => 'nullable|integer|min:50|max:250',
    'diastolic_bp' => 'nullable|integer|min:30|max:150',
    'newborn_weight' => 'nullable|numeric|min:0.5|max:6.0',
    'officer_name' => 'required|string|max:255',
    'officer_role' => 'required|string|max:255',
    'officer_designation' => 'required|string|max:255',
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

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
      $this->autoFillMonthYear();
      $this->autoPopulateFromLatestDelivery();
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

  public function updatedVisitDate($value)
  {
    $this->autoFillMonthYear();
    $this->recalculatePostnatalDays();
  }

  public function updatedDeliveryDate($value)
  {
    $this->recalculatePostnatalDays();
  }

  private function validatePatientAccess()
  {
    $patient = Patient::with(['facility', 'activeAntenatalRegistration'])->find($this->patientId);

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

    if (!$patient->activeAntenatalRegistration) {
      $this->hasAccess = false;
      $this->accessError = 'Patient has no active pregnancy. Please register for ANC first.';
      return;
    }

    $this->hasAccess = true;
    $this->patient = $patient;
    $this->activation_time = Carbon::parse($todaysActivation->check_in_time)->format('h:i A');
  }

  private function loadPatientData()
  {
    if (!$this->patient) return;

    $this->patient_din = $this->patient->din;
    $this->first_name = $this->patient->first_name;
    $this->last_name = $this->patient->last_name;
    $this->middle_name = $this->patient->middle_name;
    $this->patient_phone = $this->patient->phone;
    $this->patient_dob = $this->patient->date_of_birth?->format('d M Y');
    $this->patient_gender = $this->patient->gender;
    $this->patient_age = $this->patient->date_of_birth ? $this->patient->date_of_birth->age : null;
    $this->age_range = $this->patient->age_group ?? $this->getAgeRangeFromAge($this->patient_age);
    $activeAnc = $this->patient->activeAntenatalRegistration;
    if ($activeAnc) {
      $this->lmp = $activeAnc->lmp?->format('Y-m-d');
      $this->edd = $activeAnc->edd?->format('Y-m-d');
      $this->pregnancy_number = $activeAnc->pregnancy_number;
    }
    $this->visit_date = Carbon::now()->format('Y-m-d');
  }

  public function store()
  {
    DB::beginTransaction();
    try {
      $this->autoFillMonthYear();
      $this->autoPopulateFromLatestDelivery();
      $this->recalculatePostnatalDays();
      if (!$this->age_range) {
        $this->age_range = $this->getAgeRangeFromAge($this->patient_age);
      }
      $this->validate();

      $existingRecord = PostnatalRecord::where('patient_id', $this->patientId)
        ->whereDate('visit_date', Carbon::parse($this->visit_date)->format('Y-m-d'))
        ->first();

      if ($existingRecord) {
        DB::rollBack();
        toastr()->error('Postnatal record for this patient on this date already exists.');
        return;
      }

      $data = array_diff_key($this->all(), [
        'patientId' => '',
        'patient' => '',
        'patient_din' => '',
        'middle_name' => '',
        'patient_phone' => '',
        'patient_dob' => '',
        'patient_age' => '',
        'patient_gender' => '',
        'state_name' => '',
        'lga_name' => '',
        'ward_name' => '',
        'facility_name' => '',
        'states' => '',
        'lgas' => '',
        'wards' => '',
        'hasAccess' => '',
        'accessError' => '',
        'activation_time' => ''
      ]);

      $data['patient_id'] = $this->patientId;
      PostnatalRecord::create($data);

      DB::commit();
      toastr()->success('Postnatal record created successfully.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      toastr()->error('Database error occurred while creating the postnatal record.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while creating the postnatal record.');
      throw $e;
    }
  }

  public function edit($id)
  {
    DB::beginTransaction();
    try {
      $post = PostnatalRecord::findOrFail($id);
      $this->post_id = $id;
      $this->first_name = $this->patient->first_name ?? 'N/A';
      $this->last_name = $this->patient->last_name ?? 'N/A';
      $this->facility_id = $post->facility_id;
      $this->facility_name = Facility::find($post->facility_id)->name ?? 'N/A';
      $this->state_id = $post->state_id;
      $this->state_name = State::find($post->state_id)->name ?? 'N/A';
      $this->lga_id = $post->lga_id;
      $this->lga_name = Lga::find($post->lga_id)->name ?? 'N/A';
      $this->ward_id = $post->ward_id;
      $this->ward_name = Ward::find($post->ward_id)->name ?? 'N/A';
      $this->month_year = $post->month_year ? Carbon::parse($post->month_year)->format('Y-m-d') : null;
      $this->visit_date = $post->visit_date ? Carbon::parse($post->visit_date)->format('Y-m-d') : null;
      $this->delivery_date = $post->delivery_date ? Carbon::parse($post->delivery_date)->format('Y-m-d') : null;
      $this->days_postpartum = $post->days_postpartum;
      $this->age_range = $post->age_range ?: $this->getAgeRangeFromAge($this->patient_age);
      $this->parity_count = $post->parity_count;
      $this->attendance = $post->attendance;
      $this->associated_problems = $post->associated_problems;
      $this->mother_days = $post->mother_days;
      $this->child_days = $post->child_days;
      $this->child_sex = $post->child_sex;
      $this->nutrition_counseling = $post->nutrition_counseling;
      $this->breast_examination = $post->breast_examination;
      $this->breastfeeding_status = $post->breastfeeding_status;
      $this->family_planning = $post->family_planning;
      $this->female_genital_mutilation = $post->female_genital_mutilation;
      $this->vaginal_examination = $post->vaginal_examination;
      $this->packed_cell_volume = $post->packed_cell_volume;
      $this->urine_test_results = $post->urine_test_results;
      $this->newborn_care = $post->newborn_care;
      $this->kangaroo_mother_care = $post->kangaroo_mother_care;
      $this->visit_outcome = $post->visit_outcome;
      $this->systolic_bp = $post->systolic_bp;
      $this->diastolic_bp = $post->diastolic_bp;
      $this->newborn_weight = $post->newborn_weight;
      $this->officer_name = $post->officer_name;
      $this->officer_role = $post->officer_role;
      $this->officer_designation = $post->officer_designation;
      $this->modal_flag = true;
      $this->dispatch('open-main-modal');

      DB::commit();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while loading the postnatal record.');
      throw $e;
    }
  }

  public function update()
  {
    DB::beginTransaction();
    try {
      $rules = array_diff_key($this->rules, [
        'patientId' => '',
      ]);
      $this->autoFillMonthYear();
      $this->autoPopulateFromLatestDelivery();
      $this->recalculatePostnatalDays();
      if (!$this->age_range) {
        $this->age_range = $this->getAgeRangeFromAge($this->patient_age);
      }
      $this->validate($rules);

      $post = PostnatalRecord::findOrFail($this->post_id);
      $post->update(array_diff_key($this->all(), [
        'patientId' => '',
        'patient' => '',
        'patient_din' => '',
        'middle_name' => '',
        'patient_phone' => '',
        'patient_dob' => '',
        'patient_age' => '',
        'patient_gender' => '',
        'state_name' => '',
        'lga_name' => '',
        'ward_name' => '',
        'facility_name' => '',
        'states' => '',
        'lgas' => '',
        'wards' => '',
        'hasAccess' => '',
        'accessError' => '',
        'activation_time' => ''
      ]));

      DB::commit();
      toastr()->success('Postnatal record updated successfully.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      toastr()->error('Database error while updating the postnatal record.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the postnatal record.');
      throw $e;
    }
  }

  public function delete($id)
  {
    DB::beginTransaction();
    try {
      $post = PostnatalRecord::findOrFail($id);
      $post->delete();

      DB::commit();
      toastr()->success('Postnatal record deleted successfully.');
      $this->resetForm();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting the postnatal record.');
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
      'first_name',
      'last_name',
      'age_range',
      'month_year',
      'visit_date',
      'delivery_date',
      'days_postpartum',
      'parity_count',
      'attendance',
      'associated_problems',
      'mother_days',
      'child_days',
      'child_sex',
      'nutrition_counseling',
      'breast_examination',
      'breastfeeding_status',
      'family_planning',
      'female_genital_mutilation',
      'vaginal_examination',
      'packed_cell_volume',
      'urine_test_results',
      'newborn_care',
      'kangaroo_mother_care',
      'visit_outcome',
      'systolic_bp',
      'diastolic_bp',
      'newborn_weight',
      'post_id',
      'modal_flag',
    ]);
    $this->mount($this->patientId);
  }

  private function autoPopulateFromLatestDelivery(): void
  {
    $delivery = Delivery::where('patient_id', $this->patientId)
      ->orderBy('dodel', 'desc')
      ->first();

    if (!$delivery) return;

    if (empty($this->delivery_date) && $delivery->dodel) {
      $this->delivery_date = Carbon::parse($delivery->dodel)->format('Y-m-d');
    }
    if (empty($this->child_sex) && $delivery->baby_sex) {
      $this->child_sex = $delivery->baby_sex;
    }
  }

  private function autoFillMonthYear(): void
  {
    if ($this->visit_date) {
      try {
        $this->month_year = Carbon::parse($this->visit_date)->startOfMonth()->format('Y-m-d');
      } catch (Exception $e) {
        // ignore invalid date
      }
    }
  }

  private function getAgeRangeFromAge(?int $age): ?string
  {
    if ($age === null) return null;
    if ($age < 11) return '11 - 14 years';
    if ($age >= 11 && $age <= 14) return '11 - 14 years';
    if ($age >= 15 && $age <= 19) return '15 - 19 years';
    if ($age >= 20 && $age <= 24) return '20 - 24 years';
    if ($age >= 25 && $age <= 29) return '25 - 29 years';
    if ($age >= 30 && $age <= 34) return '30 - 34 years';
    if ($age >= 35 && $age <= 49) return '35 - 49 years';
    if ($age >= 50) return '50 + years';
    return null;
  }

  private function recalculatePostnatalDays(): void
  {
    if (!$this->delivery_date || !$this->visit_date) {
      return;
    }

    try {
      $delivery = Carbon::parse($this->delivery_date);
      $visit = Carbon::parse($this->visit_date);
      $days = $delivery->diffInDays($visit, false);
      if ($days < 0) {
        return;
      }
      $this->days_postpartum = $days;
      $this->mother_days = $days;
      $this->child_days = $days;
    } catch (Exception $e) {
      // Skip auto-calc if date parsing fails
    }
  }

  public function render()
  {
    $posts = PostnatalRecord::with('patient', 'facility')
      ->where('patient_id', $this->patientId)
      ->latest()
      ->get();
    return view('livewire.workspaces.modules.anc.postnatal', [
      'posts' => $posts,
      'states' => $this->states,
      'lgas' => $this->lgas,
      'wards' => $this->wards,
      'facility_name' => $this->facility_name,
      'state_name' => $this->state_name,
      'lga_name' => $this->lga_name,
      'ward_name' => $this->ward_name
    ])->layout('layouts.dataOfficerLayout');
  }

  public function backToDashboard()
  {
    return redirect()->route('workspaces-antenatal', ['patientId' => $this->patientId]);
  }

  public function placeholder()
  {
    return view('placeholder');
  }
}
