@php
    use Carbon\Carbon;
@endphp

@section('title', 'Staff Management')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Staff Management</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-user-circle me-1"></i>Facility Staff Management</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Single workflow: create/update profiles, designation alignment, and account status control.</div>
            </div>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('facility-departments') }}" class="btn btn-outline-primary">
                    <i class="bx bx-building-house me-1"></i>Facility Departments
                </a>
                <button type="button" class="btn btn-primary" wire:click="openCreateModal" wire:loading.attr="disabled"
                    wire:target="openCreateModal">
                    <span wire:loading.remove wire:target="openCreateModal"><i class="bx bx-user-plus me-1"></i>Add Staff</span>
                    <span wire:loading wire:target="openCreateModal"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Total Staff</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.8" />
                            <path d="M5 19a7 7 0 0114 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['total'] }}</div>
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
                <div class="metric-value">{{ $summary['active'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-rose h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Disabled</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M8 8l8 8M16 8l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['disabled'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Data Officers</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="9" cy="9" r="3" stroke="currentColor" stroke-width="1.8" />
                            <path d="M3.5 19a5.5 5.5 0 0111 0M16 7h5M16 11h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['data_officers'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-violet h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Verification</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 3l7 4v5c0 4-2.8 6.9-7 9-4.2-2.1-7-5-7-9V7l7-4z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                            <path d="M9.5 12.5l1.8 1.8L14.8 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['verification'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-amber h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Dept Assigned</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 19h16M6 19V7h12v12M9 11h.01M12 11h.01M15 11h.01M9 14h.01M12 14h.01M15 14h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['assigned_department'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-dark h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Dept Missing</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['unassigned_department'] }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Staff Directory</h5>
        </div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="staffManagementTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staffRows as $staff)
                        @php
                            $status = ($staff->account_status ?? null) ?: ((bool) $staff->is_active ? 'active' : 'disabled');
                            $statusClass = $status === 'active' ? 'success' : 'danger';
                            $roleClass = $staff->role === 'Verification Officer' ? 'warning' : 'primary';
                        @endphp
                        <tr wire:key="staff-row-{{ $staff->id }}">
                            <td class="fw-semibold">{{ trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) ?: 'N/A' }}</td>
                            <td>{{ $staff->username ?: 'N/A' }}</td>
                            <td>{{ $staff->email ?: 'N/A' }}</td>
                            <td><span class="badge bg-label-{{ $roleClass }}">{{ $staff->role }}</span></td>
                            <td>{{ $staff->designation ?: 'N/A' }}</td>
                            <td>{{ $staff->department?->name ?: 'Unassigned' }}</td>
                            <td><span class="badge bg-label-{{ $statusClass }}">{{ ucfirst($status) }}</span></td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0)" wire:click="openEditModal({{ $staff->id }})">
                                            <i class="icon-base ti tabler-pencil me-1"></i>Edit Profile
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0)" wire:click="openStatusModal({{ $staff->id }})">
                                            <i class="icon-base ti tabler-lock me-1"></i>Manage Status
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0)" wire:click="openResetPasswordModal({{ $staff->id }})">
                                            <i class="icon-base ti tabler-key me-1"></i>Reset Password
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No staff records found for this facility.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Staff Change Audit Log</h5>
        </div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="staffAuditTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Time</th>
                        <th>Action</th>
                        <th>Target Staff</th>
                        <th>Changed By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditRows as $audit)
                        <tr>
                            <td data-order="{{ $audit->created_at?->format('Y-m-d H:i:s') }}">{{ $audit->created_at?->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td class="fw-semibold">{{ ucwords(str_replace('_', ' ', $audit->action)) }}</td>
                            <td>{{ trim(($audit->targetUser->first_name ?? '') . ' ' . ($audit->targetUser->last_name ?? '')) ?: 'N/A' }}</td>
                            <td>{{ $audit->changed_by_name ?: 'N/A' }}</td>
                            <td>{{ $audit->notes ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No audit records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="staffFormModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $modal_mode === 'edit' ? 'Edit Staff Profile' : 'Create Staff Profile' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model.live="first_name" placeholder="Enter first name">
                            @error('first_name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model.live="last_name" placeholder="Enter last name">
                            @error('last_name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model.live="username" placeholder="Enter username">
                            @error('username') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" wire:model.live="email" placeholder="Optional email">
                            @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Designation <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="designation">
                                <option value="">Select designation...</option>
                                @foreach ($designations as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Selecting Verification Officer sets role to Verification Officer; others map to Data Officer.</small>
                            @error('designation') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select class="form-select" wire:model.live="department_id">
                                <option value="">Unassigned</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Status <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="account_status">
                                @foreach ($statusOptions as $option)
                                    <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                                @endforeach
                            </select>
                            @error('account_status') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">{{ $modal_mode === 'edit' ? '(Optional)' : '*' }}</span></label>
                            <input type="password" class="form-control" wire:model="password"
                                placeholder="{{ $modal_mode === 'edit' ? 'Leave blank to keep current password' : 'Enter password' }}">
                            @error('password') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password <span class="text-danger">{{ $modal_mode === 'edit' ? '(Optional)' : '*' }}</span></label>
                            <input type="password" class="form-control" wire:model="password_confirmation" placeholder="Confirm password">
                            @error('password_confirmation') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" wire:click="closeFormModal"
                        wire:loading.attr="disabled" wire:target="closeFormModal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="saveStaff"
                        wire:loading.attr="disabled" wire:target="saveStaff">
                        <span wire:loading.remove wire:target="saveStaff">{{ $modal_mode === 'edit' ? 'Update Staff' : 'Create Staff' }}</span>
                        <span wire:loading wire:target="saveStaff"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="staffStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Staff Account Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Staff</label>
                            <input type="text" class="form-control" value="{{ $status_staff_name ?: 'N/A' }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Current Status</label>
                            <input type="text" class="form-control" value="{{ ucfirst($current_account_status) }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">New Status <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="new_account_status">
                                @foreach ($statusOptions as $option)
                                    <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                                @endforeach
                            </select>
                            @error('new_account_status') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" wire:click="closeStatusModal"
                        wire:loading.attr="disabled" wire:target="closeStatusModal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="updateStatus"
                        wire:loading.attr="disabled" wire:target="updateStatus">
                        <span wire:loading.remove wire:target="updateStatus">Update Status</span>
                        <span wire:loading wire:target="updateStatus"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="staffResetPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Staff Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Staff</label>
                            <input type="text" class="form-control" value="{{ $reset_staff_name ?: 'N/A' }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Staff ID</label>
                            <input type="text" class="form-control" value="{{ $reset_staff_id ?: 'N/A' }}" readonly>
                        </div>
                        @if ($reset_temp_password)
                            <div class="col-12">
                                <div class="alert alert-success mb-0">
                                    <div class="fw-semibold mb-1">Temporary Password Generated</div>
                                    <div class="small text-muted mb-2">Share this securely with the staff and require immediate change after login.</div>
                                    <input type="text" class="form-control fw-semibold" value="{{ $reset_temp_password }}" readonly>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" wire:click="closeResetPasswordModal"
                        wire:loading.attr="disabled" wire:target="closeResetPasswordModal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="confirmResetPassword"
                        wire:loading.attr="disabled" wire:target="confirmResetPassword">
                        <span wire:loading.remove wire:target="confirmResetPassword">Generate Temporary Password</span>
                        <span wire:loading wire:target="confirmResetPassword"><span class="spinner-border spinner-border-sm me-1"></span>Generating...</span>
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

        .metric-card-sky {
            border-color: #bae6fd;
            background: #f0f9ff;
            color: #0c4a6e;
        }

        .metric-card-violet {
            border-color: #ddd6fe;
            background: #f5f3ff;
            color: #5b21b6;
        }

        .metric-card-amber {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .metric-card-dark {
            border-color: #cbd5e1;
            background: #f1f5f9;
            color: #334155;
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
            const formModalEl = document.getElementById('staffFormModal');
            const statusModalEl = document.getElementById('staffStatusModal');
            const resetPasswordModalEl = document.getElementById('staffResetPasswordModal');

            const cleanupModalArtifacts = () => {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
            };

            Livewire.on('open-staff-form-modal', () => {
                if (!formModalEl) return;
                const modal = bootstrap.Modal.getInstance(formModalEl) || new bootstrap.Modal(formModalEl);
                modal.show();
            });

            Livewire.on('close-staff-form-modal', () => {
                if (!formModalEl) return;
                const modal = bootstrap.Modal.getInstance(formModalEl) || new bootstrap.Modal(formModalEl);
                modal.hide();
                cleanupModalArtifacts();
            });

            Livewire.on('open-staff-status-modal', () => {
                if (!statusModalEl) return;
                const modal = bootstrap.Modal.getInstance(statusModalEl) || new bootstrap.Modal(statusModalEl);
                modal.show();
            });

            Livewire.on('close-staff-status-modal', () => {
                if (!statusModalEl) return;
                const modal = bootstrap.Modal.getInstance(statusModalEl) || new bootstrap.Modal(statusModalEl);
                modal.hide();
                cleanupModalArtifacts();
            });

            Livewire.on('open-staff-reset-password-modal', () => {
                if (!resetPasswordModalEl) return;
                const modal = bootstrap.Modal.getInstance(resetPasswordModalEl) || new bootstrap.Modal(resetPasswordModalEl);
                modal.show();
            });

            Livewire.on('close-staff-reset-password-modal', () => {
                if (!resetPasswordModalEl) return;
                const modal = bootstrap.Modal.getInstance(resetPasswordModalEl) || new bootstrap.Modal(resetPasswordModalEl);
                modal.hide();
                cleanupModalArtifacts();
            });
        });
    </script>

    @include('_partials.datatables-init-multi', [
        'tableIds' => ['staffManagementTable', 'staffAuditTable'],
        'orders' => [
            'staffManagementTable' => [0, 'asc'],
            'staffAuditTable' => [0, 'desc'],
        ],
    ])
</div>
