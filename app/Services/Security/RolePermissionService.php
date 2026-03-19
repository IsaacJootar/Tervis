<?php

namespace App\Services\Security;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use stdClass;

class RolePermissionService
{
  private static ?bool $tableExists = null;

  /**
   * @return array<int,array{key:string,label:string,group:string}>
   */
  public static function permissionDefinitions(): array
  {
    return [
      ['key' => 'central.dashboard.view', 'label' => 'View Central Dashboard', 'group' => 'central'],
      ['key' => 'central.admins.manage', 'label' => 'Manage Administrators', 'group' => 'central'],
      ['key' => 'central.facilities.manage', 'label' => 'Manage Facilities', 'group' => 'central'],
      ['key' => 'central.module_access.manage', 'label' => 'Manage Facility Module Access', 'group' => 'central'],
      ['key' => 'central.roles_permissions.manage', 'label' => 'Manage Roles & Permissions', 'group' => 'central'],

      ['key' => 'analytics.view', 'label' => 'View Analytics Dashboards', 'group' => 'analytics'],

      ['key' => 'registers.manage', 'label' => 'Access Registration Entry Points', 'group' => 'registers'],
      ['key' => 'avo.din_activation.manage', 'label' => 'Manage DIN Activations', 'group' => 'verification'],

      ['key' => 'core.facility_dashboard.view', 'label' => 'View Facility Admin Dashboard', 'group' => 'core'],
      ['key' => 'core.state_dashboard.view', 'label' => 'View State Officer Dashboard', 'group' => 'core'],
      ['key' => 'core.lga_dashboard.view', 'label' => 'View LGA Officer Dashboard', 'group' => 'core'],
      ['key' => 'core.staff.manage', 'label' => 'Manage Staff Accounts', 'group' => 'core'],
      ['key' => 'core.facility_administration.manage', 'label' => 'Manage Facility Administration', 'group' => 'core'],
      ['key' => 'core.sections.manage', 'label' => 'Manage Facility Departments/Sections', 'group' => 'core'],
      ['key' => 'core.beds.manage', 'label' => 'Manage Beds', 'group' => 'core'],
      ['key' => 'core.inpatient.manage', 'label' => 'Manage Admitted Patients', 'group' => 'core'],
      ['key' => 'core.laboratory.manage', 'label' => 'Manage Laboratory Operations', 'group' => 'core'],
      ['key' => 'core.pharmacy.manage', 'label' => 'Manage Pharmacy Operations', 'group' => 'core'],
      ['key' => 'core.patients.view', 'label' => 'View Facility Patients', 'group' => 'core'],
      ['key' => 'core.appointments.view', 'label' => 'View Patient Appointments', 'group' => 'core'],
      ['key' => 'core.reminders.manage', 'label' => 'Manage Reminders Hub', 'group' => 'core'],
      ['key' => 'core.reports.view', 'label' => 'View Reports Hub', 'group' => 'core'],

      ['key' => 'workspace.dashboard.view', 'label' => 'Open Patient Workspace', 'group' => 'workspace'],
      ['key' => 'workspace.activities.view', 'label' => 'View Patient Activities', 'group' => 'workspace'],
      ['key' => 'workspace.attendance.manage', 'label' => 'Manage Attendance', 'group' => 'workspace'],
      ['key' => 'workspace.assessments.manage', 'label' => 'Manage Doctor Assessments', 'group' => 'workspace'],
      ['key' => 'workspace.anc.manage', 'label' => 'Manage ANC Modules', 'group' => 'workspace'],
      ['key' => 'workspace.child_health.manage', 'label' => 'Manage Child Health Modules', 'group' => 'workspace'],
      ['key' => 'workspace.laboratory.manage', 'label' => 'Manage Patient Laboratory Entries', 'group' => 'workspace'],
      ['key' => 'workspace.prescriptions.manage', 'label' => 'Manage Prescriptions/Dispensing', 'group' => 'workspace'],
      ['key' => 'workspace.invoices.manage', 'label' => 'Manage Invoices & Payments', 'group' => 'workspace'],
      ['key' => 'workspace.appointments.view', 'label' => 'View Appointments', 'group' => 'workspace'],
      ['key' => 'workspace.visits.view', 'label' => 'View Visits', 'group' => 'workspace'],
      ['key' => 'workspace.reminders.manage', 'label' => 'Manage Patient Reminders', 'group' => 'workspace'],
      ['key' => 'workspace.family_planning.manage', 'label' => 'Manage Family Planning Follow-up', 'group' => 'workspace'],
      ['key' => 'workspace.health_insurance.manage', 'label' => 'Manage Health Insurance', 'group' => 'workspace'],
      ['key' => 'workspace.referrals.manage', 'label' => 'Manage Referrals', 'group' => 'workspace'],
      ['key' => 'workspace.drug_catalog.manage', 'label' => 'Manage Drug Catalog', 'group' => 'workspace'],
    ];
  }

