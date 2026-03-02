<?php

namespace App\Livewire\Workspaces\Modules\Child;

use App\Models\Activity;
use App\Models\ChildHealthActivityRecord;
use App\Models\Facility;
use App\Models\LinkedChild;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dataOfficerLayout')]
class ActivityRegister extends Component
{
  public const VACCINE_SCHEDULE = [
    ['id' => 'bcg', 'name' => 'BCG', 'age' => 'At Birth'],
    ['id' => 'hepbo', 'name' => 'Hep. B0', 'age' => 'At Birth'],
    ['id' => 'opv0', 'name' => 'OPV 0', 'age' => 'At Birth'],
    ['id' => 'opv1', 'name' => 'OPV 1', 'age' => '6 Weeks'],
    ['id' => 'penta1', 'name' => 'PENTA 1', 'age' => '6 Weeks'],
    ['id' => 'pcv1', 'name' => 'PCV 1', 'age' => '6 Weeks'],
    ['id' => 'rota1', 'name' => 'ROTA 1', 'age' => '6 Weeks'],
    ['id' => 'ipv1', 'name' => 'IPV 1', 'age' => '6 Weeks'],
    ['id' => 'opv2', 'name' => 'OPV 2', 'age' => '10 Weeks'],
    ['id' => 'penta2', 'name' => 'PENTA 2', 'age' => '10 Weeks'],
    ['id' => 'pcv2', 'name' => 'PCV 2', 'age' => '10 Weeks'],
    ['id' => 'rota2', 'name' => 'ROTA 2', 'age' => '10 Weeks'],
    ['id' => 'opv3', 'name' => 'OPV 3', 'age' => '14 Weeks'],
    ['id' => 'penta3', 'name' => 'PENTA 3', 'age' => '14 Weeks'],
    ['id' => 'pcv3', 'name' => 'PCV 3', 'age' => '14 Weeks'],
    ['id' => 'rota3', 'name' => 'ROTA 3', 'age' => '14 Weeks'],
    ['id' => 'ipv2', 'name' => 'IPV 2', 'age' => '14 Weeks'],
    ['id' => 'malaria1', 'name' => 'Malaria 1', 'age' => '5 Months'],
    ['id' => 'malaria2', 'name' => 'Malaria 2', 'age' => '6 Months'],
    ['id' => 'vita1', 'name' => 'Vitamin A-1', 'age' => '6 Months'],
    ['id' => 'malaria3', 'name' => 'Malaria 3', 'age' => '7 Months'],
    ['id' => 'measles1', 'name' => 'Measles 1', 'age' => '9 Months'],
    ['id' => 'yfever', 'name' => 'Yellow Fever', 'age' => '9 Months'],
    ['id' => 'mening', 'name' => 'Meningitis', 'age' => '9 Months'],
    ['id' => 'measles2', 'name' => 'Measles 2', 'age' => '15 Months'],
    ['id' => 'malaria4', 'name' => 'Malaria 4', 'age' => '24 Months'],
    ['id' => 'vita2', 'name' => 'Vitamin A-2', 'age' => '24 Months'],
  ];

  public const WHO_BOYS_MEDIAN = [3.3, 4.5, 5.6, 6.4, 7.0, 7.5, 7.9, 8.3, 8.6, 8.9, 9.2, 9.4, 9.6, 9.9, 10.1, 10.3, 10.5, 10.7, 10.9, 11.1, 11.3, 11.5, 11.8, 12.0, 12.2];
  public const WHO_BOYS_MINUS2 = [2.5, 3.4, 4.3, 5.0, 5.6, 6.0, 6.4, 6.7, 6.9, 7.1, 7.4, 7.6, 7.7, 8.0, 8.2, 8.4, 8.6, 8.7, 8.9, 9.1, 9.2, 9.4, 9.7, 9.9, 10.1];

  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;

  public $record_id, $modal_flag = false;
  public $active_tab = 'child';

