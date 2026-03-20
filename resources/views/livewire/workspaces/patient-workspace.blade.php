@php
    use Carbon\Carbon;
@endphp

@section('title', 'Patient Workspace')

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
    <div class="mb-3">
        <span class="badge bg-label-primary text-uppercase">Patient Workspace Access</span>
    </div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                style="width:64px;height:64px;font-weight:700;">
                {{ strtoupper(substr($facility_name ?? 'F', 0, 1)) }}{{ strtoupper(substr($officer_name ?? 'C', 0, 1)) }}
            </div>
            <div class="flex-grow-1">
                <h4 class="mb-1">Access Patient Workspace</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge bg-label-primary">Facility: {{ $facility_name ?? 'N/A' }}</span>
                    <span class="badge bg-label-info">State: {{ $facility_state ?? 'N/A' }}</span>
                    <span class="badge bg-label-secondary">LGA: {{ $facility_lga ?? 'N/A' }}</span>
                    <span class="badge bg-label-dark">Ward: {{ $facility_ward ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="ms-lg-auto">
                <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal"
                    data-bs-target="#dinVerificationModal" wire:click="openDinModal" wire:target="openDinModal"
                    wire:loading.attr="disabled">
                    <i class="bx bx-search me-1"></i>Verify DIN
                </button>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h6 class="mb-1"><i class="bx bx-list-check me-1"></i>Today's Pending Queues</h6>
                    <small class="text-muted">Open queue view to resolve pending lab tests, prescriptions, and reminders.</small>
                </div>
                <a href="{{ url('/workspaces/pending-queues') }}" class="btn btn-outline-dark btn-sm">
                    <i class="bx bx-right-arrow-alt me-1"></i>Open Pending Queues
                </a>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <span class="badge bg-label-info">Pending Lab Orders: {{ number_format((int) $pending_lab_orders_count) }}</span>
                <span class="badge bg-label-success">Pending Prescriptions: {{ number_format((int) $pending_prescriptions_count) }}</span>
                <span class="badge bg-label-warning">Due Reminders: {{ number_format((int) $due_reminders_count) }}</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-primary h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-label">Facility</div>
                        <div class="metric-value metric-value-sm">{{ $facility_name ?? 'N/A' }}</div>
                    </div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 19V6.5A1.5 1.5 0 016.5 5h11A1.5 1.5 0 0119 6.5V19M9 9h2m2 0h2m-6 4h2m2 0h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-info h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-label">State</div>
                        <div class="metric-value metric-value-sm">{{ $facility_state ?? 'N/A' }}</div>
                    </div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 3l7 4v5c0 4.5-3 7-7 9-4-2-7-4.5-7-9V7l7-4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-success h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-label">LGA</div>
                        <div class="metric-value metric-value-sm">{{ $facility_lga ?? 'N/A' }}</div>
                    </div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M7.5 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm9 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 20a4.5 4.5 0 0 1 9 0M12 20a4.5 4.5 0 0 1 9 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-warning h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-label">Ward</div>
                        <div class="metric-value metric-value-sm">{{ $facility_ward ?? 'N/A' }}</div>
                    </div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 21s6-4.2 6-10a6 6 0 1 0-12 0c0 5.8 6 10 6 10Z" stroke="currentColor" stroke-width="1.8"/>
                            <circle cx="12" cy="11" r="2" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Workflow Steps</h5>
            <small class="text-muted">Follow this sequence to open a patient workspace</small>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="workflow-card workflow-card-slate h-100">
                        <h6 class="mb-2">Step 1: DIN Activation</h6>
                        <p class="text-muted mb-0">Confirm patient checked in today from DIN activation.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="workflow-card workflow-card-sky h-100">
                        <h6 class="mb-2">Step 2: Verify DIN</h6>
                        <p class="text-muted mb-0">Enter the 8-digit DIN to validate today activation status.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="workflow-card workflow-card-emerald h-100">
                        <h6 class="mb-2">Step 3: Open Workspace</h6>
                        <p class="text-muted mb-0">Open patient dashboard cards and continue section workflows.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="dinVerificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" style="max-width: 560px;">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">DIN Verification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="exit"></button>
                </div>

                <div class="modal-body px-4 pb-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Enter 8-Digit DIN <span class="text-danger">*</span></label>
                        <div
                            class="auth-input-wrapper d-flex align-items-center justify-content-between numeral-mask-wrapper mb-2">
                            @for ($i = 0; $i < 8; $i++)
                                <input type="tel" inputmode="numeric"
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

                    <div class="d-grid gap-2 mb-3">
                        <button wire:click="verifyPatient" type="button" class="btn btn-primary"
                            :disabled="din.length !== 8" id="verify-btn" wire:target="verifyPatient"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="verifyPatient">
                                <i class="bx bx-search me-1"></i>Verify Activation
                            </span>
                            <span wire:loading wire:target="verifyPatient">
                                <span class="spinner-border spinner-border-sm me-1" role="status"
                                    aria-hidden="true"></span>Verifying...
                            </span>
                        </button>

                        <button wire:click="exit" type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x me-1"></i>Cancel
                        </button>
                    </div>

                    @if ($isPatientNotFound)
                        <div class="verification-card verification-danger">
                            <span class="badge bg-label-danger mb-2"><i class="bx bx-error-circle me-1"></i>Patient Not Registered</span>
                            <p class="mb-1"><strong>This DIN does not exist in the system.</strong></p>
                            <p class="mb-0">Patient needs registration before accessing services.</p>
                            <div class="d-grid gap-2 mt-3">
                                <button wire:click="resetForNextPatient" type="button" class="btn btn-primary">
                                    <i class="bx bx-refresh me-1"></i>Try Another DIN
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                                    wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        </div>
                    @endif

                    @if ($isNotActivatedToday)
                        <div class="verification-card verification-warning">
                            <span class="badge bg-label-warning mb-2"><i class="bx bx-time-five me-1"></i>Not Checked In Today</span>
                            <div class="small">
                                <p class="mb-1"><strong>DIN:</strong> {{ $patient_din }}</p>
                                <p class="mb-1"><strong>Name:</strong> {{ $first_name }} {{ $middle_name }} {{ $last_name }}</p>
                                <p class="mb-0"><strong>Registered At:</strong> {{ $patient_registration_facility }}</p>
                            </div>
                            <div class="alert alert-warning mt-3 mb-0">
                                Patient must complete DIN activation before workspace access.
                            </div>
                            <div class="d-grid gap-2 mt-3">
                                <button wire:click="resetForNextPatient" type="button" class="btn btn-outline-primary">
                                    <i class="bx bx-refresh me-1"></i>Try Another Patient
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                                    wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        </div>
                    @endif

                    @if ($isPatientVerified)
                        <div class="verification-card verification-success">
                            <span class="badge bg-label-success mb-2"><i class="bx bx-check-circle me-1"></i>Patient Verified</span>
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <div class="verified-item">
                                        <span class="verified-label">DIN</span>
                                        <span class="verified-value">{{ $patient_din }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="verified-item">
                                        <span class="verified-label">Name</span>
                                        <span class="verified-value">{{ $first_name }} {{ $middle_name }} {{ $last_name }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="verified-item">
                                        <span class="verified-label">Gender</span>
                                        <span class="verified-value">{{ $patient_gender }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="verified-item">
                                        <span class="verified-label">Age</span>
                                        <span class="verified-value">{{ $patient_age }} years</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="verified-item">
                                        <span class="verified-label">Check-In</span>
                                        <span class="verified-value">{{ $activation_time }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="verified-item">
                                        <span class="verified-label">Phone</span>
                                        <span class="verified-value">{{ $patient_phone ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="verified-item">
                                        <span class="verified-label">Registered At</span>
                                        <span class="verified-value">{{ $patient_registration_facility }}</span>
                                    </div>
                                </div>
                            </div>

                            @if (count($entry_points) > 0)
                                <div class="mt-3">
                                    <h6 class="mb-2">Registered Programs</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($entry_points as $entry)
                                            <span class="badge bg-label-info">{{ $entry }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info mt-3 mb-0 py-2">
                                    Patient has no program registrations yet.
                                </div>
                            @endif

                            <div class="d-grid gap-2 mt-3">
                                <button wire:click="openWorkspace" type="button" class="btn btn-success btn-lg"
                                    wire:target="openWorkspace" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="openWorkspace">
                                        <i class="bx bx-grid-alt me-1"></i>Open Patient Workspace
                                    </span>
                                    <span wire:loading wire:target="openWorkspace">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"
                                            aria-hidden="true"></span>Opening...
                                    </span>
                                </button>
                                <button wire:click="resetForNextPatient" type="button" class="btn btn-outline-primary">
                                    <i class="bx bx-user-plus me-1"></i>Access Another Patient
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                                    wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @once
        <style>
            .metric-card {
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 12px;
                min-height: 112px;
            }

            .metric-card-primary {
                background: #eff6ff;
                border-color: #bfdbfe;
                color: #1e40af;
            }

            .metric-card-info {
                background: #f0f9ff;
                border-color: #bae6fd;
                color: #075985;
            }

            .metric-card-success {
                background: #ecfdf3;
                border-color: #bbf7d0;
                color: #166534;
            }

            .metric-card-warning {
                background: #fff7ed;
                border-color: #fed7aa;
                color: #9a3412;
            }

            .metric-label {
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                font-weight: 700;
                color: rgba(15, 23, 42, 0.7);
            }

            .metric-value {
                font-size: 1.3rem;
                font-weight: 700;
                margin-top: 4px;
                line-height: 1.3rem;
            }

            .metric-value-sm {
                font-size: 1rem;
                line-height: 1.2rem;
            }

            .metric-icon {
                width: 32px;
                height: 32px;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: rgba(15, 23, 42, 0.08);
                flex-shrink: 0;
            }

            .metric-icon svg {
                width: 18px;
                height: 18px;
            }

            .workflow-card {
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 14px;
                min-height: 168px;
            }

            .workflow-card-slate {
                background: #f8fafc;
                border-color: #cbd5e1;
            }

            .workflow-card-sky {
                background: #f0f9ff;
                border-color: #bae6fd;
            }

            .workflow-card-emerald {
                background: #ecfdf5;
                border-color: #a7f3d0;
            }

            .verification-card {
                border-radius: 12px;
                border: 1px solid #e5e7eb;
                padding: 14px;
                background: #fff;
            }

            .verification-danger {
                border-color: #fecaca;
                background: #fef2f2;
            }

            .verification-warning {
                border-color: #fcd34d;
                background: #fffbeb;
            }

            .verification-success {
                border-color: #86efac;
                background: #f0fdf4;
            }

            .verified-item {
                border: 1px solid #d1fae5;
                background: #ffffff;
                border-radius: 10px;
                padding: 8px 10px;
                height: 100%;
            }

            .verified-label {
                display: block;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #065f46;
                margin-bottom: 2px;
            }

            .verified-value {
                display: block;
                font-size: 0.92rem;
                font-weight: 600;
                color: #0f172a;
                line-height: 1.3;
                word-break: break-word;
            }
        </style>
    @endonce

    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('close-modals', () => {
                    const modalId = 'dinVerificationModal';
                    const inst = bootstrap.Modal.getInstance(document.getElementById(modalId));
                    if (inst) inst.hide();
                });

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
