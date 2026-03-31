@php
    use Carbon\Carbon;
@endphp
@section('title', 'My Antenatal Records')

<div>
    <div class="card portal-section-card">
        <div class="card-header border-0 pb-0">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="portal-section-icon">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <rect x="4" y="4" width="16" height="16" rx="4" stroke="currentColor" stroke-width="1.8" />
                    </svg>
                </span>
                <h6 class="portal-section-title mb-0">Antenatal Timeline</h6>
            </div>
            <small class="text-muted">Every antenatal registration tied to this patient, with quick drill-down into each booking.</small>
        </div>
        <div class="table-responsive pt-0">
            <table class="table align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Unit No.</th>
                        <th>Date of Booking</th>
                        <th>LMP</th>
                        <th>EDD</th>
                        <th>Age</th>
                        <th>Consultant</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($antenatal_records as $record)
                        <tr>
                            <td>{{ $record->unit_no ?? 'N/A' }}</td>
                            <td>{{ $record->date_of_booking ? Carbon::parse($record->date_of_booking)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td>{{ $record->lmp ? Carbon::parse($record->lmp)->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $record->edd ? Carbon::parse($record->edd)->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $record->age ?? 'N/A' }} years</td>
                            <td>{{ $record->consultant ?? 'N/A' }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    wire:click="viewRecord({{ $record->id }})" data-bs-toggle="modal"
                                    data-bs-target="#antenatalViewModal">
                                    <i class="bx bx-show me-1"></i> View Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="portal-empty d-inline-block w-100">
                                    <i class="bx bx-folder-open bx-lg mb-2"></i>
                                    <p class="mb-0">No antenatal records are available for this patient yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($antenatal_records->hasPages())
            <div class="card-body pt-3">
                {{ $antenatal_records->links() }}
            </div>
        @endif
    </div>

    <!-- Antenatal View Modal -->
    <div wire:ignore.self class="modal fade" id="antenatalViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h4 class="modal-title">Antenatal Record Details</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    @if ($selected_record)
                        <!-- Patient Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-primary">Patient
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Full Name</label>
                                        <p class="form-control-static">{{ $selected_record->user->first_name }}
                                            {{ $selected_record->user->last_name }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">DIN</label>
                                        <p class="form-control-static">{{ $selected_record->user->DIN }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Age</label>
                                        <p class="form-control-static">{{ $selected_record->age }} years</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Unit No.</label>
                                        <p class="form-control-static">{{ $selected_record->unit_no ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">X-ray No.</label>
                                        <p class="form-control-static">{{ $selected_record->xray_no ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Address</label>
                                        <p class="form-control-static">{{ $selected_record->address }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pregnancy Details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-success">Pregnancy Details</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Date of Booking</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->date_of_booking ? Carbon::parse($selected_record->date_of_booking)->format('F d, Y') : 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Last Menstrual Period</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->lmp ? Carbon::parse($selected_record->lmp)->format('F d, Y') : 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Expected Delivery Date</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->edd ? Carbon::parse($selected_record->edd)->format('F d, Y') : 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Previous Pregnancies</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->previous_pregnancies ?? '0' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Total Births</label>
                                        <p class="form-control-static">{{ $selected_record->total_births ?? '0' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Living Children</label>
                                        <p class="form-control-static">{{ $selected_record->living_children ?? '0' }}
                                        </p>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Indication for Booking</label>
                                        <p class="form-control-static">
                                            {{ $selected_record->indication_for_booking ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Physical Examination -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-dark">Physical
                                        Examination</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Height</label>
                                        <p class="form-control-static">{{ $selected_record->height }} cm</p>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Weight</label>
                                        <p class="form-control-static">{{ $selected_record->weight }} kg</p>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Blood Pressure</label>
                                        <p class="form-control-static">{{ $selected_record->blood_pressure }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Hemoglobin</label>
                                        <p class="form-control-static">{{ $selected_record->hemoglobin }} g/dL</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Genotype</label>
                                        <p class="form-control-static">{{ $selected_record->genotype }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Blood Group & Rhesus</label>
                                        <p class="form-control-static">{{ $selected_record->blood_group_rhesus }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">KAHN Test</label>
                                        <p class="form-control-static">
                                            <span
                                                class="badge bg-{{ $selected_record->kahn_test === 'positive' ? 'danger' : 'success' }}">
                                                {{ ucfirst($selected_record->kahn_test ?? 'N/A') }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Follow Up Assessment -->
                        @if ($selected_record->follow_up_date)
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><span class="badge text-bg-info">Follow Up
                                            Assessment</span></h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Follow Up Date</label>
                                            <p class="form-control-static">
                                                {{ $selected_record->follow_up_date ? Carbon::parse($selected_record->follow_up_date)->format('F d, Y') : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Weight</label>
                                            <p class="form-control-static">{{ $selected_record->follow_up_weight }} kg
                                            </p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Blood Pressure</label>
                                            <p class="form-control-static">{{ $selected_record->follow_up_bp }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Fundal Height</label>
                                            <p class="form-control-static">
                                                {{ $selected_record->follow_up_fundal_height }} cm</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Fetal Heart Rate</label>
                                            <p class="form-control-static">{{ $selected_record->follow_up_fhr }} bpm
                                            </p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Next Visit</label>
                                            <p class="form-control-static">
                                                {{ $selected_record->follow_up_next_visit ? Carbon::parse($selected_record->follow_up_next_visit)->format('F d, Y') : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Comments and Additional Information -->
                        @if ($selected_record->comments || $selected_record->special_instructions)
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><span class="badge text-bg-secondary">Additional
                                            Information</span></h5>
                                </div>
                                <div class="card-body">
                                    @if ($selected_record->comments)
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Comments</label>
                                            <p class="form-control-static">{{ $selected_record->comments }}</p>
                                        </div>
                                    @endif
                                    @if ($selected_record->special_instructions)
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Special Instructions</label>
                                            <p class="form-control-static">
                                                {{ $selected_record->special_instructions }}</p>
                                        </div>
                                    @endif
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Examiner</label>
                                            <p class="form-control-static">{{ $selected_record->examiner ?? 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Officer</label>
                                            <p class="form-control-static">{{ $selected_record->officer_name }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Consultant</label>
                                            <p class="form-control-static">{{ $selected_record->consultant ?? 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
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
    <!--/ Antenatal View Modal -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('antenatalViewModal');

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
</div>