  public $linked_child_id;
  public $visit_date, $month_year;

  public $vaccination_dates = [];
  public $vaccination_notes = [];
  public $weight_entries = [];
  public $breastfeeding_entries = [];

  public $weight_entry_date, $weight_entry_age_months, $weight_entry_kg, $weight_entry_notes;

  public $comments;

  protected $rules = [
    'patientId' => 'required',
    'facility_id' => 'required|exists:facilities,id',
    'linked_child_id' => 'required|exists:linked_children,id',
    'visit_date' => 'required|date|before_or_equal:today',
    'month_year' => 'required|date',
    'vaccination_dates' => 'nullable|array',
    'vaccination_dates.*' => 'nullable|date',
    'vaccination_notes' => 'nullable|array',
    'vaccination_notes.*' => 'nullable|string|max:255',
    'weight_entries' => 'nullable|array',
    'weight_entries.*.date' => 'required_with:weight_entries|date',
    'weight_entries.*.age' => 'required_with:weight_entries|numeric|min:0|max:60',
    'weight_entries.*.kg' => 'required_with:weight_entries|numeric|min:0.5|max:40',
    'weight_entries.*.notes' => 'nullable|string|max:255',
    'breastfeeding_entries' => 'nullable|array',
    'breastfeeding_entries.*' => 'nullable|in:E,P,BW,NO',
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
    $this->initializeFormCollections();

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
      $firstChild = $this->getLinkedChildren()->first();
      $this->linked_child_id = $firstChild?->id;
      $this->loadLatestRecordForSelectedChild();
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
    $this->loadLatestRecordForSelectedChild();
    $this->dispatchWeightDataUpdated();
  }

  private function autoFillMonthYear(): void
  {
    if ($this->visit_date) {
      $this->month_year = Carbon::parse($this->visit_date)->startOfMonth()->format('Y-m-d');
    }
  }

    public function setActiveTab(string $tab): void
  {
    if (!in_array($tab, ['child', 'vaccination', 'weight', 'breastfeeding'], true)) {
      return;
    }

    $this->active_tab = $tab;

    if ($tab === 'weight') {
      $this->dispatchWeightDataUpdated();
      $this->dispatch('activity-weight-tab-opened');
    }
  }

  public function openCreateModal(): void
  {
    $this->resetForm();
    $this->modal_flag = false;
    $this->active_tab = 'child';
    $this->dispatchWeightDataUpdated();
  }

  public function addWeightEntry(): void
  {
    $this->appendWeightEntryFromInputs();
    toastr()->info('Weight entry added. Click Save Record to persist all changes.');
  }

  public function removeWeightEntry(int $index): void
  {
    if (!isset($this->weight_entries[$index])) {
      return;
    }

    unset($this->weight_entries[$index]);
    $this->weight_entries = array_values($this->weight_entries);
    $this->dispatchWeightDataUpdated();
  }
  private function initializeFormCollections(): void
  {
    $dateValues = [];
    $noteValues = [];
    foreach (self::VACCINE_SCHEDULE as $vaccine) {
      $key = $vaccine['id'];
      $dateValues[$key] = $this->vaccination_dates[$key] ?? null;
      $noteValues[$key] = $this->vaccination_notes[$key] ?? null;
    }

    $this->vaccination_dates = $dateValues;
    $this->vaccination_notes = $noteValues;

    $bfValues = [];
    foreach (range(1, 24) as $month) {
      $key = (string) $month;
      $bfValues[$key] = $this->breastfeeding_entries[$key] ?? null;
    }

    $this->breastfeeding_entries = $bfValues;
    $this->weight_entries = $this->normalizeWeightEntries($this->weight_entries);
  }

  private function normalizeVaccinationDates(array $values): array
  {
    $normalized = [];
    foreach (self::VACCINE_SCHEDULE as $vaccine) {
      $key = $vaccine['id'];
      $value = $values[$key] ?? null;
      $normalized[$key] = $value ?: null;
    }

    return $normalized;
  }

