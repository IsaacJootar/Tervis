@php
    use Carbon\Carbon;
@endphp

@section('title', 'Facility Departments')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Facility Departments</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-building-house me-1"></i>Facility Departments</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Create and maintain your facility department list for staff assignment workflows.</div>
            </div>
            <div class="ms-auto">
                <button type="button" class="btn btn-primary" wire:click="openCreateModal" wire:loading.attr="disabled"
                    wire:target="openCreateModal">
                    <span wire:loading.remove wire:target="openCreateModal"><i class="bx bx-plus me-1"></i>Create Department</span>
                    <span wire:loading wire:target="openCreateModal"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Total</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 19h16M6 19V7h12v12M9 11h.01M12 11h.01M15 11h.01M9 14h.01M12 14h.01M15 14h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $departments->count() }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Active</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $departments->where('is_active', true)->count() }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-rose h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Inactive</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M8 8l8 8M16 8l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $departments->where('is_active', false)->count() }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Department Directory</h5>
        </div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="facilityDepartmentsTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Department Name</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr wire:key="department-row-{{ $department->id }}">
                            <td class="fw-semibold">{{ $department->name }}</td>
                            <td>{{ $department->details ?: 'No details provided' }}</td>
                            <td><span class="badge {{ $department->status_badge }}">{{ $department->status_text }}</span></td>
                            <td>{{ $department->formatted_created_at }}</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0)" wire:click="openEditModal({{ $department->id }})">
                                            <i class="icon-base ti tabler-edit me-1"></i>Edit
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0)" wire:click="toggleStatus({{ $department->id }})"
                                            wire:loading.attr="disabled" wire:target="toggleStatus({{ $department->id }})">
                                            <i class="icon-base ti tabler-{{ $department->is_active ? 'eye-off' : 'eye' }} me-1"></i>
                                            {{ $department->is_active ? 'Deactivate' : 'Activate' }}
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="javascript:void(0)"
                                            wire:click="deleteDepartment({{ $department->id }})"
                                            wire:confirm="Are you sure you want to delete this department? This action cannot be undone.">
                                            <i class="icon-base ti tabler-trash me-1"></i>Delete
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No departments found for this facility.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="departmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $edit_mode ? 'Edit Department' : 'Create Department' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Department Name <span class="text-danger">*</span></label>
                            <input wire:model.live="name" type="text" class="form-control" placeholder="Enter department name" maxlength="100">
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Details</label>
                            <textarea wire:model.live="details" class="form-control" rows="4"
                                placeholder="Enter department details (optional)" maxlength="1000"></textarea>
                            @error('details') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="text-muted">Maximum 1000 characters.</small>
                        </div>
                        <div class="col-12">
                            <div class="form-check mt-1">
                                <input wire:model.live="is_active" class="form-check-input" type="checkbox" id="isActive">
                                <label class="form-check-label" for="isActive">
                                    <strong>Active Department</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="saveDepartment"
                        wire:loading.attr="disabled" wire:target="saveDepartment">
                        <span wire:loading.remove wire:target="saveDepartment">{{ $edit_mode ? 'Update Department' : 'Create Department' }}</span>
                        <span wire:loading wire:target="saveDepartment"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .metric-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            padding: 14px 16px;
            min-height: 108px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 26px -22px rgba(15, 23, 42, 0.45);
        }

        .metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-weight: 700;
        }

        .metric-value {
            margin-top: 6px;
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .metric-icon {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.08);
        }

        .metric-icon svg {
            width: 18px;
            height: 18px;
        }

        .metric-card-slate {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
        }

        .metric-card-emerald {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #065f46;
        }

        .metric-card-rose {
            border-color: #fecdd3;
            background: #fff1f2;
            color: #9f1239;
        }

        .form-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: #64748b;
        }
    </style>

    <script>
        document.addEventListener('livewire:initialized', function() {
            const modalEl = document.getElementById('departmentModal');
            if (!modalEl) return;

            let modalInstance = null;
            const getModal = () => {
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(modalEl);
                }
                return modalInstance;
            };

            const cleanupModalArtifacts = () => {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
            };

            Livewire.on('open-department-modal', () => {
                getModal().show();
            });

            Livewire.on('close-department-modal', () => {
                if (modalInstance) {
                    modalInstance.hide();
                }
            });

            modalEl.addEventListener('hidden.bs.modal', function() {
                @this.call('onModalHidden');
                cleanupModalArtifacts();
            });
        });
    </script>

    @include('_partials.datatables-init-multi', [
        'tableIds' => ['facilityDepartmentsTable'],
        'orders' => [
            'facilityDepartmentsTable' => [0, 'asc'],
        ],
    ])
</div>

