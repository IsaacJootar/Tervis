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
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                <div class="flex-grow-1">
                    <h5 class="mb-1 d-flex align-items-center gap-2">
                        <i class='bx bx-check-shield text-primary'></i>
                        DIN Activation - Patient Attendance
                    </h5>
                    <div class="small text-muted">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-label-dark"><i class="bx bx-building me-1"></i>{{ $facility_name ?? 'N/A' }}</span>
                        <span class="badge bg-label-primary"><i class="bx bx-map-pin me-1"></i>{{ $facility_state ?? 'N/A' }}</span>
                        <span class="badge bg-label-info"><i class="bx bx-map me-1"></i>{{ $facility_lga ?? 'N/A' }}</span>
                        <span class="badge bg-label-warning"><i class="bx bx-current-location me-1"></i>Ward:
                            {{ $facility_ward ?? 'N/A' }}</span>
                        <span class="badge bg-label-success"><i class="bx bx-user-check me-1"></i>{{ $todayCount ?? 0 }}
                            Check-Ins Today</span>
                    </div>
                </div>
                <div class="w-100 w-lg-auto">
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-lg-end header-action-group">
                        <button type="button" class="btn btn-dark w-100 w-sm-auto btn-header-stable"
                            data-bs-toggle="modal" data-bs-target="#dinVerificationModal" wire:click="openDinModal">
                            <span class="icon-base ti tabler-scan me-1"></span>Activate DIN / Check-In Patient
                        </button>
                        <button type="button" class="btn btn-outline-dark w-100 w-sm-auto btn-header-stable"
                            wire:click="exportTodayCsv" wire:loading.attr="disabled" wire:target="exportTodayCsv">
                            <span wire:loading.remove wire:target="exportTodayCsv">
                                <span class="icon-base ti tabler-download me-1"></span>Export Today's Check-Ins
                            </span>
                            <span wire:loading wire:target="exportTodayCsv">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Exporting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- DataTable - Today's Activations --}}
    <div class="card">
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="todayActivationsTable" class="table">
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

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Activation History (Last 30 Days)</h5>
        </div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="activationHistoryTable" class="table">
                <thead class="table-dark">
                    <tr>
                        <th>Visit Date</th>
                        <th>DIN</th>
                        <th>Patient Name</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Phone</th>
                        <th>Check-In Time</th>
                        <th>Checked By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activationHistory ?? [] as $history)
                        <tr wire:key="history-{{ $history->id }}">
                            <td data-order="{{ $history->visit_date ? Carbon::parse($history->visit_date)->format('Y-m-d') : '' }}">
                                {{ $history->visit_date ? Carbon::parse($history->visit_date)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td><span class="badge bg-label-info">{{ $history->patient_din ?? 'N/A' }}</span></td>
                            <td>{{ trim((string) ($history->patient_first_name ?? '') . ' ' . (string) ($history->patient_last_name ?? '')) ?: 'N/A' }}</td>
                            <td>{{ $history->patient_gender ?? 'N/A' }}</td>
                            <td>{{ $history->patient_age ?? 'N/A' }}</td>
                            <td>{{ $history->patient_phone ?? 'N/A' }}</td>
                            <td>
                                {{ $history->check_in_time ? Carbon::parse($history->check_in_time)->format('h:i A') : 'N/A' }}
                            </td>
                            <td>{{ $history->officer_name ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No activation history records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- DIN VERIFICATION MODAL --}}
    <div wire:ignore.self class="modal fade" id="dinVerificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" style="max-width: 560px;">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">DIN Activation</h5>
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
                                <i class="bx bx-search me-1"></i>Verify DIN
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
                            <span class="badge bg-label-danger mb-2"><i class="bx bx-error-circle me-1"></i>DIN Not Found</span>
                            <p class="mb-1"><strong>This DIN does not exist in the system.</strong></p>
                            <p class="mb-0">Verify the DIN and try again, or send patient for registration.</p>
                            <div class="d-grid gap-2 mt-3">
                                <button wire:click="resetForNextPatient" type="button" class="btn btn-primary">
                                    <i class="bx bx-refresh me-1"></i>Try Another DIN
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        </div>
                    @endif

                    @if ($isAlreadyActivatedToday)
                        <div class="verification-card verification-warning">
                            <span class="badge bg-label-warning mb-2"><i class="bx bx-check-circle me-1"></i>Already Checked In Today</span>
                            <div class="row g-2 mt-1">
                                <div class="col-md-8">
                                    <div class="verified-item">
                                        <span class="verified-label">Name</span>
                                        <span class="verified-value">{{ $first_name }} {{ $middle_name }} {{ $last_name }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="verified-item">
                                        <span class="verified-label">Check-In Time</span>
                                        <span class="verified-value">{{ $existing_activation_time }}</span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="verified-item">
                                        <span class="verified-label">Registered At</span>
                                        <span class="verified-value">{{ $patient_registration_facility }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid gap-2 mt-3">
                                <button wire:click="resetForNextPatient" type="button" class="btn btn-primary">
                                    <i class="bx bx-user-plus me-1"></i>Activate Another Patient
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        </div>
                    @endif

                    @if ($isPatientVerified)
                        <div class="verification-card verification-success">
                            <span class="badge bg-label-success mb-2"><i class="bx bx-check-circle me-1"></i>Patient Verified</span>
                            <div class="row g-2 mt-1">
                                <div class="col-md-4">
                                    <div class="verified-item">
                                        <span class="verified-label">DIN</span>
                                        <span class="verified-value">{{ $patient_din }}</span>
                                    </div>
                                </div>
                                <div class="col-md-8">
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
                                        <span class="verified-label">Phone</span>
                                        <span class="verified-value">{{ $patient_phone ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="verified-item">
                                        <span class="verified-label">Registered At</span>
                                        <span class="verified-value">{{ $patient_registration_facility }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 mt-3">
                                <button wire:click="activateDin" type="button" class="btn btn-success"
                                    wire:target="activateDin" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="activateDin">
                                        <i class="bx bx-check-shield me-1"></i>Activate DIN & Check-In
                                    </span>
                                    <span wire:loading wire:target="activateDin">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"
                                            aria-hidden="true"></span>Activating...
                                    </span>
                                </button>
                                <button type="button" class="btn btn-outline-primary" wire:click="resetForNextPatient">
                                    <i class="bx bx-user-plus me-1"></i>Access Another Patient
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="exit">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    {{-- End DIN Verification Modal --}}

    @include('_partials.din-verification-style')

    <style>
        .header-action-group .btn-header-stable {
            min-width: 210px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>

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

    @include('_partials.datatables-init-multi', [
        'tableIds' => ['todayActivationsTable', 'activationHistoryTable'],
        'orders' => [
            'todayActivationsTable' => [5, 'desc'],
            'activationHistoryTable' => [0, 'desc'],
        ],
    ])

</div>
{{-- End of Alpine DataTable wrapper --}}