  private function normalizeVaccinationNotes(array $values): array
  {
    $normalized = [];
    foreach (self::VACCINE_SCHEDULE as $vaccine) {
      $key = $vaccine['id'];
      $value = trim((string) ($values[$key] ?? ''));
      $normalized[$key] = $value !== '' ? $value : null;
    }

    return $normalized;
  }

  private function normalizeWeightEntries(array $entries): array
  {
    $normalized = collect($entries)
      ->map(function ($entry) {
        return [
          'date' => $entry['date'] ?? null,
          'age' => isset($entry['age']) ? (float) $entry['age'] : null,
          'kg' => isset($entry['kg']) ? (float) $entry['kg'] : null,
          'notes' => trim((string) ($entry['notes'] ?? '')),
        ];
      })
      ->filter(fn($entry) => !empty($entry['date']) && !is_null($entry['age']) && !is_null($entry['kg']))
      ->sort(function (array $a, array $b) {
        return [$a['age'], $a['date']] <=> [$b['age'], $b['date']];
      })
      ->values()
      ->all();

    return $normalized;
  }
  private function hasPendingWeightEntryInput(): bool
  {
    return filled($this->weight_entry_date)
      || filled($this->weight_entry_age_months)
      || filled($this->weight_entry_kg)
      || filled(trim((string) $this->weight_entry_notes));
  }

  private function appendWeightEntryFromInputs(): void
  {
    $this->validate([
      'weight_entry_date' => 'required|date',
      'weight_entry_age_months' => 'required|numeric|min:0|max:60',
      'weight_entry_kg' => 'required|numeric|min:0.5|max:40',
      'weight_entry_notes' => 'nullable|string|max:255',
    ]);

    $child = $this->currentChild();
    if ($child?->date_of_birth && $this->weight_entry_date < $child->date_of_birth->format('Y-m-d')) {
      throw ValidationException::withMessages([
        'weight_entry_date' => 'Weight entry date cannot be earlier than child DOB.',
      ]);
    }

    $this->weight_entries[] = [
      'date' => $this->weight_entry_date,
      'age' => (float) $this->weight_entry_age_months,
      'kg' => (float) $this->weight_entry_kg,
      'notes' => trim((string) $this->weight_entry_notes),
    ];

    $this->weight_entries = $this->normalizeWeightEntries($this->weight_entries);

    $this->reset([
      'weight_entry_date',
      'weight_entry_age_months',
      'weight_entry_kg',
      'weight_entry_notes',
    ]);

    $this->dispatchWeightDataUpdated();
  }

  private function commitPendingWeightEntryIfPresent(): void
  {
    if (!$this->hasPendingWeightEntryInput()) {
      return;
    }

    $this->appendWeightEntryFromInputs();
  }

  private function normalizeBreastfeedingEntries(array $values): array
  {
    $normalized = [];
    foreach (range(1, 24) as $month) {
      $key = (string) $month;
      $value = $values[$key] ?? null;
      $normalized[$key] = in_array($value, ChildHealthActivityRecord::BREASTFEEDING_OPTIONS, true) ? $value : null;
    }

    return $normalized;
  }

  private function validateVaccineDatesAgainstDob(): void
  {
    $child = $this->currentChild();
    if (!$child || !$child->date_of_birth) {
      return;
    }

    $dob = $child->date_of_birth->format('Y-m-d');
    foreach ($this->vaccination_dates as $field => $value) {
      if ($value && $value < $dob) {
        throw ValidationException::withMessages([
          "vaccination_dates.$field" => 'Vaccine date cannot be earlier than child DOB.',
        ]);
      }
    }
  }

