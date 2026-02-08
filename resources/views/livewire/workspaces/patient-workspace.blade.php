@php
    use Carbon\Carbon;
@endphp

@section('title', 'Patient Workspace')

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
                            <i class='bx bx-grid-alt me-2'></i>
                            Access Patient Workspace
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
                        </div>
                    </div>

                    {{-- CTA Button --}}
                    <div class="demo-inline-spacing mt-3">
                        <button type="button"
                            class="btn btn-lg btn-dark px-5 py-3 d-inline-flex align-items-center shadow"
                            style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#dinVerificationModal"
                            wire:click="openDinModal">
                            <span class="icon-base ti tabler-user-search icon-24px me-2 text-white"></span>
                            <span class="fw-bold">Access Patient Workspace</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Information Cards --}}
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-check-shield text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title">Step 1: DIN Activation</h5>
                    <p class="card-text text-muted">
                        Patient must first check-in at the DIN Activation desk to record attendance for the day.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-search-alt text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title">Step 2: Verify Activation</h5>
                    <p class="card-text text-muted">
                        Enter patient's DIN to verify they have been activated for today before accessing workspace.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-grid-alt text-info" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title">Step 3: Open Workspace</h5>
                    <p class="card-text text-muted">
                        Access the patient's workspace with 19 activity cards for clinical services.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- DIN VERIFICATION MODAL --}}
    {{-- ============================================ --}}
    <div wire:ignore.self class="modal fade" id="dinVerificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 520px;">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="exit"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem 2.5rem 2.5rem;">
                    <div class="text-center mb-4">
                        <h3 class="mb-3">
                            <i class="menu-icon icon-base ti tabler-user-search me-1 text-primary"
                                style="font-size: 1.2rem;"></i>
                            Access Patient Workspace
                        </h3>
                        <p class="text-muted mb-2">Enter the Patient's 8-digit DIN to access their workspace.</p>
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
                                    <i class="bx bx-search me-1"></i>Verify Activation
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
                        {{-- SCENARIO 1: Patient NOT FOUND IN SYSTEM --}}
                        {{-- ============================================ --}}
                        @if ($isPatientNotFound)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-danger mb-2">
                                    <i class="bx bx-error-circle me-1"></i>Patient Not Registered
                                </span>
                                <div class="alert alert-danger">
                                    <p class="mb-0"><strong>This DIN does not exist in the system.</strong></p>
                                    <p class="mb-0">Patient needs to be registered first before accessing services.
                                    </p>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button wire:click="resetForNextPatient" type="button"
                                        class="btn btn-primary flex-fill">
                                        <i class="bx bx-refresh me-1"></i>Try Another DIN
                                    </button>
                                </div>
                                <button type="button" class="btn btn-secondary w-100 mt-2" data-bs-dismiss="modal"
                                    wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        @endif

                        {{-- ============================================ --}}
                        {{-- SCENARIO 2: NOT ACTIVATED TODAY --}}
                        {{-- ============================================ --}}
                        @if ($isNotActivatedToday)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-warning mb-2">
                                    <i class="bx bx-time-five me-1"></i>Not Checked In Today
                                </span>
                                <div class="card p-3 bg-light">
                                    <p class="mb-1"><strong>DIN:</strong> <span
                                            class="badge bg-primary">{{ $patient_din }}</span></p>
                                    <p class="mb-1"><strong>Name:</strong> {{ $first_name }} {{ $middle_name }}
                                        {{ $last_name }}</p>
                                    <p class="mb-0"><strong>Registered At:</strong>
                                        {{ $patient_registration_facility }}</p>
                                </div>
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <strong>Patient has not been checked in today.</strong><br>
                                    Please direct the patient to the DIN Activation desk first.
                                </div>

                                <button wire:click="resetForNextPatient" type="button"
                                    class="btn btn-outline-primary w-100 mt-2">
                                    <i class="bx bx-refresh me-1"></i>Try Another Patient
                                </button>
                                <button type="button" class="btn btn-secondary w-100 mt-2" data-bs-dismiss="modal"
                                    wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        @endif

                        {{-- ============================================ --}}
                        {{-- SCENARIO 3: VERIFIED & ACTIVATED - READY --}}
                        {{-- ============================================ --}}
                        @if ($isPatientVerified)
                            <div class="text-center mt-3">
                                <span class="badge bg-label-success mb-2">
                                    <i class="bx bx-check-circle me-1"></i>Patient Verified & Checked In
                                </span>
                                <div class="card p-3 bg-light">
                                    <p class="mb-1"><strong>DIN:</strong> <span
                                            class="badge bg-primary">{{ $patient_din }}</span></p>
                                    <p class="mb-1"><strong>Name:</strong> {{ $first_name }} {{ $middle_name }}
                                        {{ $last_name }}</p>
                                    <p class="mb-1"><strong>Gender:</strong> {{ $patient_gender }}</p>
                                    <p class="mb-1"><strong>Age:</strong> {{ $patient_age }} years</p>
                                    <p class="mb-1"><strong>Phone:</strong> {{ $patient_phone ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Check-In Time:</strong>
                                        <span class="badge bg-success">{{ $activation_time }}</span>
                                    </p>
                                    <p class="mb-0"><strong>Registered At:</strong>
                                        {{ $patient_registration_facility }}</p>
                                </div>

                                {{-- Entry Points (Registered Programs) --}}
                                @if (count($entry_points) > 0)
                                    <div class="mt-3">
                                        <h6 class="text-start mb-2"><i class="bx bx-list-check me-1"></i>Registered
                                            Programs:</h6>
                                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                                            @foreach ($entry_points as $entry)
                                                <span class="badge bg-info">{{ $entry }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-info mt-3 mb-0 py-2">
                                        <small><i class="bx bx-info-circle me-1"></i>Patient has no program
                                            registrations yet.</small>
                                    </div>
                                @endif

                                {{-- Open Workspace Button --}}
                                <button wire:click="openWorkspace" type="button"
                                    class="btn btn-success btn-lg w-100 mt-3">
                                    <span wire:loading.remove wire:target="openWorkspace">
                                        <i class="bx bx-grid-alt me-1"></i>Open Patient Workspace
                                    </span>
                                    <span wire:loading wire:target="openWorkspace">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                        Opening...
                                    </span>
                                </button>

                                <button wire:click="resetForNextPatient" type="button"
                                    class="btn btn-outline-primary w-100 mt-2">
                                    <i class="bx bx-user-plus me-1"></i>Access Another Patient
                                </button>

                                <button type="button" class="btn btn-secondary w-100 mt-2" data-bs-dismiss="modal"
                                    wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Close
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
                    const firstInput = document.querySelector('#dinVerificationModal .numeral-mask');
                    if (firstInput) firstInput.focus();
                });
            });
        </script>
    @endpush

</div>
{{-- End of wrapper --}}
