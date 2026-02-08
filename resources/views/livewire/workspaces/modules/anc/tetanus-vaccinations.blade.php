@php
    use Carbon\Carbon;
@endphp

@section('title', 'TT Vaccinations - Antenatal Workspace')

<div x-data="dataTable()">
    {{-- ============================================ --}}
    {{-- ACCESS DENIED VIEW --}}
    {{-- ============================================ --}}
    @if (!$hasAccess)
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="bx bx-error-circle text-danger" style="font-size: 5rem;"></i>
                        </div>
                        <h3 class="text-danger mb-3">Access Denied</h3>
                        <p class="text-muted mb-4">{{ $accessError }}</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('patient.workspace') }}" class="btn btn-primary">
                                <i class="bx bx-search me-1"></i>Go to Patient Workspace
                            </a>
                            <a href="{{ route('din.activations') }}" class="btn btn-outline-success">
                                <i class="bx bx-check-shield me-1"></i>DIN Activation
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-3">
            <span class="badge bg-label-primary text-uppercase">TT Vaccinations</span>
        </div>

        {{-- ============================================ --}}
        {{-- PROFILE HEADER --}}
        {{-- ============================================ --}}
        <div class="card mb-4 tt-hero">
            <div class="tt-hero-cover"></div>
            <div class="card-body tt-hero-body">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="tt-avatar">
                        {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-1">ANC TT Vaccinations</h4>
                        <div class="text-muted small">
                            {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                            <span class="badge bg-label-{{ $patient_gender === 'Female' ? 'danger' : 'info' }}">
                                {{ $patient_gender }}
                            </span>
                            <span class="badge bg-label-secondary">{{ $patient_age }} years</span>
                            <span class="badge bg-label-secondary">Pregnancy #{{ $pregnancy_number }}</span>
                        </div>
                    </div>
                    <div class="ms-lg-auto">
                        <button wire:click="backToDashboard" type="button"
                            class="btn btn-primary px-4 py-2 d-inline-flex align-items-center">
                            <i class="bx bx-arrow-back me-2"></i>
                            Back to Dashboard
                        </button>
                    </div>
                </div>

                <div class="row g-2 mt-3">
                    <div class="col-6 col-lg-3">
                        <div class="tt-stat">
                            <div class="text-muted small">Facility</div>
                            <div class="fw-semibold">{{ $facility_name ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="tt-stat">
                            <div class="text-muted small">Location</div>
                            <div class="fw-semibold">{{ $facility_lga ?? 'N/A' }}, {{ $facility_state ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="tt-stat">
                            <div class="text-muted small">Total Doses</div>
                            <div class="fw-semibold">{{ $totalVaccinations }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="tt-stat">
                            <div class="text-muted small">Status</div>
                            <div class="fw-semibold">
                                @if ($has_completed_all_doses)
                                    Fully Protected
                                @else
                                    Next: {{ $next_dose_label }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Patient Overview --}}
            <div class="col-12 col-lg-4">
                <div class="card h-100 tt-panel">
                    <div class="card-body">
                        <h5 class="mb-3">Patient Overview</h5>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="tt-avatar tt-avatar-sm">
                                {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-semibold tt-patient-name">
                                    {{ $first_name }} {{ $middle_name }} {{ $last_name }}
                                </div>
                                <div class="text-muted small">Gestational Age: {{ $gestational_age }}</div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Phone</span>
                                <span class="fw-semibold">{{ $patient_phone ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">LMP</span>
                                <span class="fw-semibold">{{ $lmp ? Carbon::parse($lmp)->format('d M Y') : 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">EDD</span>
                                <span class="fw-semibold">{{ $edd ? Carbon::parse($edd)->format('d M Y') : 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">TT Status</span>
                                <span class="fw-semibold">
                                    @if ($has_completed_all_doses)
                                        Fully Protected (5/5)
                                    @else
                                        {{ count($vaccination_history) }}/5 doses
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="text-muted small mb-2">Dose Progress</div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach (['TT1', 'TT2', 'TT3', 'TT4', 'TT5'] as $dose)
                                    @php
                                        $given = $vaccination_history->firstWhere('current_tt_dose', $dose);
                                    @endphp
                                    <span class="badge bg-label-{{ $given ? 'success' : ($dose === $next_dose_label ? 'warning' : 'secondary') }}">
                                        {{ $dose }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Vaccinations Table --}}
            <div class="col-12 col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0">Vaccination Records</h5>
                                <small class="text-muted">{{ $totalVaccinations }} Total</small>
                            </div>
                            @if (!$has_completed_all_doses)
                                <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#ttVaccinationModal">
                                    <i class="bx bx-plus me-1"></i>Record {{ $next_dose_label }}
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="dataTable" class="table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Dose</th>
                                    <th>Dose Date</th>
                                    <th>Protection</th>
                                    <th>Site</th>
                                    <th>Batch #</th>
                                    <th>Next Appointment</th>
                                    <th>Adverse</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vaccinations as $vaccination)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $vaccination->dose_badge_color }}">
                                                {{ $vaccination->current_tt_dose }}
                                            </span>
                                        </td>
                                        <td>{{ $vaccination->formatted_dose_date }}</td>
                                        <td>
                                            <span class="badge bg-label-{{ $vaccination->protection_status_color }}">
                                                {{ $vaccination->protection_status }}
                                            </span>
                                        </td>
                                        <td>{{ $vaccination->vaccination_site ?? 'N/A' }}</td>
                                        <td>{{ $vaccination->batch_number ?? 'N/A' }}</td>
                                        <td>
                                            @if ($vaccination->next_appointment_date)
                                                <span class="badge bg-label-{{ $vaccination->is_overdue ? 'danger' : 'info' }}">
                                                    {{ $vaccination->formatted_next_appointment_date }}
                                                    @if ($vaccination->is_overdue)
                                                        <i class="bx bx-error-circle ms-1"></i>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($vaccination->adverse_event !== 'None')
                                                <span class="badge bg-label-warning">
                                                    {{ $vaccination->adverse_event }}
                                                </span>
                                            @else
                                                <span class="text-success">None</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow"
                                                    data-bs-toggle="dropdown">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="javascript:void(0)"
                                                        wire:click="edit({{ $vaccination->id }})">
                                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                                    </a>
                                                    <a class="dropdown-item text-danger" href="javascript:void(0)"
                                                        wire:click="delete({{ $vaccination->id }})"
                                                        wire:confirm="Are you sure you want to delete this vaccination record?">
                                                        <i class="bx bx-trash me-1"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>
                                                No TT vaccinations recorded yet for this pregnancy.
                                            </div>
                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @once
            <style>
                .tt-hero {
                    overflow: hidden;
                    border: 1px solid #e5e7eb;
                }

                .tt-hero-cover {
                    height: 24px;
                    background: #ffffff;
                }

                .tt-hero-body {
                    margin-top: 0;
                }

                .tt-avatar {
                    width: 68px;
                    height: 68px;
                    border-radius: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 700;
                    background: #ffffff;
                    color: #1e293b;
                    border: 3px solid #ffffff;
                    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
                    font-size: 1.2rem;
                }

                .tt-avatar-sm {
                    width: 44px;
                    height: 44px;
                    font-size: 0.95rem;
                }

                .tt-stat {
                    border: 1px solid #e5e7eb;
                    border-radius: 10px;
                    padding: 10px 12px;
                    background: #f8fafc;
                    height: 100%;
                }

                .tt-panel {
                    background: #f8fafc;
                    border: 1px solid #e5e7eb;
                }

                .tt-patient-name {
                    font-size: 1.1rem;
                }
            </style>
        @endonce

        {{-- ============================================ --}}
        {{-- TT VACCINATION FORM MODAL --}}
        {{-- ============================================ --}}
        <div wire:ignore.self class="modal fade" id="ttVaccinationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bx bx-injection me-2"></i>
                            {{ $vaccination_id ? 'Edit TT Vaccination' : 'Record ' . ($current_tt_dose ?? 'TT') . ' Vaccination' }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="{{ $vaccination_id ? 'update' : 'store' }}">
                            @csrf

                            {{-- Patient Summary (Read-only) --}}
                            <div class="alert alert-light mb-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Patient:</strong> {{ $first_name }} {{ $last_name }}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>DIN:</strong> {{ $patient_din }}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Pregnancy:</strong> #{{ $pregnancy_number }}
                                    </div>
                                </div>
                            </div>

                            {{-- Vaccination Details --}}
                            <div class="mb-4">
                                <h6 class="text-primary border-bottom pb-2">
                                    <i class="bx bx-injection me-1"></i>Vaccination Details
                                </h6>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Visit Date <span class="text-danger">*</span></label>
                                    <input wire:model="visit_date" type="date" class="form-control">
                                    @error('visit_date')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">TT Dose <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control bg-light" value="{{ $current_tt_dose }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Dose Date <span class="text-danger">*</span></label>
                                    <input wire:model.live="dose_date" type="date" class="form-control">
                                    @error('dose_date')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Protection Status</label>
                                    <input type="text" class="form-control bg-light" value="{{ $protection_status }}" readonly>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Vaccination Site</label>
                                    <select wire:model="vaccination_site" class="form-select">
                                        <option value="">Select Site</option>
                                        <option value="Left Upper Arm">Left Upper Arm</option>
                                        <option value="Right Upper Arm">Right Upper Arm</option>
                                        <option value="Left Thigh">Left Thigh</option>
                                        <option value="Right Thigh">Right Thigh</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Batch Number</label>
                                    <input wire:model="batch_number" type="text" class="form-control" placeholder="Enter batch number">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Vaccine Expiry Date</label>
                                    <input wire:model="expiry_date" type="date" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Next Appointment</label>
                                    <input wire:model="next_appointment_date" type="date" class="form-control">
                                    @if ($dose_interval)
                                        <small class="text-muted">Recommended: {{ $dose_interval }} days</small>
                                    @endif
                                </div>
                            </div>

                            {{-- Safety Monitoring --}}
                            <div class="mb-4">
                                <h6 class="text-warning border-bottom pb-2">
                                    <i class="bx bx-shield-quarter me-1"></i>Safety Monitoring
                                </h6>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Adverse Event <span class="text-danger">*</span></label>
                                    <select wire:model="adverse_event" class="form-select">
                                        <option value="None">None</option>
                                        <option value="Mild Pain">Mild Pain</option>
                                        <option value="Swelling">Swelling</option>
                                        <option value="Fever">Fever</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Adverse Event Details</label>
                                    <input wire:model="adverse_event_details" type="text" class="form-control"
                                        placeholder="Describe if any adverse event occurred">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-12">
                                    <label class="form-label">Notes</label>
                                    <textarea wire:model="notes" class="form-control" rows="2" placeholder="Additional notes (optional)"></textarea>
                                </div>
                            </div>

                            {{-- Officer Information --}}
                            <div class="mb-4">
                                <h6 class="text-secondary border-bottom pb-2">
                                    <i class="bx bx-user-check me-1"></i>Officer Information
                                </h6>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Officer Name</label>
                                    <input type="text" class="form-control bg-light" value="{{ $officer_name }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Officer Role</label>
                                    <input type="text" class="form-control bg-light" value="{{ $officer_role }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Officer Designation</label>
                                    <input type="text" class="form-control bg-light" value="{{ $officer_designation }}" readonly>
                                </div>
                            </div>

                            {{-- Form Actions --}}
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <span wire:loading.remove wire:target="store,update">
                                        <i class="bx bx-check me-1"></i>
                                        {{ $vaccination_id ? 'Update Vaccination' : 'Record Vaccination' }}
                                    </span>
                                    <span wire:loading wire:target="store,update">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        {{ $vaccination_id ? 'Updating...' : 'Recording...' }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        {{-- End Modal --}}
    @endif

    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('open-modal', () => {
                    const modal = new bootstrap.Modal(document.getElementById('ttVaccinationModal'));
                    modal.show();
                });

                Livewire.on('close-modal', () => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('ttVaccinationModal'));
                    if (modal) modal.hide();
                });
            });
        </script>
    @endpush
</div>
