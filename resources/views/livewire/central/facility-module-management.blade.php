@php
    use Carbon\Carbon;
@endphp

@section('title', 'Facility Module Management')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Central Control</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-shield-quarter me-1"></i>Facility Module Access Management</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Central admin controls which modules are enabled per facility.</div>
            </div>
        </div>
    </div>

    @if (!$tables_ready)
        <div class="alert alert-warning mb-4">
            <strong>Module access table is not ready.</strong>
            Run `php artisan migrate` to create `facility_module_accesses`.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Facilities</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 19V5h14v14M9 9h2M13 9h2M9 13h2M13 13h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['facilities_total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Active Facilities</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['facilities_active'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Enabled Modules</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 5h6v6H5V5zm8 0h6v6h-6V5zM5 13h6v6H5v-6zm8 0h6v6h-6v-6z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['modules_enabled'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-violet h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Disabled Modules</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['modules_disabled'] }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Select Facility</label>
                    <select class="form-select" wire:model.live="selected_facility_id">
                        <option value="">Select facility...</option>
                        @foreach ($facilities as $facility)
                            <option value="{{ $facility->id }}">{{ $facility->name }} ({{ $facility->lga ?: 'N/A' }}, {{ $facility->state ?: 'N/A' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 text-md-end text-start">
                    @if ($selectedFacility)
                        <div class="small text-muted">
                            Managing: <strong>{{ $selectedFacility->name }}</strong>
                            <span class="ms-2 badge {{ $selectedFacility->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                                {{ $selectedFacility->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Facility Module Access Table</h5></div>
        <div class="card-datatable table-responsive pt-0">
            <table id="centralFacilityModuleAccessTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Module Key</th>
                        <th>Module Label</th>
                        <th>Status</th>
                        <th>Updated At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($moduleRows as $module)
                        <tr>
                            <td class="fw-semibold">{{ $module->module_key }}</td>
                            <td>{{ $module->module_label }}</td>
                            <td>
                                <span class="badge {{ $module->is_enabled ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ $module->is_enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </td>
                            <td data-order="{{ optional($module->updated_at)->format('Y-m-d H:i:s') }}">{{ optional($module->updated_at)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm {{ $module->is_enabled ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    wire:click="toggleModuleAccess({{ $module->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleModuleAccess({{ $module->id }})">
                                    <span wire:loading.remove wire:target="toggleModuleAccess({{ $module->id }})">{{ $module->is_enabled ? 'Disable' : 'Enable' }}</span>
                                    <span wire:loading wire:target="toggleModuleAccess({{ $module->id }})"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No module access rows available for the selected facility.</td>
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
        .metric-icon { width: 32px; height: 32px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: rgba(15,23,42,.08); }
        .metric-icon svg { width: 18px; height: 18px; }
        .metric-card-slate { border-color: #cbd5e1; background: #f8fafc; color: #0f172a; }
        .metric-card-emerald { border-color: #a7f3d0; background: #ecfdf5; color: #065f46; }
        .metric-card-sky { border-color: #bae6fd; background: #f0f9ff; color: #075985; }
        .metric-card-violet { border-color: #ddd6fe; background: #f5f3ff; color: #5b21b6; }
        .form-label { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; color: #64748b; }
    </style>

    @include('_partials.datatables-init-multi', [
        'tableIds' => ['centralFacilityModuleAccessTable'],
        'orders' => [
            'centralFacilityModuleAccessTable' => [1, 'asc'],
        ],
    ])
</div>