  /**
   * @return array<int,string>
   */
  public static function roles(): array
  {
    return [
      'Central Admin',
      'Central Administrator',
      'Facility Administrator',
      'State Data Administrator',
      'LGA Officer',
      'Data Officer',
      'Verification Officer',
      'Patient',
    ];
  }

  public static function can(?User $user, string $permissionKey): bool
  {
    if (!$user) {
      return false;
    }

    $permissionKey = trim($permissionKey);
    if ($permissionKey === '') {
      return false;
    }

    $roleName = trim((string) ($user->role ?? ''));
    if ($roleName === '') {
      return false;
    }

    if (self::tableExists()) {
      $row = RolePermission::query()
        ->where('role_name', $roleName)
        ->where('permission_key', $permissionKey)
        ->first();

      if ($row) {
        return (bool) $row->is_allowed;
      }
    }

    return self::defaultAllowed($roleName, $permissionKey);
  }

  public static function canAccessMenuUrl(?User $user, ?string $url): bool
  {
    $permissionKey = self::permissionForPath($url);
    if ($permissionKey === null) {
      return true;
    }

    return self::can($user, $permissionKey);
  }

  /**
   * @param  mixed  $node
   */
  public static function canRenderMenuNode(?User $user, $node): bool
  {
    if (!$node instanceof stdClass && !is_array($node)) {
      return true;
    }

    $url = (string) (self::nodeValue($node, 'url') ?? '');
    if ($url !== '' && !self::canAccessMenuUrl($user, $url)) {
      return false;
    }

    $submenu = self::nodeValue($node, 'submenu');
    if (is_iterable($submenu)) {
      $hasVisibleChild = false;
      foreach ($submenu as $child) {
        if (self::canRenderMenuNode($user, $child)) {
          $hasVisibleChild = true;
          break;
        }
      }

      if (!$hasVisibleChild && $url === '') {
        return false;
      }
    }

    return true;
  }

  public static function permissionForPath(?string $urlOrPath): ?string
  {
    $path = self::normalizePath($urlOrPath);
    if ($path === '') {
      return null;
    }

    if (str_starts_with($path, 'central/')) {
      return match ($path) {
        'central/central-admin-dashboard' => 'central.dashboard.view',
        'central/create-administrators' => 'central.admins.manage',
        'central/create-facility' => 'central.facilities.manage',
        'central/facility-module-management' => 'central.module_access.manage',
        'central/roles-permissions' => 'central.roles_permissions.manage',
        default => null,
      };
    }

    if (str_starts_with($path, 'core/')) {
      return match ($path) {
        'core/facility-admin-dashboard' => 'core.facility_dashboard.view',
        'core/state-officer-dashboard' => 'core.state_dashboard.view',
        'core/lga-officer-dashboard' => 'core.lga_dashboard.view',
        'core/staff-management',
        'core/create-data-officers',
        'core/update-officer-designation',
        'core/disable-data-officer-account' => 'core.staff.manage',
        'core/facility-administration' => 'core.facility_administration.manage',
        'core/facility-departments',
        'core/facility-sections' => 'core.sections.manage',
        'core/bed-management' => 'core.beds.manage',
        'core/admitted-patients' => 'core.inpatient.manage',
        'core/laboratory-operations' => 'core.laboratory.manage',
        'core/pharmacy-operations' => 'core.pharmacy.manage',
        'core/facility-patients' => 'core.patients.view',
        'core/patient-appointments' => 'core.appointments.view',
        'core/reminders-notifications-hub' => 'core.reminders.manage',
        'core/facility-reports',
        'core/reports-hub',
        'core/reports-hub/print' => 'core.reports.view',
        default => null,
      };
    }

    if (str_starts_with($path, 'analytics/')) {
      return 'analytics.view';
    }

    if ($path === 'avo/din-activations') {
      return 'avo.din_activation.manage';
    }

    if (str_starts_with($path, 'registers/')) {
      return 'registers.manage';
    }

    if (str_starts_with($path, 'workspaces/')) {
      if ($path === 'workspaces/patient-workspace') {
        return 'workspace.dashboard.view';
      }

      if ($path === 'workspaces/drug-catalog') {
        return 'workspace.drug_catalog.manage';
      }

      $segments = explode('/', $path);
      if (count($segments) < 3) {
        return null;
      }

      if (($segments[1] ?? '') === '') {
        return null;
      }

      $modulePath = implode('/', array_slice($segments, 2));
      if ($modulePath === 'dashboard') {
        return 'workspace.dashboard.view';
      }

      if ($modulePath === 'activities') {
        return 'workspace.activities.view';
      }

      if ($modulePath === 'attendance') {
        return 'workspace.attendance.manage';
      }

      if ($modulePath === 'assessments') {
        return 'workspace.assessments.manage';
      }

      if (str_starts_with($modulePath, 'anc')) {
        return 'workspace.anc.manage';
      }

      if (str_starts_with($modulePath, 'child-health')) {
        return 'workspace.child_health.manage';
      }

      return match ($modulePath) {
        'laboratory' => 'workspace.laboratory.manage',
        'prescriptions' => 'workspace.prescriptions.manage',
        'invoices' => 'workspace.invoices.manage',
        'appointments' => 'workspace.appointments.view',
        'visits' => 'workspace.visits.view',
        'reminders' => 'workspace.reminders.manage',
        'family-planning' => 'workspace.family_planning.manage',
        'health-insurance' => 'workspace.health_insurance.manage',
        'referrals' => 'workspace.referrals.manage',
        'drug-catalog' => 'workspace.drug_catalog.manage',
        default => null,
      };
    }

    return null;
  }

