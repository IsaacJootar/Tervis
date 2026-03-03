<?php

namespace App\Livewire\Workspaces\Modules\Clinical;

use App\Models\Activity;
use App\Models\Facility;
use App\Models\LabTest;
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
class Laboratory extends Component
{
  public const REPORT_INPUT_FIELDS = [
    'fbs',
    'rbs',
    'pcv',
    'hb',
    'twbc',
    'diff_n',
    'diff_e',
    'diff_b',
    'diff_l',
    'esr',
  ];

  public const REPORT_TOGGLE_FIELDS = [
    'mp' => ['Positive', 'Negative', 'N/A'],
    'preg' => ['Positive', 'Negative', 'N/A'],
  ];

  public const BLOOD_GROUP_OPTIONS = ['A', 'B', 'AB', 'O'];
  public const RHD_OPTIONS = ['RhD+', 'RhD-', 'NC'];
  public const HB_GENOTYPE_OPTIONS = ['AA', 'AS', 'SS', 'AC', 'SC'];

  public const WIDAL_FIELDS = ['Oa', 'Ob', 'Oc', 'Od', 'Ha', 'Hb', 'Hc', 'Hd'];

  public const STOOL_TOGGLE_FIELDS = [
    'us' => ['Positive', 'Negative', 'N/A'],
  ];

  public const MCS_TOGGLE_FIELDS = [
    'vdrl' => ['Reactive', 'Non-Reactive', 'N/A'],
    'rvs' => ['Positive', 'Negative', 'N/A'],
    'hbsag' => ['Positive', 'Negative', 'N/A'],
    'hcv' => ['Positive', 'Negative', 'N/A'],
    'tb' => ['Positive', 'Negative', 'N/A'],
    'hpylori' => ['Positive', 'Negative', 'N/A'],
    'chlamydia' => ['Positive', 'Negative', 'N/A'],
    'hvs' => ['Positive', 'Negative', 'N/A'],
    'urine_mcs' => ['Positive', 'Negative', 'N/A'],
  ];

  public const MCS_LABELS = [
    'vdrl' => 'VDRL',
    'rvs' => 'RVS',
    'hbsag' => 'HBSAg',
    'hcv' => 'HCV',
    'tb' => 'TB',
    'hpylori' => 'H.Pylori',
    'chlamydia' => 'Chlamydia Trach.',
    'hvs' => 'HVS / Cervical Swab',
    'urine_mcs' => 'Urine M/C/S',
  ];

  public const URINALYSIS_SELECT_OPTIONS = [
    'colour' => ['Pale Yellow', 'Yellow', 'Dark Yellow', 'Amber', 'Brown', 'Red/Blood', 'Cloudy', 'Clear'],
    'app' => ['Clear', 'Slightly Turbid', 'Turbid', 'Cloudy'],
    'glucose' => ['Negative', 'Trace', '+1', '+2', '+3', '+4'],
    'nitrite' => ['Negative', 'Positive'],
    'protein' => ['Negative', 'Trace', '+1', '+2', '+3', '+4'],
    'bilirubin' => ['Negative', '+1', '+2', '+3'],
    'urobilinogen' => ['Normal', '+1', '+2', '+3'],
    'ascorbic' => ['Negative', 'Positive'],
    'blood' => ['Negative', 'Trace', '+1', '+2', '+3'],
  ];

  public const MICROSCOPY_OPTIONS = [
    'epith_cells' => ['Nil', '+', '++', '+++'],
    'yeast_cells' => ['Nil', '+', '++', '+++'],
    'parasite' => ['Nil', 'Seen'],
    'pus_cells' => ['Nil', '+', '++', '+++', '++++'],
    'rbc' => ['Nil', '+', '++', '+++'],
    'crystal' => ['Nil', 'Few', 'Moderate', 'Many'],
    'bacteria' => ['Nil', 'Few', 'Moderate', 'Many'],
  ];

