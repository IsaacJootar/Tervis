@php
    use Carbon\Carbon;
@endphp
@section('title', 'My Tetanus Records')

<div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card portal-section-card">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="portal-section-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 4l6 2.5v4.8c0 4.2-2.6 7.8-6 8.7-3.4-.9-6-4.5-6-8.7V6.5L12 4z" stroke="currentColor" stroke-width="1.8" />
                                <path d="M9.5 12.2l1.7 1.7 3.3-3.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <h6 class="portal-section-title mb-0">Tetanus Protection Status</h6>
                    </div>
                    <small class="text-muted">A modern summary of dose completion, protection level, and next action.</small>
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

    <div class="card portal-section-card">
        <div class="card-header border-0 pb-0">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="portal-section-icon">
                    <svg viewBox="0 0 24 24" fill="none">
                        <rect x="5" y="5" width="14" height="14" rx="3" stroke="currentColor" stroke-width="1.8" />
                        <path d="M8.5 10.5h7M8.5 13.5h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </span>
                <h6 class="portal-section-title mb-0">Vaccination History</h6>
            </div>
            <small class="text-muted">Every tetanus record captured for this patient, with dose-level detail.</small>
        </div>
        <div class="table-responsive pt-0">
            <table class="table align-middle mb-0">
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
                                <div class="portal-empty d-inline-block w-100">
                                    <i class="bx bx-shield bx-lg mb-2"></i>
                                    <p class="mb-0">No tetanus vaccination records are available yet.</p>
                                </div>
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
