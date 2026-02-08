<?php

namespace App\Livewire\Workspaces\Modules\ANC;

use Exception;
use Carbon\Carbon;
use App\Models\Lga;
use App\Models\Ward;
use App\Models\State;
use Livewire\Component;
use App\Models\Patient;
use App\Models\Facility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Models\Registrations\DinActivation;
use App\Models\AntenatalFollowUpAssessment;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class FollowUpAssessment extends Component
{
  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;
  public $lmp, $edd, $pregnancy_number;

  public $assessment_id, $modal_flag = false;

  public $month_year, $visit_date, $bp, $pcv, $weight, $fundal_height, $presentation_position, $relation_to_brim;
  public $fetal_heart_rate, $urine_test, $oedema, $clinical_remarks, $special_delivery_instructions, $next_return_date;
  public $xray_pelvimetry = false, $pelvic_inlet, $pelvic_cavity, $pelvic_outlet;
  public $hb_genotype, $rhesus, $kahn_vdrl, $antimalarials_therapy;

  protected $rules = [
    'patientId' => 'required',
    'facility_id' => 'required|exists:facilities,id',
    'month_year' => 'required|date',
    'visit_date' => 'required|date',
    'bp' => ['nullable', 'regex:/^\d{2,3}\/\d{2,3}$/'],
    'pcv' => 'nullable|numeric|min:0|max:100',
    'weight' => 'nullable|numeric|min:0|max:300',
    'fundal_height' => 'nullable|numeric|min:0|max:60',
    'presentation_position' => 'nullable|string|max:50',
    'relation_to_brim' => 'nullable|string|max:50',
    'fetal_heart_rate' => 'nullable|integer|min:50|max:250',
    'urine_test' => 'nullable|in:Trace/Nil,Trace/Trace,+/Nil,Nil/+, +/+, ++/+, ++/++, +++/++,+/Trace,Nil/Trace,Trace/+,Nil/Nil',
    'oedema' => 'nullable|in:none,+,++,+++',
    'clinical_remarks' => 'nullable|string|max:2000',
    'special_delivery_instructions' => 'nullable|string|max:2000',
    'next_return_date' => 'nullable|date',
    'xray_pelvimetry' => 'boolean',
    'pelvic_inlet' => 'nullable|string|max:100',
    'pelvic_cavity' => 'nullable|string|max:100',
    'pelvic_outlet' => 'nullable|string|max:100',
    'hb_genotype' => 'nullable|string|max:100',
    'rhesus' => 'nullable|string|max:50',
    'kahn_vdrl' => 'nullable|string|max:50',
    'antimalarials_therapy' => 'nullable|string|max:2000',
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

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
      $this->visit_date = Carbon::now()->format('Y-m-d');
      $this->autoFillMonthYear();
    }
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

    $activeAnc = $this->patient->activeAntenatalRegistration;
    if ($activeAnc) {
      $this->lmp = $activeAnc->lmp?->format('Y-m-d');
      $this->edd = $activeAnc->edd?->format('Y-m-d');
      $this->pregnancy_number = $activeAnc->pregnancy_number;
    }
  }

  public function updatedVisitDate()
  {
    $this->autoFillMonthYear();
  }

  private function autoFillMonthYear(): void
  {
    if ($this->visit_date) {
      $this->month_year = Carbon::parse($this->visit_date)->startOfMonth()->format('Y-m-d');
    }
  }

  public function store()
  {
    DB::beginTransaction();
    try {
      $this->autoFillMonthYear();
      $this->validate();
      $this->validateBpRange();

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
        'hasAccess' => '',
        'accessError' => '',
        'activation_time' => '',
        'lmp' => '',
        'edd' => '',
        'pregnancy_number' => '',
      ]);

      $data['patient_id'] = $this->patientId;
      $data['facility_id'] = $this->facility_id;
      $data['officer_name'] = $this->officer_name;
      $data['officer_role'] = $this->officer_role;
      $data['officer_designation'] = $this->officer_designation;

      AntenatalFollowUpAssessment::create($data);

      DB::commit();
      toastr()->success('Follow-up assessment saved.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      toastr()->error('Database error occurred while saving the assessment.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while saving the assessment.');
      throw $e;
    }
  }

  public function edit($id)
  {
    DB::beginTransaction();
    try {
      $assessment = AntenatalFollowUpAssessment::findOrFail($id);
      $this->assessment_id = $id;
      $this->facility_id = $assessment->facility_id;
      $this->facility_name = Facility::find($assessment->facility_id)->name ?? 'N/A';
      $this->state_id = $assessment->state_id;
      $this->state_name = State::find($assessment->state_id)->name ?? 'N/A';
      $this->lga_id = $assessment->lga_id;
      $this->lga_name = Lga::find($assessment->lga_id)->name ?? 'N/A';
      $this->ward_id = $assessment->ward_id;
      $this->ward_name = Ward::find($assessment->ward_id)->name ?? 'N/A';
      $this->month_year = $assessment->month_year ? Carbon::parse($assessment->month_year)->format('Y-m-d') : null;
      $this->visit_date = $assessment->visit_date ? Carbon::parse($assessment->visit_date)->format('Y-m-d') : null;
      $this->bp = $assessment->bp;
      $this->pcv = $assessment->pcv;
      $this->weight = $assessment->weight;
      $this->fundal_height = $assessment->fundal_height;
      $this->presentation_position = $assessment->presentation_position;
      $this->relation_to_brim = $assessment->relation_to_brim;
      $this->fetal_heart_rate = $assessment->fetal_heart_rate;
      $this->urine_test = $assessment->urine_test;
      $this->oedema = $assessment->oedema;
      $this->clinical_remarks = $assessment->clinical_remarks;
      $this->special_delivery_instructions = $assessment->special_delivery_instructions;
      $this->next_return_date = $assessment->next_return_date ? Carbon::parse($assessment->next_return_date)->format('Y-m-d') : null;
      $this->xray_pelvimetry = (bool) $assessment->xray_pelvimetry;
      $this->pelvic_inlet = $assessment->pelvic_inlet;
      $this->pelvic_cavity = $assessment->pelvic_cavity;
      $this->pelvic_outlet = $assessment->pelvic_outlet;
      $this->hb_genotype = $assessment->hb_genotype;
      $this->rhesus = $assessment->rhesus;
      $this->kahn_vdrl = $assessment->kahn_vdrl;
      $this->antimalarials_therapy = $assessment->antimalarials_therapy;
      $this->officer_name = $assessment->officer_name ?: $this->officer_name;
      $this->officer_role = $assessment->officer_role ?: $this->officer_role;
      $this->officer_designation = $assessment->officer_designation ?: $this->officer_designation;
      $this->modal_flag = true;
      $this->dispatch('open-main-modal');

      DB::commit();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while loading the assessment.');
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
      $this->validate($rules);
      $this->validateBpRange();

      $assessment = AntenatalFollowUpAssessment::findOrFail($this->assessment_id);
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
        'hasAccess' => '',
        'accessError' => '',
        'activation_time' => '',
        'lmp' => '',
        'edd' => '',
        'pregnancy_number' => '',
      ]);
      $data['officer_name'] = $this->officer_name;
      $data['officer_role'] = $this->officer_role;
      $data['officer_designation'] = $this->officer_designation;
      $assessment->update($data);

      DB::commit();
      toastr()->success('Follow-up assessment updated.');
      $this->resetForm();
      $this->dispatch('close-modals');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $field => $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      toastr()->error('Database error while updating the assessment.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while updating the assessment.');
      throw $e;
    }
  }

  public function delete($id)
  {
    DB::beginTransaction();
    try {
      $assessment = AntenatalFollowUpAssessment::findOrFail($id);
      $assessment->delete();

      DB::commit();
      toastr()->success('Assessment deleted successfully.');
      $this->resetForm();
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while deleting the assessment.');
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
      'month_year',
      'visit_date',
      'bp',
      'pcv',
      'weight',
      'fundal_height',
      'presentation_position',
      'relation_to_brim',
      'fetal_heart_rate',
      'urine_test',
      'oedema',
      'clinical_remarks',
      'special_delivery_instructions',
      'next_return_date',
      'xray_pelvimetry',
      'pelvic_inlet',
      'pelvic_cavity',
      'pelvic_outlet',
      'hb_genotype',
      'rhesus',
      'kahn_vdrl',
      'antimalarials_therapy',
      'assessment_id',
      'modal_flag',
    ]);
    $this->mount($this->patientId);
  }

  private function validateBpRange(): void
  {
    if (!$this->bp) return;

    $parts = explode('/', $this->bp);
    if (count($parts) !== 2) {
      throw ValidationException::withMessages([
        'bp' => 'Blood pressure must be in the format 120/80.',
      ]);
    }

    $systolic = (int) $parts[0];
    $diastolic = (int) $parts[1];

    if ($systolic < 50 || $systolic > 250 || $diastolic < 30 || $diastolic > 150) {
      throw ValidationException::withMessages([
        'bp' => 'Blood pressure values are out of range.',
      ]);
    }
  }

  public function backToDashboard()
  {
    return redirect()->route('workspaces-antenatal', ['patientId' => $this->patientId]);
  }

  public function render()
  {
    $assessments = AntenatalFollowUpAssessment::with('patient', 'facility')
      ->where('patient_id', $this->patientId)
      ->latest()
      ->get();

    return view('livewire.workspaces.modules.anc.follow-up-assessment', [
      'assessments' => $assessments,
      'facility_name' => $this->facility_name,
      'state_name' => $this->state_name,
      'lga_name' => $this->lga_name,
      'ward_name' => $this->ward_name,
    ])->layout('layouts.dataOfficerLayout');
  }

  public function placeholder()
  {
    return view('placeholder');
  }
}
