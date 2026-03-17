@php
    use Carbon\Carbon;
@endphp

@section('title', 'Facility Administration')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Facility Administration</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-cog me-1"></i>Facility Administration</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Manage facility profile, service catalog, fee schedules, view module access, and audit logs.</div>
            </div>
            <div class="ms-auto d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" wire:click="openServiceModal" wire:loading.attr="disabled" wire:target="openServiceModal" {{ !$tables_ready ? 'disabled' : '' }}>
                    <span wire:loading.remove wire:target="openServiceModal"><i class="bx bx-plus me-1"></i>New Service</span>
                    <span wire:loading wire:target="openServiceModal"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
                <button type="button" class="btn btn-primary" wire:click="openFeeModal" wire:loading.attr="disabled" wire:target="openFeeModal" {{ !$tables_ready ? 'disabled' : '' }}>
                    <span wire:loading.remove wire:target="openFeeModal"><i class="bx bx-receipt me-1"></i>New Fee Schedule</span>
                    <span wire:loading wire:target="openFeeModal"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
            </div>
        </div>
    </div>

    @if (!$tables_ready)
        <div class="alert alert-warning mb-4">
            <strong>Facility Administration tables are not ready.</strong>
            Run `php artisan migrate` to create the required tables for Service Catalog, Fee Schedules, Module Access, and Audit Trail.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Services</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['services_total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Active Services</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['services_active'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Active Fees</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="4.5" y="6.5" width="15" height="11" rx="2.5" stroke="currentColor" stroke-width="1.8" />
                            <path d="M8 11h8M8 14h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['fees_active'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-violet h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Enabled Modules</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 5h6v6H5V5zm8 0h6v6h-6V5zM5 13h6v6H5v-6zm8 0h6v6h-6v-6z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['modules_enabled'] }}</div>
            </div>
        </div>
    </div>

    @if ($tables_ready)
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Facility Profile Settings</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Facility Name <span class="text-danger">*</span></label><input type="text" class="form-control" wire:model.live="facility_name">@error('facility_name') <small class="text-danger">{{ $message }}</small> @enderror</div>
                <div class="col-md-4"><label class="form-label">Phone</label><input type="text" class="form-control" wire:model.live="facility_phone">@error('facility_phone') <small class="text-danger">{{ $message }}</small> @enderror</div>
                <div class="col-md-4"><label class="form-label">Email</label><input type="email" class="form-control" wire:model.live="facility_email">@error('facility_email') <small class="text-danger">{{ $message }}</small> @enderror</div>
                <div class="col-md-4"><label class="form-label">Type</label><input type="text" class="form-control" wire:model.live="facility_type">@error('facility_type') <small class="text-danger">{{ $message }}</small> @enderror</div>
                <div class="col-md-4"><label class="form-label">Ownership</label><input type="text" class="form-control" wire:model.live="facility_ownership">@error('facility_ownership') <small class="text-danger">{{ $message }}</small> @enderror</div>
                <div class="col-md-4"><label class="form-label d-block">Status</label><div class="form-check mt-2"><input type="checkbox" class="form-check-input" id="facilityStatus" wire:model.live="facility_is_active"><label class="form-check-label" for="facilityStatus">Facility Active</label></div></div>
                <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" rows="2" wire:model.live="facility_address"></textarea>@error('facility_address') <small class="text-danger">{{ $message }}</small> @enderror</div>
                <div class="col-12 d-flex justify-content-end"><button type="button" class="btn btn-primary" wire:click="saveFacilityProfile" wire:loading.attr="disabled" wire:target="saveFacilityProfile"><span wire:loading.remove wire:target="saveFacilityProfile"><i class="bx bx-save me-1"></i>Save Profile Settings</span><span wire:loading wire:target="saveFacilityProfile"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span></button></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Service Catalog</h5><button type="button" class="btn btn-primary btn-sm" wire:click="openServiceModal" wire:loading.attr="disabled" wire:target="openServiceModal"><span wire:loading.remove wire:target="openServiceModal"><i class="bx bx-plus me-1"></i>Add Service</span><span wire:loading wire:target="openServiceModal"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span></button></div>
        <div class="px-4 pt-3 text-muted small">Services are the billable facility activities (example: ANC Booking, Full Blood Count, Ultrasound).</div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="facilityServiceCatalogTable" class="table align-middle">
                <thead class="table-dark"><tr><th>Service Code</th><th>Service Name</th><th>Category</th><th>Base Fee</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr>
                            <td class="fw-semibold">{{ $service->service_code }}</td>
                            <td>{{ $service->service_name }}</td>
                            <td>{{ $service->service_category ?: 'N/A' }}</td>
                            <td>NGN {{ number_format((float) $service->base_fee, 2) }}</td>
                            <td><span class="badge {{ $service->is_active ? 'bg-label-success' : 'bg-label-danger' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base ti tabler-dots-vertical"></i></button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0)" wire:click="openServiceModal({{ $service->id }})"><i class="icon-base ti tabler-edit me-1"></i>Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0)" wire:click="toggleServiceStatus({{ $service->id }})" wire:loading.attr="disabled" wire:target="toggleServiceStatus({{ $service->id }})"><i class="icon-base ti tabler-{{ $service->is_active ? 'eye-off' : 'eye' }} me-1"></i>{{ $service->is_active ? 'Deactivate' : 'Activate' }}</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No service catalog records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Fee Schedule</h5><button type="button" class="btn btn-primary btn-sm" wire:click="openFeeModal" wire:loading.attr="disabled" wire:target="openFeeModal"><span wire:loading.remove wire:target="openFeeModal"><i class="bx bx-plus me-1"></i>Add Fee</span><span wire:loading wire:target="openFeeModal"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span></button></div>
        <div class="px-4 pt-3 text-muted small">Fee Schedule sets the amount and effective dates for each service. Only one active schedule should apply per service.</div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="facilityFeeScheduleTable" class="table align-middle">
                <thead class="table-dark"><tr><th>Service</th><th>Amount</th><th>Effective From</th><th>Effective To</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($feeSchedules as $fee)
                        <tr>
                            <td class="fw-semibold">{{ $fee->service?->service_name ?: 'N/A' }} ({{ $fee->service?->service_code ?: 'N/A' }})</td>
                            <td>NGN {{ number_format((float) $fee->amount, 2) }}</td>
                            <td data-order="{{ optional($fee->effective_from)->format('Y-m-d') }}">{{ optional($fee->effective_from)->format('M d, Y') ?: 'N/A' }}</td>
                            <td data-order="{{ optional($fee->effective_to)->format('Y-m-d') }}">{{ optional($fee->effective_to)->format('M d, Y') ?: 'Open' }}</td>
                            <td><span class="badge {{ $fee->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $fee->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>{{ $fee->notes ?: 'N/A' }}</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base ti tabler-dots-vertical"></i></button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0)" wire:click="openFeeModal({{ $fee->id }})"><i class="icon-base ti tabler-edit me-1"></i>Edit</a>
                                        <a class="dropdown-item" href="javascript:void(0)" wire:click="toggleFeeStatus({{ $fee->id }})" wire:loading.attr="disabled" wire:target="toggleFeeStatus({{ $fee->id }})"><i class="icon-base ti tabler-{{ $fee->is_active ? 'eye-off' : 'eye' }} me-1"></i>{{ $fee->is_active ? 'Deactivate' : 'Activate' }}</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No fee schedule records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Module Access Control</h5></div>
        <div class="px-4 pt-3 text-muted small">Module access toggles are controlled from Central Admin.</div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="facilityModuleAccessTable" class="table align-middle">
                <thead class="table-dark"><tr><th>Module</th><th>Access Status</th><th>Last Updated</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse ($moduleAccessRows as $module)
                        <tr>
                            <td class="fw-semibold">{{ $module->module_label }}</td>
                            <td><span class="badge {{ $module->is_enabled ? 'bg-label-success' : 'bg-label-danger' }}">{{ $module->is_enabled ? 'Enabled' : 'Disabled' }}</span></td>
                            <td data-order="{{ optional($module->updated_at)->format('Y-m-d H:i:s') }}">{{ optional($module->updated_at)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td><span class="badge bg-label-secondary">Managed By Central Admin</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">No module access records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Administration Audit Trail</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="facilityAdminAuditTable" class="table align-middle">
                <thead class="table-dark"><tr><th>Time</th><th>Action</th><th>Target</th><th>Changed By</th><th>Notes</th></tr></thead>
                <tbody>
                    @forelse ($auditRows as $audit)
                        <tr>
                            <td data-order="{{ optional($audit->created_at)->format('Y-m-d H:i:s') }}">{{ optional($audit->created_at)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td class="fw-semibold">{{ ucwords(str_replace('_', ' ', $audit->action)) }}</td>
                            <td>{{ $audit->target_type ?: 'N/A' }}{{ $audit->target_id ? ' #' . $audit->target_id : '' }}</td>
                            <td>{{ $audit->changed_by_name ?: 'System' }}</td>
                            <td>{{ $audit->notes ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No audit records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="facilityServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">{{ $service_mode === 'edit' ? 'Edit Service' : 'Create Service' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Service Code <span class="text-danger">*</span></label><input type="text" class="form-control" wire:model.live="service_code">@error('service_code') <small class="text-danger">{{ $message }}</small> @enderror</div>
                        <div class="col-md-8"><label class="form-label">Service Name <span class="text-danger">*</span></label><input type="text" class="form-control" wire:model.live="service_name">@error('service_name') <small class="text-danger">{{ $message }}</small> @enderror</div>
                        <div class="col-md-6"><label class="form-label">Category</label><input type="text" class="form-control" wire:model.live="service_category">@error('service_category') <small class="text-danger">{{ $message }}</small> @enderror</div>
                        <div class="col-md-6"><label class="form-label">Base Fee (NGN) <span class="text-danger">*</span></label><input type="number" step="0.01" min="0" class="form-control" wire:model.live="service_base_fee">@error('service_base_fee') <small class="text-danger">{{ $message }}</small> @enderror</div>
                        <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" rows="3" wire:model.live="service_description"></textarea>@error('service_description') <small class="text-danger">{{ $message }}</small> @enderror</div>
                        <div class="col-12"><div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="serviceIsActive" wire:model.live="service_is_active"><label class="form-check-label" for="serviceIsActive"><strong>Service Active</strong></label></div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" wire:click="saveService" wire:loading.attr="disabled" wire:target="saveService"><span wire:loading.remove wire:target="saveService">{{ $service_mode === 'edit' ? 'Update Service' : 'Create Service' }}</span><span wire:loading wire:target="saveService"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span></button></div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="facilityFeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">{{ $fee_mode === 'edit' ? 'Edit Fee Schedule' : 'Create Fee Schedule' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12"><label class="form-label">Service <span class="text-danger">*</span></label><select class="form-select" wire:model.live="fee_service_id"><option value="">Select service...</option>@foreach ($serviceOptions as $service)<option value="{{ $service->id }}">{{ $service->service_name }} ({{ $service->service_code }})</option>@endforeach</select>@error('fee_service_id') <small class="text-danger">{{ $message }}</small> @enderror</div>
                        <div class="col-md-4"><label class="form-label">Amount (NGN) <span class="text-danger">*</span></label><input type="number" min="0" step="0.01" class="form-control" wire:model.live="fee_amount">@error('fee_amount') <small class="text-danger">{{ $message }}</small> @enderror</div>
                        <div class="col-md-4"><label class="form-label">Effective From <span class="text-danger">*</span></label><input type="date" class="form-control" wire:model.live="fee_effective_from">@error('fee_effective_from') <small class="text-danger">{{ $message }}</small> @enderror</div>
                        <div class="col-md-4"><label class="form-label">Effective To</label><input type="date" class="form-control" wire:model.live="fee_effective_to">@error('fee_effective_to') <small class="text-danger">{{ $message }}</small> @enderror</div>
                        <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" rows="3" wire:model.live="fee_notes"></textarea>@error('fee_notes') <small class="text-danger">{{ $message }}</small> @enderror</div>
                        <div class="col-12"><div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="feeIsActive" wire:model.live="fee_is_active"><label class="form-check-label" for="feeIsActive"><strong>Set as Active Schedule</strong></label></div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" wire:click="saveFeeSchedule" wire:loading.attr="disabled" wire:target="saveFeeSchedule"><span wire:loading.remove wire:target="saveFeeSchedule">{{ $fee_mode === 'edit' ? 'Update Fee Schedule' : 'Create Fee Schedule' }}</span><span wire:loading wire:target="saveFeeSchedule"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span></button></div>
            </div>
        </div>
    </div>
    @endif

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

    <script>
        document.addEventListener('livewire:initialized', function() {
            const serviceModalEl = document.getElementById('facilityServiceModal');
            const feeModalEl = document.getElementById('facilityFeeModal');
            let serviceModal = null;
            let feeModal = null;
            const cleanup = () => { document.body.classList.remove('modal-open'); document.body.style.removeProperty('padding-right'); document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove()); };
            if (serviceModalEl) {
                const getServiceModal = () => { if (!serviceModal) serviceModal = new bootstrap.Modal(serviceModalEl); return serviceModal; };
                Livewire.on('open-facility-service-modal', () => getServiceModal().show());
                Livewire.on('close-facility-service-modal', () => { if (serviceModal) serviceModal.hide(); });
                serviceModalEl.addEventListener('hidden.bs.modal', function() { @this.call('onServiceModalHidden'); cleanup(); });
            }
            if (feeModalEl) {
                const getFeeModal = () => { if (!feeModal) feeModal = new bootstrap.Modal(feeModalEl); return feeModal; };
                Livewire.on('open-facility-fee-modal', () => getFeeModal().show());
                Livewire.on('close-facility-fee-modal', () => { if (feeModal) feeModal.hide(); });
                feeModalEl.addEventListener('hidden.bs.modal', function() { @this.call('onFeeModalHidden'); cleanup(); });
            }
        });
    </script>

    @include('_partials.datatables-init-multi', [
        'tableIds' => ['facilityServiceCatalogTable','facilityFeeScheduleTable','facilityModuleAccessTable','facilityAdminAuditTable'],
        'orders' => [
            'facilityServiceCatalogTable' => [0, 'asc'],
            'facilityFeeScheduleTable' => [2, 'desc'],
            'facilityModuleAccessTable' => [0, 'asc'],
            'facilityAdminAuditTable' => [0, 'desc'],
        ],
    ])
</div>
