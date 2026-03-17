<?php

namespace App\Livewire\Central;

use App\Models\Facility;
use App\Models\FacilityModuleAccess;
use App\Models\Patient;
use App\Models\ReminderDispatchLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class CentralAdminDashboard extends Component
{
  public function mount(): void
  {
    $user = Auth::user();
    if (!$user || !in_array((string) $user->role, ['Central Admin', 'Central Administrator'], true)) {
      abort(403, 'Unauthorized: Central admin access only.');
    }
  }

  public function render()
  {
    $tables = [
      'facilities' => Schema::hasTable('facilities'),
      'users' => Schema::hasTable('users'),
      'patients' => Schema::hasTable('patients'),
      'facility_module_accesses' => Schema::hasTable('facility_module_accesses'),
      'reminder_dispatch_logs' => Schema::hasTable('reminder_dispatch_logs'),
    ];

    $summary = [
      'facilities_total' => 0,
      'facilities_active' => 0,
      'users_total' => 0,
      'admins_total' => 0,
      'patients_total' => 0,
      'modules_enabled' => 0,
      'modules_disabled' => 0,
      'dispatch_total_30d' => 0,
      'dispatch_failed_30d' => 0,
      'reports_disabled_facilities' => 0,
    ];

    if ($tables['facilities']) {
      $summary['facilities_total'] = Facility::query()->count();
      $summary['facilities_active'] = Facility::query()->where('is_active', true)->count();
    }

    if ($tables['users']) {
      $summary['users_total'] = User::query()->count();
      $summary['admins_total'] = User::query()
        ->whereIn('role', ['Central Admin', 'Central Administrator', 'Facility Administrator', 'LGA Officer', 'State Data Administrator'])
        ->count();
    }

    if ($tables['patients']) {
      $summary['patients_total'] = Patient::query()->count();
    }

    if ($tables['facility_module_accesses']) {
      $summary['modules_enabled'] = FacilityModuleAccess::query()->where('is_enabled', true)->count();
      $summary['modules_disabled'] = FacilityModuleAccess::query()->where('is_enabled', false)->count();
      $summary['reports_disabled_facilities'] = FacilityModuleAccess::query()
        ->where('module_key', 'reports')
        ->where('is_enabled', false)
        ->distinct()
        ->count('facility_id');
    }

    if ($tables['reminder_dispatch_logs']) {
      $windowStart = now()->subDays(30);
      $summary['dispatch_total_30d'] = ReminderDispatchLog::query()
        ->where('created_at', '>=', $windowStart)
        ->count();
      $summary['dispatch_failed_30d'] = ReminderDispatchLog::query()
        ->where('created_at', '>=', $windowStart)
        ->where('status', 'failed')
        ->count();
    }

    $facilityRows = collect();
    if ($tables['facilities']) {
      $facilityRows = Facility::query()
        ->select(['id', 'name', 'state', 'lga', 'is_active', 'created_at'])
        ->orderByDesc('id')
        ->limit(200)
        ->get();

      $staffCountMap = $tables['users']
        ? User::query()
          ->whereNotNull('facility_id')
          ->selectRaw('facility_id, COUNT(*) as total')
          ->groupBy('facility_id')
          ->pluck('total', 'facility_id')
        : collect();

      $disabledModuleMap = $tables['facility_module_accesses']
        ? FacilityModuleAccess::query()
          ->where('is_enabled', false)
          ->selectRaw('facility_id, COUNT(*) as total')
          ->groupBy('facility_id')
          ->pluck('total', 'facility_id')
        : collect();

      $facilityRows = $facilityRows->map(function ($facility) use ($staffCountMap, $disabledModuleMap) {
        $facility->staff_count = (int) ($staffCountMap[$facility->id] ?? 0);
        $facility->disabled_module_count = (int) ($disabledModuleMap[$facility->id] ?? 0);
        return $facility;
      });
    }

    $moduleRows = collect();
    if ($tables['facility_module_accesses']) {
      $moduleRows = FacilityModuleAccess::query()
        ->selectRaw("
          module_key,
          module_label,
          SUM(CASE WHEN is_enabled = 1 THEN 1 ELSE 0 END) as enabled_count,
          SUM(CASE WHEN is_enabled = 0 THEN 1 ELSE 0 END) as disabled_count,
          COUNT(*) as total_rows
        ")
        ->groupBy('module_key', 'module_label')
        ->orderBy('module_label')
        ->get();
    }

    $dispatchRows = collect();
    if ($tables['reminder_dispatch_logs']) {
      $dispatchRows = ReminderDispatchLog::query()
        ->with('facility:id,name')
        ->select(['id', 'facility_id', 'channel', 'status', 'recipient', 'provider', 'sent_at', 'failed_at', 'created_at'])
        ->latest('id')
        ->limit(200)
        ->get();
    }

    $adminRows = collect();
    if ($tables['users']) {
      $adminRows = User::query()
        ->select(['id', 'first_name', 'last_name', 'email', 'role', 'designation', 'created_at'])
        ->whereIn('role', ['Central Admin', 'Central Administrator', 'Facility Administrator', 'LGA Officer', 'State Data Administrator'])
        ->latest('id')
        ->limit(200)
        ->get();
    }

    return view('livewire.central.central-admin-dashboard', [
      'summary' => $summary,
      'tables' => $tables,
      'facilityRows' => $facilityRows,
      'moduleRows' => $moduleRows,
      'dispatchRows' => $dispatchRows,
      'adminRows' => $adminRows,
    ])->layout('layouts.centralAdminLayout');
  }
}
