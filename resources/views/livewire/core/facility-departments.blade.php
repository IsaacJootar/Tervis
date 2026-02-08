<div>
    @php use Carbon\Carbon; @endphp
    @section('title', 'Facility Departments')
    <div x-data="{ modal_flag: @entangle('modal_flag').live }">

        <!-- Hero Card Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="hero-card">
                    <div class="hero-content">
                        <div class="hero-text">
                            <h4 class="hero-title" style="color: white; font-size: 28px;">
                                <i class='bx bx-building me-2'></i>
                                Facility Departments
                            </h4>

                            <div class="hero-stats">
                                <span class="hero-stat">
                                    <i class="bx bx-layer"></i>
                                    {{ count($departments) }} Total Departments
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-check-circle"></i>
                                    {{ $departments->where('is_active', true)->count() }} Active
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-x-circle"></i>
                                    {{ $departments->where('is_active', false)->count() }} Inactive
                                </span>
                                {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                            </div>

                            <div class="mt-3">
                                <button wire:click="openCreateModal"
                                    class="btn btn-light btn-lg rounded-pill shadow-sm d-inline-flex align-items-center"
                                    style="border: 1px solid #ddd; padding: 10px 20px;" data-bs-toggle="modal"
                                    data-bs-target="#departmentModal" type="button" title="Create New Department">
                                    <i class="bx bx-building-add me-2" style="font-size: 15px;"></i>
                                    + Create New Department
                                </button>
                            </div>

                        </div>
                        <div class="hero-decoration">
                            <div class="floating-shape shape-1"></div>
                            <div class="floating-shape shape-2"></div>
                            <div class="floating-shape shape-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <div class="card">
            <div class="card-datatable table-responsive pt-0" wire:ignore>
                <table id="dataTable" class="table">
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
                        @foreach ($departments as $department)
                            <tr wire:key="{{ $department->id }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-wrapper">
                                            <div class="avatar avatar-sm me-3">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    {{ strtoupper(substr($department->name, 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $department->name }}</h6>

                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-truncate d-block" style="max-width: 200px;"
                                        title="{{ $department->details }}">
                                        {{ $department->details ?: 'No details provided' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $department->status_badge }}">
                                        {{ $department->status_text }}
                                    </span>
                                </td>
                                <td>{{ $department->formatted_created_at }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="icon-base ti tabler-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript:void(0)"
                                                wire:click="openEditModal({{ $department->id }})">
                                                <i class="icon-base ti tabler-edit me-1"></i> Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript:void(0)"
                                                wire:click="toggleStatus({{ $department->id }})">
                                                <i
                                                    class="icon-base ti tabler-{{ $department->is_active ? 'eye-off' : 'eye' }} me-1"></i>
                                                {{ $department->is_active ? 'Deactivate' : 'Activate' }}
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="javascript:void(0)"
                                                wire:click="deleteDepartment({{ $department->id }})"
                                                wire:confirm="Are you sure you want to delete this department? This action cannot be undone.">
                                                <i class="icon-base ti tabler-trash me-1"></i> Delete
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

        <!-- Create/Edit Department Modal -->
        <div wire:ignore.self class="modal fade" id="departmentModal" tabindex="-1"
            aria-labelledby="departmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-simple modal-add-new-cc">
                <div class="modal-content">
                    <div class="modal-body">
                        <button wire:click="exit" type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                        <div class="text-center mb-4">
                            <h4 class="mb-2" id="departmentModalLabel">
                                {{ $edit_mode ? 'Edit Department' : 'Create New Department' }}
                            </h4>
                            <p class="text-muted">
                                <span class="badge bg-info">
                                    {{ $edit_mode ? 'Update Information' : 'Add New Department' }}
                                </span>
                            </p>
                        </div>
                        <form onsubmit="return false">
                            @csrf

                            <div class="row g-3">
                                <!-- Department Name -->
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Department Name <span class="text-danger">*</span></label>
                                    <input wire:model.live="name" type="text" class="form-control"
                                        placeholder="Enter department name" maxlength="100">
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Details -->
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Details</label>
                                    <textarea wire:model.live="details" class="form-control" rows="4"
                                        placeholder="Enter department details (optional)" maxlength="1000"></textarea>
                                    @error('details')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <small class="text-muted">Maximum 1000 characters</small>
                                </div>

                                <!-- Status -->
                                <div class="col-md-12 mb-3">
                                    <div class="form-check">
                                        <input wire:model.live="is_active" class="form-check-input" type="checkbox"
                                            id="isActive">
                                        <label class="form-check-label" for="isActive">
                                            <strong>Active Department</strong>
                                            <small class="text-muted d-block">Uncheck to create as inactive</small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-12 text-center mt-4">
                                @if ($modal_flag)
                                    <button wire:click="saveDepartment" type="button" class="btn btn-primary">
                                        <i class="bx bx-check me-1"></i>
                                        {{ $edit_mode ? 'Update Department' : 'Create Department' }}
                                    </button>
                                    <button wire:click="exit" type="button" class="btn btn-label-secondary"
                                        data-bs-dismiss="modal" aria-label="Close">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Create/Edit Department Modal -->

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const departmentModal = document.getElementById('departmentModal');

            // Listen for Livewire events to open/close modal
            Livewire.on('open-department-modal', () => {
                const modal = new bootstrap.Modal(departmentModal);
                modal.show();
            });

            Livewire.on('close-department-modal', () => {
                const modal = bootstrap.Modal.getInstance(departmentModal);
                if (modal) {
                    modal.hide();
                }
            });

            // Handle modal close events
            departmentModal.addEventListener('hidden.bs.modal', function() {
                @this.call('exit');
            });
        });
    </script>

    @include('_partials.datatables-init')
</div>
