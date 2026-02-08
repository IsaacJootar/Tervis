@php
    use Carbon\Carbon;
@endphp

@section('title', 'DIN Activation - Patient Attendance')

{{-- Wrap EVERYTHING in Alpine DataTable component with integrated DIN logic --}}
<div x-data="{
    din: '',
    handleInput(e, index) {
        const inputs = document.querySelectorAll('#dinVerificationModal .numeral-mask');
        const val = e.target.value;
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
        this.din = combined;
        @this.set('din', combined);
    },
    submitOnEnter(e) {
        if (e.key === 'Enter' && this.din.length === 8) {
            e.preventDefault();
            @this.verifyPatient();
        }
    }
}" x-on:keydown.enter.window="submitOnEnter($event)">
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
                            <i class='bx bx-check-shield me-2'></i>
                            DIN Activation - Patient Attendance
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
                                <i class="bx bx-user-check"></i>
                                {{ $todayCount ?? 0 }} Check-Ins Today
                            </span>
                        </div>
                    </div>

                    {{-- CTA Button --}}
                    <div class="demo-inline-spacing mt-3">
                        <button type="button"
                            class="btn btn-lg btn-dark px-5 py-3 d-inline-flex align-items-center shadow"
                            style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#dinVerificationModal"
                            wire:click="openDinModal">
                            <span class="icon-base ti tabler-scan icon-24px me-2 text-white"></span>
                            <span class="fw-bold">Activate DIN / Check-In Patient</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- DataTable - Today's Activations --}}
    <div class="card">
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="dataTable" class="table">
                <thead class="table-dark">
                    <tr>
                        <th>DIN</th>
                        <th>Patient Name</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Phone</th>
                        <th>Check-In Time</th>
                        <th>Checked By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activations ?? [] as $activation)
                        <tr wire:key="{{ $activation->id }}">
                            <td>
                                <span class="badge bg-label-info">{{ $activation->patient_din ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $activation->patient_first_name . ' ' . $activation->patient_last_name ?? 'N/A' }}
                            </td>
                            <td>
                                @if ($activation->patient_gender === 'Male')
                                    <span class="badge bg-label-primary">
                                        <i class="bx bx-male-sign me-1"></i>Male
                                    </span>
                                @else
                                    <span class="badge bg-label-danger">
                                        <i class="bx bx-female-sign me-1"></i>Female
                                    </span>
                                @endif
                            </td>
                            <td>{{ $activation->patient_age ?? 'N/A' }} yrs</td>
                            <td>{{ $activation->patient_phone ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-label-success">
                                    <i class="bx bx-time-five me-1"></i>
                                    {{ $activation->check_in_time ? Carbon::parse($activation->check_in_time)->format('h:i A') : 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $activation->officer_name ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item"">
                                            <i class="icon-base ti tabler-dashboard me-1"></i> Open Dashboard
                                        </a>
                                        <a class="dropdown-item text-danger" href="javascript:void(0)"
                                            wire:click="delete({{ $activation->id }})">
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

    {{-- ============================================ --}}
    {{-- DIN VERIFICATION MODAL --}}
    {{-- ============================================ --}}
    <div wire:ignore.self class="modal fade" id="dinVerificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 500px;">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="exit"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem 2.5rem 2.5rem;">
                    <div class="text-center mb-4">
                        <h3 class="mb-3">
                            <i class="menu-icon icon-base ti tabler-scan me-1 text-primary"
                                style="font-size: 1.2rem;"></i>
                            DIN Activation
                        </h3>
                        <p class="text-muted mb-2">Enter the Patient's 8-digit DIN to activate
                        </p>
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
                                    <i class="bx bx-search me-1"></i>Verify DIN
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

                        {{-- ============================================ --}}
                        {{-- SCENARIO 1: Patient NOT FOUND --}}
                        {{-- ============================================ --}}
                        @if ($isPatientNotFound)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-danger mb-2">
                                    <i class="bx bx-error-circle me-1"></i>DIN Not Found
                                </span>
                                <div class="alert alert-danger">
                                    <p class="mb-0"><strong>This DIN does not exist in the system.</strong></p>
                                    <p class="mb-0">Please verify the DIN and try again, or send patient for
                                        registration.</p>
                                </div>
                                <button wire:click="resetForNextPatient" type="button"
                                    class="btn btn-primary w-100 mt-3">
                                    <i class="bx bx-refresh me-1"></i>Try Another DIN
                                </button>
                                <button type="button" class="btn btn-secondary w-100 mt-2" data-bs-dismiss="modal"
                                    wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        @endif

                        {{-- ============================================ --}}
                        {{-- SCENARIO 2: ALREADY ACTIVATED TODAY --}}
                        {{-- ============================================ --}}
                        @if ($isAlreadyActivatedToday)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-success mb-2">
                                    <i class="bx bx-check-circle me-1"></i>✅ Checked In Successfully
                                </span>
                                <div class="card p-3 bg-light">
                                    <p class="mb-1"><strong>Name:</strong> {{ $first_name }} {{ $middle_name }}
                                        {{ $last_name }}</p>
                                    <p class="mb-1"><strong>Check-In Time:</strong> {{ $existing_activation_time }}
                                    </p>
                                    <p class="mb-0"><strong>Registration Facility:</strong>
                                        {{ $patient_registration_facility }}</p>
                                </div>
                                <div class="alert alert-success mt-3 mb-0">
                                    <strong>Patient has been checked in.</strong><br>
                                    You can activate another patient or close this modal.
                                </div>
                                <button wire:click="resetForNextPatient" type="button"
                                    class="btn btn-primary w-100 mt-3">
                                    <i class="bx bx-user-plus me-1"></i>Activate Another Patient
                                </button>
                                <button type="button" class="btn btn-secondary w-100 mt-2" data-bs-dismiss="modal"
                                    wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        @endif

                        {{-- ============================================ --}}
                        {{-- SCENARIO 3: VERIFIED - READY TO ACTIVATE --}}
                        {{-- ============================================ --}}
                        @if ($isPatientVerified)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-success mb-2">
                                    <i class="bx bx-check-circle me-1"></i>Patient Verified - Ready to Check-In
                                </span>
                                <div class="card p-3 bg-light">
                                    <p class="mb-1"><strong>DIN:</strong> <span
                                            class="badge bg-primary">{{ $patient_din }}</span></p>
                                    <p class="mb-1"><strong>Name:</strong> {{ $first_name }} {{ $middle_name }}
                                        {{ $last_name }}</p>
                                    <p class="mb-1"><strong>Gender:</strong> {{ $patient_gender }}</p>
                                    <p class="mb-1"><strong>Age:</strong> {{ $patient_age }} years</p>
                                    <p class="mb-1"><strong>Phone:</strong> {{ $patient_phone ?? 'N/A' }}</p>
                                    <p class="mb-0"><strong>Registration Facility:</strong>
                                        {{ $patient_registration_facility }}</p>
                                </div>

                                {{-- Activate DIN Button --}}
                                <button wire:click="activateDin" type="button" class="btn btn-success w-100 mt-3">
                                    <span wire:loading.remove wire:target="activateDin">
                                        <i class="bx bx-check-shield me-1"></i>Activate DIN & Check-In
                                    </span>
                                    <span wire:loading wire:target="activateDin">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                        Activating...
                                    </span>
                                </button>

                                <button type="button" class="btn btn-secondary w-100 mt-2" data-bs-dismiss="modal"
                                    wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- End DIN Verification Modal --}}

    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('close-modals', () => {
                    const modalId = 'dinVerificationModal';
                    const inst = bootstrap.Modal.getInstance(document.getElementById(modalId));
                    if (inst) inst.hide();
                });

                // Clear DIN inputs when resetting for next patient
                Livewire.on('clear-din-inputs', () => {
                    document.querySelectorAll('#dinVerificationModal .numeral-mask').forEach(input => {
                        input.value = '';
                    });
                    // Focus on first input
                    const firstInput = document.querySelector('#dinVerificationModal .numeral-mask');
                    if (firstInput) firstInput.focus();
                });
            });
        </script>
    @endpush

</div>
{{-- End of Alpine DataTable wrapper --}}
