@php
    use Carbon\Carbon;
@endphp

@section('title', 'Manage Administrators')

<div x-data="{ role: @entangle('role').live, state_id: @entangle('state_id').live }">
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Central Admin</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-user me-1"></i>Administrator Management</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Create and maintain facility, LGA, and state administrators.</div>
            </div>
            <div class="ms-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adminModal" type="button">
                    <i class="bx bx-user-plus me-1"></i>New Administrator
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Total Admins</div>
                    <span class="metric-icon" aria-hidden="true"><i class="bx bx-group"></i></span>
                </div>
                <div class="metric-value">{{ count($admins) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Facility Admins</div>
                    <span class="metric-icon" aria-hidden="true"><i class="bx bx-building"></i></span>
                </div>
                <div class="metric-value">{{ $admins->where('role', 'Facility Administrator')->count() }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">LGA Officers</div>
                    <span class="metric-icon" aria-hidden="true"><i class="bx bx-map"></i></span>
                </div>
                <div class="metric-value">{{ $admins->where('role', 'LGA Officer')->count() }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-violet h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">State Admins</div>
                    <span class="metric-icon" aria-hidden="true"><i class="bx bx-map-pin"></i></span>
                </div>
                <div class="metric-value">{{ $admins->where('role', 'State Data Administrator')->count() }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Administrators</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="dataTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Designation</th>
                        <th>Scope</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($admins as $admin)
                        <tr>
                            <td>{{ $admin->id }}</td>
                            <td class="fw-semibold">{{ $admin->first_name }} {{ $admin->last_name }}</td>
                            <td>{{ $admin->email }}</td>
                            <td>
                                <span class="badge {{ $admin->role === 'Facility Administrator' ? 'bg-label-primary' : ($admin->role === 'LGA Officer' ? 'bg-label-info' : 'bg-label-success') }}">
                                    {{ $admin->role }}
                                </span>
                            </td>
                            <td>{{ $admin->designation }}</td>
                            <td>
                                @if ($admin->role === 'Facility Administrator')
                                    {{ $admin->facility?->name ?: 'N/A' }}
                                @elseif ($admin->role === 'LGA Officer')
                                    {{ $admin->state?->name ?: 'N/A' }} / {{ $admin->lga?->name ?: 'N/A' }}
                                @else
                                    {{ $admin->state?->name ?: 'N/A' }}
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#adminModal" wire:click="edit({{ $admin->id }})">
                                            <i class="icon-base ti tabler-pencil me-1"></i>Edit
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0)" wire:click="delete({{ $admin->id }})">
                                            <i class="icon-base ti tabler-trash me-1"></i>Delete
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $modal_flag ? 'Update Administrator' : 'Register New Administrator' }}</h5>
                    <button wire:click="exit" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input wire:model.live="first_name" type="text" class="form-control" placeholder="Enter first name">
                            @error('first_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input wire:model.live="last_name" type="text" class="form-control" placeholder="Enter last name">
                            @error('last_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input wire:model.live="email" type="email" class="form-control" placeholder="Enter email">
                            @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">{{ $modal_flag ? '(optional on update)' : '*' }}</span></label>
                            <input wire:model="password" type="password" class="form-control" placeholder="Enter password">
                            @error('password') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password <span class="text-danger">{{ $modal_flag ? '(optional on update)' : '*' }}</span></label>
                            <input wire:model="password_confirmation" type="password" class="form-control" placeholder="Confirm password">
                            @error('password_confirmation') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select wire:model.live="role" class="form-select">
                                <option value="">Select role...</option>
                                @foreach ($roles as $roleOption)
                                    <option value="{{ $roleOption }}">{{ $roleOption }}</option>
                                @endforeach
                            </select>
                            @error('role') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Designation <span class="text-danger">*</span></label>
                            <select wire:model.live="designation" class="form-select">
                                <option value="">Select designation...</option>
                                @foreach ($designations as $designationOption)
                                    <option value="{{ $designationOption }}">{{ $designationOption }}</option>
                                @endforeach
                            </select>
                            @error('designation') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Facility <span class="text-danger" x-show="role === 'Facility Administrator'">*</span></label>
                            <select wire:model.live="facility_id" class="form-select" :disabled="role !== 'Facility Administrator'">
                                <option value="">Select facility...</option>
                                @foreach ($facilities as $facilityOption)
                                    <option value="{{ $facilityOption->id }}">{{ $facilityOption->name }}</option>
                                @endforeach
                            </select>
                            @error('facility_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">State <span class="text-danger" x-show="role === 'LGA Officer' || role === 'State Data Administrator'">*</span></label>
                            <select wire:model.live="state_id" class="form-select" :disabled="role !== 'LGA Officer' && role !== 'State Data Administrator'">
                                <option value="">Select state...</option>
                                @foreach ($states as $stateOption)
                                    <option value="{{ $stateOption->id }}">{{ $stateOption->name }}</option>
                                @endforeach
                            </select>
                            @error('state_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">LGA <span class="text-danger" x-show="role === 'LGA Officer'">*</span></label>
                            <select wire:model.live="lga_id" class="form-select" :disabled="role !== 'LGA Officer' || !state_id">
                                <option value="">Select LGA...</option>
                                @foreach ($lgas as $lgaOption)
                                    <option value="{{ $lgaOption->id }}">{{ $lgaOption->name }}</option>
                                @endforeach
                            </select>
                            @error('lga_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button wire:click="exit" type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    @if ($modal_flag)
                        <button wire:click="update" type="button" class="btn btn-primary" wire:loading.attr="disabled" wire:target="update">
                            <span wire:loading.remove wire:target="update">Update Administrator</span>
                            <span wire:loading wire:target="update"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                        </button>
                    @else
                        <button wire:click="store" type="button" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store">
                            <span wire:loading.remove wire:target="store">Register Administrator</span>
                            <span wire:loading wire:target="store"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .metric-card { border-radius: 18px; border: 1px solid rgba(148,163,184,.25); padding: 14px 16px; min-height: 108px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 10px 26px -22px rgba(15,23,42,.45); }
        .metric-label { font-size: 11px; text-transform: uppercase; letter-spacing: .14em; font-weight: 700; }
        .metric-value { margin-top: 6px; font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
        .metric-icon { width: 32px; height: 32px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: rgba(15,23,42,.08); font-size: 18px; }
        .metric-card-slate { border-color: #cbd5e1; background: #f8fafc; color: #0f172a; }
        .metric-card-sky { border-color: #bae6fd; background: #f0f9ff; color: #075985; }
        .metric-card-emerald { border-color: #a7f3d0; background: #ecfdf5; color: #065f46; }
        .metric-card-violet { border-color: #ddd6fe; background: #f5f3ff; color: #5b21b6; }
    </style>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const adminModal = document.getElementById('adminModal');

        Livewire.on('close-modal', () => {
            const modal = bootstrap.Modal.getInstance(adminModal);
            if (modal) modal.hide();
        });

        adminModal.addEventListener('hidden.bs.modal', function() {
            @this.call('exit');
        });

        const clearTableSearch = () => {
            const dt = window.__app1MultiDataTables?.instances?.dataTable ?? null;
            if (dt && typeof dt.search === 'function') {
                dt.search('').draw();
            }

            const searchInput = document.querySelector('.dt-search input');
            if (searchInput) {
                searchInput.setAttribute('autocomplete', 'off');
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };

        setTimeout(clearTableSearch, 140);
        document.addEventListener('livewire:navigated', () => setTimeout(clearTableSearch, 140));
    });
</script>

@include('_partials.datatables-init-multi', [
    'tableIds' => ['dataTable'],
    'orders' => [
        'dataTable' => [0, 'desc'],
    ],
])
