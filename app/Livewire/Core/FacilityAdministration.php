<?php

namespace App\Livewire\Core;

use App\Models\Facility;
use App\Models\FacilityAdminAudit;
use App\Models\FacilityFeeSchedule;
use App\Models\FacilityModuleAccess;
use App\Models\FacilityServiceCatalogItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class FacilityAdministration extends Component
{
  public $facility_id;
  public $admin_user_id;
  public $admin_name;

  public $facility_name;
  public $facility_address;
  public $facility_phone;
  public $facility_email;
  public $facility_type;
  public $facility_ownership;
  public $facility_is_active = true;

  public $service_id;
  public $service_mode = 'create';
  public $service_code;
  public $service_name;
  public $service_category;
  public $service_description;
  public $service_base_fee = 0;
  public $service_is_active = true;

  public $fee_id;
  public $fee_mode = 'create';
  public $fee_service_id;
  public $fee_amount = 0;
  public $fee_effective_from;
  public $fee_effective_to;
  public $fee_notes;
  public $fee_is_active = true;
  public $tables_ready = false;
  public $refresh_after_service_modal_close = false;
  public $refresh_after_fee_modal_close = false;

  public function mount(): void
  {
    $admin = Auth::user();
    if (!$admin || $admin->role !== 'Facility Administrator') {
      abort(403, 'Unauthorized: Only Facility Administrators can access this page.');
    }

    $this->facility_id = (int) $admin->facility_id;
    $this->admin_user_id = (int) $admin->id;
    $this->admin_name = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '')) ?: ($admin->username ?? 'Facility Admin');

    $this->loadFacilityProfile();
    $this->tables_ready = $this->hasFacilityAdministrationTables();
    if ($this->tables_ready) {
      $this->seedModuleAccesses();
    } else {
      toastr()->warning('Facility Administration tables are not migrated yet. Run migrations to enable full operations.');
    }
  }

  private function hasFacilityAdministrationTables(): bool
  {
    return Schema::hasTable('facility_service_catalog_items')
      && Schema::hasTable('facility_fee_schedules')
      && Schema::hasTable('facility_module_accesses')
      && Schema::hasTable('facility_admin_audits');
  }

  private function ensureTablesReady(): bool
  {
    if ($this->tables_ready) {
      return true;
    }

    toastr()->error('Facility Administration data tables are missing. Please run migrations.');
    return false;
  }

  private function loadFacilityProfile(): void
  {
    $facility = Facility::query()->findOrFail($this->facility_id);
    $this->facility_name = $facility->name;
    $this->facility_address = $facility->address;
    $this->facility_phone = $facility->phone;
    $this->facility_email = $facility->email;
    $this->facility_type = $facility->type;
    $this->facility_ownership = $facility->ownership;
    $this->facility_is_active = (bool) $facility->is_active;
  }

  private function profileRules(): array
  {
    return [
      'facility_name' => 'required|string|max:255',
      'facility_address' => 'nullable|string|max:255',
      'facility_phone' => 'nullable|string|max:30',
      'facility_email' => 'nullable|email|max:255',
      'facility_type' => 'nullable|string|max:100',
      'facility_ownership' => 'nullable|string|max:100',
      'facility_is_active' => 'boolean',
    ];
  }

  private function serviceRules(): array
  {
    return [
      'service_code' => [
        'required',
        'string',
        'max:40',
        Rule::unique('facility_service_catalog_items', 'service_code')
          ->where(fn($q) => $q->where('facility_id', $this->facility_id))
          ->ignore($this->service_id),
      ],
      'service_name' => 'required|string|max:150',
      'service_category' => 'nullable|string|max:120',
      'service_description' => 'nullable|string|max:2000',
      'service_base_fee' => 'required|numeric|min:0',
      'service_is_active' => 'boolean',
    ];
  }

  private function feeRules(): array
  {
    return [
      'fee_service_id' => [
        'required',
        Rule::exists('facility_service_catalog_items', 'id')->where(function ($q) {
          $q->where('facility_id', $this->facility_id);
        }),
      ],
      'fee_amount' => 'required|numeric|min:0',
      'fee_effective_from' => 'required|date',
      'fee_effective_to' => 'nullable|date|after_or_equal:fee_effective_from',
      'fee_notes' => 'nullable|string|max:2000',
      'fee_is_active' => 'boolean',
    ];
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
    ];
  }

  private function seedModuleAccesses(): void
  {
    if (!$this->tables_ready) {
      return;
    }

    $existing = FacilityModuleAccess::query()
      ->where('facility_id', $this->facility_id)
      ->pluck('module_key')
      ->all();

    foreach ($this->moduleDefinitions() as $module) {
      if (in_array($module['key'], $existing, true)) {
        continue;
      }

      FacilityModuleAccess::query()->create([
        'facility_id' => $this->facility_id,
        'module_key' => $module['key'],
        'module_label' => $module['label'],
        'is_enabled' => true,
        'last_changed_by_user_id' => $this->admin_user_id,
      ]);
    }
  }

  private function resetServiceForm(): void
  {
    $this->reset([
      'service_id',
      'service_code',
      'service_name',
      'service_category',
      'service_description',
      'service_base_fee',
      'service_is_active',
    ]);
    $this->service_mode = 'create';
    $this->service_base_fee = 0;
    $this->service_is_active = true;
  }

  private function resetFeeForm(): void
  {
    $this->reset([
      'fee_id',
      'fee_service_id',
      'fee_amount',
      'fee_effective_from',
      'fee_effective_to',
      'fee_notes',
      'fee_is_active',
    ]);
    $this->fee_mode = 'create';
    $this->fee_amount = 0;
    $this->fee_effective_from = now()->toDateString();
    $this->fee_is_active = true;
  }

  private function refreshPageSoon(int $delayMs = 1000): void
  {
    $this->js("setTimeout(() => window.location.reload(), {$delayMs})");
  }

  private function logAudit(
    string $action,
    ?string $targetType = null,
    ?int $targetId = null,
    ?array $oldValues = null,
    ?array $newValues = null,
    ?string $notes = null
  ): void {
    try {
      FacilityAdminAudit::query()->create([
        'facility_id' => $this->facility_id,
        'changed_by_user_id' => $this->admin_user_id,
        'changed_by_name' => $this->admin_name,
        'action' => $action,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'old_values' => $oldValues,
        'new_values' => $newValues,
        'notes' => $notes,
      ]);
    } catch (\Throwable $e) {
      // Audit failure should not block core workflow.
    }
  }

  public function saveFacilityProfile(): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->profileRules());
      $facility = Facility::query()->findOrFail($this->facility_id);

      $oldValues = [
        'name' => $facility->name,
        'address' => $facility->address,
        'phone' => $facility->phone,
        'email' => $facility->email,
        'type' => $facility->type,
        'ownership' => $facility->ownership,
        'is_active' => (bool) $facility->is_active,
      ];

      $facility->update([
        'name' => trim((string) $this->facility_name),
        'address' => trim((string) $this->facility_address) ?: null,
        'phone' => trim((string) $this->facility_phone) ?: null,
        'email' => trim((string) $this->facility_email) ?: null,
        'type' => trim((string) $this->facility_type) ?: null,
        'ownership' => trim((string) $this->facility_ownership) ?: null,
        'is_active' => (bool) $this->facility_is_active,
      ]);

      $this->logAudit(
        'facility_profile_updated',
        'facility',
        (int) $facility->id,
        $oldValues,
        [
          'name' => $facility->name,
          'address' => $facility->address,
          'phone' => $facility->phone,
          'email' => $facility->email,
          'type' => $facility->type,
          'ownership' => $facility->ownership,
          'is_active' => (bool) $facility->is_active,
        ],
        'Facility profile updated from Facility Administration module.'
      );

      DB::commit();
      toastr()->success('Facility profile updated successfully.');
      $this->refreshPageSoon(900);
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      toastr()->error('Unable to update facility profile.');
      report($e);
    }
  }

  public function openServiceModal(?int $id = null): void
  {
    if (!$this->ensureTablesReady()) {
      return;
    }

    $this->resetErrorBag();
    $this->resetValidation();

    if ($id) {
      $service = FacilityServiceCatalogItem::query()
        ->where('facility_id', $this->facility_id)
        ->findOrFail($id);

      $this->service_mode = 'edit';
      $this->service_id = (int) $service->id;
      $this->service_code = $service->service_code;
      $this->service_name = $service->service_name;
      $this->service_category = $service->service_category;
      $this->service_description = $service->description;
      $this->service_base_fee = (float) $service->base_fee;
      $this->service_is_active = (bool) $service->is_active;
    } else {
      $this->resetServiceForm();
    }

    $this->refresh_after_service_modal_close = false;
    $this->dispatch('open-facility-service-modal');
  }

  public function saveService(): void
  {
    if (!$this->ensureTablesReady()) {
      return;
    }

    DB::beginTransaction();
    try {
      $this->validate($this->serviceRules());

      $payload = [
        'facility_id' => $this->facility_id,
        'service_code' => strtoupper(trim((string) $this->service_code)),
        'service_name' => trim((string) $this->service_name),
        'service_category' => trim((string) $this->service_category) ?: null,
        'description' => trim((string) $this->service_description) ?: null,
        'base_fee' => (float) $this->service_base_fee,
        'is_active' => (bool) $this->service_is_active,
        'updated_by_user_id' => $this->admin_user_id,
      ];

      if ($this->service_mode === 'edit' && $this->service_id) {
        $service = FacilityServiceCatalogItem::query()
          ->where('facility_id', $this->facility_id)
          ->findOrFail($this->service_id);
        $oldValues = $service->only([
          'service_code',
          'service_name',
          'service_category',
          'description',
          'base_fee',
          'is_active',
        ]);
        $service->update($payload);
        $action = 'service_updated';
        $message = "Service '{$service->service_name}' updated successfully.";
      } else {
        $payload['created_by_user_id'] = $this->admin_user_id;
        $service = FacilityServiceCatalogItem::query()->create($payload);
        $oldValues = null;
        $action = 'service_created';
        $message = "Service '{$service->service_name}' created successfully.";
      }

      $this->logAudit(
        $action,
        'facility_service_catalog_item',
        (int) $service->id,
        $oldValues,
        $service->only(['service_code', 'service_name', 'service_category', 'description', 'base_fee', 'is_active']),
        'Service catalog updated.'
      );

      DB::commit();
      toastr()->success($message);
      $this->refresh_after_service_modal_close = true;
      if ($this->service_mode !== 'edit') {
        $this->resetServiceForm();
      }
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      toastr()->error('Unable to save service catalog item.');
      report($e);
    }
  }

  public function toggleServiceStatus(int $id): void
  {
    if (!$this->ensureTablesReady()) {
      return;
    }

    try {
      $service = FacilityServiceCatalogItem::query()
        ->where('facility_id', $this->facility_id)
        ->findOrFail($id);

      $oldStatus = (bool) $service->is_active;
      $service->update([
        'is_active' => !$oldStatus,
        'updated_by_user_id' => $this->admin_user_id,
      ]);

      $this->logAudit(
        'service_status_toggled',
        'facility_service_catalog_item',
        (int) $service->id,
        ['is_active' => $oldStatus],
        ['is_active' => (bool) $service->is_active],
        "Service status toggled for {$service->service_name}."
      );

      toastr()->success("Service '{$service->service_name}' status updated.");
      $this->refreshPageSoon(800);
    } catch (\Throwable $e) {
      toastr()->error('Unable to update service status.');
      report($e);
    }
  }

  public function openFeeModal(?int $id = null): void
  {
    if (!$this->ensureTablesReady()) {
      return;
    }

    $this->resetErrorBag();
    $this->resetValidation();

    if ($id) {
      $fee = FacilityFeeSchedule::query()
        ->where('facility_id', $this->facility_id)
        ->findOrFail($id);

      $this->fee_mode = 'edit';
      $this->fee_id = (int) $fee->id;
      $this->fee_service_id = (int) $fee->facility_service_catalog_item_id;
      $this->fee_amount = (float) $fee->amount;
      $this->fee_effective_from = optional($fee->effective_from)->toDateString();
      $this->fee_effective_to = optional($fee->effective_to)->toDateString();
      $this->fee_notes = $fee->notes;
      $this->fee_is_active = (bool) $fee->is_active;
    } else {
      $this->resetFeeForm();
    }

    $this->refresh_after_fee_modal_close = false;
    $this->dispatch('open-facility-fee-modal');
  }

  public function saveFeeSchedule(): void
  {
    if (!$this->ensureTablesReady()) {
      return;
    }

    DB::beginTransaction();
    try {
      $this->validate($this->feeRules());

      $payload = [
        'facility_id' => $this->facility_id,
        'facility_service_catalog_item_id' => (int) $this->fee_service_id,
        'amount' => (float) $this->fee_amount,
        'effective_from' => $this->fee_effective_from,
        'effective_to' => $this->fee_effective_to ?: null,
        'is_active' => (bool) $this->fee_is_active,
        'notes' => trim((string) $this->fee_notes) ?: null,
        'updated_by_user_id' => $this->admin_user_id,
      ];

      if ($this->fee_mode === 'edit' && $this->fee_id) {
        $fee = FacilityFeeSchedule::query()
          ->where('facility_id', $this->facility_id)
          ->findOrFail($this->fee_id);
        $oldValues = $fee->only([
          'facility_service_catalog_item_id',
          'amount',
          'effective_from',
          'effective_to',
          'is_active',
          'notes',
        ]);
        $fee->update($payload);
        $action = 'fee_schedule_updated';
        $message = 'Fee schedule updated successfully.';
      } else {
        $payload['created_by_user_id'] = $this->admin_user_id;
        $fee = FacilityFeeSchedule::query()->create($payload);
        $oldValues = null;
        $action = 'fee_schedule_created';
        $message = 'Fee schedule created successfully.';
      }

      if ((bool) $fee->is_active) {
        FacilityFeeSchedule::query()
          ->where('facility_id', $this->facility_id)
          ->where('facility_service_catalog_item_id', $fee->facility_service_catalog_item_id)
          ->where('id', '!=', $fee->id)
          ->where('is_active', true)
          ->update([
            'is_active' => false,
            'updated_by_user_id' => $this->admin_user_id,
            'updated_at' => now(),
          ]);
      }

      $this->logAudit(
        $action,
        'facility_fee_schedule',
        (int) $fee->id,
        $oldValues,
        $fee->only(['facility_service_catalog_item_id', 'amount', 'effective_from', 'effective_to', 'is_active', 'notes']),
        'Fee schedule maintained.'
      );

      DB::commit();
      toastr()->success($message);
      $this->refresh_after_fee_modal_close = true;
      if ($this->fee_mode !== 'edit') {
        $this->resetFeeForm();
      }
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      toastr()->error('Unable to save fee schedule.');
      report($e);
    }
  }

  public function toggleFeeStatus(int $id): void
  {
    if (!$this->ensureTablesReady()) {
      return;
    }

    DB::beginTransaction();
    try {
      $fee = FacilityFeeSchedule::query()
        ->where('facility_id', $this->facility_id)
        ->findOrFail($id);

      $newStatus = !(bool) $fee->is_active;
      $fee->update([
        'is_active' => $newStatus,
        'updated_by_user_id' => $this->admin_user_id,
      ]);

      if ($newStatus) {
        FacilityFeeSchedule::query()
          ->where('facility_id', $this->facility_id)
          ->where('facility_service_catalog_item_id', $fee->facility_service_catalog_item_id)
          ->where('id', '!=', $fee->id)
          ->where('is_active', true)
          ->update([
            'is_active' => false,
            'updated_by_user_id' => $this->admin_user_id,
            'updated_at' => now(),
          ]);
      }

      $this->logAudit(
        'fee_status_toggled',
        'facility_fee_schedule',
        (int) $fee->id,
        ['is_active' => !$newStatus],
        ['is_active' => $newStatus],
        'Fee schedule status changed.'
      );

      DB::commit();
      toastr()->success('Fee schedule status updated.');
      $this->refreshPageSoon(800);
    } catch (\Throwable $e) {
      DB::rollBack();
      toastr()->error('Unable to update fee schedule status.');
      report($e);
    }
  }

  public function toggleModuleAccess(int $id): void
  {
    if (!$this->ensureTablesReady()) {
      return;
    }

    try {
      $module = FacilityModuleAccess::query()
        ->where('facility_id', $this->facility_id)
        ->findOrFail($id);

      $oldStatus = (bool) $module->is_enabled;
      $module->update([
        'is_enabled' => !$oldStatus,
        'last_changed_by_user_id' => $this->admin_user_id,
      ]);

      $this->logAudit(
        'module_access_toggled',
        'facility_module_access',
        (int) $module->id,
        ['is_enabled' => $oldStatus],
        ['is_enabled' => (bool) $module->is_enabled],
        "{$module->module_label} module access toggled."
      );

      toastr()->success("Module access updated for '{$module->module_label}'.");
      $this->refreshPageSoon(800);
    } catch (\Throwable $e) {
      toastr()->error('Unable to update module access.');
      report($e);
    }
  }

  public function onServiceModalHidden(): void
  {
    $this->resetServiceForm();
    if ($this->refresh_after_service_modal_close) {
      $this->refresh_after_service_modal_close = false;
      $this->refreshPageSoon(150);
    }
  }

  public function onFeeModalHidden(): void
  {
    $this->resetFeeForm();
    if ($this->refresh_after_fee_modal_close) {
      $this->refresh_after_fee_modal_close = false;
      $this->refreshPageSoon(150);
    }
  }

  public function render()
  {
    if (!$this->tables_ready) {
      return view('livewire.core.facility-administration', [
        'summary' => [
          'services_total' => 0,
          'services_active' => 0,
          'fees_active' => 0,
          'modules_enabled' => 0,
        ],
        'services' => collect(),
        'serviceOptions' => collect(),
        'feeSchedules' => collect(),
        'moduleAccessRows' => collect(),
        'auditRows' => collect(),
      ])->layout('layouts.facilityAdminLayout');
    }

    $services = FacilityServiceCatalogItem::query()
      ->where('facility_id', $this->facility_id)
      ->latest()
      ->get();

    $feeSchedules = FacilityFeeSchedule::query()
      ->with('service:id,service_code,service_name')
      ->where('facility_id', $this->facility_id)
      ->latest()
      ->get();

    $moduleAccessRows = FacilityModuleAccess::query()
      ->where('facility_id', $this->facility_id)
      ->orderBy('module_label')
      ->get();

    $auditRows = FacilityAdminAudit::query()
      ->where('facility_id', $this->facility_id)
      ->latest()
      ->limit(300)
      ->get();

    $summary = [
      'services_total' => $services->count(),
      'services_active' => $services->where('is_active', true)->count(),
      'fees_active' => $feeSchedules->where('is_active', true)->count(),
      'modules_enabled' => $moduleAccessRows->where('is_enabled', true)->count(),
    ];

    return view('livewire.core.facility-administration', [
      'summary' => $summary,
      'services' => $services,
      'serviceOptions' => $services->where('is_active', true)->sortBy('service_name')->values(),
      'feeSchedules' => $feeSchedules,
      'moduleAccessRows' => $moduleAccessRows,
      'auditRows' => $auditRows,
    ])->layout('layouts.facilityAdminLayout');
  }
}