  public static function ensureRoleRows(string $roleName, ?int $changedByUserId = null): void
  {
    if (!self::tableExists()) {
      return;
    }

    $roleName = trim($roleName);
    if ($roleName === '') {
      return;
    }

    $existing = RolePermission::query()
      ->where('role_name', $roleName)
      ->pluck('permission_key')
      ->all();

    foreach (self::permissionDefinitions() as $definition) {
      $key = (string) $definition['key'];
      if (in_array($key, $existing, true)) {
        continue;
      }

      RolePermission::query()->create([
        'role_name' => $roleName,
        'permission_key' => $key,
        'permission_label' => (string) $definition['label'],
        'permission_group' => (string) $definition['group'],
        'is_allowed' => self::defaultAllowed($roleName, $key),
        'last_changed_by_user_id' => $changedByUserId,
      ]);
    }
  }

  public static function defaultAllowed(string $roleName, string $permissionKey): bool
  {
    $matrix = self::defaultMatrix();
    $allowedKeys = $matrix[$roleName] ?? [];
    return in_array($permissionKey, $allowedKeys, true);
  }

  /**
   * @return array<string,array<int,string>>
   */
  private static function defaultMatrix(): array
  {
    $allCentral = [
      'central.dashboard.view',
      'central.admins.manage',
      'central.facilities.manage',
      'central.module_access.manage',
      'central.roles_permissions.manage',
    ];

    $allCoreFacility = [
      'core.facility_dashboard.view',
      'core.staff.manage',
      'core.facility_administration.manage',
      'core.sections.manage',
      'core.beds.manage',
      'core.inpatient.manage',
      'core.laboratory.manage',
      'core.pharmacy.manage',
      'core.patients.view',
      'core.appointments.view',
      'core.reminders.manage',
      'core.reports.view',
    ];

    $allWorkspace = [
      'workspace.dashboard.view',
      'workspace.activities.view',
      'workspace.attendance.manage',
      'workspace.assessments.manage',
      'workspace.anc.manage',
      'workspace.child_health.manage',
      'workspace.laboratory.manage',
      'workspace.prescriptions.manage',
      'workspace.invoices.manage',
      'workspace.appointments.view',
      'workspace.visits.view',
      'workspace.reminders.manage',
      'workspace.family_planning.manage',
      'workspace.health_insurance.manage',
      'workspace.referrals.manage',
      'workspace.drug_catalog.manage',
    ];

    return [
      'Central Admin' => $allCentral,
      'Central Administrator' => $allCentral,
      'Facility Administrator' => array_merge($allCoreFacility, ['analytics.view']),
      'State Data Administrator' => ['core.state_dashboard.view', 'core.reports.view', 'analytics.view'],
      'LGA Officer' => ['core.lga_dashboard.view', 'core.reports.view', 'analytics.view'],
      'Data Officer' => array_merge($allWorkspace, ['registers.manage']),
      'Verification Officer' => ['avo.din_activation.manage'],
      'Patient' => [],
    ];
  }

  private static function tableExists(): bool
  {
    if (self::$tableExists !== null) {
      return self::$tableExists;
    }

    self::$tableExists = Schema::hasTable('role_permissions');
    return self::$tableExists;
  }

  private static function normalizePath(?string $urlOrPath): string
  {
    $raw = trim((string) ($urlOrPath ?? ''));
    if ($raw === '' || $raw === 'javascript:void(0);' || $raw === 'javascript:void(0)') {
      return '';
    }

    $path = parse_url($raw, PHP_URL_PATH);
    if (!is_string($path) || trim($path) === '') {
      $path = $raw;
    }

    return trim(ltrim($path, '/'));
  }

  /**
   * @param  mixed  $node
   * @return mixed
   */
  private static function nodeValue($node, string $key)
  {
    if (is_array($node)) {
      return $node[$key] ?? null;
    }

    if ($node instanceof stdClass) {
      return $node->{$key} ?? null;
    }

    return null;
  }
}
