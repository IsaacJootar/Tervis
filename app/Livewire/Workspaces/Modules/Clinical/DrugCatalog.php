<?php

namespace App\Livewire\Workspaces\Modules\Clinical;

use App\Models\DrugCatalogItem;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dataOfficerLayout')]
class DrugCatalog extends Component
{
  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;

  public $catalog_id;
  public $catalog_drug_name;
  public $catalog_formulation;
  public $catalog_strength;
  public $catalog_route;
  public $catalog_notes;
  public $catalog_is_active = true;

  protected $rules = [
    'catalog_drug_name' => 'required|string|max:150',
    'catalog_formulation' => 'nullable|string|max:120',
    'catalog_strength' => 'nullable|string|max:120',
    'catalog_route' => 'nullable|string|max:80',
    'catalog_notes' => 'nullable|string|max:1000',
    'catalog_is_active' => 'boolean',
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

  public function saveCatalogItem(): void
  {
    $this->validate();

    DrugCatalogItem::updateOrCreate(
      [
        'id' => $this->catalog_id,
        'facility_id' => $this->facility_id,
      ],
      [
        'state_id' => $this->state_id,
        'lga_id' => $this->lga_id,
        'ward_id' => $this->ward_id,
        'drug_name' => trim((string) $this->catalog_drug_name),
        'formulation' => trim((string) $this->catalog_formulation) ?: null,
        'strength' => trim((string) $this->catalog_strength) ?: null,
        'route' => trim((string) $this->catalog_route) ?: null,
        'notes' => trim((string) $this->catalog_notes) ?: null,
        'is_active' => (bool) $this->catalog_is_active,
      ]
    );

    toastr()->success($this->catalog_id ? 'Drug catalog item updated.' : 'Drug added to catalog.');
    $this->resetCatalogForm();
  }

  public function editCatalogItem($id): void
  {
    $item = DrugCatalogItem::query()
      ->where('facility_id', $this->facility_id)
      ->findOrFail($id);

    $this->catalog_id = $item->id;
    $this->catalog_drug_name = $item->drug_name;
    $this->catalog_formulation = $item->formulation;
    $this->catalog_strength = $item->strength;
    $this->catalog_route = $item->route;
    $this->catalog_notes = $item->notes;
    $this->catalog_is_active = (bool) $item->is_active;
  }

  public function toggleCatalogStatus($id): void
  {
    $item = DrugCatalogItem::query()
      ->where('facility_id', $this->facility_id)
      ->findOrFail($id);

    $item->update([
      'is_active' => !$item->is_active,
    ]);

    toastr()->success('Catalog status updated.');
  }

  public function deleteCatalogItem($id): void
  {
    $item = DrugCatalogItem::query()
      ->where('facility_id', $this->facility_id)
      ->findOrFail($id);

    $item->delete();
    toastr()->success('Catalog item removed.');
  }

  public function resetCatalogForm(): void
  {
    $this->reset([
      'catalog_id',
      'catalog_drug_name',
      'catalog_formulation',
      'catalog_strength',
      'catalog_route',
      'catalog_notes',
    ]);
    $this->catalog_is_active = true;
  }

  public function goToDispensing()
  {
    return redirect()->route('workspaces-prescriptions', ['patientId' => $this->patientId]);
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function render()
  {
    $catalogItems = DrugCatalogItem::query()
      ->where('facility_id', $this->facility_id)
      ->latest('id')
      ->get();

    return view('livewire.workspaces.modules.clinical.drug-catalog', [
      'catalogItems' => $catalogItems,
    ]);
  }
}

