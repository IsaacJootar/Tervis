@php
    use Carbon\Carbon;
@endphp

@section('title', 'Antenatal Registration')

{{-- Wrap EVERYTHING in Alpine DataTable component with integrated DIN logic --}}
<div x-data="Object.assign(dataTable(), {
    din: '',
    handleInput(e, index) {
        const inputs = document.querySelectorAll('#dinVerificationModal .numeral-mask');
        const val = e.target.value;

        // Auto-tab forward
        if (val.length === 1 && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }

        this.syncDin();
    },
    handleBackspace(e, index) {
        const inputs = document.querySelectorAll('#dinVerificationModal .numeral-mask');
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
            inputs[index - 1].focus();
        }
        this.syncDin();
    },
    syncDin() {
        let combined = '';
        document.querySelectorAll('#dinVerificationModal .numeral-mask').forEach(i => combined += i.value);

        // Update Alpine state (this keeps the button enabled)
        this.din = combined;

        // Sync with Livewire property
        @this.set('din', combined);
    }
})">
    {{-- Hero Card Header --}}
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">

                {{-- Floating Decorations --}}
                <div class="hero-decoration">
                    <span class="floating-shape shape-1"></span>
                    <span class="floating-shape shape-2"></span>
                    <span class="floating-shape shape-3"></span>
                </div>

                {{-- Hero Content --}}
                <div class="hero-content">

                    <div class="hero-text">
                        <h4 class="hero-title mb-1" style="color: white; font-size: 22px;">
                            <i class='bx bx-plus-medical me-2'></i>
                            Antenatal Registration & Management
                        </h4>

                        <p class="mb-2" style="color: rgba(255, 255, 255, 0.85); font-size: 0.875rem;">
                            <i class="bx bx-time me-1"></i>
                            {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                        </p>

                        <div class="hero-stats">
                            <span class="hero-stat">
                                <i class="bx bx-building"></i>
                                {{ $facility_name ?? 'N/A' }}
                            </span>

                            <span class="hero-stat">
                                <i class="bx bx-map-pin"></i>
                                {{ $facility_state ?? 'N/A' }}
                            </span>

                            <span class="hero-stat">
                                <i class="bx bx-map"></i>
                                {{ $facility_lga ?? 'N/A' }}
                            </span>

                            <span class="hero-stat">
                                <i class="bx bx-current-location"></i>
                                Ward: {{ $facility_ward ?? 'N/A' }}
                            </span>

                            <span class="hero-stat">
                                <i class="bx bx-list-check"></i>
                                {{ isset($antenatals) ? count($antenatals) : 0 }} Total Registrations
                            </span>

                            <span class="hero-stat">
                                <i class="bx bx-check-circle"></i>
                                {{ collect($antenatals ?? [])->where('is_active', true)->count() }}
                                Active Pregnancies
                            </span>
                        </div>
                    </div>

                    {{-- CTA (same position as original) --}}
                    <div class="demo-inline-spacing mt-3">
                        <button type="button"
                            class="btn btn-lg btn-dark px-5 py-3 d-inline-flex align-items-center shadow"
                            style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#dinVerificationModal"
                            wire:click="openDinModal">
                            <span class="icon-base ti tabler-plus icon-24px me-2 text-white"></span>
                            <span class="fw-bold">New Registration</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- DataTable --}}
    <div class="card">
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="dataTable" class="table">
                <thead class="table-dark">
                    <tr>
                        <th>DIN</th>
                        <th>Patient Name</th>
                        <th>Unit No.</th>
                        <th>Booking Date</th>
                        <th>Pregnancy #</th>
                        <th>EDD</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($antenatals ?? [] as $antenatal)
                        <tr wire:key="{{ $antenatal->id }}">
                            <td>
                                <span class="badge bg-label-info">{{ $antenatal->patient->din ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $antenatal->patient->first_name . ' ' . $antenatal->patient->last_name ?? 'N/A' }}
                            </td>
                            <td>{{ $antenatal->unit_no ?? 'N/A' }}</td>
                            <td>{{ $antenatal->date_of_booking ? Carbon::parse($antenatal->date_of_booking)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td>
                                <span class="badge bg-label-primary">Pregnancy
                                    #{{ $antenatal->pregnancy_number ?? 1 }}</span>
                            </td>
                            <td>{{ $antenatal->edd ? Carbon::parse($antenatal->edd)->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                @if ($antenatal->is_active ?? false)
                                    <span class="badge bg-label-success">
                                        <i class="bx bx-check-circle me-1"></i>Active
                                    </span>
                                @else
                                    <span class="badge bg-label-secondary">Completed</span>
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
                                            data-bs-target="#antenatalRegistrationModal"
                                            wire:click="edit({{ $antenatal->id }})">
                                            <i class="icon-base ti tabler-pencil me-1"></i> Edit
                                        </a>
                                        <a class="dropdown-item text-danger" href="javascript:void(0)"
                                            wire:click="delete({{ $antenatal->id }})">
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

    {{-- DIN VERIFICATION MODAL --}}
    <div wire:ignore.self class="modal fade" id="dinVerificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 500px;">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="exit"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem 2.5rem 2.5rem;">
                    <div class="text-center mb-4">
                        <h3 class="mb-3"><i class="menu-icon icon-base ti tabler-checklist me-1 text-success"
                                style="font-size: 1.2rem;"></i>
                            Verify Patient for ANC</h3>
                        <p class="text-muted mb-2">Enter the Patient's 8-digit DIN to proceed with ANC registration.</p>

                        {{-- New Patient Link --}}
                        <div class="alert alert-info py-2 mb-3">
                            <small class="d-block mb-1">Don't have a DIN yet?</small>
                            <button wire:click="openRegistrationModal" type="button"
                                class="btn btn-sm btn-outline-primary" data-bs-dismiss="modal" data-bs-toggle="modal"
                                data-bs-target="#antenatalRegistrationModal">
                                <i class="bx bx-user-plus me-1"></i>Register New Patient
                            </button>
                        </div>
                    </div>
                    <form onSubmit="return false">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">Enter 8-Digit DIN <span
                                    class="text-danger">*</span></label>
                            <div
                                class="auth-input-wrapper d-flex align-items-center justify-content-between numeral-mask-wrapper mb-3">
                                @for ($i = 0; $i < 8; $i++)
                                    <input type="tel"
                                        class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                        maxlength="1" {{ $i === 0 ? 'autofocus' : '' }}
                                        x-on:input="handleInput($event, {{ $i }})"
                                        x-on:keydown="handleBackspace($event, {{ $i }})" />
                                @endfor
                            </div>
                            <input type="hidden" x-model="din" />
                            @error('din')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="text-center mb-3">
                            <button wire:click="verifyPatient" type="button" class="btn btn-primary w-100"
                                :disabled="din.length !== 8" id="verify-btn">
                                <span wire:loading.remove wire:target="verifyPatient">
                                    <i class="bx bx-check me-1"></i>Verify
                                </span>
                                <span wire:loading wire:target="verifyPatient">
                                    <span class="spinner-border spinner-border-sm" role="status"
                                        aria-hidden="true"></span>
                                    Verifying...
                                </span>
                            </button>
                            <button wire:click="exit" type="button" class="btn btn-label-secondary w-100 mt-2"
                                data-bs-dismiss="modal" aria-label="Close">
                                <i class="bx bx-x me-1"></i>Cancel
                            </button>
                        </div>

                        {{-- SCENARIO 1: Patient has ACTIVE ANC - show message only --}}
                        @if ($hasActiveAncRegistration)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-danger mb-2">🫃 Patient Found: Active Pregnancy In
                                    Progress</span>
                                <div class="card p-3 bg-light">
                                    <p class="mb-1"><strong>Name:</strong> {{ $first_name }} {{ $last_name }}
                                    </p>
                                    <p class="mb-1"><strong>Current Pregnancy:</strong> #{{ $pregnancy_number - 1 }}
                                    </p>
                                    <p class="mb-0"><strong>Registration Facility:</strong>
                                        {{ $patient_registration_facility }}</p>
                                </div>
                                <div class="alert alert-warning mt-3 mb-0">
                                    <strong>This patient has an active pregnancy.</strong><br>
                                    Cannot register a new pregnancy until the current one is completed.
                                </div>
                                <a href="{{ route('patient-dashboard', $patient_id) }}"
                                    class="btn btn-primary w-100 mt-3">
                                    <i class="bx bx-tachometer me-1"></i>Go to Patient Dashboard
                                </a>
                                <button type="button" class="btn btn-secondary w-100 mt-3" data-bs-dismiss="modal"
                                    wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        @endif

                        {{-- SCENARIO 2: Patient NOT found - NEW PATIENT (can register via ANC entry point) --}}
                        @if ($isNewPatient)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-warning mb-2">⚠️ DIN Not Found - New Patient</span>
                                <div class="alert alert-info">
                                    <p class="mb-0"><strong>This patient does not exist in the system.</strong></p>
                                    <p class="mb-0">Click below to register a new patient through ANC.</p>
                                </div>
                                <button wire:click="openRegistrationModal" type="button"
                                    class="btn btn-primary w-100 mt-3" data-bs-dismiss="modal" data-bs-toggle="modal"
                                    data-bs-target="#antenatalRegistrationModal">
                                    <i class="bx bx-user-plus me-1"></i>Proceed to New Patient Registration
                                </button>
                            </div>
                        @endif

                        {{-- SCENARIO 3: VERIFIED PATIENT (DIN found, no active ANC) --}}
                        @if ($isPatientVerified)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-success mb-2">✅ Patient Successfully Verified</span>
                                <div class="card p-3 bg-light">
                                    <p class="mb-1"><strong>Name:</strong> {{ $first_name }} {{ $last_name }}
                                    </p>
                                    <p class="mb-1"><strong>Registration Facility:</strong>
                                        {{ $patient_registration_facility }}</p>
                                    <p class="mb-0"><strong>This will be:</strong> Pregnancy
                                        #{{ $pregnancy_number }}</p>
                                    @if ($pregnancy_number > 1)
                                        <p class="mb-0 mt-2 text-muted"><small>Previous pregnancies:
                                                {{ $pregnancy_number - 1 }}</small></p>
                                    @endif
                                </div>
                                <button wire:click="openRegistrationModal" type="button"
                                    class="btn btn-success w-100 mt-3" data-bs-dismiss="modal" data-bs-toggle="modal"
                                    data-bs-target="#antenatalRegistrationModal">
                                    <i class="bx bx-arrow-right me-1"></i>Proceed to ANC Registration
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- End DIN Verification Modal --}}

    {{-- ANC REGISTRATION MODAL (COMPLETE FORM) --}}
    <div wire:ignore.self class="modal fade" id="antenatalRegistrationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">
                        <i class="bx bx-plus-medical me-2"></i>
                        {{ $registration_id ? 'Edit ANC Registration' : 'New ANC Registration - Pregnancy #' . $pregnancy_number }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close" wire:click="exit"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="{{ $registration_id ? 'update' : 'store' }}">
                        @csrf

                        {{-- NEW PATIENT: Show full patient registration fields --}}
                        @if (!$patient_id)
                            <div class="mb-4">
                                <h5 class="badge text-bg-info bg-primary text-white px-3 py-2 rounded">
                                    <i class="bx bx-user me-1"></i>Section A: Patient Demographics
                                </h5>
                            </div>

                            <h6 class="text-secondary mb-3">
                                <i class="bx bx-id-card me-1"></i>Patient Information (New Registration)
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Surname <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" wire:model="last_name"
                                        placeholder="Enter surname">
                                    @error('last_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" wire:model="first_name"
                                        placeholder="Enter first name">
                                    @error('first_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Other Names</label>
                                    <input type="text" class="form-control" wire:model="middle_name"
                                        placeholder="Enter other names">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select class="form-select" wire:model="patient_gender">
                                        <option value="">--Select Gender--</option>
                                        <option value="Male">Male (M)</option>
                                        <option value="Female">Female (F)</option>
                                    </select>
                                    @error('patient_gender')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-4" x-data="{
                                    dob: @entangle('patient_dob'),
                                    age: @entangle('patient_age'),
                                    calculateAge() {
                                        if (this.dob) {
                                            const birthDate = new Date(this.dob);
                                            const today = new Date();
                                            let calculatedAge = today.getFullYear() - birthDate.getFullYear();
                                            const monthDiff = today.getMonth() - birthDate.getMonth();

                                            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                                                calculatedAge--;
                                            }

                                            this.age = calculatedAge;
                                        } else {
                                            this.age = '';
                                        }
                                    }
                                }">
                                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" x-model="dob"
                                        @change="calculateAge()" :max="new Date().toISOString().split('T')[0]">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Age (Calculated)</label>
                                    <input type="text" class="form-control bg-light" readonly
                                        wire:model="patient_age" placeholder="Auto-calculated">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" wire:model="patient_phone"
                                        placeholder="08012345678">
                                    @error('patient_phone')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" wire:model="patient_email"
                                        placeholder="patient@example.com">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Marital Status</label>
                                    <select class="form-select" wire:model="marital_status">
                                        <option value="">--Select--</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-12">
                                    <label class="form-label">Residential Address <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" wire:model="address" rows="2" placeholder="Enter full address"></textarea>
                                    @error('address')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">State <span class="text-danger">*</span></label>
                                    <select class="form-select" wire:model.live="state_id">
                                        <option value="">--Select State--</option>
                                        @foreach ($states ?? [] as $state)
                                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('state_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">LGA <span class="text-danger">*</span></label>
                                    <select class="form-select" wire:model.live="lga_id">
                                        <option value="">--Select LGA--</option>
                                        @foreach ($lgas ?? [] as $lga)
                                            <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('lga_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ward</label>
                                    <select class="form-select" wire:model="ward_id">
                                        <option value="">--Select Ward--</option>
                                        @foreach ($wards ?? [] as $ward)
                                            <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">NHIS Status</label>
                                    <select class="form-select" wire:model="is_nhis_subscriber">
                                        <option value="">--Select--</option>
                                        <option value="0">Non-NHIS</option>
                                        <option value="1">NHIS Subscriber</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">NHIS Number</label>
                                    <input type="text" class="form-control" wire:model="nhis_number"
                                        placeholder="Enter NHIS number">
                                </div>
                            </div>
                        @else
                            {{-- EXISTING PATIENT: Show summary only --}}
                            <div class="alert alert-info mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Patient:</strong> {{ $first_name }} {{ $last_name }}
                                    </div>
                                    <div class="col-md-2">
                                        <strong>DIN:</strong> {{ $din }}
                                    </div>
                                    <div class="col-md-2">
                                        <strong>Age:</strong> {{ $patient_age }}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Pregnancy #:</strong> {{ $pregnancy_number }}
                                    </div>
                                    <div class="col-md-2">
                                        <strong>Previous:</strong>
                                        {{ $pregnancy_number > 1 ? $pregnancy_number - 1 : 0 }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ANC-Specific Biographical Information (Always shown) --}}
                        <div class="mb-4">
                            <h5 class="badge text-bg-info bg-primary text-white px-3 py-2 rounded">
                                <span><i class="bx bx-user me-1"></i>ANC Biographical Information</span>
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Unit No. <span class="text-danger">*</span></label>
                                    <input wire:model="unit_no" type="text" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">X-Ray No.</label>
                                    <input wire:model="xray_no" type="text" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Occupation</label>
                                    <input wire:model="occupation" type="text" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ethnic Group</label>
                                    <input wire:model="ethnic_group" type="text" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Literate</label>
                                    <select wire:model="literate" class="form-select">
                                        <option value="">Select</option>
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Speaks English</label>
                                    <select wire:model="speaks_english" class="form-select">
                                        <option value="">Select</option>
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Husband/Partner Information --}}
                        <div class="mb-4">
                            <h5 class="badge text-bg-info bg-secondary text-white px-3 py-2 rounded">
                                <i class="bx bx-male-sign me-1"></i>Husband / Partner Information
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Husband's Name</label>
                                    <input wire:model="husband_name" type="text" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Occupation</label>
                                    <input wire:model="husband_occupation" type="text" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Employer</label>
                                    <input wire:model="husband_employer" type="text" class="form-control">
                                </div>
                            </div>
                        </div>

                        {{-- Current Pregnancy Details --}}
                        <div class="mb-4">
                            <h5 class="badge text-bg-info bg-success text-white px-3 py-2 rounded">
                                <i class="bx bx-calendar-heart me-1"></i>Current Pregnancy Details
                            </h5>
                            <div class="row g-3" x-data="{
                                lmp: @entangle('lmp'),
                                edd: @entangle('edd'),
                                gaWeeks: @entangle('gestational_age_weeks'),
                                gaDays: @entangle('gestational_age_days'),
                                trimester: @entangle('booking_trimester'),
                                gestationalAge: '',
                                calculatePregnancyDetails() {
                                    if (this.lmp) {
                                        // Calculate EDD (LMP + 280 days)
                                        const lmpDate = new Date(this.lmp);
                                        const eddDate = new Date(lmpDate);
                                        eddDate.setDate(lmpDate.getDate() + 280);
                                        this.edd = eddDate.toISOString().split('T')[0];

                                        // Calculate Gestational Age
                                        const today = new Date();
                                        const diffTime = Math.abs(today - lmpDate);
                                        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                                        const weeks = Math.floor(diffDays / 7);
                                        const days = diffDays % 7;

                                        // Sync to Livewire
                                        this.gaWeeks = weeks;
                                        this.gaDays = days;
                                        this.gestationalAge = weeks + ' weeks, ' + days + ' days';

                                        // Determine Trimester
                                        if (weeks < 14) {
                                            this.trimester = 'First';
                                        } else if (weeks < 27) {
                                            this.trimester = 'Second';
                                        } else {
                                            this.trimester = 'Third';
                                        }
                                    } else {
                                        this.edd = '';
                                        this.gaWeeks = null;
                                        this.gaDays = null;
                                        this.gestationalAge = '';
                                        this.trimester = '';
                                    }
                                }
                            }">
                                <div class="col-md-3">
                                    <label class="form-label">Booking Date <span class="text-danger">*</span></label>
                                    <input wire:model="date_of_booking" type="date" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">L.M.P <span class="text-danger">*</span></label>
                                    <input x-model="lmp" @change="calculatePregnancyDetails()" type="date"
                                        class="form-control" :max="new Date().toISOString().split('T')[0]">
                                    @error('lmp')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">E.D.D (Auto-calculated)</label>
                                    <input x-model="edd" type="date" class="form-control bg-light" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gravida / Parity</label>
                                    <div class="input-group">
                                        <input wire:model="gravida" type="number" class="form-control"
                                            placeholder="G" min="1">
                                        <input wire:model="parity" type="number" class="form-control"
                                            placeholder="P" min="0">
                                    </div>
                                    @error('gravida')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                    @error('parity')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                    @if ($suggested_gravida)
                                        <small class="text-muted">Suggested: G{{ $suggested_gravida }}
                                            P{{ $suggested_parity }}</small>
                                    @endif
                                </div>

                                {{-- Display Calculated Values --}}
                                <div class="col-md-6">
                                    <label class="form-label">Gestational Age (Current)</label>
                                    <input x-model="gestationalAge" type="text" class="form-control bg-light"
                                        readonly placeholder="Auto-calculated">
                                    <input type="hidden" x-model="gaWeeks">
                                    <input type="hidden" x-model="gaDays">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Trimester (At Booking)</label>
                                    <input x-model="trimester" type="text" class="form-control bg-light" readonly
                                        placeholder="Auto-calculated">
                                </div>
                            </div>
                        </div>

                        {{-- Obstetric History (Previous Pregnancies) --}}
                        <div class="mb-4">
                            <h5 class="badge text-bg-info bg-warning text-white px-3 py-2 rounded">
                                <i class="bx bx-history me-1"></i>Obstetric History (Previous Pregnancies)
                            </h5>
                            @for ($i = 0; $i < 5; $i++)
                                <div class="row g-2 mb-2 border-bottom pb-2">
                                    <div class="col-md-1 text-center mt-4"><strong>#{{ $i + 1 }}</strong></div>
                                    <div class="col-md-2">
                                        <label class="small">DOB</label>
                                        <input wire:model="preg_{{ $i }}_dob" type="date"
                                            class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small">Duration</label>
                                        <input wire:model="preg_{{ $i }}_dur" type="text"
                                            class="form-control form-control-sm" placeholder="weeks">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small">Outcome/Labour</label>
                                        <input wire:model="preg_{{ $i }}_outcome" type="text"
                                            class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small">Weight (kg)</label>
                                        <input wire:model="preg_{{ $i }}_weight" type="text"
                                            class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small">Baby NNDD</label>
                                        <input wire:model="preg_{{ $i }}_nndd" type="text"
                                            class="form-control form-control-sm">
                                    </div>
                                </div>
                            @endfor
                        </div>

                        {{-- Medical Assessment & Vitals --}}
                        <div class="mb-4">
                            <h5 class="badge text-bg-info bg-danger text-white px-3 py-2 rounded">
                                <i class="bx bx-pulse me-1"></i>Medical Assessment & Vitals
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">BP (mmHg)</label>
                                    <input wire:model="blood_pressure" type="text" class="form-control"
                                        placeholder="120/80">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Weight (kg)</label>
                                    <input wire:model="weight" type="number" step="0.1" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Height (cm)</label>
                                    <input wire:model="height" type="number" step="0.1" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Genotype</label>
                                    <select wire:model="genotype" class="form-select">
                                        <option value="">Select</option>
                                        <option value="AA">AA</option>
                                        <option value="AS">AS</option>
                                        <option value="AC">AC</option>
                                        <option value="SS">SS</option>
                                        <option value="SC">SC</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Blood Group/Rh</label>
                                    <input wire:model="blood_group_rhesus" type="text" class="form-control"
                                        placeholder="O+">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <label class="form-label">Comments / Special Instructions</label>
                                    <textarea wire:model="special_points" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Data Officer Information (Auto-Populated) --}}
                        <div class="mb-4 mt-5">
                            <h5 class="badge text-bg-info bg-dark text-white px-3 py-2 rounded">
                                <i class="bx bx-user-check me-1"></i>Data Officer Information (Auto-Populated)
                            </h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="bx bx-user me-1"></i>Officer Name
                                </label>
                                <input type="text" class="form-control" value="{{ $officer_name }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="bx bx-briefcase me-1"></i>Officer Role
                                </label>
                                <input type="text" class="form-control" value="{{ $officer_role }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="bx bx-id-card me-1"></i>Officer Designation
                                </label>
                                <input type="text" class="form-control" value="{{ $officer_designation }}"
                                    readonly>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                wire:click="exit">
                                <i class="bx bx-x me-1"></i>Cancel
                            </button>

                            <x-app-loader target="store,update">
                                @if ($registration_id)
                                    Update Registration
                                @else
                                    Complete Registration
                                @endif
                            </x-app-loader>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('open-main-modal', () => {
                    const dinModal = bootstrap.Modal.getInstance(document.getElementById(
                        'dinVerificationModal'));
                    if (dinModal) dinModal.hide();
                    new bootstrap.Modal(document.getElementById('antenatalRegistrationModal')).show();
                });

                Livewire.on('close-modals', () => {
                    ['dinVerificationModal', 'antenatalRegistrationModal'].forEach(id => {
                        const inst = bootstrap.Modal.getInstance(document.getElementById(id));
                        if (inst) inst.hide();
                    });
                });
            });
        </script>
    @endpush

</div>
{{-- End of Alpine DataTable wrapper --}}
