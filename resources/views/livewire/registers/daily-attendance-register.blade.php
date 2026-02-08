@php
    use Carbon\Carbon;
@endphp
@section('title', ' Daily Attendance Register')
<div>
    <!-- Hero Card Header -->
    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 28px;">
                            <i class='bx bx-calendar-check me-2'></i>
                            Daily Attendance Register
                        </h4>

                        <div class="hero-stats">
                            <span class="hero-stat">
                                <i class="bx bx-list-check"></i>
                                {{ count($dailies) }} Total Records
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-calendar-check"></i>
                                {{ $dailies->where('visit_date', Carbon::today()->format('Y-m-d'))->count() }} Today's
                                Visits
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-user-check"></i>
                                {{ $dailies->where('first_contact', 1)->count() }} First Contacts
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-building"></i>
                                {{ $facility_name ?? 'N/A' }}
                            </span>
                            {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-light btn-lg rounded-pill shadow-sm d-inline-flex align-items-center"
                                style="border: 1px solid #ddd; padding: 10px 20px;" data-bs-toggle="modal"
                                data-bs-target="#dinVerificationModal" wire:click="openDinModal" type="button"
                                title="Record New Attendance">
                                <i class="bx bx-plus me-2" style="font-size: 15px;"></i>
                                + Record New Attendance
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

                        <th>DIN</th>
                        <th>Patient Name</th>
                        <th>State</th>
                        <th>Visit Date</th>
                        <th>Gender</th>
                        <th>First Contact</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dailies as $daily)
                        <tr wire:key="{{ $daily->id }}">

                            <td>
                                <span class="badge bg-label-info">{{ $daily->user->DIN ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $daily->user->first_name . ' ' . $daily->user->last_name ?? 'N/A' }}</td>
                            <td>
                                <i class="bx bx-map me-1"></i>
                                {{ $daily->state->name ?? 'N/A' }}
                            </td>
                            <td>{{ $daily->visit_date ? Carbon::parse($daily->visit_date)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td>
                                <span
                                    class="badge {{ $daily->gender === 'Male' ? 'bg-label-primary' : 'bg-label-success' }}">
                                    {{ $daily->gender ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span
                                    class="badge {{ $daily->first_contact ? 'bg-label-warning' : 'bg-label-secondary' }}">
                                    {{ $daily->first_contact ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal"
                                            data-bs-target="#dailyRegistrationModal"
                                            wire:click="edit({{ $daily->id }})">
                                            <i class="icon-base ti tabler-pencil me-1"></i> Edit
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                            wire:click="delete({{ $daily->id }})">
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

    <!-- DIN Verification Modal -->
    <div wire:ignore.self class="modal fade" id="dinVerificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content" style="height: 600px;">
                <div class="modal-body" style="padding: 2.5rem;">
                    <div class="text-center mb-5">
                        <h3 class="mb-3"><i class="menu-icon icon-base ti tabler-checklist me-1 text-success"
                                style="font-size: 1.2rem;"></i>
                            Verify Patient</h3>
                        <p class="text-muted mb-2">Enter the Patient's 6-digit DIN to proceed with verification.</p>


                    </div>
                    <form onSubmit="return false">
                        @csrf
                        <div class="mb-4 form-control-validation">
                            <label class="form-label fw-bold">Enter 6-Digit DIN <span
                                    class="text-danger">*</span></label>
                            <div
                                class="auth-input-wrapper d-flex align-items-center justify-content-between numeral-mask-wrapper mb-3">
                                <input type="tel"
                                    class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                    maxlength="1" autofocus />
                                <input type="tel"
                                    class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                    maxlength="1" />
                                <input type="tel"
                                    class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                    maxlength="1" />
                                <input type="tel"
                                    class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                    maxlength="1" />
                                <input type="tel"
                                    class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                    maxlength="1" />
                                <input type="tel"
                                    class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                    maxlength="1" />
                            </div>
                            <input type="hidden" name="din" wire:model="din" />
                            @error('din')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div id="din-error" class="text-danger mt-2" style="display: none;">Please enter all 6
                                digits.</div>
                        </div>
                        <div class="text-center mb-4">
                            <button wire:click="verifyPatient" type="button" class="btn btn-primary"
                                id="verify-btn" disabled>
                                <i class="bx bx-check me-1"></i>Verify
                            </button>
                            <button wire:click="exit" type="button" class="btn btn-label-secondary"
                                data-bs-dismiss="modal" aria-label="Close">
                                <i class="bx bx-x me-1"></i>Cancel
                            </button>
                        </div>
                        @if ($isPatientVerified)
                            <div class="text-center mt-4">
                                <span class="badge bg-label-success mb-2">✅ Patient Successfully Verified</span>
                                <div class="card p-3 bg-light">
                                    <p class="mb-1"><strong>Name:</strong> {{ $first_name }} {{ $last_name }}
                                    </p>
                                    <p class="mb-1"><strong>Registration Facility:</strong>
                                        {{ $patient_registration_facility }}</p>
                                </div>
                                <p class="mt-3"></p>
                                <button wire:click="openAttendanceModal" type="button" class="btn btn-success"
                                    data-bs-dismiss="modal" data-bs-toggle="modal"
                                    data-bs-target="#dailyRegistrationModal">
                                    <i class="bx bx-arrow-right me-1"></i>Proceed to Attendance
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End DIN Verification Modal -->

    <!-- Daily Registration Modal -->
    <div wire:ignore.self class="modal fade" id="dailyRegistrationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-body">
                    <button wire:click='exit' type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h4 class="mb-2">
                            {{ $daily_id ? 'Edit Daily Attendance Registration' : 'Daily Attendance Registration' }}
                        </h4>
                        <p class="text-muted"><span class="badge bg-info">Primary Health Care Register</span></p>
                    </div>
                    <form wire:submit.prevent="{{ $modal_flag ? 'update' : 'store' }}">
                        @csrf
                        <!-- Facility Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-primary">Facility
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">State <span class="text-danger">*</span></label>
                                        <input wire:model='state_name' type="text" class="form-control" readonly>
                                        <input wire:model='state_id' type="hidden">
                                        @error('state_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">LGA</label>
                                        <input wire:model='lga_name' type="text" class="form-control" readonly>
                                        <input wire:model='lga_id' type="hidden">
                                        @error('lga_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ward</label>
                                        <input wire:model='ward_name' type="text" class="form-control" readonly>
                                        <input wire:model='ward_id' type="hidden">
                                        @error('ward_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Health Facility <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='facility_name' type="text" class="form-control"
                                            readonly>
                                        <input wire:model='facility_id' type="hidden">
                                        @error('facility_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Month/Day/Year</label>
                                        <input wire:model='month_year' readonly type="date" class="form-control">
                                        @error('month_year')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Patient Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-secondary">Patient
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">First Name <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='first_name' type="text" class="form-control" readonly>
                                        @error('first_name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input wire:model='last_name' type="text" class="form-control" readonly>
                                        @error('last_name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date of Visit</label>
                                        <input wire:model='visit_date' type="date" class="form-control">
                                        @error('visit_date')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date of Birth <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='date_of_birth' type="date" class="form-control">
                                        @error('date_of_birth')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                                        <select wire:model='gender' class="form-select">
                                            <option value="">--Select--</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                        @error('gender')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Age Group</label>
                                        <select wire:model='age_group' class="form-select">
                                            <option value="">--Select--</option>
                                            <option value="11 - 14 years">11 - 14 years</option>
                                            <option value="15 - 19 years">15 - 19 years</option>
                                            <option value="20 - 24 years">20 - 24 years</option>
                                            <option value="25 - 29 years">25 - 29 years</option>
                                            <option value="30 - 34 years">30 - 34 years</option>
                                            <option value="35 - 49 years">35 - 49 years</option>
                                            <option value="50 + years">50 + years</option>
                                        </select>
                                        @error('age_group')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Address</label>
                                        <input wire:model='address' type="text" class="form-control"
                                            placeholder="Enter address">
                                        @error('address')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">State of Origin</label>
                                        <select wire:model='state_of_origin_id' class="form-select">
                                            <option value="">--Select State--</option>
                                            @foreach ($states as $stateOption)
                                                <option value="{{ $stateOption->id }}">{{ $stateOption->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('state_of_origin_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number</label>
                                        <input wire:model='phone' type="text" class="form-control"
                                            placeholder="Enter phone number">
                                        @error('phone')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">First Contact with Facility</label>
                                        <select wire:model='first_contact' class="form-select">
                                            <option value="">--Select--</option>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                        @error('first_contact')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Next of Kin Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-warning">Next of Kin
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Name</label>
                                        <input wire:model='next_of_kin_name' type="text" class="form-control"
                                            placeholder="Enter name">
                                        @error('next_of_kin_name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Relationship with Patient</label>
                                        <input wire:model='next_of_kin_relation' type="text" class="form-control"
                                            placeholder="Enter relationship">
                                        @error('next_of_kin_relation')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact Address</label>
                                        <input wire:model='next_of_kin_address' type="text" class="form-control"
                                            placeholder="Enter address">
                                        @error('next_of_kin_address')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number</label>
                                        <input wire:model='next_of_kin_phone' type="text" class="form-control"
                                            placeholder="Enter phone number">
                                        @error('next_of_kin_phone')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Officer Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-info">Officer
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Officer Name</label>
                                        <input wire:model='officer_name' type="text" class="form-control"
                                            readonly>
                                        @error('officer_name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Officer Role</label>
                                        <input wire:model='officer_role' type="text" class="form-control"
                                            readonly>
                                        @error('officer_role')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Officer Designation</label>
                                        <input wire:model='officer_designation' type="text" class="form-control"
                                            readonly>
                                        @error('officer_designation')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Action Buttons -->
                        <div class="col-12 text-center">
                            @if ($modal_flag)
                                <button type="submit" class="btn btn-primary" id="update-btn">
                                    <i class="bx bx-check me-1"></i>Update Register
                                </button>
                                <button wire:click="exit" type="button" class="btn btn-label-secondary"
                                    data-bs-dismiss="modal" aria-label="Close">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </button>
                            @else
                                <button type="submit" class="btn btn-primary" id="record-btn">
                                    <i class="bx bx-plus me-1"></i>Record Attendance
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
    <!--/ Daily Registration Modal -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const inputs = document.querySelectorAll('.numeral-mask');
            const hiddenInput = document.querySelector('input[name="din"]');
            const verifyBtn = document.querySelector('#verify-btn');
            const errorDiv = document.querySelector('#din-error');

            // Store original button text
            const originalRecordText = '<i class="bx bx-plus me-1"></i>Record Attendance';
            const originalUpdateText = '<i class="bx bx-check me-1"></i>Update Register';

            // Function to validate inputs and toggle button state
            const updateFormState = () => {
                const dinValue = Array.from(inputs)
                    .map(inp => inp.value.match(/^[0-9]$/) ? inp.value : '')
                    .join('');

                // Update hidden input
                if (hiddenInput) {
                    hiddenInput.value = dinValue;
                    // Trigger Livewire update
                    hiddenInput.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                }

                // Enable/disable button based on input length
                if (verifyBtn) {
                    if (dinValue.length === 6) {
                        verifyBtn.removeAttribute('disabled');
                        if (errorDiv) errorDiv.style.display = 'none';
                    } else {
                        verifyBtn.setAttribute('disabled', 'true');
                        if (errorDiv) errorDiv.style.display = 'none';
                    }
                }
            };

            // Handle verify button click
            if (verifyBtn) {
                verifyBtn.addEventListener('click', () => {
                    // Show loading state
                    verifyBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';
                    verifyBtn.setAttribute('disabled', 'true');
                });
            }

            // Handle form submission for record/update buttons
            const form = document.querySelector('#dailyRegistrationModal form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    const recordBtn = document.querySelector('#record-btn');
                    const updateBtn = document.querySelector('#update-btn');

                    if (recordBtn) {
                        recordBtn.innerHTML =
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Recording...';
                        recordBtn.setAttribute('disabled', 'true');
                    }
                    if (updateBtn) {
                        updateBtn.innerHTML =
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
                        updateBtn.setAttribute('disabled', 'true');
                    }
                });
            }

            // Listen for Livewire response to reset buttons
            document.addEventListener('livewire:finished', () => {
                if (verifyBtn) {
                    verifyBtn.innerHTML = '<i class="bx bx-check me-1"></i>Verify';
                    updateFormState(); // Re-enable if DIN is complete
                }

                // Reset record/update buttons
                const recordBtn = document.querySelector('#record-btn');
                const updateBtn = document.querySelector('#update-btn');

                if (recordBtn) {
                    recordBtn.innerHTML = originalRecordText;
                    recordBtn.removeAttribute('disabled');
                }
                if (updateBtn) {
                    updateBtn.innerHTML = originalUpdateText;
                    updateBtn.removeAttribute('disabled');
                }
            });

            inputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    // Only allow numbers
                    if (!input.value.match(/^[0-9]$/)) {
                        input.value = '';
                    }

                    // Move to next input on valid digit
                    if (input.value.match(/^[0-9]$/) && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }

                    updateFormState();
                });

                // Handle backspace
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !input.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
            });

            // Initial state
            updateFormState();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dinModal = document.getElementById('dinVerificationModal');
            const dailyModal = document.getElementById('dailyRegistrationModal');

            // Listen for Livewire event to close modals
            Livewire.on('close-modals', () => {
                console.log('close-modals event received');
                const dinModalInstance = bootstrap.Modal.getInstance(dinModal);
                const dailyModalInstance = bootstrap.Modal.getInstance(dailyModal);
                if (dinModalInstance) {
                    dinModalInstance.hide();
                    console.log('DIN modal closed');
                }
                if (dailyModalInstance) {
                    dailyModalInstance.hide();
                    console.log('Daily modal closed');
                }
            });

            // Handle modal close events (backdrop click or ESC key)
            dinModal.addEventListener('hidden.bs.modal', function(event) {
                console.log('DIN modal hidden');
                if (!dailyModal.classList.contains('show')) {
                    console.log('Calling exit for DIN modal');
                    @this.call('exit');
                }
            });
            dailyModal.addEventListener('hidden.bs.modal', function(event) {
                console.log('Daily modal hidden');
                if (!dinModal.classList.contains('show')) {
                    console.log('Calling exit for Daily modal');
                    @this.call('exit');
                }
            });
        });
    </script>

    @livewireScripts
    @include('_partials.datatables-init')
</div>
