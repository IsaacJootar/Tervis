<div>
    @php use Carbon\Carbon; @endphp
    @section('title', 'Update Officer Designations')
    <div x-data="{ modal_flag: @entangle('modal_flag').live }">

        <!-- Hero Card Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="hero-card">
                    <div class="hero-content">
                        <div class="hero-text">
                            <h4 class="hero-title" style="color: white; font-size: 28px;">
                                <i class='bx bx-id-card me-2'></i>
                                Update Data Officer Designations
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
                                    <i class="bx bx-heart-circle"></i>
                                    {{ $dataOfficers->where('designation', 'Midwife')->count() }} Midwives
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-test-tube"></i>
                                    {{ $dataOfficers->where('designation', 'Lab Attendant')->count() }} Lab Attendants
                                </span>
                                {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
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
                            <th>Role</th>
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
                                <td>
                                    <span class="badge bg-label-primary">
                                        Data Officer
                                    </span>
                                </td>
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
                                                data-bs-target="#designationModal"
                                                wire:click="openModal({{ $officer->id }})">
                                                <i class="icon-base ti tabler-pencil me-1"></i> Update Designation
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

        <!-- Designation Update Modal -->
        <div wire:ignore.self class="modal fade" id="designationModal" tabindex="-1"
            aria-labelledby="designationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-simple modal-add-new-cc">
                <div class="modal-content">
                    <div class="modal-body">
                        <button wire:click="exit" type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                        <div class="text-center mb-4">
                            <h4 class="mb-2" id="designationModalLabel">Update Officer Designation</h4>
                            <p class="text-muted"><span class="badge bg-info">Designation Management</span></p>
                        </div>
                        <form onsubmit="return false">
                            @csrf
                            <!-- Officer Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><span class="badge text-bg-primary">Officer
                                            Information</span></h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" value="{{ $first_name }}"
                                                readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" value="{{ $last_name }}"
                                                readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control" value="Data Officer" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Designation Update -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><span class="badge text-bg-secondary">Designation
                                            Update</span></h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Current Designation</label>
                                            <input type="text" class="form-control"
                                                value="{{ $current_designation }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">New Designation <span
                                                    class="text-danger">*</span></label>
                                            <select wire:model.live="new_designation" class="form-select">
                                                <option value="">--Select Designation--</option>
                                                @foreach ($available_designations as $designation)
                                                    <option value="{{ $designation }}">{{ $designation }}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Select new designation (Nurse, Doctor, Midwife,
                                                Lab Attendant, Volunteer).</small>
                                            @error('new_designation')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-12 text-center">
                                @if ($modal_flag)
                                    <x-app-loader /><button wire:click="updateDesignation" type="button"
                                        class="btn btn-primary" @if (!$new_designation || $new_designation === $current_designation) disabled @endif>
                                        <i class="bx bx-check me-1"></i>Update Designation
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
        <!--/ Designation Update Modal -->

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const designationModal = document.getElementById('designationModal');

            // Listen for Livewire event to close modal
            Livewire.on('close-designation-modal', () => {
                const modal = bootstrap.Modal.getInstance(designationModal);
                if (modal) {
                    modal.hide();
                }
            });

            // Handle modal close events (both backdrop click and ESC key)
            designationModal.addEventListener('hidden.bs.modal', function() {
                @this.call('exit');
            });
        });
    </script>

    @include('_partials.datatables-init')

</div>
