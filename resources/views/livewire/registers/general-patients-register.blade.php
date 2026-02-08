@php
    use Carbon\Carbon;
@endphp

@section('title', 'General Patient Registration')

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
                <div class="hero-content d-flex justify-content-between align-items-start flex-wrap gap-3">

                    <div class="hero-text">
                        <h4 class="hero-title mb-2" style="color: white; font-size: 28px;">
                            <i class='bx bx-user-plus me-2'></i>
                            General Patient Registration
                        </h4>

                        <div class="hero-stats">

                            <span class="hero-stat">
                                <i class="bx bx-building"></i>
                                {{ $facility_name ?? 'N/A' }}
                            </span>

                            <span class="hero-stat">
                                <i class="bx bx-time"></i>
                                {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                            </span>

                            <span class="hero-stat">
                                <i class="bx bx-map"></i>
                                {{ $facility_state ?? 'N/A' }}
                                • {{ $facility_lga ?? 'N/A' }}
                                • Ward: {{ $facility_ward ?? 'N/A' }}
                            </span>

                            <span class="hero-stat">
                                <i class="bx bx-list-check"></i>
                                {{ count($registrations) }} Total Registrations
                            </span>

                            <span class="hero-stat">
                                <i class="bx bx-shield-alt-2"></i>
                                {{ $registrations->where('patient.is_nhis_subscriber', true)->count() ?? 0 }}
                                NHIS Subscribers
                            </span>

                        </div>
                    </div>

                    {{-- CTA --}}
                    <div class="demo-inline-spacing">
                        <button type="button"
                            class="btn btn-lg btn-dark px-5 py-3 d-inline-flex align-items-center shadow"
                            style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#dinVerificationModal"
                            wire:click="openDinModal">
                            <span class="icon-base ti tabler-plus icon-24px me-2 text-white"></span>
                            <span class="fw-bold">New Patient</span>
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
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>State</th>
                        <th>NHIS Status</th>
                        <th>Registration Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registrations as $registration)
                        <tr wire:key="{{ $registration->id }}">
                            <td>
                                <span class="badge bg-label-info">{{ $registration->patient->din ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $registration->patient->first_name . ' ' . $registration->patient->last_name ?? 'N/A' }}
                            </td>
                            <td>{{ $registration->patient->phone ?? 'N/A' }}</td>
                            <td>
                                <span
                                    class="badge {{ $registration->patient->gender === 'Male' ? 'bg-label-primary' : 'bg-label-success' }}">
                                    {{ $registration->patient->gender === 'Male' ? 'M' : 'F' }}
                                </span>
                            </td>
                            <td>
                                <i class="bx bx-map me-1"></i>
                                {{ $registration->patient->state->name ?? 'N/A' }}
                            </td>
                            <td>
                                @if ($registration->patient->is_nhis_subscriber)
                                    <span class="badge bg-label-success">
                                        <i class="bx bx-shield-alt-2 me-1"></i>NHIS
                                    </span>
                                @else
                                    <span class="badge bg-label-secondary">Non-NHIS</span>
                                @endif
                            </td>
                            <td>{{ $registration->registration_date ? Carbon::parse($registration->registration_date)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal"
                                            data-bs-target="#registrationModal"
                                            wire:click="edit({{ $registration->id }})">
                                            <i class="icon-base ti tabler-pencil me-1"></i> Edit
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                            wire:click="delete({{ $registration->id }})"
                                            onclick="return confirm('Are you sure you want to delete this registration?')">
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
                            Verify Patient</h3>
                        <p class="text-muted mb-2">Enter the Patient's 8-digit DIN to proceed with verification.</p>

                        {{-- New Patient Link --}}
                        <div class="alert alert-info py-2 mb-3">
                            <small class="d-block mb-1">Don't have a DIN yet?</small>
                            <button wire:click="openRegistrationModal" type="button"
                                class="btn btn-sm btn-outline-primary" data-bs-dismiss="modal" data-bs-toggle="modal"
                                data-bs-target="#registrationModal">
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

                        {{-- SCENARIO 3: Patient has OPD - show dashboard button --}}
                        @if ($hasOpdRegistration)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-danger mb-2">✅ Patient Registration Found</span>
                                <div class="card p-3 bg-light">
                                    <p class="mb-1"><strong>Name:</strong> {{ $first_name }} {{ $last_name }}
                                    </p>
                                    <p class="mb-0"><strong>Registration Facility:</strong>
                                        {{ $patient_registration_facility }}</p>
                                </div>
                                <div class="alert alert-warning mt-3 mb-0">
                                    <strong>This patient already has an OPD registration.</strong><br>
                                    You cannot register them again. Click below to view their dashboard.
                                </div>
                                <a href="{{ route('patient-dashboard', $patient_id) }}"
                                    class="btn btn-primary w-100 mt-3">
                                    <i class="bx bx-tachometer me-1"></i>Go to Patient Dashboard
                                </a>
                            </div>
                        @endif

                        {{-- SCENARIO 1: NEW PATIENT (DIN not found) --}}
                        @if ($isNewPatient)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-warning mb-2">⚠️ DIN Not Found - New Patient</span>
                                <div class="alert alert-info">
                                    <p class="mb-0"><strong>This patient does not exist in the system.</strong></p>
                                    <p class="mb-0">Click below to proceed with new patient registration.</p>
                                </div>
                                <button wire:click="openRegistrationModal" type="button"
                                    class="btn btn-primary w-100 mt-3" data-bs-dismiss="modal" data-bs-toggle="modal"
                                    data-bs-target="#registrationModal">
                                    <i class="bx bx-user-plus me-1"></i>Proceed to New Patient Registration
                                </button>
                            </div>
                        @endif

                        {{-- SCENARIO 2: VERIFIED PATIENT (DIN found, no OPD) --}}
                        @if ($isPatientVerified)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-success mb-2">✅ Patient Successfully Verified</span>
                                <div class="card p-3 bg-light">
                                    <p class="mb-1"><strong>Name:</strong> {{ $first_name }} {{ $last_name }}
                                    </p>
                                    <p class="mb-0"><strong>Registration Facility:</strong>
                                        {{ $patient_registration_facility }}</p>
                                </div>
                                <button wire:click="openRegistrationModal" type="button"
                                    class="btn btn-success w-100 mt-3" data-bs-dismiss="modal" data-bs-toggle="modal"
                                    data-bs-target="#registrationModal">
                                    <i class="bx bx-arrow-right me-1"></i>Proceed to OPD Registration
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- End DIN Verification Modal --}}

    {{-- Registration Modal --}}
    <div wire:ignore.self class="modal fade" id="registrationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" x-data="{ state_id: @entangle('state_id').live, lga_id: @entangle('lga_id').live, ward_id: @entangle('ward_id').live }">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-user-plus me-2"></i>
                        {{ $registration_id ? 'Edit Patient Registration' : 'Register New Patient' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="exit"></button>
                </div>
                <div class="modal-body" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                    <form wire:submit.prevent="{{ $registration_id ? 'update' : 'store' }}">
                        @csrf

                        <!-- Section A: Patient & Next of Kin Details -->
                        <div class="mb-4">
                            <h5 class="badge text-bg-info bg-primary text-white px-3 py-2 rounded">
                                <i class="bx bx-user me-1"></i>Section A: Patient & Next of Kin Details
                            </h5>
                        </div>

                        <!-- Patient Demographics -->
                        <h6 class="text-secondary mb-3">
                            <i class="bx bx-id-card me-1"></i>Patient Demographics
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Surname <span class="text-danger">*</span></label>
                                <input type="text" class="form-control " wire:model="last_name"
                                    placeholder="Enter surname">
                                @error('last_name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control " wire:model="first_name"
                                    placeholder="Enter first name">
                                @error('first_name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Other Names</label>
                                <input type="text" class="form-control " wire:model="middle_name"
                                    placeholder="Enter other names">
                                @error('middle_name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- Date of Birth with Alpine.js Age Calculation -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select class="form-select " wire:model="gender">
                                    <option value="">--Select Gender--</option>
                                    <option value="Male">Male (M)</option>
                                    <option value="Female">Female (F)</option>
                                </select>
                                @error('gender')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4" x-data="{
                                dob: @entangle('date_of_birth'),
                                age: @entangle('calculated_age'),
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
                                <input type="date" class="form-control " x-model="dob" @change="calculateAge()"
                                    :max="new Date().toISOString().split('T')[0]">
                                @error('date_of_birth')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Age (Calculated)</label>
                                <input type="text" class="form-control" readonly wire:model="calculated_age"
                                    placeholder="Auto-calculated">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" class="form-control " wire:model="phone"
                                    placeholder="Enter phone number">
                                @error('phone')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control " wire:model="email"
                                    placeholder="Enter email address">
                                @error('email')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <select class="form-select " wire:model.live="state_id" x-model="state_id">
                                    <option value="">--Select State--</option>
                                    @foreach ($states as $stateOption)
                                        <option value="{{ $stateOption->id }}">{{ $stateOption->name }}</option>
                                    @endforeach
                                </select>
                                @error('state_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">LGA <span class="text-danger">*</span></label>
                                <select class="form-select " wire:model.live="lga_id" x-model="lga_id"
                                    :="!state_id">
                                    <option value="">
                                        <span x-show="!state_id">--Select State First--</span>
                                        <span x-show="state_id">--Select LGA--</span>
                                    </option>
                                    @foreach ($lgas as $lga)
                                        <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                    @endforeach
                                </select>
                                @error('lga_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ward</label>
                                <select class="form-select " wire:model.live="ward_id" x-model="ward_id"
                                    :="!lga_id">
                                    <option value="">
                                        <span x-show="!lga_id">--Select LGA First--</span>
                                        <span x-show="lga_id">--Select Ward--</span>
                                    </option>
                                    @foreach ($wards as $ward)
                                        <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                                    @endforeach
                                </select>
                                @error('ward_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- NHIS Information -->
                        <h6 class="text-secondary mb-3">
                            <i class="bx bx-shield-alt-2 me-1"></i>NHIS (National Health Insurance) Information
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="is_nhis_subscriber"
                                        wire:model.live="is_nhis_subscriber">
                                    <label class="form-check-label" for="is_nhis_subscriber">
                                        <i class="bx bx-shield-alt-2 me-1"></i>Is NHIS Subscriber?
                                    </label>
                                </div>
                            </div>
                        </div>

                        @if ($is_nhis_subscriber)
                            <div class="card bg-label-success mb-4">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">NHIS Number <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control " wire:model="nhis_number"
                                                placeholder="Enter NHIS number">
                                            @error('nhis_number')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">NHIS Provider <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control " wire:model="nhis_provider"
                                                placeholder="Enter NHIS provider">
                                            @error('nhis_provider')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">NHIS Expiry Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" class="form-control "
                                                wire:model="nhis_expiry_date">
                                            @error('nhis_expiry_date')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">NHIS Plan Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select " wire:model.live="nhis_plan_type">
                                                <option value="">--Select Plan Type--</option>
                                                <option value="Individual">Individual</option>
                                                <option value="Family">Family</option>
                                                <option value="Corporate">Corporate</option>
                                            </select>
                                            @error('nhis_plan_type')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        @if (in_array($nhis_plan_type, ['Family', 'Corporate']))
                                            <div class="col-md-6">
                                                <label class="form-label">Principal Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control "
                                                    wire:model="nhis_principal_name"
                                                    placeholder="Enter principal name">
                                                @error('nhis_principal_name')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Principal Number <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control "
                                                    wire:model="nhis_principal_number"
                                                    placeholder="Enter principal number">
                                                @error('nhis_principal_number')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Additional Patient Details -->
                        <h6 class="text-secondary mb-3">
                            <i class="bx bx-detail me-1"></i>Additional Patient Details
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Marital Status</label>
                                <select class="form-select " wire:model="marital_status">
                                    <option value="">--Select--</option>
                                    <option value="Single">Single (S)</option>
                                    <option value="Married">Married (M)</option>
                                    <option value="Widowed">Widowed (W)</option>
                                    <option value="Divorced">Divorced (D)</option>
                                </select>
                                @error('marital_status')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Occupation</label>
                                <input type="text" class="form-control " wire:model="occupation"
                                    placeholder="Enter occupation">
                                @error('occupation')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Religion</label>
                                <select class="form-select " wire:model="religion">
                                    <option value="">--Select Religion--</option>
                                    <option value="Christian">Christian</option>
                                    <option value="Muslim">Muslim</option>
                                    <option value="Other">Other</option>
                                </select>
                                @error('religion')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Place of Origin</label>
                                <input type="text" class="form-control " wire:model="place_of_origin"
                                    placeholder="Enter place of origin">
                                @error('place_of_origin')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tribe</label>
                                <input type="text" class="form-control " wire:model="tribe"
                                    placeholder="Enter tribe">
                                @error('tribe')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label">Home Address</label>
                                <textarea class="form-control " wire:model="home_address" rows="2" placeholder="Enter home address"></textarea>
                                @error('home_address')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Town</label>
                                <input type="text" class="form-control " wire:model="town"
                                    placeholder="Enter town">
                                @error('town')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Landmark</label>
                                <input type="text" class="form-control " wire:model="landmark"
                                    placeholder="Enter landmark">
                                @error('landmark')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">P.O. Box No.</label>
                                <input type="text" class="form-control " wire:model="po_box_no"
                                    placeholder="Enter P.O. Box number">
                                @error('po_box_no')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label">X-Ray No.</label>
                                <input type="text" class="form-control " wire:model="xray_no"
                                    placeholder="Enter X-Ray number">
                                @error('xray_no')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- Next of Kin Information -->
                        <h6 class="text-secondary mb-3">
                            <i class="bx bx-user-voice me-1"></i>Next of Kin Information
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Name of Next of Kin</label>
                                <input type="text" class="form-control " wire:model="nok_name"
                                    placeholder="Enter next of kin name">
                                @error('nok_name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Relationship</label>
                                <input type="text" class="form-control " wire:model="nok_relationship"
                                    placeholder="Enter relationship">
                                @error('nok_relationship')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Next of Kin Phone</label>
                                <input type="text" class="form-control " wire:model="nok_phone"
                                    placeholder="Enter next of kin phone">
                                @error('nok_phone')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address of Next of Kin</label>
                                <textarea class="form-control " wire:model="nok_address" rows="2" placeholder="Enter next of kin address"></textarea>
                                @error('nok_address')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- Officer Tracking Section -->
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
                                    Register Patient
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
                    new bootstrap.Modal(document.getElementById('registrationModal')).show();
                });

                Livewire.on('close-modals', () => {
                    ['dinVerificationModal', 'registrationModal'].forEach(id => {
                        const inst = bootstrap.Modal.getInstance(document.getElementById(id));
                        if (inst) inst.hide();
                    });
                });
            });
        </script>
    @endpush

</div>

{{-- End of Alpine DataTable wrapper --}}
