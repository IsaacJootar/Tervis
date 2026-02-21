@php use Carbon\Carbon; @endphp
@section('title', 'Create Data Officer')
<div>

    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 28px;">
                            <i class='bx bx-user me-2'></i>
                            Manage Data Officers
                        </h4>

                        <div class="hero-stats">

                            <span class="hero-stat">
                                <i class="bx bx-group"></i>
                                {{ count($dataOfficers) }} Total Data Officers
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-user-check"></i>
                                {{ $dataOfficers->where('designation', 'Doctor')->count() }} Doctors
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-plus-medical"></i>
                                {{ $dataOfficers->where('designation', 'Nurse')->count() }} Nurses
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-test-tube"></i>
                                {{ $dataOfficers->where('designation', 'Lab Attendant')->count() }} Lab Attendants
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-test-tube"></i>
                                {{ $dataOfficers->where('designation', 'Volunteer')->count() }} Volunteers
                            </span>
                            {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-light btn-lg rounded-pill shadow-sm d-inline-flex align-items-center"
                                style="border: 1px solid #ddd; padding: 10px 20px;" data-bs-toggle="modal"
                                data-bs-target="#dataOfficerModal" type="button" title="Create New Data Officer">
                                <i class="bx bx-user-plus me-2" style="font-size: 15px;"> </i>
                                + Create New Data Officer
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
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Designation</th>
                        <th>Facility</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dataOfficers as $officer)
                        <tr wire:key="{{ $officer->id }}">
                            <td>{{ $officer->first_name }}</td>
                            <td>{{ $officer->last_name }}</td>
                            <td>{{ $officer->email ?: 'N/A' }}</td>
                            <td>{{ $officer->username }}</td>
                            <td>
                                <span
                                    class="badge
                                    @if ($officer->designation === 'Doctor') bg-label-success
                                    @elseif($officer->designation === 'Nurse') bg-label-primary
                                    @elseif($officer->designation === 'Midwife') bg-label-info
                                    @else bg-label-warning @endif">
                                    {{ $officer->designation }}
                                </span>
                            </td>
                            <td>
                                <i class="bx bx-building me-1"></i>
                                {{ $officer->facility ? $officer->facility->name : 'N/A' }}
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal"
                                            data-bs-target="#dataOfficerModal" wire:click="edit({{ $officer->id }})">
                                            <i class="icon-base ti tabler-pencil me-1"></i> Edit
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                            wire:click="delete({{ $officer->id }})">
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

    <!-- Data Officer Registration Modal -->
    <div wire:ignore.self class="modal fade" id="dataOfficerModal" tabindex="-1"
        aria-labelledby="dataOfficerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-simple modal-add-new-cc">
            <div class="modal-content">
                <div class="modal-body">
                    <button wire:click="exit" type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h4 class="mb-2" id="dataOfficerModalLabel">Data Officer Registration</h4>
                        <p class="text-muted"><span class="badge bg-info">Data Officer Details</span></p>
                    </div>
                    <form onsubmit="return false">
                        @csrf
                        <!-- Data Officer Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-primary">Data Officer
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
                                        <label class="form-label">Email</label>
                                        <input wire:model.live="email" type="email" class="form-control"
                                            placeholder="Enter email (optional)">
                                        @error('email')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Username <span class="text-danger">*</span></label>
                                        <input wire:model.live="username" type="text" class="form-control"
                                            placeholder="Enter username">
                                        @error('username')
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
                                        <label class="form-label">Designation <span
                                                class="text-danger">*</span></label>
                                        <select wire:model.live="designation" class="form-select">
                                            <option value="">--Select Designation--</option>
                                            @foreach ($designations as $designationOption)
                                                <option value="{{ $designationOption }}">{{ $designationOption }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Select designation (Nurse, Doctor, Midwife, Lab
                                            Attendant, Volunteer).</small>
                                        @error('designation')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Facility <span class="text-danger">*</span></label>
                                        <select wire:model.live="facility_id" class="form-select" disabled>
                                            @foreach ($facilities as $facilityOption)
                                                <option value="{{ $facilityOption->id }}" selected>
                                                    {{ $facilityOption->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Facility is automatically assigned based on your
                                            admin role.</small>
                                        @error('facility_id')
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
                                    <i class="bx bx-check me-1"></i>Update Data Officer
                                </button>
                                <button wire:click="exit" type="button" class="btn btn-label-secondary"
                                    data-bs-dismiss="modal" aria-label="Close">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </button>
                            @else
                                <x-app-loader /> <button wire:click="store" type="button" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Register Data Officer
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
    <!--/ Data Officer Registration Modal -->

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dataOfficerModal = document.getElementById('dataOfficerModal');

        // Listen for Livewire event to close modal
        Livewire.on('close-modal', () => {
            const modal = bootstrap.Modal.getInstance(dataOfficerModal);
            if (modal) {
                modal.hide();
            }
        });

        // Handle modal close events (both backdrop click and ESC key)
        dataOfficerModal.addEventListener('hidden.bs.modal', function() {
            // Trigger the exit method when modal is closed by any means
            @this.call('exit');
        });
    });
</script>

@include('_partials.datatables-init')