  private function buildSummaryMap(): array
  {
    $vaccinationDates = $this->normalizeVaccinationDates($this->vaccination_dates);
    $weightEntries = $this->normalizeWeightEntries($this->weight_entries);
    $breastfeedingEntries = $this->normalizeBreastfeedingEntries($this->breastfeeding_entries);

    return [
      'vaccines_completed' => collect($vaccinationDates)->filter(fn($date) => !empty($date))->count(),
      'total_vaccine_slots' => count(self::VACCINE_SCHEDULE),
      'weight_entries_count' => count($weightEntries),
      'breastfeeding_months_logged' => collect($breastfeedingEntries)->filter(fn($value) => !empty($value))->count(),
      'exclusive_bf_months' => collect($breastfeedingEntries)->filter(fn($value) => $value === 'E')->count(),
    ];
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
      'vaccination_dates' => $this->normalizeVaccinationDates($this->vaccination_dates),
      'vaccination_notes' => $this->normalizeVaccinationNotes($this->vaccination_notes),
      'weight_entries' => $this->normalizeWeightEntries($this->weight_entries),
      'breastfeeding_entries' => $this->normalizeBreastfeedingEntries($this->breastfeeding_entries),
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
    $activeTab = $this->active_tab;
    try {
      $this->autoFillMonthYear();
      $this->commitPendingWeightEntryIfPresent();
      $this->validate();
      $this->validateVaccineDatesAgainstDob();

      $record = ChildHealthActivityRecord::create($this->payload());

      DB::commit();
      $this->logActivity('create', 'Recorded child health activity register');
      $this->edit($record->id, $activeTab);
      toastr()->success('Child health activity record saved.');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (QueryException $e) {
      DB::rollBack();
      if (str_contains($e->getMessage(), 'child_health_activity_child_visit_unique')) {
        toastr()->error('This child already has an activity register entry for the selected visit date.');
        return;
      }
      toastr()->error('Database error occurred while saving record.');
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while saving record.');
      throw $e;
    }
  }

  public function edit($id, ?string $preferredTab = null): void
  {
    $record = ChildHealthActivityRecord::where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->findOrFail($id);

    $this->record_id = $record->id;
    $this->linked_child_id = $record->linked_child_id;
    $this->visit_date = $record->visit_date?->format('Y-m-d');
    $this->month_year = $record->month_year?->format('Y-m-d');
    $this->vaccination_dates = (array) ($record->vaccination_dates ?? []);
    $this->vaccination_notes = (array) ($record->vaccination_notes ?? []);
    $this->weight_entries = (array) ($record->weight_entries ?? []);
    $this->breastfeeding_entries = (array) ($record->breastfeeding_entries ?? []);
    $this->comments = $record->comments;

    $this->initializeFormCollections();

    $this->modal_flag = true;
    $this->active_tab = $preferredTab ?: 'child';
    $this->dispatchWeightDataUpdated();
  }

  public function update(): void
  {
    DB::beginTransaction();
    $activeTab = $this->active_tab;
    try {
      $rules = array_diff_key($this->rules, ['patientId' => '']);
      $this->autoFillMonthYear();
      $this->commitPendingWeightEntryIfPresent();
      $this->validate($rules);
      $this->validateVaccineDatesAgainstDob();

      $record = ChildHealthActivityRecord::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($this->record_id);

      $record->update($this->payload());

      DB::commit();
      $this->logActivity('update', 'Updated child health activity register');
      $this->edit($record->id, $activeTab);
      toastr()->success('Child health activity record updated.');
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
      $record = ChildHealthActivityRecord::where('facility_id', $this->facility_id)
        ->where('patient_id', $this->patientId)
        ->findOrFail($id);

      $record->delete();
      DB::commit();

      $this->logActivity('delete', 'Deleted child health activity register');
      toastr()->success('Child health activity record deleted.');
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
        'module' => 'child_health_activity_register',
        'action' => $action,
        'description' => $description,
        'performed_by' => $this->officer_name,
        'meta' => [
          'linked_child_id' => $this->linked_child_id,
          'visit_date' => $this->visit_date,
          'vaccines_completed' => collect($this->normalizeVaccinationDates($this->vaccination_dates))
            ->filter(fn($value) => !empty($value))
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
      'vaccination_dates',
      'vaccination_notes',
      'weight_entries',
      'breastfeeding_entries',
      'weight_entry_date',
      'weight_entry_age_months',
      'weight_entry_kg',
      'weight_entry_notes',
      'comments',
      'modal_flag',
      'active_tab',
    ]);

    $this->linked_child_id = $keepChild ?: $this->getLinkedChildren()->first()?->id;
    $this->visit_date = now()->format('Y-m-d');
    $this->weight_entry_date = $this->visit_date;
    $this->autoFillMonthYear();
    $this->active_tab = 'child';
    $this->initializeFormCollections();
    $this->dispatchWeightDataUpdated();
  }

  private function chartWeightEntriesFromDatabase(): array
  {
    $query = ChildHealthActivityRecord::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id);

    if ($this->linked_child_id) {
      $query->where('linked_child_id', $this->linked_child_id);
    }

    $entries = [];
    foreach ($query->orderBy('visit_date')->orderBy('id')->get(['weight_entries']) as $record) {
      foreach ((array) ($record->weight_entries ?? []) as $entry) {
        $entries[] = $entry;
      }
    }

    return $this->normalizeWeightEntries($entries);
  }
  private function loadLatestRecordForSelectedChild(): void
  {
    $query = ChildHealthActivityRecord::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id);

