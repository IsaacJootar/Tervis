@php
    use Carbon\Carbon;
@endphp

@section('title', 'Admitted Patients')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Admitted Patients</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-plus-medical me-1"></i>Inpatient Admissions</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Admit, discharge, and refer admitted patients while keeping bed occupancy in sync.</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Active Admissions</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 14h16v4H4z" stroke="currentColor" stroke-width="1.8" />
                            <path d="M7 14V9h10v5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['active'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Today Admissions</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                            <path d="M12 8v8M8 12h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['today_admissions'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-violet h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Today Discharges</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                            <path d="M8 12h8M12 8v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['today_discharges'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-amber h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Bed Occupancy</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 14h16v4H4z" stroke="currentColor" stroke-width="1.8" />
                            <path d="M6 14V10h4v4M14 14V8h4v6" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['occupancy_rate'] }}%</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Admit Patient</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6 position-relative">
                    <label class="form-label">Search Patient <span class="text-danger">*</span></label>
                    @if ($selected_patient_display)
                        <div class="selected-subject">
                            <div class="fw-semibold">{{ $selected_patient_display }}</div>
                            <button type="button" class="btn btn-sm btn-light border" wire:click="clearSelectedPatient">Change</button>
                        </div>
                    @else
                        <input type="text" class="form-control" wire:model.live.debounce.250ms="patient_search"
                            placeholder="Search by DIN, phone, or patient name">
                        @if (strlen(trim((string) $patient_search)) >= 2)
                            <div class="search-popover mt-2">
                                @forelse ($patientSearchResults as $patientOption)
                                    <button type="button" class="search-result-item" wire:key="admit-search-{{ $patientOption->id }}"
                                        wire:click="selectPatient({{ $patientOption->id }})">
                                        <span class="fw-semibold">{{ trim(($patientOption->first_name ?? '') . ' ' . ($patientOption->last_name ?? '')) }}</span>
                                        <small class="text-muted">DIN: {{ $patientOption->din ?: 'N/A' }} | {{ $patientOption->phone ?: 'No phone' }}</small>
                                    </button>
                                @empty
                                    <div class="text-muted small p-2">No active patient match found.</div>
                                @endforelse
                            </div>
                        @endif
                    @endif
                    @error('admission_patient_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Ward / Section <span class="text-danger">*</span></label>
                    <select class="form-select" wire:model.live="bed_section_id">
                        <option value="">Select section...</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>
                    @error('bed_section_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Bed <span class="text-danger">*</span></label>
                    <select class="form-select" wire:model.live="bed_id" @disabled(!$bed_section_id)>
                        <option value="">{{ $bed_section_id ? 'Select bed...' : 'Select section first...' }}</option>
                        @foreach ($availableBeds as $bedOption)
                            <option value="{{ $bedOption->id }}">{{ $bedOption->bed_code }}{{ $bedOption->room_label ? ' | Room ' . $bedOption->room_label : '' }}</option>
                        @endforeach
                    </select>
                    @error('bed_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Admission Date & Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" class="form-control" wire:model.live="admitted_at">
                    @error('admitted_at')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label">Admission Reason</label>
                    <textarea class="form-control" rows="2" wire:model.live="admission_reason" placeholder="Optional reason, findings, or admission note"></textarea>
                    @error('admission_reason')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="button" class="btn btn-primary" wire:click="admitPatient" wire:loading.attr="disabled"
                    wire:target="admitPatient">
                    <span wire:loading.remove wire:target="admitPatient"><i class="bx bx-check-circle me-1"></i>Save Admission</span>
                    <span wire:loading wire:target="admitPatient"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                </button>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Active Admissions <small class="text-muted">({{ $activeAdmissions->count() }})</small></h5>
        </div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="activeAdmissionsTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Admitted At</th>
                        <th>Patient</th>
                        <th>Admission Code</th>
                        <th>Section / Bed</th>
                        <th>Admitted By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activeAdmissions as $admission)
                        <tr wire:key="active-admission-{{ $admission->id }}">
                            <td data-order="{{ $admission->admitted_at?->format('Y-m-d H:i:s') }}">
                                {{ $admission->admitted_at?->format('M d, Y h:i A') ?: 'N/A' }}
                            </td>
                            <td>
                                {{ trim(($admission->patient->first_name ?? '') . ' ' . ($admission->patient->last_name ?? '')) ?: 'N/A' }}
                                <br><small class="text-muted">DIN: {{ $admission->patient->din ?? 'N/A' }}</small>
                            </td>
                            <td><span class="fw-semibold">{{ $admission->admission_code }}</span></td>
                            <td>
                                {{ $admission->section->name ?? 'N/A' }}
                                <br><small class="text-muted">Bed: {{ $admission->bed->bed_code ?? 'N/A' }}</small>
                            </td>
                            <td>{{ $admission->admitted_by ?: 'N/A' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-light text-dark border"
                                        wire:click="startClose({{ $admission->id }}, 'discharged')">
                                        Discharge
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light text-dark border"
                                        wire:click="startClose({{ $admission->id }}, 'referred')">
                                        Refer Out
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No active admissions in this facility.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Admission History <small class="text-muted">({{ $historyAdmissions->count() }})</small></h5>
        </div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="admissionHistoryTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Closed At</th>
                        <th>Patient</th>
                        <th>Admission Code</th>
                        <th>Status</th>
                        <th>Section / Bed</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($historyAdmissions as $admission)
                        @php
                            $statusClass = match ($admission->status) {
                                'discharged' => 'success',
                                'referred' => 'warning',
                                default => 'secondary',
                            };
                        @endphp
                        <tr wire:key="history-admission-{{ $admission->id }}">
                            <td data-order="{{ $admission->discharged_at?->format('Y-m-d H:i:s') }}">
                                {{ $admission->discharged_at?->format('M d, Y h:i A') ?: 'N/A' }}
                            </td>
                            <td>
                                {{ trim(($admission->patient->first_name ?? '') . ' ' . ($admission->patient->last_name ?? '')) ?: 'N/A' }}
                                <br><small class="text-muted">DIN: {{ $admission->patient->din ?? 'N/A' }}</small>
                            </td>
                            <td><span class="fw-semibold">{{ $admission->admission_code }}</span></td>
                            <td>
                                <span class="badge bg-label-{{ $statusClass }}">{{ ucfirst($admission->status) }}</span>
                            </td>
                            <td>
                                {{ $admission->section->name ?? 'N/A' }}
                                <br><small class="text-muted">Bed: {{ $admission->bed->bed_code ?? 'N/A' }}</small>
                            </td>
                            <td>
                                @if ($admission->status === 'referred' && $admission->referral_destination)
                                    <strong>Destination:</strong> {{ $admission->referral_destination }}<br>
                                @endif
                                <small class="text-muted">{{ $admission->discharge_note ?: 'N/A' }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No admission history yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="closeAdmissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $close_action === 'referred' ? 'Refer Patient Out' : 'Discharge Patient' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($close_admission_id && $selectedCloseAdmission)
                        <div class="alert alert-warning py-2 mb-3">
                            <strong>Patient:</strong>
                            {{ trim(($selectedCloseAdmission->patient->first_name ?? '') . ' ' . ($selectedCloseAdmission->patient->last_name ?? '')) }}
                            | <strong>DIN:</strong> {{ $selectedCloseAdmission->patient->din ?? 'N/A' }}
                            | <strong>Bed:</strong> {{ $selectedCloseAdmission->bed->bed_code ?? 'N/A' }}
                            | <strong>Admission:</strong> {{ $selectedCloseAdmission->admission_code ?? 'N/A' }}
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Action</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $close_action === 'referred' ? 'Referred Out' : 'Discharged' }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Close Date & Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" wire:model.live="close_at">
                                @error('close_at')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            @if ($close_action === 'referred')
                                <div class="col-md-4">
                                    <label class="form-label">Referral Destination <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" wire:model.live="close_referral_destination"
                                        placeholder="Receiving facility / destination">
                                    @error('close_referral_destination')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            @endif
                            <div class="col-12">
                                <label class="form-label">{{ $close_action === 'referred' ? 'Referral Note' : 'Discharge Note' }}</label>
                                <textarea class="form-control" rows="2" wire:model.live="close_note" placeholder="Optional closing note"></textarea>
                                @error('close_note')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    @else
                        <div class="alert alert-danger mb-0">Admission not found or no longer active.</div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if ($close_admission_id && $selectedCloseAdmission)
                        <button type="button" class="btn btn-primary" wire:click="completeCloseAdmission"
                            wire:loading.attr="disabled" wire:target="completeCloseAdmission">
                            <span wire:loading.remove wire:target="completeCloseAdmission">
                                {{ $close_action === 'referred' ? 'Confirm Referral' : 'Confirm Discharge' }}
                            </span>
                            <span wire:loading wire:target="completeCloseAdmission"><span
                                    class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .metric-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            padding: 14px 16px;
            min-height: 108px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 26px -22px rgba(15, 23, 42, 0.45);
        }

        .metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-weight: 700;
        }

        .metric-value {
            margin-top: 6px;
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .metric-icon {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.08);
        }

        .metric-icon svg {
            width: 18px;
            height: 18px;
        }

        .metric-card-sky {
            border-color: #bae6fd;
            background: #f0f9ff;
            color: #0c4a6e;
        }

        .metric-card-emerald {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #065f46;
        }

        .metric-card-violet {
            border-color: #ddd6fe;
            background: #f5f3ff;
            color: #5b21b6;
        }

        .metric-card-amber {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .form-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: #64748b;
        }

        .search-popover {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 18px 35px -28px rgba(15, 23, 42, 0.5);
            max-height: 220px;
            overflow-y: auto;
        }

        .search-result-item {
            width: 100%;
            border: 0;
            background: #fff;
            text-align: left;
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .search-result-item + .search-result-item {
            border-top: 1px solid #f1f5f9;
        }

        .search-result-item:hover {
            background: #f8fafc;
        }

        .selected-subject {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
    </style>

    <script>
        document.addEventListener('livewire:initialized', function() {
            const modalEl = document.getElementById('closeAdmissionModal');
            if (!modalEl) return;
            let modalInstance = null;

            const getModal = () => {
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(modalEl);
                }
                return modalInstance;
            };

            const cleanupModalArtifacts = () => {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
            };

            Livewire.on('open-close-admission-modal', () => {
                getModal().show();
            });

            Livewire.on('close-close-admission-modal', () => {
                if (modalInstance) {
                    modalInstance.hide();
                }
            });

            modalEl.addEventListener('hidden.bs.modal', function() {
                @this.call('cancelClose');
                cleanupModalArtifacts();
            });
        });
    </script>

    @include('_partials.datatables-init-multi', [
        'tableIds' => ['activeAdmissionsTable', 'admissionHistoryTable'],
        'orders' => [
            'activeAdmissionsTable' => [0, 'desc'],
            'admissionHistoryTable' => [0, 'desc'],
        ],
    ])
</div>
