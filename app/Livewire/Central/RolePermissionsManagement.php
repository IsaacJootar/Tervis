<?php

namespace App\Livewire\Central;

use App\Models\RolePermission;
use App\Services\Security\RolePermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class RolePermissionsManagement extends Component
{
  public $selected_role = '';
  public $tables_ready = false;

  public function mount(): void
  {
    $user = Auth::user();
    if (!$user || !in_array((string) $user->role, ['Central Admin', 'Central Administrator'], true)) {
      abort(403, 'Unauthorized: Central admin access only.');
    }

    $this->tables_ready = Schema::hasTable('role_permissions');
    $this->selected_role = RolePermissionService::roles()[0] ?? '';

    if ($this->tables_ready) {
      foreach (RolePermissionService::roles() as $role) {
        RolePermissionService::ensureRoleRows((string) $role, (int) Auth::id());
      }
    }
  }

  public function updatedSelectedRole($value): void
  {
    $roleName = trim((string) $value);
    if ($roleName === '' || !$this->tables_ready) {
      return;
    }

    RolePermissionService::ensureRoleRows($roleName, (int) Auth::id());
  }

  public function seedSelectedRoleDefaults(): void
  {
    if (!$this->tables_ready || trim((string) $this->selected_role) === '') {
      toastr()->error('Select a role first.');
      return;
    }

    RolePermissionService::ensureRoleRows((string) $this->selected_role, (int) Auth::id());
    toastr()->success('Permission defaults ensured for selected role.');
    $this->js('setTimeout(() => window.location.reload(), 250)');
  }

  public function togglePermission(int $id): void
  {
    if (!$this->tables_ready) {
      toastr()->error('Role permissions table is not available.');
      return;
    }

    try {
      $row = RolePermission::query()
        ->where('role_name', (string) $this->selected_role)
        ->findOrFail($id);

      $row->update([
        'is_allowed' => !(bool) $row->is_allowed,
        'last_changed_by_user_id' => (int) Auth::id(),
      ]);

      toastr()->success("Permission '{$row->permission_key}' updated.");
      $this->js('setTimeout(() => window.location.reload(), 200)');
    } catch (\Throwable $e) {
      report($e);
      toastr()->error('Unable to update permission.');
    }
  }

  public function render()
  {
    $roles = RolePermissionService::roles();

    $rows = collect();
    if ($this->tables_ready && trim((string) $this->selected_role) !== '') {
      RolePermissionService::ensureRoleRows((string) $this->selected_role, (int) Auth::id());

      $rows = RolePermission::query()
        ->where('role_name', (string) $this->selected_role)
        ->orderBy('permission_group')
        ->orderBy('permission_label')
        ->get();
    }

    $summary = [
      'total' => $rows->count(),
      'allowed' => $rows->where('is_allowed', true)->count(),
      'blocked' => $rows->where('is_allowed', false)->count(),
      'groups' => $rows->pluck('permission_group')->filter()->unique()->count(),
    ];

    return view('livewire.central.role-permissions-management', [
      'roles' => $roles,
      'rows' => $rows,
      'summary' => $summary,
    ])->layout('layouts.centralAdminLayout');
  }
}