  public const MICROSCOPY_LABELS = [
    'epith_cells' => 'Epith Cells',
    'yeast_cells' => 'Yeast Cells',
    'parasite' => 'Parasite',
    'pus_cells' => 'Pus Cells',
    'rbc' => 'RBC',
    'crystal' => 'Crystal',
    'bacteria' => 'Bacteria',
  ];

  public const SENSITIVITY_LABELS = [
    'erythromycin' => 'Erythromycin',
    'ampicillin' => 'Ampicillin',
    'chloramphenicol' => 'Chloramphenicol',
    'gentamycin' => 'Gentamycin',
    'nitrofurantoin' => 'Nitrofurantoin',
    'penicillin' => 'Penicillin',
    'septrin' => 'Septrin',
    'tarivid' => 'Tarivid',
    'ciproxin' => 'Ciproxin',
    'streptomycin' => 'Streptomycin',
  ];

  public const SENSITIVITY_VALUES = ['S', 'R', 'I'];

  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;

  public $record_id;
  public $visit_date, $month_year;
  public $lab_no, $specimen, $clinician_diagnosis, $age_sex, $examination;

  public $report_values = [];
  public $widal_values = [];
  public $stool_values = [];
  public $mcs_results = [];
  public $urinalysis_results = [];
  public $microscopy_results = [];
  public $sensitivity_results = [];

  public $comment, $mlt_sign, $sign_date;

