@section('title', 'Create Administrators')
<div x-data="{ role: @entangle('role').live, state_id: @entangle('state_id').live }">

    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 32px;">
                            <i class='bx bx-user me-2'></i>
                            Manage Administrators
                        </h4>
                        <p class="hero-subtitle">{{ \Carbon\Carbon::today()->format('l, F j, Y') }}</p>
                        <div class="hero-stats">
                            <span class="hero-stat">
                                <i class="bx bx-group"></i>
                                {{ count($admins) }} Total Administrators
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-building"></i>
                                {{ $admins->where('role', 'Facility Administrator')->count() }} Facility Admins
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-map"></i>
                                {{ $admins->where('role', 'LGA Officer')->count() }} LGA Officers
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-map-pin"></i>
                                {{ $admins->where('role', 'State Data Administrator')->count() }} State Admins
                            </span>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-light btn-lg rounded-pill shadow-sm d-inline-flex align-items-center"
                                style="border: 1px solid #ddd; padding: 12px 24px;" data-bs-toggle="modal"
                                data-bs-target="#adminModal" type="button" title="Create New Administrator">
                                <i class="bx bx-user-plus me-2" style="font-size: 20px;"></i>
                                Create New Administrator
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

    <!-- DataTable with Buttons -->
    <div class="card">
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="dataTable" class="table">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Designation</th>
                        <th>Facility/State-LGA</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($admins as $admin)
                        <tr>
                            <td>{{ $admin->id }}</td>
                            <td>{{ $admin->first_name }}</td>
                            <td>{{ $admin->last_name }}</td>
                            <td>{{ $admin->email }}</td>
                            <td>
                                <span
                                    class="badge {{ $admin->role === 'Facility Administrator' ? 'bg-label-primary' : ($admin->role === 'LGA Officer' ? 'bg-label-info' : 'bg-label-success') }}">
                                    {{ $admin->role }}
                                </span>
                            </td>
                            <td>{{ $admin->designation }}</td>
                            <td>
                                @if ($admin->role === 'Facility Administrator')
                                    <i class="bx bx-building me-1"></i>
                                    {{ $admin->facility ? $admin->facility->name : 'N/A' }}
                                @elseif ($admin->role === 'LGA Officer')
                                    <i class="bx bx-map me-1"></i>
                                    {{ $admin->state ? $admin->state->name : 'N/A' }} /
                                    {{ $admin->lga ? $admin->lga->name : 'N/A' }}
                                @else
                                    <i class="bx bx-map-pin me-1"></i>
                                    {{ $admin->state ? $admin->state->name : 'N/A' }}
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal"
                                            data-bs-target="#adminModal" wire:click="edit({{ $admin->id }})">
                                            <i class="icon-base ti tabler-pencil me-1"></i> Edit
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                            wire:click="delete({{ $admin->id }})">
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

    <!-- Administrator Registration Modal -->
    <div wire:ignore.self class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-simple modal-add-new-cc">
            <div class="modal-content">
                <div class="modal-body">
                    <button wire:click="exit" type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h4 class="mb-2" id="adminModalLabel">Administrator Registration</h4>
                        <p class="text-muted"><span class="badge bg-info">Administrator Details</span></p>
                    </div>
                    <form onsubmit="return false">
                        @csrf
                        <!-- Administrator Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-primary">Administrator
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input wire:model.live="first_name" type="text" class="form-control"
                                            placeholder="Enter first name">
                                        @error('first_name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input wire:model.live="last_name" type="text" class="form-control"
                                            placeholder="Enter last name">
                                        @error('last_name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input wire:model.live="email" type="email" class="form-control"
                                            placeholder="Enter email">
                                        @error('email')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Password <span class="text-danger">* @if ($modal_flag)
                                                    (Leave blank to keep unchanged)
                                                @endif
                                            </span>
                                        </label>
                                        <input wire:model="password" type="password" class="form-control"
                                            placeholder="Enter password">
                                        @error('password')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm Password <span class="text-danger">*
                                                @if ($modal_flag)
                                                    (Leave blank to keep unchanged)
                                                @endif
                                            </span></label>
                                        <input wire:model="password_confirmation" type="password"
                                            class="form-control" placeholder="Confirm password">
                                        @error('password_confirmation')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Role <span class="text-danger">*</span></label>
                                        <select wire:model.live="role" class="form-select">
                                            <option value="">--Select Role--</option>
                                            @foreach ($roles as $roleOption)
                                                <option value="{{ $roleOption }}">{{ $roleOption }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Select one role only (Facility Administrator, LGA
                                            Officer, or State Data Administrator).</small>
                                        @error('role')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Designation <span
                                                class="text-danger">*</span></label>
                                        <select wire:model.live="designation" class="form-select">
                                            <option value="">--Select Designation--</option>
                                            @foreach ($designations as $designationOption)
                                                <option value="{{ $designationOption }}">{{ $designationOption }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Select one designation only (Facility Data
                                            Administrator, LGA Data Administrator, or State Data Administrator).</small>
                                        @error('designation')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Facility <span class="text-danger"
                                                x-show="role === 'Facility Administrator'">*</span></label>
                                        <select wire:model.live="facility_id" class="form-select"
                                            :disabled="role !== 'Facility Administrator'">
                                            <option value="">--Select Facility--</option>
                                            @foreach ($facilities as $facilityOption)
                                                <option value="{{ $facilityOption->id }}">
                                                    {{ $facilityOption->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Each facility can be assigned to only one
                                            administrator.</small>
                                        @error('facility_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">State <span class="text-danger"
                                                x-show="role === 'LGA Officer' || role === 'State Data Administrator'">*</span></label>
                                        <select wire:model.live="state_id" class="form-select"
                                            :disabled="role !== 'LGA Officer' && role !== 'State Data Administrator'">
                                            <option value="">--Select State--</option>
                                            @foreach ($states as $stateOption)
                                                <option value="{{ $stateOption->id }}">{{ $stateOption->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Select a state for LGA Officer or State Data
                                            Administrator.</small>
                                        @error('state_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">LGA <span class="text-danger"
                                                x-show="role === 'LGA Officer'">*</span></label>
                                        <select wire:model.live="lga_id" class="form-select"
                                            :disabled="role !== 'LGA Officer' || !state_id">
                                            <option value="">--Select LGA--</option>
                                            @foreach ($lgas as $lgaOption)
                                                <option value="{{ $lgaOption->id }}">{{ $lgaOption->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Each State-LGA pair can be assigned to only one
                                            LGA Officer.</small>
                                        @error('lga_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Action Buttons -->
                        <div class="col-12 text-center">
                            @if ($modal_flag)
                                <x-app-loader /><button wire:click="update" type="button" class="btn btn-primary">
                                    <i class="bx bx-check me-1"></i>Update Administrator
                                </button>
                                <button wire:click="exit" type="button" class="btn btn-label-secondary"
                                    data-bs-dismiss="modal" aria-label="Close">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </button>
                            @else
                                <x-app-loader /> <button wire:click="store" type="button" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Register Administrator
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
    <!--/ Administrator Registration Modal -->

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const adminModal = document.getElementById('adminModal');

        // Listen for Livewire event to close modal
        Livewire.on('close-modal', () => {
            const modal = bootstrap.Modal.getInstance(adminModal);
            if (modal) {
                modal.hide();
            }
        });

        // Handle modal close events (both backdrop click and ESC key)
        adminModal.addEventListener('hidden.bs.modal', function() {
            // Trigger the exit method when modal is closed by any means
            @this.call('exit');
        });
    });
</script>

@include('_partials.datatables-init')
