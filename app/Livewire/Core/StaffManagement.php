<?php

namespace App\Livewire\Core;

use App\Models\Facility;
use App\Models\FacilityDepartment;
use App\Models\StaffManagementAudit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class StaffManagement extends Component
{
  public $facility_id;
  public $facility_name;
  public $admin_name;

  public $designations = ['Nurse', 'Doctor', 'Midwife', 'Lab Attendant', 'Verification Officer', 'Volunteer'];
  public $statusOptions = ['active', 'disabled'];

  public $staff_id;
  public $modal_mode = 'create';
  public $first_name;
  public $last_name;
  public $email;
  public $username;
  public $password;
  public $password_confirmation;
  public $designation;
  public $department_id;
  public $account_status = 'active';

  public $status_staff_id;
  public $status_staff_name;
  public $current_account_status = 'active';
  public $new_account_status = 'active';

  public $reset_staff_id;
  public $reset_staff_name;
  public $reset_temp_password;

  public function mount(): void
  {
    $admin = Auth::user();
    if (!$admin || $admin->role !== 'Facility Administrator') {
      abort(403, 'Unauthorized: Only Facility Administrators can access this page.');
    }

    $this->facility_id = (int) $admin->facility_id;
    $this->facility_name = Facility::find($this->facility_id)?->name ?? 'Unknown Facility';
    $this->admin_name = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '')) ?: 'Facility Admin';
  }

  private function formRules(): array
  {
    $rules = [
      'first_name' => 'required|string|max:255',
      'last_name' => 'required|string|max:255',
      'email' => 'nullable|email|max:255',
      'designation' => ['required', Rule::in($this->designations)],
      'department_id' => [
        'nullable',
        Rule::exists('facility_departments', 'id')->where(function ($query) {
          $query->where('facility_id', $this->facility_id);
        }),
      ],
      'account_status' => ['required', Rule::in($this->statusOptions)],
    ];

    if ($this->modal_mode === 'edit' && $this->staff_id) {
      $rules['username'] = ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->staff_id)];
      $rules['password'] = 'nullable|string|min:8|confirmed';
    } else {
      $rules['username'] = 'required|string|max:255|unique:users,username';
      $rules['password'] = 'required|string|min:8|confirmed';
    }

    return $rules;
  }

  private function deriveRole(string $designation): string
  {
    return $designation === 'Verification Officer' ? 'Verification Officer' : 'Data Officer';
  }

  private function normalizeStatus(?string $status, ?bool $isActive): string
  {
    if (in_array($status, $this->statusOptions, true)) {
      return $status;
    }

    return $isActive === false ? 'disabled' : 'active';
  }

  private function refreshPageSoon(int $delayMs = 1000): void
  {
    $this->js("setTimeout(() => window.location.reload(), {$delayMs})");
  }

  private function logAudit(
    string $action,
    ?int $targetUserId,
    ?array $oldValues = null,
    ?array $newValues = null,
    ?string $notes = null
  ): void {
    try {
      StaffManagementAudit::create([
        'facility_id' => $this->facility_id,
        'target_user_id' => $targetUserId,
        'action' => $action,
        'old_values' => $oldValues,
        'new_values' => $newValues,
        'changed_by_user_id' => Auth::id(),
        'changed_by_name' => $this->admin_name,
        'notes' => $notes,
      ]);
    } catch (\Throwable $e) {
      // Audit failure must not block workflow.
    }
  }

  public function openCreateModal(): void
  {
    $this->resetForm();
    $this->modal_mode = 'create';
    $this->dispatch('open-staff-form-modal');
  }

  public function openEditModal(int $id): void
  {
    $staff = User::query()
      ->where('facility_id', $this->facility_id)
      ->whereIn('role', ['Data Officer', 'Verification Officer'])
      ->findOrFail($id);

    $this->modal_mode = 'edit';
    $this->staff_id = (int) $staff->id;
    $this->first_name = $staff->first_name;
    $this->last_name = $staff->last_name;
    $this->email = $staff->email;
    $this->username = $staff->username;
    $this->designation = $staff->designation;
    $this->department_id = $staff->department_id;
    $this->account_status = $this->normalizeStatus($staff->account_status ?? null, (bool) $staff->is_active);
    $this->password = null;
    $this->password_confirmation = null;

    $this->dispatch('open-staff-form-modal');
  }

  public function saveStaff(): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->formRules());

      $duplicateName = User::query()
        ->where('facility_id', $this->facility_id)
        ->whereIn('role', ['Data Officer', 'Verification Officer'])
        ->whereRaw('LOWER(first_name) = ?', [strtolower((string) $this->first_name)])
        ->whereRaw('LOWER(last_name) = ?', [strtolower((string) $this->last_name)])
        ->when($this->staff_id, fn($q) => $q->where('id', '!=', $this->staff_id))
        ->exists();

      if ($duplicateName) {
        throw ValidationException::withMessages([
          'first_name' => 'A staff with this first and last name already exists in this facility.',
        ]);
      }

      $role = $this->deriveRole($this->designation);
      $status = $this->account_status;
      $isActive = $status === 'active';

      if ($this->modal_mode === 'edit' && $this->staff_id) {
        $staff = User::query()
          ->where('facility_id', $this->facility_id)
          ->whereIn('role', ['Data Officer', 'Verification Officer'])
          ->findOrFail($this->staff_id);

        $oldValues = [
          'first_name' => $staff->first_name,
          'last_name' => $staff->last_name,
          'email' => $staff->email,
          'username' => $staff->username,
          'designation' => $staff->designation,
          'department_id' => $staff->department_id,
          'role' => $staff->role,
          'account_status' => $this->normalizeStatus($staff->account_status ?? null, (bool) $staff->is_active),
          'is_active' => (bool) $staff->is_active,
        ];

        $payload = [
          'first_name' => $this->first_name,
          'last_name' => $this->last_name,
          'email' => $this->email ?: null,
          'username' => $this->username,
          'designation' => $this->designation,
          'department_id' => $this->department_id ?: null,
          'role' => $role,
          'account_status' => $status,
          'is_active' => $isActive,
          'facility_id' => $this->facility_id,
        ];

        if (!empty($this->password)) {
          $payload['password'] = Hash::make($this->password);
        }

        $staff->update($payload);
        $this->logAudit(
          'staff_updated',
          (int) $staff->id,
          $oldValues,
          [
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'email' => $payload['email'],
            'username' => $payload['username'],
            'designation' => $payload['designation'],
            'department_id' => $payload['department_id'],
            'role' => $payload['role'],
            'account_status' => $payload['account_status'],
            'is_active' => $payload['is_active'],
          ],
          'Staff profile edited via staff management.'
        );
        toastr()->success('Staff profile updated successfully.');
      } else {
        $staff = User::create([
          'first_name' => $this->first_name,
          'last_name' => $this->last_name,
          'email' => $this->email ?: null,
          'username' => $this->username,
          'password' => Hash::make((string) $this->password),
          'designation' => $this->designation,
          'department_id' => $this->department_id ?: null,
          'role' => $role,
          'account_status' => $status,
          'is_active' => $isActive,
          'facility_id' => $this->facility_id,
        ]);
        $this->logAudit(
          'staff_created',
          (int) $staff->id,
          null,
          [
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'username' => $staff->username,
            'designation' => $staff->designation,
            'department_id' => $staff->department_id,
            'role' => $staff->role,
            'account_status' => $status,
            'is_active' => $isActive,
          ],
          'New staff account created.'
        );
        toastr()->success('Staff account created successfully.');
      }

      DB::commit();
      $this->dispatch('close-staff-form-modal');
      $this->resetForm();
      $this->refreshPageSoon();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      report($e);
      toastr()->error('Unable to save staff profile.');
    }
  }

  public function openStatusModal(int $id): void
  {
    $staff = User::query()
      ->where('facility_id', $this->facility_id)
      ->whereIn('role', ['Data Officer', 'Verification Officer'])
      ->findOrFail($id);

    if ((int) $staff->id === (int) Auth::id()) {
      toastr()->warning('You cannot change your own account status.');
      return;
    }

    $status = $this->normalizeStatus($staff->account_status ?? null, (bool) $staff->is_active);

    $this->status_staff_id = (int) $staff->id;
    $this->status_staff_name = trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) ?: 'N/A';
    $this->current_account_status = $status;
    $this->new_account_status = $status;

    $this->dispatch('open-staff-status-modal');
  }

  public function updateStatus(): void
  {
    DB::beginTransaction();
    try {
      $this->validate([
        'status_staff_id' => 'required|integer',
        'new_account_status' => ['required', Rule::in($this->statusOptions)],
      ]);

      $staff = User::query()
        ->where('facility_id', $this->facility_id)
        ->whereIn('role', ['Data Officer', 'Verification Officer'])
        ->findOrFail($this->status_staff_id);

      if ((int) $staff->id === (int) Auth::id()) {
        throw ValidationException::withMessages([
          'new_account_status' => 'You cannot change your own account status.',
        ]);
      }

      if ($this->new_account_status === $this->current_account_status) {
        throw ValidationException::withMessages([
          'new_account_status' => 'No status change detected.',
        ]);
      }

      $status = $this->new_account_status;
      $oldValues = [
        'account_status' => $this->current_account_status,
        'is_active' => (bool) $staff->is_active,
      ];
      $staff->update([
        'account_status' => $status,
        'is_active' => $status === 'active',
      ]);
      $this->logAudit(
        'staff_status_updated',
        (int) $staff->id,
        $oldValues,
        [
          'account_status' => $status,
          'is_active' => $status === 'active',
        ],
        'Staff status changed from ' . $this->current_account_status . ' to ' . $status . '.'
      );

      DB::commit();
      toastr()->success('Account status updated successfully.');
      $this->dispatch('close-staff-status-modal');
      $this->resetStatusForm();
      $this->refreshPageSoon();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      report($e);
      toastr()->error('Unable to update account status.');
    }
  }

  public function openResetPasswordModal(int $id): void
  {
    $staff = User::query()
      ->where('facility_id', $this->facility_id)
      ->whereIn('role', ['Data Officer', 'Verification Officer'])
      ->findOrFail($id);

    if ((int) $staff->id === (int) Auth::id()) {
      toastr()->warning('You cannot reset your own password from this action.');
      return;
    }

    $this->reset_staff_id = (int) $staff->id;
    $this->reset_staff_name = trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) ?: 'N/A';
    $this->reset_temp_password = null;
    $this->dispatch('open-staff-reset-password-modal');
  }

  public function confirmResetPassword(): void
  {
    DB::beginTransaction();
    try {
      $this->validate([
        'reset_staff_id' => 'required|integer',
      ]);

      $staff = User::query()
        ->where('facility_id', $this->facility_id)
        ->whereIn('role', ['Data Officer', 'Verification Officer'])
        ->findOrFail($this->reset_staff_id);

      if ((int) $staff->id === (int) Auth::id()) {
        throw ValidationException::withMessages([
          'reset_staff_id' => 'You cannot reset your own password from this action.',
        ]);
      }

      $tempPassword = strtoupper(Str::random(4)) . random_int(1000, 9999);
      $staff->update([
        'password' => Hash::make($tempPassword),
      ]);

      $this->reset_temp_password = $tempPassword;
      $this->logAudit(
        'staff_password_reset',
        (int) $staff->id,
        null,
        ['password_reset' => true],
        'Temporary password generated by admin.'
      );

      DB::commit();
      toastr()->success('Temporary password generated. Share it securely with staff.');
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      report($e);
      toastr()->error('Unable to reset password.');
    }
  }

  public function closeFormModal(): void
  {
    $this->resetForm();
    $this->dispatch('close-staff-form-modal');
  }

  public function closeStatusModal(): void
  {
    $this->resetStatusForm();
    $this->dispatch('close-staff-status-modal');
  }

  public function closeResetPasswordModal(): void
  {
    $this->resetResetPasswordForm();
    $this->dispatch('close-staff-reset-password-modal');
  }

  private function resetForm(): void
  {
    $this->reset([
      'staff_id',
      'first_name',
      'last_name',
      'email',
      'username',
      'password',
      'password_confirmation',
      'designation',
      'department_id',
      'account_status',
      'modal_mode',
    ]);

    $this->modal_mode = 'create';
    $this->account_status = 'active';
    $this->department_id = null;
  }

  private function resetStatusForm(): void
  {
    $this->reset([
      'status_staff_id',
      'status_staff_name',
      'current_account_status',
      'new_account_status',
    ]);

    $this->current_account_status = 'active';
    $this->new_account_status = 'active';
  }

  private function resetResetPasswordForm(): void
  {
    $this->reset([
      'reset_staff_id',
      'reset_staff_name',
      'reset_temp_password',
    ]);
  }

  public function render()
  {
    $staffRows = User::query()
      ->where('facility_id', $this->facility_id)
      ->whereIn('role', ['Data Officer', 'Verification Officer'])
      ->with('department:id,name')
      ->latest('id')
      ->limit(1000)
      ->get();

    $departments = FacilityDepartment::query()
      ->where('facility_id', $this->facility_id)
      ->where('is_active', true)
      ->orderBy('name')
      ->get(['id', 'name']);

    $auditRows = StaffManagementAudit::query()
      ->where('facility_id', $this->facility_id)
      ->with('targetUser:id,first_name,last_name')
      ->latest('id')
      ->limit(500)
      ->get();

    $summary = [
      'total' => $staffRows->count(),
      'active' => $staffRows->filter(fn($row) => $this->normalizeStatus($row->account_status ?? null, (bool) $row->is_active) === 'active')->count(),
      'disabled' => $staffRows->filter(fn($row) => $this->normalizeStatus($row->account_status ?? null, (bool) $row->is_active) === 'disabled')->count(),
      'verification' => $staffRows->where('role', 'Verification Officer')->count(),
      'data_officers' => $staffRows->where('role', 'Data Officer')->count(),
      'assigned_department' => $staffRows->filter(fn($row) => !empty($row->department_id))->count(),
      'unassigned_department' => $staffRows->filter(fn($row) => empty($row->department_id))->count(),
    ];

    return view('livewire.core.staff-management', [
      'summary' => $summary,
      'staffRows' => $staffRows,
      'departments' => $departments,
      'auditRows' => $auditRows,
      'designations' => $this->designations,
      'statusOptions' => $this->statusOptions,
    ])->layout('layouts.facilityAdminLayout');
  }
}
