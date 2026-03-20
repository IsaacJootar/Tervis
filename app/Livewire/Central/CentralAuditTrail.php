<?php

namespace App\Livewire\Central;

use App\Models\Facility;
use App\Models\FacilityAdminAudit;
use App\Models\StaffManagementAudit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class CentralAuditTrail extends Component
{
  public string $selected_facility_id = '';
  public string $selected_source = 'all';
  public string $selected_action = '';

  public function mount(): void
  {
    $user = Auth::user();
    if (!$user || !in_array((string) $user->role, ['Central Admin', 'Central Administrator'], true)) {
      abort(403, 'Unauthorized: Central admin access only.');
    }
  }

  public function render()
  {
    $hasFacilityAdminAudits = Schema::hasTable('facility_admin_audits');
    $hasStaffAudits = Schema::hasTable('staff_management_audits');
    $hasFacilities = Schema::hasTable('facilities');

    $facilityId = (int) $this->selected_facility_id;
    $actionFilter = trim($this->selected_action);
    $sourceFilter = trim($this->selected_source);

    $facilityRows = $hasFacilities
      ? Facility::query()->select(['id', 'name'])->orderBy('name')->get()
      : collect();

    $facilityAudits = collect();
    if ($hasFacilityAdminAudits && ($sourceFilter === 'all' || $sourceFilter === 'facility_admin')) {
      $query = FacilityAdminAudit::query()
        ->with('facility:id,name')
        ->select(['id', 'facility_id', 'action', 'target_type', 'target_id', 'changed_by_name', 'notes', 'created_at'])
        ->latest('id')
        ->limit(500);

      if ($facilityId > 0) {
        $query->where('facility_id', $facilityId);
      }
      if ($actionFilter !== '') {
        $query->where('action', 'like', '%' . $actionFilter . '%');
      }

      $facilityAudits = $query->get()->map(function (FacilityAdminAudit $row) {
        return [
          'source' => 'Facility Administration',
          'facility' => (string) ($row->facility?->name ?? 'N/A'),
          'action' => (string) ($row->action ?? 'N/A'),
          'target' => trim((string) ($row->target_type ?? 'N/A') . (!empty($row->target_id) ? (' #' . $row->target_id) : '')),
          'changed_by' => (string) ($row->changed_by_name ?? 'N/A'),
          'notes' => (string) ($row->notes ?? ''),
          'created_at' => optional($row->created_at),
        ];
      });
    }

    $staffAudits = collect();
    if ($hasStaffAudits && ($sourceFilter === 'all' || $sourceFilter === 'staff_management')) {
      $query = StaffManagementAudit::query()
        ->with('facility:id,name')
        ->select(['id', 'facility_id', 'action', 'target_user_id', 'changed_by_name', 'notes', 'created_at'])
        ->latest('id')
        ->limit(500);

      if ($facilityId > 0) {
        $query->where('facility_id', $facilityId);
      }
      if ($actionFilter !== '') {
        $query->where('action', 'like', '%' . $actionFilter . '%');
      }

      $staffAudits = $query->get()->map(function (StaffManagementAudit $row) {
        return [
          'source' => 'Staff Management',
          'facility' => (string) ($row->facility?->name ?? 'N/A'),
          'action' => (string) ($row->action ?? 'N/A'),
          'target' => !empty($row->target_user_id) ? ('User #' . $row->target_user_id) : 'N/A',
          'changed_by' => (string) ($row->changed_by_name ?? 'N/A'),
          'notes' => (string) ($row->notes ?? ''),
          'created_at' => optional($row->created_at),
        ];
      });
    }

    $rows = $facilityAudits
      ->merge($staffAudits)
      ->sortByDesc(fn(array $row) => $row['created_at']?->timestamp ?? 0)
      ->values()
      ->take(800);

    $actions = $rows
      ->pluck('action')
      ->filter()
      ->unique()
      ->sort()
      ->values();

    $summary = [
      'total_rows' => $rows->count(),
      'facility_admin_rows' => $rows->where('source', 'Facility Administration')->count(),
      'staff_rows' => $rows->where('source', 'Staff Management')->count(),
      'last_event_at' => optional($rows->first()['created_at'] ?? null)?->format('M d, Y h:i A') ?? 'N/A',
    ];

    return view('livewire.central.central-audit-trail', [
      'rows' => $rows,
      'summary' => $summary,
      'actions' => $actions,
      'facilityRows' => $facilityRows,
      'tablesReady' => $hasFacilityAdminAudits || $hasStaffAudits,
    ])->layout('layouts.centralAdminLayout');
  }
}