    if ($this->linked_child_id) {
      $query->where('linked_child_id', $this->linked_child_id);
    }

    $record = $query->latest('visit_date')->latest('id')->first();

    if (!$record) {
      $this->record_id = null;
      $this->visit_date = now()->format('Y-m-d');
      $this->weight_entry_date = $this->visit_date;
      $this->autoFillMonthYear();
      $this->vaccination_dates = [];
      $this->vaccination_notes = [];
      $this->weight_entries = [];
      $this->breastfeeding_entries = [];
      $this->comments = null;
      $this->initializeFormCollections();
      return;
    }

    $this->record_id = $record->id;
    $this->visit_date = $record->visit_date?->format('Y-m-d');
    $this->month_year = $record->month_year?->format('Y-m-d');
    $this->vaccination_dates = (array) ($record->vaccination_dates ?? []);
    $this->vaccination_notes = (array) ($record->vaccination_notes ?? []);
    $this->weight_entries = (array) ($record->weight_entries ?? []);
    $this->breastfeeding_entries = (array) ($record->breastfeeding_entries ?? []);
    $this->comments = $record->comments;
    $this->weight_entry_date = $this->visit_date;
    $this->initializeFormCollections();
  }

  private function dispatchWeightDataUpdated(): void
  {
    $this->dispatch('activity-weight-data-updated', entries: $this->chartWeightEntriesFromDatabase());
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

  public function backToImmunizations()
  {
    return redirect()->route('workspaces-child-health-immunizations', ['patientId' => $this->patientId]);
  }

  public function exit(): void
  {
    $this->resetForm();
  }

  public function render()
  {
    $records = ChildHealthActivityRecord::with('linkedChild')
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->latest('visit_date')
      ->latest('id')
      ->get();

    return view('livewire.workspaces.modules.child.activity-register', [
      'records' => $records,
      'linkedChildren' => $this->getLinkedChildren(),
      'currentChild' => $this->currentChild(),
      'hasAccess' => $this->hasAccess,
      'accessError' => $this->accessError,
      'vaccineSchedule' => self::VACCINE_SCHEDULE,
      'whoBoysMedian' => self::WHO_BOYS_MEDIAN,
      'whoBoysMinus2' => self::WHO_BOYS_MINUS2,
      'chartWeightEntries' => $this->chartWeightEntriesFromDatabase(),
    ])->layout('layouts.dataOfficerLayout');
  }

  public function placeholder()
  {
    return view('placeholder');
  }
}
























