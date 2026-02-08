@php
    use Carbon\Carbon;
@endphp
@section('title', 'My Tetanus Records')

<div>
    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 32px;">
                            <i class='bx bx-shield-plus me-2'></i>
                            My Tetanus Vaccination Records
                        </h4>
                        <div class="hero-info mb-2">
                            <p class="hero-subtitle">{{ Carbon::today()->format('l, F j, Y') }}</p>
                            <div class="hero-stats">
                                <span class="hero-stat">
                                    <i class="bx bx-shield-check"></i>
                                    {{ $doses_completed }}/5 Doses Completed
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-calendar"></i>
                                    Next Due: {{ $next_due_dose }}
                                </span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-3 text-white mb-1">

                            <span>
                                <i class="bx bx-id-card me-1"></i>
                                <strong>DIN:</strong> {{ $user->DIN }}
                            </span>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('patient-dashboard') }}"
                                class="btn btn-light btn-lg rounded-pill shadow-sm d-inline-flex align-items-center"
                                style="border: 1px solid #ddd; padding: 12px 24px;">
                                <i class="bx bx-arrow-left me-2" style="font-size: 20px;"></i>
                                Back to Dashboard
                            </a>
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

    <!-- Protection Status Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tetanus Protection Status</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bx bx-shield-check bx-lg text-{{ $protection_status['color'] }} me-3"></i>
                                <div>
                                    <h6 class="mb-1">{{ $protection_status['status'] }}</h6>
                                    <small class="text-muted">{{ $protection_status['description'] }}</small>
                                </div>
                            </div>
                            <div class="progress mb-2" style="height: 12px;">
                                <div class="progress-bar bg-{{ $protection_status['color'] }}" role="progressbar"
                                    style="width: {{ $protection_status['percentage'] }}%"
                                    aria-valuenow="{{ $protection_status['percentage'] }}" aria-valuemin="0"
                                    aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>0 doses</span>
                                <span>5 doses (Complete)</span>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="position-relative d-inline-block">
                                <div class="circular-progress-{{ $protection_status['color'] }}">
                                    <svg width="120" height="120">
                                        <circle cx="60" cy="60" r="54" stroke="#e9ecef" stroke-width="8"
                                            fill="none" />
                                        <circle cx="60" cy="60" r="54" stroke="currentColor"
                                            stroke-width="8" fill="none" stroke-dasharray="339.292"
                                            stroke-dashoffset="{{ 339.292 - (339.292 * $protection_status['percentage']) / 100 }}"
                                            stroke-linecap="round" transform="rotate(-90 60 60)" />
                                    </svg>
                                    <div class="position-absolute top-50 start-50 translate-middle">
                                        <div class="text-center">
                                            <div class="h4 mb-0 text-{{ $protection_status['color'] }}">
                                                {{ $doses_completed }}/5</div>
                                            <small class="text-muted">Doses</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if ($next_due_dose !== 'Complete')
                        <div class="alert alert-info mt-3">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Next Due:</strong> {{ $next_due_dose }} - Please consult your healthcare provider
                            for your next vaccination.
                        </div>
                    @else
                        <div class="alert alert-success mt-3">
                            <i class="bx bx-check-circle me-2"></i>
                            <strong>Congratulations!</strong> You have completed all 5 tetanus vaccinations and are
                            fully protected for life.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable with Records -->
    <div class="card">
        <div class="card-datatable table-responsive pt-0">
            <table id="dataTable" class="table">
                <thead class="table-dark">
                    <tr>
                        <th>Visit Date</th>
                        <th>TT Dose</th>
                        <th>Dose Date</th>
                        <th>Protection Status</th>
                        <th>Vaccination Site</th>
                        <th>Adverse Event</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tetanus_records as $record)
                        <tr>
                            <td>{{ $record->visit_date ? Carbon::parse($record->visit_date)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td>
                                <span class="badge bg-label-primary">{{ $record->current_tt_dose ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $record->dose_date ? Carbon::parse($record->dose_date)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td>
                                <span
                                    class="badge {{ $record->protection_status === 'Fully Protected' ? 'bg-label-success' : ($record->protection_status === 'Protected' ? 'bg-label-info' : 'bg-label-warning') }}">
                                    {{ $record->protection_status ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $record->vaccination_site ?? 'N/A' }}</td>
                            <td>
                                <span
                                    class="badge {{ $record->adverse_event === 'None' ? 'bg-label-success' : 'bg-label-warning' }}">
                                    {{ $record->adverse_event ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    wire:click="viewRecord({{ $record->id }})" data-bs-toggle="modal"
                                    data-bs-target="#tetanusViewModal">
                                    <i class="bx bx-show me-1"></i> View Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bx bx-shield bx-lg text-muted mb-2"></i>
                                <p class="text-muted">No tetanus vaccination records found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">
                {{ $tetanus_records->links() }}
            </div>
        </div>
    </div>

    <!-- Tetanus View Modal -->
    <div wire:ignore.self class="modal fade" id="tetanusViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tetanus Vaccination Details</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    @if ($selected_record)
                        <!-- Visit Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-primary">Visit
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Visit Date</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->visit_date ? Carbon::parse($selected_record->visit_date)->format('F d, Y') : 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">TT Dose</label>
                                        <p class="form-control-static">
                                            <span
                                                class="badge bg-primary">{{ $selected_record->current_tt_dose ?? 'N/A' }}</span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Protection Status</label>
                                        <p class="form-control-static">
                                            <span
                                                class="badge {{ $selected_record->protection_status === 'Fully Protected' ? 'bg-success' : ($selected_record->protection_status === 'Protected' ? 'bg-info' : 'bg-warning') }}">
                                                {{ $selected_record->protection_status ?? 'N/A' }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Dose Date</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->dose_date ? Carbon::parse($selected_record->dose_date)->format('F d, Y') : 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vaccination Details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-info">Vaccination
                                        Details</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Vaccination Site</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->vaccination_site ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Dose Interval</label>
                                        <p class="form-control-static">{{ $selected_record->dose_interval ?? 'N/A' }}
                                            days</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Batch Number</label>
                                        <p class="form-control-static">{{ $selected_record->batch_number ?? 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Expiry Date</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->expiry_date ? Carbon::parse($selected_record->expiry_date)->format('F d, Y') : 'N/A' }}
                                        </p>
                                    </div>
                                    @if ($selected_record->next_appointment_date)
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Next Appointment</label>
                                            <p class="form-control-static">
                                                {{ Carbon::parse($selected_record->next_appointment_date)->format('F d, Y') }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Adverse Events and Notes -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-warning">Adverse Events &
                                        Notes</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Adverse Event</label>
                                        <p class="form-control-static">
                                            <span
                                                class="badge {{ $selected_record->adverse_event === 'None' ? 'bg-success' : 'bg-warning' }} fs-6">
                                                {{ $selected_record->adverse_event ?? 'N/A' }}
                                            </span>
                                        </p>
                                    </div>
                                    @if ($selected_record->notes)
                                        <div class="col-md-12">
                                            <label class="form-label fw-bold">Notes/Comments</label>
                                            <p class="form-control-static">{{ $selected_record->notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Facility Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-secondary">Facility
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Facility</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->facility ? $selected_record->facility->name : 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">State</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->state ? $selected_record->state->name : 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">LGA</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->lga ? $selected_record->lga->name : 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Officer Name</label>
                                        <p class="form-control-static">{{ $selected_record->officer_name ?? 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Officer Designation</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->officer_designation ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click="closeModal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--/ Tetanus View Modal -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('tetanusViewModal');

            // Listen for Livewire event to open modal
            Livewire.on('open-view-modal', () => {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            });

            // Handle modal close
            modal.addEventListener('hidden.bs.modal', function() {
                @this.call('closeModal');
            });
        });
    </script>

    <style>
        .circular-progress-success {
            color: #28a745;
        }

        .circular-progress-info {
            color: #17a2b8;
        }

        .circular-progress-warning {
            color: #ffc107;
        }

        .circular-progress-danger {
            color: #dc3545;
        }
    </style>
</div>
