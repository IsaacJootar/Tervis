@php
    use App\Services\Security\RolePermissionService;
    use Carbon\Carbon;
    $authUser = auth()->user();
@endphp

@section('title', 'Central Admin Dashboard')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Central Overview</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-shield-quarter me-1"></i>Central Admin Dashboard</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Cross-facility visibility, module governance, and dispatch monitoring.</div>
            </div>
            <div class="ms-auto d-flex flex-wrap gap-2">
                @if (RolePermissionService::can($authUser, 'central.admins.manage'))
                    <a href="{{ url('/central/create-administrators') }}" class="btn btn-outline-primary">
                        <i class="bx bx-user-plus me-1"></i>Manage Administrators
                    </a>
                @endif
                @if (RolePermissionService::can($authUser, 'central.facilities.manage'))
                    <a href="{{ url('/central/create-facility') }}" class="btn btn-outline-primary">
                        <i class="bx bx-buildings me-1"></i>Manage Facilities
                    </a>
                @endif
                @if (RolePermissionService::can($authUser, 'central.module_access.manage'))
                    <a href="{{ url('/central/facility-module-management') }}" class="btn btn-primary">
                        <i class="bx bx-slider-alt me-1"></i>Facility Module Access
                    </a>
                @endif
                @if (RolePermissionService::can($authUser, 'central.audit_trail.view'))
                    <a href="{{ url('/central/audit-trail') }}" class="btn btn-outline-dark">
                        <i class="bx bx-history me-1"></i>Audit Trail
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if (in_array(false, $tables, true))
        <div class="alert alert-warning mb-4">
            <strong>Some dashboard widgets are unavailable.</strong>
            Required tables are missing. Run `php artisan migrate` and refresh.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Facilities</div>
                    <span class="metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 19V5h14v14M9 9h2M13 9h2M9 13h2M13 13h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                </div>
                <div class="metric-value">{{ $summary['facilities_total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Active Facilities</div>
                    <span class="metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </div>
                <div class="metric-value">{{ $summary['facilities_active'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Users</div>
                    <span class="metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M7.5 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm9 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 20a4.5 4.5 0 0 1 9 0M12 20a4.5 4.5 0 0 1 9 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                </div>
                <div class="metric-value">{{ $summary['users_total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-violet h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Admin Users</div>
                    <span class="metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3l7 4v5c0 4.5-3 7-7 9-4-2-7-4.5-7-9V7l7-4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="m9.5 12 1.7 1.7 3.3-3.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </div>
                <div class="metric-value">{{ $summary['admins_total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-gold h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Patients</div>
                    <span class="metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4.5 19v-1a4.5 4.5 0 0 1 9 0v1M9 10.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm9.5 8.5v-5m-2.5 2.5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                </div>
                <div class="metric-value">{{ $summary['patients_total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-teal h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Modules Enabled</div>
                    <span class="metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 5h6v6H5V5zm8 0h6v6h-6V5zM5 13h6v6H5v-6zm8 0h6v6h-6v-6z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
                </div>
                <div class="metric-value">{{ $summary['modules_enabled'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-rose h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Modules Disabled</div>
                    <span class="metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                </div>
                <div class="metric-value">{{ $summary['modules_disabled'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-indigo h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Dispatch Failed (30d)</div>
                    <span class="metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 9v4m0 4h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M10.3 3.7 2.7 17a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
                </div>
                <div class="metric-value">{{ $summary['dispatch_failed_30d'] }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Operations Snapshot</h5></div>
                <div class="card-body">
                    <div class="small text-muted mb-2">Last 30 days</div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Reminder Dispatch Attempts</span><strong>{{ number_format($summary['dispatch_total_30d']) }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Reminder Dispatch Failures</span><strong>{{ number_format($summary['dispatch_failed_30d']) }}</strong></div>
                    <div class="d-flex justify-content-between py-2"><span>Facilities With Reports Module Disabled</span><strong>{{ number_format($summary['reports_disabled_facilities']) }}</strong></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Quick Access</h5></div>
                <div class="card-body d-flex flex-column gap-2">
                    @if (RolePermissionService::can($authUser, 'central.admins.manage'))
                        <a href="{{ url('/central/create-administrators') }}" class="btn btn-outline-dark text-start"><i class="bx bx-user-plus me-1"></i>Open Administrators</a>
                    @endif
                    @if (RolePermissionService::can($authUser, 'central.facilities.manage'))
                        <a href="{{ url('/central/create-facility') }}" class="btn btn-outline-dark text-start"><i class="bx bx-buildings me-1"></i>Open Facilities</a>
                    @endif
                    @if (RolePermissionService::can($authUser, 'central.module_access.manage'))
                        <a href="{{ url('/central/facility-module-management') }}" class="btn btn-outline-dark text-start"><i class="bx bx-slider-alt me-1"></i>Open Module Access</a>
                    @endif
                    @if (RolePermissionService::can($authUser, 'central.roles_permissions.manage'))
                        <a href="{{ url('/central/roles-permissions') }}" class="btn btn-outline-dark text-start"><i class="bx bx-lock me-1"></i>Open Roles & Permissions</a>
                    @endif
                    @if (RolePermissionService::can($authUser, 'central.audit_trail.view'))
                        <a href="{{ url('/central/audit-trail') }}" class="btn btn-outline-dark text-start"><i class="bx bx-history me-1"></i>Open Audit Trail</a>
                    @endif
                    @if (RolePermissionService::can($authUser, 'central.notifications.view'))
                        <a href="{{ url('/central/platform-notifications') }}" class="btn btn-outline-dark text-start"><i class="bx bx-bell me-1"></i>Open Platform Notifications</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Facilities Overview</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="centralFacilitiesTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Facility</th>
                        <th>State/LGA</th>
                        <th>Status</th>
                        <th>Staff</th>
                        <th>Disabled Modules</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($facilityRows as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->name }}</td>
                            <td>{{ $row->state ?: 'N/A' }} / {{ $row->lga ?: 'N/A' }}</td>
                            <td><span class="badge {{ $row->is_active ? 'bg-label-success' : 'bg-label-danger' }}">{{ $row->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>{{ $row->staff_count }}</td>
                            <td>{{ $row->disabled_module_count }}</td>
                            <td data-order="{{ optional($row->created_at)->format('Y-m-d H:i:s') }}">{{ optional($row->created_at)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No facilities found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Module Enablement Distribution</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="centralModuleDistributionTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Module</th>
                        <th>Enabled Rows</th>
                        <th>Disabled Rows</th>
                        <th>Total Rows</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($moduleRows as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->module_label ?: $row->module_key }}</td>
                            <td>{{ (int) $row->enabled_count }}</td>
                            <td>{{ (int) $row->disabled_count }}</td>
                            <td>{{ (int) $row->total_rows }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">No module access rows found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Recent Reminder Dispatch Logs</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="centralDispatchLogsTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Facility</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>Recipient</th>
                        <th>Provider</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dispatchRows as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->facility?->name ?: 'N/A' }}</td>
                            <td>{{ strtoupper((string) $row->channel) }}</td>
                            <td><span class="badge {{ $row->status === 'sent' ? 'bg-label-success' : ($row->status === 'failed' ? 'bg-label-danger' : 'bg-label-secondary') }}">{{ ucfirst((string) $row->status) }}</span></td>
                            <td>{{ $row->recipient ?: 'N/A' }}</td>
                            <td>{{ $row->provider ?: 'N/A' }}</td>
                            <td data-order="{{ optional($row->created_at)->format('Y-m-d H:i:s') }}">{{ optional($row->created_at)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No reminder dispatch logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Recent Administrators</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="centralAdminsTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Designation</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($adminRows as $row)
                        <tr>
                            <td class="fw-semibold">{{ trim($row->first_name . ' ' . $row->last_name) ?: 'N/A' }}</td>
                            <td>{{ $row->email ?: 'N/A' }}</td>
                            <td>{{ $row->role ?: 'N/A' }}</td>
                            <td>{{ $row->designation ?: 'N/A' }}</td>
                            <td data-order="{{ optional($row->created_at)->format('Y-m-d H:i:s') }}">{{ optional($row->created_at)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No administrators found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .metric-card { border-radius: 18px; border: 1px solid rgba(148,163,184,.25); padding: 14px 16px; min-height: 108px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 10px 26px -22px rgba(15,23,42,.45); }
        .metric-label { font-size: 11px; text-transform: uppercase; letter-spacing: .14em; font-weight: 700; }
        .metric-value { margin-top: 6px; font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
        .metric-icon { width: 32px; height: 32px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: rgba(15,23,42,.08); }
        .metric-icon svg { width: 18px; height: 18px; }
        .metric-card-slate { border-color: #cbd5e1; background: #f8fafc; color: #0f172a; }
        .metric-card-emerald { border-color: #a7f3d0; background: #ecfdf5; color: #065f46; }
        .metric-card-sky { border-color: #bae6fd; background: #f0f9ff; color: #075985; }
        .metric-card-violet { border-color: #ddd6fe; background: #f5f3ff; color: #5b21b6; }
        .metric-card-gold { border-color: #fde68a; background: #fffbeb; color: #92400e; }
        .metric-card-teal { border-color: #99f6e4; background: #f0fdfa; color: #115e59; }
        .metric-card-rose { border-color: #fecdd3; background: #fff1f2; color: #9f1239; }
        .metric-card-indigo { border-color: #c7d2fe; background: #eef2ff; color: #3730a3; }
    </style>

    @include('_partials.datatables-init-multi', [
        'tableIds' => [
            'centralFacilitiesTable',
            'centralModuleDistributionTable',
            'centralDispatchLogsTable',
            'centralAdminsTable',
        ],
        'orders' => [
            'centralFacilitiesTable' => [5, 'desc'],
            'centralModuleDistributionTable' => [0, 'asc'],
            'centralDispatchLogsTable' => [5, 'desc'],
            'centralAdminsTable' => [4, 'desc'],
        ],
    ])
</div>