  protected $rules = [
    'patientId' => 'required',
    'facility_id' => 'required|exists:facilities,id',
    'visit_date' => 'required|date',
    'month_year' => 'required|date',
    'lab_no' => 'nullable|string|max:50',
    'specimen' => 'nullable|string|max:255',
    'clinician_diagnosis' => 'nullable|string|max:255',
    'age_sex' => 'nullable|string|max:60',
    'examination' => 'nullable|string|max:255',
    'report_values' => 'nullable|array',
    'widal_values' => 'nullable|array',
    'stool_values' => 'nullable|array',
    'mcs_results' => 'nullable|array',
    'urinalysis_results' => 'nullable|array',
    'microscopy_results' => 'nullable|array',
    'sensitivity_results' => 'nullable|array',
    'comment' => 'nullable|string|max:2000',
    'mlt_sign' => 'nullable|string|max:120',
    'sign_date' => 'nullable|date',
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
    $this->sign_date = now()->format('Y-m-d');
    $this->autoFillMonthYear();
    $this->initializeFormCollections();

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
      $this->deriveAgeSexFromPatient();
      $this->mlt_sign = $this->officer_name;
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

  private function deriveAgeSexFromPatient(): void
  {
    $ageText = $this->patient_age !== null ? $this->patient_age . 'yrs' : 'N/A';
    $genderText = $this->patient_gender ? strtoupper(substr($this->patient_gender, 0, 1)) : 'N/A';
    $this->age_sex = $ageText . ' / ' . $genderText;
  }

  private function initializeFormCollections(): void
  {
    $reportDefaults = array_fill_keys(self::REPORT_INPUT_FIELDS, null);
    foreach (array_keys(self::REPORT_TOGGLE_FIELDS) as $toggleField) {
      $reportDefaults[$toggleField] = null;
    }
    $reportDefaults['blood_group'] = null;
    $reportDefaults['rhd'] = null;
    $reportDefaults['hb_geno'] = null;
    $this->report_values = $this->normalizeSection($reportDefaults, (array) $this->report_values);

    $this->widal_values = $this->normalizeSection(array_fill_keys(self::WIDAL_FIELDS, null), (array) $this->widal_values);

    $stoolDefaults = ['app' => null, 'micro' => null, 'us' => null];
    $this->stool_values = $this->normalizeSection($stoolDefaults, (array) $this->stool_values);

    $this->mcs_results = $this->normalizeSection(array_fill_keys(array_keys(self::MCS_TOGGLE_FIELDS), null), (array) $this->mcs_results);

    $urinalysisDefaults = [
      'colour' => null,
      'app' => null,
      'ph' => null,
      'glucose' => null,
      'nitrite' => null,
      'protein' => null,
      'bilirubin' => null,
      'urobilinogen' => null,
      'ascorbic' => null,
      'specific_gravity' => null,
      'blood' => null,
      'others' => null,
    ];
    $this->urinalysis_results = $this->normalizeSection($urinalysisDefaults, (array) $this->urinalysis_results);

    $microscopyDefaults = array_fill_keys(array_keys(self::MICROSCOPY_OPTIONS), null);
    $microscopyDefaults['others'] = null;
    $this->microscopy_results = $this->normalizeSection($microscopyDefaults, (array) $this->microscopy_results);

    $sensitivityDefaults = array_fill_keys(array_keys(self::SENSITIVITY_LABELS), null);
    $sensitivityDefaults['others'] = null;
    $this->sensitivity_results = $this->normalizeSection($sensitivityDefaults, (array) $this->sensitivity_results);
  }

  private function normalizeSection(array $defaults, array $values): array
  {
    foreach ($defaults as $key => $value) {
      $defaults[$key] = $values[$key] ?? $value;
    }

    return $defaults;
  }

  public function setSelection(string $group, string $field, string $value): void
  {
    if (!in_array($group, ['report_values', 'stool_values', 'mcs_results', 'microscopy_results', 'sensitivity_results'], true)) {
      return;
    }

    $payload = (array) ($this->{$group} ?? []);
    $payload[$field] = $value;
    $this->{$group} = $payload;
  }

  public function clearSelection(string $group, string $field): void
  {
    if (!in_array($group, ['report_values', 'stool_values', 'mcs_results', 'microscopy_results', 'sensitivity_results'], true)) {
      return;
    }

    $payload = (array) ($this->{$group} ?? []);
    $payload[$field] = null;
    $this->{$group} = $payload;
  }

  private function buildSummaryMap(): array
  {
    return [
      'mcs_positive_count' => collect($this->mcs_results)->filter(fn($value) => $value === 'Positive' || $value === 'Reactive')->count(),
      'sensitivity_tested' => collect($this->sensitivity_results)
        ->except('others')
        ->filter(fn($value) => in_array($value, self::SENSITIVITY_VALUES, true))
        ->count(),
      'widal_marked' => collect($this->widal_values)->filter(fn($value) => !empty($value))->count(),
    ];
  }

  private function payload(): array
  {
    $this->initializeFormCollections();

    return [
      'patient_id' => $this->patientId,
      'facility_id' => $this->facility_id,
      'state_id' => $this->state_id,
      'lga_id' => $this->lga_id,
      'ward_id' => $this->ward_id,
      'month_year' => $this->month_year,
      'visit_date' => $this->visit_date,
      'lab_no' => $this->lab_no,
      'specimen' => $this->specimen,
      'clinician_diagnosis' => $this->clinician_diagnosis,
      'age_sex' => $this->age_sex,
      'examination' => $this->examination,
      'report_values' => $this->report_values,
      'widal_values' => $this->widal_values,
      'stool_values' => $this->stool_values,
      'mcs_results' => $this->mcs_results,
      'urinalysis_results' => $this->urinalysis_results,
      'microscopy_results' => $this->microscopy_results,
      'sensitivity_results' => $this->sensitivity_results,
      'comment' => $this->comment,
      'mlt_sign' => $this->mlt_sign,
      'sign_date' => $this->sign_date,
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
      $this->validate();

      $record = LabTest::create($this->payload());

      DB::commit();
      $this->logActivity('create', 'Recorded laboratory test/report');
      $this->edit($record->id);
      toastr()->success('Laboratory record saved.');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      toastr()->error('Database error occurred while saving record.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while saving record.');
      throw $e;
    }
  }

  public function edit($id): void
  {
    $record = LabTest::where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->findOrFail($id);

    $this->record_id = $record->id;
    $this->visit_date = $record->visit_date?->format('Y-m-d');
    $this->month_year = $record->month_year?->format('Y-m-d');
    $this->lab_no = $record->lab_no;
    $this->specimen = $record->specimen;
    $this->clinician_diagnosis = $record->clinician_diagnosis;
    $this->age_sex = $record->age_sex;
    $this->examination = $record->examination;

    $this->report_values = (array) ($record->report_values ?? []);
    $this->widal_values = (array) ($record->widal_values ?? []);
    $this->stool_values = (array) ($record->stool_values ?? []);
    $this->mcs_results = (array) ($record->mcs_results ?? []);
    $this->urinalysis_results = (array) ($record->urinalysis_results ?? []);
    $this->microscopy_results = (array) ($record->microscopy_results ?? []);
    $this->sensitivity_results = (array) ($record->sensitivity_results ?? []);

    $this->comment = $record->comment;
    $this->mlt_sign = $record->mlt_sign;
    $this->sign_date = $record->sign_date?->format('Y-m-d');

    $this->initializeFormCollections();
  }

  public function update(): void
  {
    DB::beginTransaction();
    try {
      $rules = array_diff_key($this->rules, ['patientId' => '']);
      $this->autoFillMonthYear();
      $this->validate($rules);

      $record = LabTest::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($this->record_id);

      $record->update($this->payload());

      DB::commit();
      $this->logActivity('update', 'Updated laboratory test/report');
      $this->edit($record->id);
      toastr()->success('Laboratory record updated.');
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
      $record = LabTest::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($id);

      $record->delete();
      DB::commit();

      $this->logActivity('delete', 'Deleted laboratory test/report');
      if ($this->record_id === (int) $id) {
        $this->openCreate();
      }
      toastr()->success('Laboratory record deleted.');
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
        'module' => 'laboratory',
        'action' => $action,
        'description' => $description,
        'performed_by' => $this->officer_name,
        'meta' => [
          'visit_date' => $this->visit_date,
          'lab_no' => $this->lab_no,
          'specimen' => $this->specimen,
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
      'visit_date',
      'month_year',
      'lab_no',
      'specimen',
      'clinician_diagnosis',
      'age_sex',
      'examination',
      'report_values',
      'widal_values',
      'stool_values',
      'mcs_results',
      'urinalysis_results',
      'microscopy_results',
      'sensitivity_results',
      'comment',
      'mlt_sign',
      'sign_date',
    ]);

    $this->visit_date = now()->format('Y-m-d');
    $this->sign_date = now()->format('Y-m-d');
    $this->autoFillMonthYear();
    $this->deriveAgeSexFromPatient();
    $this->mlt_sign = $this->officer_name;
    $this->initializeFormCollections();
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function render()
  {
    $records = LabTest::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->latest('visit_date')
      ->latest('id')
      ->get();

    return view('livewire.workspaces.modules.clinical.laboratory', [
      'records' => $records,
      'reportInputFields' => self::REPORT_INPUT_FIELDS,
      'reportToggleFields' => self::REPORT_TOGGLE_FIELDS,
      'bloodGroupOptions' => self::BLOOD_GROUP_OPTIONS,
      'rhdOptions' => self::RHD_OPTIONS,
      'hbGenotypeOptions' => self::HB_GENOTYPE_OPTIONS,
      'widalFields' => self::WIDAL_FIELDS,
      'mcsToggleFields' => self::MCS_TOGGLE_FIELDS,
      'mcsLabels' => self::MCS_LABELS,
      'urinalysisSelectOptions' => self::URINALYSIS_SELECT_OPTIONS,
      'microscopyOptions' => self::MICROSCOPY_OPTIONS,
      'microscopyLabels' => self::MICROSCOPY_LABELS,
      'sensitivityLabels' => self::SENSITIVITY_LABELS,
      'sensitivityValues' => self::SENSITIVITY_VALUES,
    ]);
  }
}
