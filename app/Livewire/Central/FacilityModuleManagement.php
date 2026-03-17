<?php

namespace App\Livewire\Central;

use App\Models\Facility;
use App\Models\FacilityModuleAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class FacilityModuleManagement extends Component
{
  public $selected_facility_id = null;
  public $tables_ready = false;

  public function mount(): void
  {
    $user = Auth::user();
    if (!$user || !in_array((string) $user->role, ['Central Admin', 'Central Administrator'], true)) {
      abort(403, 'Unauthorized: Central admin access only.');
    }

    $this->tables_ready = Schema::hasTable('facility_module_accesses');
    $this->selected_facility_id = (int) Facility::query()->orderBy('name')->value('id');

    if ($this->tables_ready && $this->selected_facility_id) {
      $this->seedModuleAccessesForFacility($this->selected_facility_id);
    }
  }

  public function updatedSelectedFacilityId($value): void
  {
    $facilityId = (int) $value;
    if ($facilityId <= 0 || !$this->tables_ready) {
      return;
    }

    $this->seedModuleAccessesForFacility($facilityId);
  }

  public function toggleModuleAccess(int $id): void
  {
    if (!$this->tables_ready) {
      toastr()->error('Facility module access table is not available.');
      return;
    }

    $facilityId = (int) $this->selected_facility_id;
    if ($facilityId <= 0) {
      toastr()->error('Select a facility first.');
      return;
    }

    try {
      $row = FacilityModuleAccess::query()
        ->where('facility_id', $facilityId)
        ->findOrFail($id);

      $row->update([
        'is_enabled' => !(bool) $row->is_enabled,
        'last_changed_by_user_id' => (int) Auth::id(),
      ]);

      toastr()->success("Module '{$row->module_label}' updated.");
      $this->js('setTimeout(() => window.location.reload(), 250)');
    } catch (\Throwable $e) {
      toastr()->error('Unable to update module access.');
      report($e);
    }
  }

  private function seedModuleAccessesForFacility(int $facilityId): void
  {
    $existing = FacilityModuleAccess::query()
      ->where('facility_id', $facilityId)
      ->pluck('module_key')
      ->all();

    foreach ($this->moduleDefinitions() as $module) {
      if (in_array($module['key'], $existing, true)) {
        continue;
      }

      FacilityModuleAccess::query()->create([
        'facility_id' => $facilityId,
        'module_key' => $module['key'],
        'module_label' => $module['label'],
        'is_enabled' => true,
        'last_changed_by_user_id' => (int) Auth::id(),
      ]);
    }
  }

  private function moduleDefinitions(): array
  {
    return [
      ['key' => 'attendance', 'label' => 'Attendance'],
      ['key' => 'assessments', 'label' => 'Doctor Assessment'],
      ['key' => 'anc', 'label' => 'Antenatal Care'],
      ['key' => 'child_health', 'label' => 'Child Health'],
      ['key' => 'laboratory', 'label' => 'Laboratory'],
      ['key' => 'prescriptions', 'label' => 'Prescriptions'],
      ['key' => 'invoices', 'label' => 'Invoices & Payments'],
      ['key' => 'appointments', 'label' => 'Appointments'],
      ['key' => 'referrals', 'label' => 'Referrals'],
      ['key' => 'reminders', 'label' => 'Reminders'],
      ['key' => 'family_planning', 'label' => 'Family Planning'],
      ['key' => 'health_insurance', 'label' => 'Health Insurance'],
      ['key' => 'visits', 'label' => 'Visits'],
      ['key' => 'reports', 'label' => 'Reports'],
    ];
  }

  public function render()
  {
    $facilities = Facility::query()
      ->select(['id', 'name', 'lga', 'state', 'is_active'])
      ->orderBy('name')
      ->get();

    $selectedFacility = null;
    $moduleRows = collect();

    if ($this->selected_facility_id) {
      $selectedFacility = $facilities->firstWhere('id', (int) $this->selected_facility_id);
    }

    if ($this->tables_ready && $selectedFacility) {
      $moduleRows = FacilityModuleAccess::query()
        ->where('facility_id', (int) $selectedFacility->id)
        ->orderBy('module_label')
        ->get();
    }

    $summary = [
      'facilities_total' => $facilities->count(),
      'facilities_active' => $facilities->where('is_active', true)->count(),
      'modules_enabled' => $moduleRows->where('is_enabled', true)->count(),
      'modules_disabled' => $moduleRows->where('is_enabled', false)->count(),
    ];

    return view('livewire.central.facility-module-management', [
      'facilities' => $facilities,
      'selectedFacility' => $selectedFacility,
      'moduleRows' => $moduleRows,
      'summary' => $summary,
    ])->layout('layouts.centralAdminLayout');
  }
}

