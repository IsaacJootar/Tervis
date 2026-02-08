@php
    use Carbon\Carbon;
@endphp

@section('title', 'Family Planning Register')

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
                            <i class='bx bx-shield-plus me-2'></i>
                            Family Planning Register
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

                        </div>
                    </div>

                    {{-- CTA Button --}}
                    <div class="demo-inline-spacing">
                        <button type="button"
                            class="btn btn-lg btn-dark px-5 py-3 d-inline-flex align-items-center shadow"
                            style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#dinVerificationModal"
                            wire:click="openDinModal">
                            <span class="icon-base ti tabler-plus icon-24px me-2 text-white"></span>
                            <span class="fw-bold">New FP Registration</span>
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
                        <th>Registration Date</th>
                        <th>Contraceptive Method</th>
                        <th>Facility</th>
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
                            <td>{{ $registration->registration_date ? Carbon::parse($registration->registration_date)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td>
                                <span
                                    class="badge bg-label-success">{{ $registration->contraceptive_selected ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $registration->facility->name ?? 'N/A' }}</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal"
                                            data-bs-target="#fpRegistrationModal"
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
                            <button wire:click="openFPModal" type="button" class="btn btn-sm btn-outline-primary"
                                data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#fpRegistrationModal">
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

                        {{-- SCENARIO 1: NEW PATIENT (DIN not found) --}}
                        @if ($isNewPatient)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-warning mb-2">⚠️ DIN Not Found - New Patient</span>
                                <div class="alert alert-info">
                                    <p class="mb-0"><strong>This patient does not exist in the system.</strong></p>
                                    <p class="mb-0">Click below to proceed with new patient registration.</p>
                                </div>
                                <button wire:click="openFPModal" type="button" class="btn btn-primary w-100 mt-3"
                                    data-bs-dismiss="modal" data-bs-toggle="modal"
                                    data-bs-target="#fpRegistrationModal">
                                    <i class="bx bx-user-plus me-1"></i>Proceed to New Patient Registration
                                </button>
                            </div>
                        @endif

                        {{-- SCENARIO 2: VERIFIED PATIENT (DIN found, can register FP) --}}
                        @if ($isPatientVerified)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-success mb-2">✅ Patient Successfully Verified</span>
                                <div class="card p-3 bg-light">
                                    <p class="mb-1"><strong>Name:</strong> {{ $first_name }} {{ $last_name }}
                                    </p>
                                    <p class="mb-0"><strong>Registration Facility:</strong>
                                        {{ $patient_registration_facility }}</p>
                                </div>
                                <button wire:click="openFPModal" type="button" class="btn btn-success w-100 mt-3"
                                    data-bs-dismiss="modal" data-bs-toggle="modal"
                                    data-bs-target="#fpRegistrationModal">
                                    <i class="bx bx-arrow-right me-1"></i>Proceed to FP Registration
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- End DIN Verification Modal --}}

    {{-- FP Registration Modal --}}
    <div wire:ignore.self class="modal fade" id="fpRegistrationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" x-data="{
            state_id: @entangle('state_id').live,
            lga_id: @entangle('lga_id').live,
            ward_id: @entangle('ward_id').live,
            dob: @entangle('patient_dob'),
            age: @entangle('patient_age'),
            lmp: @entangle('last_menstrual_period'),
            lmpWarning: '',
            contraceptive: @entangle('contraceptive_selected'),
            brand: @entangle('brand_size_model'),
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
            },
            checkLMP() {
                if (this.lmp) {
                    const lmpDate = new Date(this.lmp);
                    const today = new Date();
                    const daysDiff = Math.floor((today - lmpDate) / (1000 * 60 * 60 * 24));
                    if (daysDiff > 45) {
                        this.lmpWarning = '⚠️ More than 45 days ago - May be pregnant!';
                    } else {
                        this.lmpWarning = daysDiff + ' days ago';
                    }
                }
            },
            suggestBrand() {
                const suggestions = {
                    'Combined Oral Contraceptive (COC)': 'Microgynon, Lo-Femenal, Nordette',
                    'Progestin-Only Pills (POP)': 'Microlut, Ovrette, Noriday',
                    'Injectable - Depo-Provera': 'Depo-Provera 150mg/ml',
                    'Injectable - Noristerat': 'Noristerat 200mg/ml',
                    'Injectable - Sayana Press': 'Sayana Press 104mg/0.65ml',
                    'Implant - Jadelle': 'Jadelle (2 rods)',
                    'Implant - Implanon': 'Implanon (1 rod)',
                    'IUD - Copper T380A': 'TCu380A',
                    'IUD - Multiload': 'Multiload Cu375',
                };
                if (suggestions[this.contraceptive]) {
                    this.brand = suggestions[this.contraceptive];
                }
            }
        }">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-shield-plus me-2"></i>
                        {{ $registration_id ? 'Edit FP Registration' : 'Family Planning Registration' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="exit"></button>
                </div>
                <div class="modal-body" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                    <form wire:submit.prevent="{{ $registration_id ? 'update' : 'store' }}">
                        @csrf

                        {{-- Patient Demographics (NEW PATIENT ONLY) --}}
                        @if (!$patient_id)
                            <div class="mb-4">
                                <h5 class="badge text-bg-info text-white px-3 py-2 rounded">
                                    <i class="bx bx-user me-1"></i>Patient Demographics
                                </h5>
                            </div>

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
                                <div class="col-md-4">
                                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" x-model="dob"
                                        @change="calculateAge()" :max="new Date().toISOString().split('T')[0]">
                                    @error('patient_dob')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Age (Calculated)</label>
                                    <input type="text" class="form-control" readonly x-model="age"
                                        placeholder="Auto-calculated">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" wire:model="patient_phone"
                                        placeholder="Enter phone number">
                                    @error('patient_phone')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" wire:model="patient_email"
                                        placeholder="Enter email address">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">State <span class="text-danger">*</span></label>
                                    <select class="form-select" wire:model.live="state_id" x-model="state_id">
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
                                    <select class="form-select" wire:model.live="lga_id" x-model="lga_id"
                                        :disabled="!state_id">
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
                                    <select class="form-select" wire:model.live="ward_id" x-model="ward_id"
                                        :disabled="!lga_id">
                                        <option value="">
                                            <span x-show="!lga_id">--Select LGA First--</span>
                                            <span x-show="lga_id">--Select Ward--</span>
                                        </option>
                                        @foreach ($wards as $ward)
                                            <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Marital Status</label>
                                    <select class="form-select" wire:model="marital_status">
                                        <option value="">--Select--</option>
                                        <option value="Single">Single (S)</option>
                                        <option value="Married">Married (M)</option>
                                        <option value="Widowed">Widowed (W)</option>
                                        <option value="Divorced">Divorced (D)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Education</label>
                                    <select class="form-select" wire:model="education">
                                        <option value="">--Select--</option>
                                        <option value="None">None</option>
                                        <option value="Primary">Primary</option>
                                        <option value="Secondary">Secondary</option>
                                        <option value="Tertiary">Tertiary</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Religion</label>
                                    <select class="form-select" wire:model="religion">
                                        <option value="">--Select Religion--</option>
                                        <option value="Christianity">Christianity</option>
                                        <option value="Islam">Islam</option>
                                        <option value="Traditional">Traditional</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-12">
                                    <label class="form-label">Home Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" wire:model="address" rows="2" placeholder="Enter home address"></textarea>
                                    @error('address')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        @else
                            {{-- Existing Patient Info - PRE-FILLED --}}
                            <div class="mb-4">
                                <h5 class="badge text-bg-info bg-info text-white px-3 py-2 rounded">
                                    <i class="bx bx-user-check me-1"></i>Patient Information (Pre-filled)
                                </h5>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" wire:model="first_name"
                                        placeholder="First name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" wire:model="middle_name"
                                        placeholder="Middle name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" wire:model="last_name"
                                        placeholder="Last name">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gender</label>
                                    <input type="text" class="form-control bg-light" wire:model="patient_gender"
                                        placeholder="Gender" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" wire:model="patient_dob">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Age</label>
                                    <input type="text" class="form-control bg-light"
                                        value="{{ $patient_age }} years" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" wire:model="patient_phone"
                                        placeholder="Phone number">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" wire:model="patient_email"
                                        placeholder="Email address">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Registration Facility</label>
                                    <input type="text" class="form-control bg-light"
                                        value="{{ $patient_registration_facility }}" readonly>
                                </div>
                            </div>
                        @endif

                        {{-- FP Registration Info --}}
                        <div class="mb-4 mt-5">
                            <h5 class="badge text-bg-info bg-success text-white px-3 py-2 rounded">
                                <i class="bx bx-file-blank me-1"></i>FP Registration Information
                            </h5>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Registration Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" wire:model="registration_date">
                                @error('registration_date')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Client Registration Number</label>
                                <input type="text" class="form-control" wire:model="client_reg_number"
                                    placeholder="FP-2025-001">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Referral Source</label>
                                <select class="form-select" wire:model="referral_source">
                                    <option value="">--Select--</option>
                                    <option value="Self">Self</option>
                                    <option value="PHC">PHC</option>
                                    <option value="Hospital">Hospital</option>
                                    <option value="NGO">NGO</option>
                                    <option value="Private">Private</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        {{-- Obstetric History --}}
                        <div class="mb-4 mt-5">
                            <h5 class="badge text-bg-info bg-warning text-white px-3 py-2 rounded">
                                <i class="bx bx-baby-carriage me-1"></i>Obstetric History
                            </h5>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Children Born Alive</label>
                                <input type="number" class="form-control" wire:model="children_born_alive"
                                    min="0" placeholder="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Children Still Living</label>
                                <input type="number" class="form-control" wire:model="children_still_living"
                                    min="0" placeholder="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Miscarriages/Stillbirths/Abortions</label>
                                <input type="number" class="form-control"
                                    wire:model="miscarriages_stillbirths_abortions" min="0" placeholder="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Breastfeeding?</label>
                                <select class="form-select" wire:model="breastfeeding">
                                    <option value="">--Select--</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Last Pregnancy Ended</label>
                                <input type="date" class="form-control" wire:model="last_pregnancy_ended">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Pregnancy Result</label>
                                <select class="form-select" wire:model="last_pregnancy_result">
                                    <option value="">--Select--</option>
                                    <option value="Live Birth">Live Birth</option>
                                    <option value="Stillbirth">Stillbirth</option>
                                    <option value="Miscarriage">Miscarriage</option>
                                    <option value="Abortion">Abortion</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Want More Children?</label>
                                <select class="form-select" wire:model="want_more_children">
                                    <option value="">--Select--</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                    <option value="Undecided">Undecided</option>
                                </select>
                            </div>
                        </div>

                        {{-- Menstrual History --}}
                        <div class="mb-4 mt-5">
                            <h5 class="badge text-bg-info bg-danger text-white px-3 py-2 rounded">
                                <i class="bx bx-calendar me-1"></i>Menstrual History
                            </h5>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Last Menstrual Period (LMP) <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" x-model="lmp" @change="checkLMP()"
                                    :max="new Date().toISOString().split('T')[0]">
                                <small x-show="lmpWarning"
                                    :class="lmpWarning.includes('⚠️') ? 'text-danger fw-bold' : 'text-muted'"
                                    x-text="lmpWarning"></small>
                                @error('last_menstrual_period')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Menstrual Cycle</label>
                                <select class="form-select" wire:model="menstrual_cycle">
                                    <option value="">--Select--</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Irregular">Irregular</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cycle Duration (Days)</label>
                                <input type="number" class="form-control" wire:model="cycle_duration"
                                    min="21" max="35" placeholder="28">
                            </div>
                        </div>

                        {{-- Medical History --}}
                        <div class="mb-4 mt-5">
                            <h5 class="badge text-bg-info bg-info text-white px-3 py-2 rounded">
                                <i class="bx bx-plus-medical me-1"></i>Medical History
                            </h5>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Medical Conditions (Select all that apply)</label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="medical_conditions" value="Diabetes" id="diabetes">
                                            <label class="form-check-label" for="diabetes">Diabetes</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="medical_conditions" value="Hypertension"
                                                id="hypertension">
                                            <label class="form-check-label" for="hypertension">Hypertension</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="medical_conditions" value="Heart Disease" id="heart">
                                            <label class="form-check-label" for="heart">Heart Disease</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="medical_conditions" value="STI" id="sti">
                                            <label class="form-check-label" for="sti">STI</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="medical_conditions" value="Migraine" id="migraine">
                                            <label class="form-check-label" for="migraine">Migraine</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="medical_conditions" value="Blood Clots" id="clots">
                                            <label class="form-check-label" for="clots">Blood Clots</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="medical_conditions" value="Liver Disease" id="liver">
                                            <label class="form-check-label" for="liver">Liver Disease</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="medical_conditions" value="Breast Cancer" id="cancer">
                                            <label class="form-check-label" for="cancer">Breast Cancer</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="medical_conditions" value="Other" id="other">
                                            <label class="form-check-label" for="other">Other</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4" x-show="$wire.medical_conditions.includes('Other')">
                            <div class="col-md-12">
                                <label class="form-label">Please Specify Other Illness</label>
                                <input type="text" class="form-control" wire:model="other_illness_specify"
                                    placeholder="Specify other illness">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Smoke?</label>
                                <select class="form-select" wire:model="smoke">
                                    <option value="">--Select--</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Pregnancy</label>
                                <select class="form-select" wire:model="last_pregnancy_complication">
                                    <option value="">--Select--</option>
                                    <option value="Normal">Normal</option>
                                    <option value="Complicated">Complicated</option>
                                </select>
                            </div>
                            <div class="col-md-4" x-show="$wire.last_pregnancy_complication === 'Complicated'">
                                <label class="form-label">Specify Complication</label>
                                <input type="text" class="form-control" wire:model="complication_specify"
                                    placeholder="Describe complication">
                            </div>
                        </div>

                        {{-- Contraceptive History --}}
                        <div class="mb-4 mt-5">
                            <h5 class="badge text-bg-info bg-secondary text-white px-3 py-2 rounded">
                                <i class="bx bx-shield me-1"></i>Contraceptive History
                            </h5>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Used Contraceptive Before?</label>
                                <select class="form-select" wire:model="prior_contraceptive">
                                    <option value="">--Select--</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6" x-show="$wire.prior_contraceptive == 1">
                                <label class="form-label">Which Method?</label>
                                <input type="text" class="form-control" wire:model="prior_method"
                                    placeholder="Previous contraceptive method">
                            </div>
                        </div>

                        {{-- Contraceptive Method Selected --}}
                        <div class="mb-4 mt-5">
                            <h5 class="badge text-bg-info bg-primary text-white px-3 py-2 rounded">
                                <i class="bx bx-check-shield me-1"></i>Contraceptive Method Selected
                            </h5>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Contraceptive Method <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" x-model="contraceptive" @change="suggestBrand()">
                                    <option value="">--Select Method--</option>
                                    <option value="Combined Oral Contraceptive (COC)">Combined Oral Contraceptive (COC)
                                    </option>
                                    <option value="Progestin-Only Pills (POP)">Progestin-Only Pills (POP)</option>
                                    <option value="Injectable - Depo-Provera">Injectable - Depo-Provera</option>
                                    <option value="Injectable - Noristerat">Injectable - Noristerat</option>
                                    <option value="Injectable - Sayana Press">Injectable - Sayana Press</option>
                                    <option value="Implant - Jadelle">Implant - Jadelle</option>
                                    <option value="Implant - Implanon">Implant - Implanon</option>
                                    <option value="IUD - Copper T380A">IUD - Copper T380A</option>
                                    <option value="IUD - Multiload">IUD - Multiload</option>
                                    <option value="Male Condoms">Male Condoms</option>
                                    <option value="Female Condoms">Female Condoms</option>
                                    <option value="Emergency Contraception">Emergency Contraception</option>
                                    <option value="Tubal Ligation (BTL)">Tubal Ligation (BTL)</option>
                                    <option value="Vasectomy">Vasectomy</option>
                                    <option value="Natural Methods (LAM, SDM)">Natural Methods (LAM, SDM)</option>
                                </select>
                                @error('contraceptive_selected')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Brand/Size/Model</label>
                                <input type="text" class="form-control" x-model="brand"
                                    placeholder="Auto-filled based on method">
                                <small class="text-muted">Auto-suggested based on selected method</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Source</label>
                                <select class="form-select" wire:model="source">
                                    <option value="">--Select--</option>
                                    <option value="Free (Government)">Free (Government)</option>
                                    <option value="Subsidized">Subsidized</option>
                                    <option value="Full Price">Full Price</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Quality</label>
                                <select class="form-select" wire:model="quality">
                                    <option value="">--Select--</option>
                                    <option value="Accepted">Accepted</option>
                                    <option value="Continuing">Continuing</option>
                                    <option value="Switching">Switching</option>
                                </select>
                            </div>
                        </div>

                        {{-- Physical Examination --}}
                        <div class="mb-4 mt-5">
                            <h5 class="badge text-bg-info bg-danger text-white px-3 py-2 rounded">
                                <i class="bx bx-heart-circle me-1"></i>Physical Examination
                            </h5>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Weight (kg) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" wire:model="weight" step="0.1"
                                    min="30" max="200" placeholder="65.5">
                                @error('weight')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Blood Pressure <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="blood_pressure"
                                    placeholder="120/80">
                                @error('blood_pressure')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Breasts</label>
                                <select class="form-select" wire:model="breasts">
                                    <option value="">--Select--</option>
                                    <option value="Normal">Normal</option>
                                    <option value="Abnormal">Abnormal</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Uterus Position</label>
                                <select class="form-select" wire:model="uterus_position">
                                    <option value="">--Select--</option>
                                    <option value="Anteverted">Anteverted</option>
                                    <option value="Retroverted">Retroverted</option>
                                    <option value="Midposition">Midposition</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Uterus Size</label>
                                <select class="form-select" wire:model="uterus_size">
                                    <option value="">--Select--</option>
                                    <option value="Normal">Normal</option>
                                    <option value="Enlarged">Enlarged</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cervix Tears?</label>
                                <select class="form-select" wire:model="cervix_tears">
                                    <option value="">--Select--</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cervix Erosion?</label>
                                <select class="form-select" wire:model="cervix_erosion">
                                    <option value="">--Select--</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Vaginal Discharge?</label>
                                <select class="form-select" wire:model="vaginal_discharge">
                                    <option value="">--Select--</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4" x-show="$wire.vaginal_discharge == 1">
                            <div class="col-md-6">
                                <label class="form-label">Discharge Colour</label>
                                <input type="text" class="form-control" wire:model="discharge_colour"
                                    placeholder="e.g., White, Yellow, Green">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Discharge Odor</label>
                                <input type="text" class="form-control" wire:model="discharge_odor"
                                    placeholder="e.g., None, Foul, Fishy">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Cervix Discharge?</label>
                                <select class="form-select" wire:model="cervix_discharge">
                                    <option value="">--Select--</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Liver Enlarged?</label>
                                <select class="form-select" wire:model="liver_enlarged">
                                    <option value="">--Select--</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Laboratory Results</label>
                                <textarea class="form-control" wire:model="laboratory_results" rows="3" placeholder="Enter lab results"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Other Observations</label>
                                <textarea class="form-control" wire:model="other_observations" rows="3" placeholder="Additional observations"></textarea>
                            </div>
                        </div>




                        {{-- Pregnancy Tracking --}}
                        <div class="mb-4 mt-5">
                            <h5 class="badge text-bg-info bg-warning text-white px-3 py-2 rounded">
                                <i class="bx bx-shield-plus me-1"></i>Pregnancies After Initial Clinic Visit
                            </h5>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <h6 class="text-primary">Pregnancy 1</h6>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date Pregnancy Ended</label>
                                <input type="date" class="form-control" wire:model="pregnancy1_date_ended">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pregnancy Outcome</label>
                                <select class="form-select" wire:model="pregnancy1_outcome">
                                    <option value="">--Select--</option>
                                    <option value="Live Birth">Live Birth</option>
                                    <option value="Miscarriage">Miscarriage</option>
                                    <option value="Stillbirth">Stillbirth</option>
                                    <option value="Live Birth died later">Live Birth died later</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Complication</label>
                                <input type="text" class="form-control" wire:model="pregnancy1_complication"
                                    placeholder="Describe complication">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <h6 class="text-primary">Pregnancy 2</h6>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date Pregnancy Ended</label>
                                <input type="date" class="form-control" wire:model="pregnancy2_date_ended">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pregnancy Outcome</label>
                                <select class="form-select" wire:model="pregnancy2_outcome">
                                    <option value="">--Select--</option>
                                    <option value="Live Birth">Live Birth</option>
                                    <option value="Miscarriage">Miscarriage</option>
                                    <option value="Stillbirth">Stillbirth</option>
                                    <option value="Live Birth died later">Live Birth died later</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Complication</label>
                                <input type="text" class="form-control" wire:model="pregnancy2_complication"
                                    placeholder="Describe complication">
                            </div>
                        </div>

                        {{-- Officer Tracking Section --}}
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
                    new bootstrap.Modal(document.getElementById('fpRegistrationModal')).show();
                });

                Livewire.on('close-modals', () => {
                    ['dinVerificationModal', 'fpRegistrationModal'].forEach(id => {
                        const inst = bootstrap.Modal.getInstance(document.getElementById(id));
                        if (inst) inst.hide();
                    });
                });
            });
        </script>
    @endpush

</div>

{{-- End of Alpine DataTable wrapper --}}
