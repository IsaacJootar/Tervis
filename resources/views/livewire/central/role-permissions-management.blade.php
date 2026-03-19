@php
    use Carbon\Carbon;
@endphp

@section('title', 'Roles & Permissions')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Central Control</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-lock-alt me-1"></i>Roles & Permissions</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Define permission policy once at Central and enforce across all users.</div>
            </div>
            <div class="ms-auto d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" wire:click="seedSelectedRoleDefaults" wire:loading.attr="disabled" wire:target="seedSelectedRoleDefaults">
                    <span wire:loading.remove wire:target="seedSelectedRoleDefaults"><i class="bx bx-refresh me-1"></i>Ensure Defaults</span>
                    <span wire:loading wire:target="seedSelectedRoleDefaults"><span class="spinner-border spinner-border-sm me-1"></span>Processing...</span>
                </button>
            </div>
        </div>
    </div>

    @if (!$tables_ready)
        <div class="alert alert-warning mb-4">
            <strong>Role permissions table is not ready.</strong>
            Run `php artisan migrate` to create `role_permissions`.
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Select Role</label>
                    <select class="form-select" wire:model.live="selected_role">
                        <option value="">Select role...</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 text-md-end text-start">
                    @if (!empty($selected_role))
                        <div class="small text-muted">Managing role: <strong>{{ $selected_role }}</strong></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Permissions</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M9 7h10M9 12h10M9 17h10M4 7h.01M4 12h.01M4 17h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Allowed</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['allowed'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-rose h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Blocked</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M9 9l6 6M15 9l-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['blocked'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Groups</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 5h6v6H5V5zm8 0h6v6h-6V5zM5 13h6v6H5v-6zm8 0h6v6h-6v-6z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['groups'] }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Permission Matrix (Selected Role)</h5></div>
        <div class="card-datatable table-responsive pt-0">
            <table id="centralRolePermissionsTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Group</th>
                        <th>Permission Label</th>
                        <th>Permission Key</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="text-uppercase">{{ $row->permission_group ?: 'general' }}</td>
                            <td class="fw-semibold">{{ $row->permission_label ?: $row->permission_key }}</td>
                            <td><code>{{ $row->permission_key }}</code></td>
                            <td>
                                <span class="badge {{ $row->is_allowed ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ $row->is_allowed ? 'Allowed' : 'Blocked' }}
                                </span>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm {{ $row->is_allowed ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    wire:click="togglePermission({{ $row->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="togglePermission({{ $row->id }})">
                                    <span wire:loading.remove wire:target="togglePermission({{ $row->id }})">{{ $row->is_allowed ? 'Block' : 'Allow' }}</span>
                                    <span wire:loading wire:target="togglePermission({{ $row->id }})"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No permission rows found for selected role.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .metric-card { border-radius: 18px; border: 1px solid rgba(148,163,184,.25); padding: 14px 16px; min-height: 108px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 10px 26px -22px rgba(15,23,42,.45); }
        .metric-label { font-size: 11px; text-transform: uppercase; letter-spacing: .14em; font-weight: 700; }
        .metric-value { margin-top: 6px; font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
        .metric-icon { width: 32px; height: 32px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: rgba(15,23,42,.08); font-size: 18px; }
        .metric-icon svg { width: 18px; height: 18px; }
        .metric-card-slate { border-color: #cbd5e1; background: #f8fafc; color: #0f172a; }
        .metric-card-emerald { border-color: #a7f3d0; background: #ecfdf5; color: #065f46; }
        .metric-card-rose { border-color: #fecdd3; background: #fff1f2; color: #9f1239; }
        .metric-card-sky { border-color: #bae6fd; background: #f0f9ff; color: #075985; }
        .form-label { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; color: #64748b; }
    </style>

    @include('_partials.datatables-init-multi', [
        'tableIds' => ['centralRolePermissionsTable'],
        'orders' => [
            'centralRolePermissionsTable' => [0, 'asc'],
        ],
    ])
</div>
