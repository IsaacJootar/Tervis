<?php

namespace App\Services\Security;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use stdClass;

/**
 * RolePermissionService
 *
 * Manages role-based access control (RBAC) and permission checking for the application.
 * Handles permission definitions, role-permission relationships, menu rendering based on permissions,
 * and provides utilities for checking user access to various features and pages.
 *
 * This service supports:
 * - Permission verification against roles from the database
 * - Fallback to default permission matrix if database table doesn't exist
 * - Menu item filtering based on user permissions
 * - URL/path to permission key mapping
 * - Menu node activation detection based on current user context
 */
class RolePermissionService
{
  // Cache for checking if role_permissions table exists, prevents repeated schema checks
  private static ?bool $tableExists = null;

  /**
   * Defines all available permissions in the system.
   *
   * Returns a list of permissions grouped by functional area (account, central, core, workspace, etc).
   * Each permission has a unique key, human-readable label, and group for organization.
   *
   * @return array<int,array{key:string,label:string,group:string}> Array of permission definitions
   */
  public static function permissionDefinitions(): array
  {
    return [
      ['key' => 'account.settings.manage', 'label' => 'Manage Account Settings', 'group' => 'account'],

      ['key' => 'central.dashboard.view', 'label' => 'View Central Dashboard', 'group' => 'central'],
      ['key' => 'central.admins.manage', 'label' => 'Manage Administrators', 'group' => 'central'],
      ['key' => 'central.facilities.manage', 'label' => 'Manage Facilities', 'group' => 'central'],
      ['key' => 'central.module_access.manage', 'label' => 'Manage Facility Module Access', 'group' => 'central'],
      ['key' => 'central.roles_permissions.manage', 'label' => 'Manage Roles & Permissions', 'group' => 'central'],
      ['key' => 'central.audit_trail.view', 'label' => 'View Central Audit Trail', 'group' => 'central'],
      ['key' => 'central.notifications.view', 'label' => 'View Platform Notifications', 'group' => 'central'],

      ['key' => 'analytics.view', 'label' => 'View Analytics Dashboards', 'group' => 'analytics'],

      ['key' => 'registers.manage', 'label' => 'Access Registration Entry Points', 'group' => 'registers'],
      ['key' => 'activations.din_activation.manage', 'label' => 'Manage DIN Activations', 'group' => 'verification'],

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
   * Defines all available roles in the system.
   *
   * Returns a list of all role names used for role-based access control.
   * Includes central administrators, facility administrators, state officers, LGA officers, and patient roles.
   *
   * @return array<int,string> Array of role names
   */
  public static function roles(): array
  {
    return [
      'Central Admin',
      'Central Administrator',
      'Facility Administrator',
      'State Data Administrator',
      'State Administrator',
      'LGA Officer',
      'LGA Data Administrator',
      'LGA Administrator',
      'Data Officer',
      'Verification Officer',
      'Patient',
    ];
  }

  /**
   * Checks if a user has permission to perform a specific action.
   *
   * First validates the user and permission key, then checks the database for the permission.
   * Falls back to default permission matrix if the table doesn't exist or permission isn't found.
   * Includes backward compatibility for legacy permission key names.
   *
   * @param  ?User  $user The user to check (null returns false)
   * @param  string  $permissionKey The permission identifier (e.g., 'core.staff.manage')
   * @return bool True if the user's role has this permission, false otherwise
   */
  public static function can(?User $user, string $permissionKey): bool
  {
    // Deny access if no user is provided
    if (!$user) {
      return false;
    }

    // Validate and trim the permission key
    $permissionKey = trim($permissionKey);
    if ($permissionKey === '') {
      return false;
    }

    // Get the user's role, ensure it exists
    $roleName = trim((string) ($user->role ?? ''));
    if ($roleName === '') {
      return false;
    }

    // Check database if the role_permissions table exists
    if (self::tableExists()) {
      $row = RolePermission::query()
        ->where('role_name', $roleName)
        ->where('permission_key', $permissionKey)
        ->first();

      if ($row) {
        return (bool) $row->is_allowed;
      }

      // Backward compatibility: check legacy permission key for DIN activation
      if ($permissionKey === 'activations.din_activation.manage') {
        $legacyRow = RolePermission::query()
          ->where('role_name', $roleName)
          ->where('permission_key', 'avo.din_activation.manage')
          ->first();
        if ($legacyRow) {
          return (bool) $legacyRow->is_allowed;
        }
      }
    }

    // Fall back to default permission matrix
    return self::defaultAllowed($roleName, $permissionKey);
  }

  /**
   * Checks if a user can access a specific menu URL.
   *
   * Maps the URL to its required permission key, then verifies the user has that permission.
   * URLs with no permission mapping are allowed by default.
   *
   * @param  ?User  $user The user to check
   * @param  ?string  $url The URL/path to check access for
   * @return bool True if user can access the URL, false otherwise
   */
  public static function canAccessMenuUrl(?User $user, ?string $url): bool
  {
    // Get the permission required for this URL
    $permissionKey = self::permissionForPath($url);

    // If no permission mapping exists, allow access by default
    if ($permissionKey === null) {
      return true;
    }

    // Check if user has the required permission
    return self::can($user, $permissionKey);
  }

  /**
   * Determines if a menu node should be rendered for the given user.
   *
   * A menu node is rendered if:
   * - It has no URL (category), and has at least one visible child
   * - It has a URL that the user has permission to access
   * - It doesn't have a submenu
   *
   * This supports recursive menu structures with categories and items.
   *
   * @param  ?User  $user The user context
   * @param  mixed  $node The menu node (array or stdClass) to evaluate
   * @return bool True if the node should be rendered, false if it should be hidden
   */
  public static function canRenderMenuNode(?User $user, $node): bool
  {
    // Only process array or object nodes
    if (!$node instanceof stdClass && !is_array($node)) {
      return true;
    }

    // Check if user can access this node's URL (if it exists)
    $url = (string) (self::nodeValue($node, 'url') ?? '');
    if ($url !== '' && !self::canAccessMenuUrl($user, $url)) {
      return false;
    }

    // Check submenu items for visibility
    $submenu = self::nodeValue($node, 'submenu');
    if (is_iterable($submenu)) {
      $hasVisibleChild = false;
      foreach ($submenu as $child) {
        if (self::canRenderMenuNode($user, $child)) {
          $hasVisibleChild = true;
          break;
        }
      }

      // Hide menu categories that have no visible children and no URL
      if (!$hasVisibleChild && $url === '') {
        return false;
      }
    }

    return true;
  }

  /**
   * Maps a URL/path to its required permission key.
   *
   * Translates application URLs into permission keys needed to access those resources.
   * Used by the permission checking system to determine what permission a user needs for a given page.
   *
   * @param  ?string  $urlOrPath The URL or path to map (e.g., 'core/staff-management')
   * @return ?string The permission key required, or null if no permission is required
   */
  public static function permissionForPath(?string $urlOrPath): ?string
  {
    // Normalize the URL path
    $path = self::normalizePath($urlOrPath);
    if ($path === '') {
      return null;
    }

    // Account section permission
    if ($path === 'account/settings') {
      return 'account.settings.manage';
    }

    // Central administration section permissions
    if (str_starts_with($path, 'central/')) {
      return match ($path) {
        'central/central-admin-dashboard' => 'central.dashboard.view',
        'central/create-administrators' => 'central.admins.manage',
        'central/create-facility' => 'central.facilities.manage',
        'central/facility-module-management' => 'central.module_access.manage',
        'central/roles-permissions' => 'central.roles_permissions.manage',
        'central/audit-trail' => 'central.audit_trail.view',
        'central/platform-notifications' => 'central.notifications.view',
        default => null,
      };
    }

    // Core facility management section permissions
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

    // Analytics section permissions
    if (str_starts_with($path, 'analytics/')) {
      return 'analytics.view';
    }

    // Activation/verification section permissions (with legacy path support)
    if ($path === 'activations/din-activations' || $path === 'avo/din-activations') {
      return 'activations.din_activation.manage';
    }

    // Registration section permissions
    if (str_starts_with($path, 'registers/')) {
      return 'registers.manage';
    }

    // Workspace/patient section permissions
    if (str_starts_with($path, 'workspaces/')) {
      // Main workspace dashboard access
      if ($path === 'workspaces/patient-workspace') {
        return 'workspace.dashboard.view';
      }

      // Queue management in workspace
      if ($path === 'workspaces/pending-queues') {
        return 'workspace.dashboard.view';
      }

      // Drug catalog in workspace
      if ($path === 'workspaces/drug-catalog') {
        return 'workspace.drug_catalog.manage';
      }

      // Parse workspace module paths (format: workspaces/{patientId}/{module})
      $segments = explode('/', $path);
      if (count($segments) < 3) {
        return null;
      }

      // Ensure a patient ID is provided
      if (($segments[1] ?? '') === '') {
        return null;
      }

      // Extract the module/feature part of the path
      $modulePath = implode('/', array_slice($segments, 2));
      // Map specific workspace modules to their permissions
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

      // Antenatal Care (ANC) modules
      if (str_starts_with($modulePath, 'anc')) {
        return 'workspace.anc.manage';
      }

      // Child health modules
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

  /**
   * Determines if a menu node should be marked as active for the current page.
   *
   * Compares the menu node against the current route and path. A node is active if:
   * - Its slug matches the current route name
   * - Its URL matches the current path
   * - Any of its submenus are active (recursive check)
   *
   * @param  mixed  $node The menu node (array or stdClass) to check
   * @param  ?string  $currentRouteName The current Laravel route name
   * @param  ?string  $currentPath The current URL path
   * @return bool True if this menu node should be highlighted as active
   */
  public static function isMenuNodeActive($node, ?string $currentRouteName, ?string $currentPath): bool
  {
    // Normalize the current route name and path
    $routeName = trim((string) ($currentRouteName ?? ''));
    $path = self::normalizePath($currentPath);

    // Check if node's slug(s) match the current route name
    $slug = self::nodeValue($node, 'slug');
    foreach (self::extractSlugs($slug) as $candidateSlug) {
      if ($candidateSlug === '') {
        continue;
      }

      // Exact route name match
      if ($routeName === $candidateSlug) {
        return true;
      }

      // Prefix route match (e.g., 'admin.users.edit' starts with 'admin.users')
      if ($routeName !== '' && str_contains($routeName, $candidateSlug) && strpos($routeName, $candidateSlug) === 0) {
        return true;
      }
    }

    // Check if node's URL matches the current path
    $url = trim((string) (self::nodeValue($node, 'url') ?? ''));
    $urlPath = self::normalizePath($url);
    if ($urlPath !== '' && $path !== '') {
      // Exact path match
      if ($path === $urlPath) {
        return true;
      }

      // Prefix path match (e.g., 'core/staff/create' is under 'core/staff')
      if (str_starts_with($path . '/', $urlPath . '/')) {
        return true;
      }
    }

    // Recursively check if any child menu items are active
    $submenu = self::nodeValue($node, 'submenu');
    if (is_iterable($submenu)) {
      foreach ($submenu as $child) {
        if (self::isMenuNodeActive($child, $routeName, $path)) {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * Ensures all permission rows exist in the database for a given role.
   *
   * Creates missing permission records for a role, initializing them with default values.
   * Useful when adding a new role to the system or after adding new permission definitions.
   * Does nothing if the role_permissions table doesn't exist.
   *
   * @param  string  $roleName The role to initialize permissions for
   * @param  ?int  $changedByUserId Optional user ID to track who made this change
   * @return void
   */
  public static function ensureRoleRows(string $roleName, ?int $changedByUserId = null): void
  {
    // Skip if the table doesn't exist
    if (!self::tableExists()) {
      return;
    }

    // Validate the role name
    $roleName = trim($roleName);
    if ($roleName === '') {
      return;
    }

    // Get all existing permission keys for this role
    $existing = RolePermission::query()
      ->where('role_name', $roleName)
      ->pluck('permission_key')
      ->all();

    // Create missing permission rows
    foreach (self::permissionDefinitions() as $definition) {
      $key = (string) $definition['key'];

      // Skip if permission already exists
      if (in_array($key, $existing, true)) {
        continue;
      }

      // Create new permission record with default values
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

  /**
   * Checks if a permission is allowed for a role according to the default permission matrix.
   *
   * Used as a fallback when the database table doesn't exist or for initial role setup.
   *
   * @param  string  $roleName The role to check
   * @param  string  $permissionKey The permission to check
   * @return bool True if the role should have this permission by default
   */
  public static function defaultAllowed(string $roleName, string $permissionKey): bool
  {
    // Get the default permission matrix
    $matrix = self::defaultMatrix();

    // Get permissions for this role, default to empty array if role not found
    $allowedKeys = $matrix[$roleName] ?? [];

    // Check if the permission is in the allowed list for this role
    return in_array($permissionKey, $allowedKeys, true);
  }

  /**
   * Defines the default permission matrix for all roles in the system.
   *
   * This matrix is used when the role_permissions table doesn't exist (initial setup)
   * or as a fallback when checking permissions. Each role is assigned a set of permissions.
   *
   * Permission groups:
   * - Account: User account settings
   * - Central: Central administration features
   * - Core: Facility/organization core operations
   * - Workspace: Patient and clinical workspace features
   *
   * @return array<string,array<int,string>> Role name to permission keys mapping
   */
  private static function defaultMatrix(): array
  {
    // Permissions available to all authenticated users
    $allAuthenticated = [
      'account.settings.manage',
    ];

    // Central administration permissions (for central admin roles)
    $allCentral = [
      'central.dashboard.view',
      'central.admins.manage',
      'central.facilities.manage',
      'central.module_access.manage',
      'central.roles_permissions.manage',
      'central.audit_trail.view',
      'central.notifications.view',
    ];

    // Core facility management permissions
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

    // Workspace/clinical operations permissions
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
    ];

    // Return the role-to-permissions matrix
    return [
      // Central platform administrators with full access
      'Central Admin' => array_merge($allAuthenticated, $allCentral),
      'Central Administrator' => array_merge($allAuthenticated, $allCentral),

      // Facility administrators with facility operations and analytics
      'Facility Administrator' => array_merge($allAuthenticated, $allCoreFacility, ['analytics.view', 'workspace.dashboard.view', 'workspace.drug_catalog.manage']),

      // State-level officers with state dashboard and reporting
      'State Data Administrator' => array_merge($allAuthenticated, ['core.state_dashboard.view', 'core.reports.view', 'analytics.view']),
      'State Administrator' => array_merge($allAuthenticated, ['core.state_dashboard.view', 'core.reports.view', 'analytics.view']),

      // LGA (Local Government Area) officers with LGA dashboard and reporting
      'LGA Officer' => array_merge($allAuthenticated, ['core.lga_dashboard.view', 'core.reports.view', 'analytics.view']),
      'LGA Data Administrator' => array_merge($allAuthenticated, ['core.lga_dashboard.view', 'core.reports.view', 'analytics.view']),
      'LGA Administrator' => array_merge($allAuthenticated, ['core.lga_dashboard.view', 'core.reports.view', 'analytics.view']),

      // Data officers who work directly with patient data
      'Data Officer' => array_merge($allAuthenticated, $allWorkspace, ['registers.manage']),

      // Verification officers handling activation verification
      'Verification Officer' => array_merge($allAuthenticated, ['activations.din_activation.manage']),

      // Patient accounts with basic access only
      'Patient' => $allAuthenticated,
    ];
  }

  /**
   * Checks if the role_permissions table exists in the database.
   *
   * Caches the result to avoid repeated schema checks during the request lifecycle.
   *
   * @return bool True if the role_permissions table exists, false otherwise
   */
  private static function tableExists(): bool
  {
    // Return cached value if already checked
    if (self::$tableExists !== null) {
      return self::$tableExists;
    }

    // Check table existence and cache the result
    self::$tableExists = Schema::hasTable('role_permissions');
    return self::$tableExists;
  }

  /**
   * Normalizes a URL or path into a consistent format for comparison.
   *
   * Removes scheme/domain, normalizes slashes, and filters out javascript: pseudo-URLs.
   * Result is a path like 'core/staff-management' without leading slash.
   *
   * @param  ?string  $urlOrPath Raw URL or path to normalize
   * @return string Normalized path, or empty string if input is invalid/empty
   */
  private static function normalizePath(?string $urlOrPath): string
  {
    // Get the raw input and trim whitespace
    $raw = trim((string) ($urlOrPath ?? ''));

    // Handle empty or invalid pseudo-URLs
    if ($raw === '' || $raw === 'javascript:void(0);' || $raw === 'javascript:void(0)') {
      return '';
    }

    // Extract path component from full URL if needed
    $path = parse_url($raw, PHP_URL_PATH);
    if (!is_string($path) || trim($path) === '') {
      // If parse_url didn't extract a path, use the raw input
      $path = $raw;
    }

    // Remove leading slash and trim whitespace
    return trim(ltrim($path, '/'));
  }

  /**
   * Extracts route slug(s) from various input types.
   *
   * Converts a slug value (which can be a string, array, or traversable) into an array of strings.
   * Handles type conversion and filtering of empty values.
   *
   * @param  mixed  $slug The slug value (string, array, or Traversable)
   * @return array<int,string> Array of non-empty slug strings
   */
  private static function extractSlugs($slug): array
  {
    // Handle string slugs
    if (is_string($slug)) {
      return [trim($slug)];
    }

    // Handle array slugs - filter out empty strings after trimming
    if (is_array($slug)) {
      return array_values(array_filter(array_map(static function ($item) {
        return is_string($item) ? trim($item) : '';
      }, $slug), static fn($item) => $item !== ''));
    }

    // Handle Traversable types (iterators, generators, etc.)
    if ($slug instanceof \Traversable) {
      $values = [];
      foreach ($slug as $item) {
        // Only add non-empty strings
        if (is_string($item) && trim($item) !== '') {
          $values[] = trim($item);
        }
      }
      return $values;
    }

    // Return empty array for unsupported types
    return [];
  }

  /**
   * Safely extracts a value from a menu node (array or object).
   *
   * Handles both associative array and stdClass object representations of menu nodes,
   * returning the value for a given key or null if it doesn't exist.
   *
   * @param  mixed  $node The node to extract from (array or stdClass)
   * @param  string  $key The key/property name to extract
   * @return mixed The value at the key, or null if not found
   */
  private static function nodeValue($node, string $key)
  {
    // Handle array nodes
    if (is_array($node)) {
      return $node[$key] ?? null;
    }

    // Handle object nodes
    if ($node instanceof stdClass) {
      return $node->{$key} ?? null;
    }

    // Return null for unsupported types
    return null;
  }
}
