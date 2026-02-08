@php
    use Carbon\Carbon;
@endphp

@section('title', 'ANC Follow-up Assessment')

<div x-data="dataTable()">
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
                            <a href="{{ route('patient-workspace') }}" class="btn btn-primary">
                                <i class="bx bx-search me-1"></i>Go to Patient Workspace
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-3">
            <span class="badge bg-label-primary text-uppercase">ANC Follow-up Assessment</span>
        </div>

        <div class="card mb-4 tt-hero">
            <div class="tt-hero-cover"></div>
            <div class="card-body tt-hero-body">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="tt-avatar">
                        {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-1">ANC Follow-up Assessment</h4>
                        <div class="text-muted small">
                            {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                            <span class="badge bg-label-{{ $patient_gender === 'Female' ? 'danger' : 'info' }}">
                                {{ $patient_gender }}
                            </span>
                            <span class="badge bg-label-secondary">{{ $patient_age }} years</span>
                            <span class="badge bg-label-secondary">Pregnancy #{{ $pregnancy_number ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="ms-lg-auto">
                        <button wire:click="backToDashboard" type="button"
                            class="btn btn-primary px-4 py-2 d-inline-flex align-items-center">
                            <i class="bx bx-arrow-back me-2"></i>
                            Back to ANC Workspace
                        </button>
                    </div>
                </div>

                <div class="row g-2 mt-3">
                    <div class="col-6 col-lg-4">
                        <div class="tt-stat">
                            <div class="text-muted small">Facility</div>
                            <div class="fw-semibold">{{ $facility_name ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-4">
                        <div class="tt-stat">
                            <div class="text-muted small">Location</div>
                            <div class="fw-semibold">{{ $lga_name ?? 'N/A' }}, {{ $state_name ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-4">
                        <div class="tt-stat">
                            <div class="text-muted small">Total Visits</div>
                            <div class="fw-semibold">{{ count($assessments) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-4 order-1 order-lg-1">
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
                                <div class="text-muted small">DOB: {{ $patient_dob ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Phone</span>
                                <span class="fw-semibold">{{ $patient_phone ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">LMP</span>
                                <span
                                    class="fw-semibold">{{ $lmp ? Carbon::parse($lmp)->format('d M Y') : 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">EDD</span>
                                <span
                                    class="fw-semibold">{{ $edd ? Carbon::parse($edd)->format('d M Y') : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8 order-2 order-lg-2">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0">Follow-up Assessments</h5>
                                <small class="text-muted">{{ count($assessments) }} Total</small>
                            </div>
                            <button type="button" class="btn btn-success text-white" data-bs-toggle="modal"
                                data-bs-target="#followUpModal">
                                <i class="bx bx-plus me-1"></i>Record Follow-up
                            </button>
                        </div>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="dataTable" class="table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Visit Date</th>
                                    <th>BP</th>
                                    <th>PCV</th>
                                    <th>FHR</th>
                                    <th>Oedema</th>
                                    <th>Next Return</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($assessments as $assessment)
                                    <tr wire:key="assessment-{{ $assessment->id }}">
                                        <td>{{ $assessment->visit_date?->format('M d, Y') ?? 'N/A' }}</td>
                                        <td>{{ $assessment->bp ?? 'N/A' }}</td>
                                        <td>{{ $assessment->pcv ?? 'N/A' }}</td>
                                        <td>{{ $assessment->fetal_heart_rate ?? 'N/A' }}</td>
                                        <td>{{ $assessment->oedema ?? 'N/A' }}</td>
                                        <td>{{ $assessment->next_return_date?->format('M d, Y') ?? 'N/A' }}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-light text-dark border"
                                                    data-bs-toggle="modal" data-bs-target="#followUpModal"
                                                    wire:click="edit({{ $assessment->id }})">
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light text-dark border"
                                                    wire:click="delete({{ $assessment->id }})">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>
                                                No follow-up assessments recorded yet.
                                            </div>
                                        </td>
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

        <div wire:ignore.self class="modal fade" id="followUpModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-clinical-dark text-white">
                        <h5 class="modal-title text-white">
                            <i class="bx bx-notepad me-2"></i>
                            {{ $assessment_id ? 'Edit Follow-up Assessment' : 'Record Follow-up Assessment' }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="{{ $assessment_id ? 'update' : 'store' }}">
                            @csrf

                            <div class="card">
                                <div
                                    class="card-header bg-clinical-dark d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-white">Follow-up Assessment Entry</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Visit Date</label>
                                                    <input type="date" class="form-control"
                                                        wire:model="visit_date">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">B.P.</label>
                                                    <input type="text" class="form-control" wire:model="bp"
                                                        placeholder="120/80">
                                                    @error('bp')
                                                        <span class="text-danger small">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">P.C.V (%)</label>
                                                    <input type="number" class="form-control" wire:model="pcv">
                                                    @error('pcv')
                                                        <span class="text-danger small">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Weight (kg)</label>
                                                    <input type="number" class="form-control" wire:model="weight">
                                                    @error('weight')
                                                        <span class="text-danger small">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Height of Fundus (cm)</label>
                                                    <input type="number" class="form-control"
                                                        wire:model="fundal_height">
                                                    @error('fundal_height')
                                                        <span class="text-danger small">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Presentation & Position</label>
                                                    <select class="form-select" wire:model="presentation_position">
                                                        <option value="">--Select--</option>
                                                        <option value="Cephalic">Cephalic</option>
                                                        <option value="Breech">Breech</option>
                                                        <option value="Transverse">Transverse</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Relation to Brim</label>
                                                    <input type="text" class="form-control"
                                                        wire:model="relation_to_brim">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Foetal Heart Rate (bpm)</label>
                                                    <input type="number" class="form-control"
                                                        wire:model="fetal_heart_rate">
                                                    @error('fetal_heart_rate')
                                                        <span class="text-danger small">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Urine (Alb/Sug)</label>
                                                    <select class="form-select" wire:model="urine_test">
                                                        <option value="">Select</option>
                                                        <option value="Trace/Nil">Trace / Nil</option>
                                                        <option value="Trace/Trace">Trace / Trace</option>
                                                        <option value="+/Nil">+ / Nil</option>
                                                        <option value="Nil/+">Nil / +</option>
                                                        <option value="+/+">+ / +</option>
                                                        <option value="++/+">++ / +</option>
                                                        <option value="++/++">++ / ++</option>
                                                        <option value="+++/++">+++ / ++</option>
                                                        <option value="+/Trace">+ / Trace</option>
                                                        <option value="Nil/Trace">Nil / Trace</option>
                                                        <option value="Trace/+">Trace / +</option>
                                                        <option value="Nil/Nil">Nil / Nil</option>
                                                    </select>
                                                    @error('urine_test')
                                                        <span class="text-danger small">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Oedema</label>
                                                    <select class="form-select" wire:model="oedema">
                                                        <option value="">Select</option>
                                                        <option value="none">None</option>
                                                        <option value="+">+</option>
                                                        <option value="++">++</option>
                                                        <option value="+++">+++</option>
                                                    </select>
                                                    @error('oedema')
                                                        <span class="text-danger small">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Clinical Remarks</label>
                                                    <textarea class="form-control" rows="2" wire:model="clinical_remarks"></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-danger fw-bold">Special Delivery
                                                        Instructions</label>
                                                    <textarea class="form-control border-danger" rows="2" wire:model="special_delivery_instructions"></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Next Return Date</label>
                                                    <input type="date" class="form-control"
                                                        wire:model="next_return_date">
                                                </div>
                                        </div>

                                    <hr class="my-4">

                                    <div class="section-header text-primary">Pelvic Assessment</div>
                                    <div class="mb-3">
                                        <label class="form-label">X-Ray Pelvimetry</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="pelv"
                                                    id="pelv_yes" value="1" wire:model="xray_pelvimetry">
                                                <label class="form-check-label">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="pelv"
                                                    id="pelv_no" value="0" wire:model="xray_pelvimetry">
                                                <label class="form-check-label">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label">Inlet</label>
                                            <input type="text" class="form-control form-control-sm"
                                                wire:model="pelvic_inlet">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Cavity</label>
                                            <input type="text" class="form-control form-control-sm"
                                                wire:model="pelvic_cavity">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Outlet</label>
                                            <input type="text" class="form-control form-control-sm"
                                                wire:model="pelvic_outlet">
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <div class="section-header text-info">Initial Laboratory</div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label">Hb/Genotype</label>
                                            <input type="text" class="form-control form-control-sm"
                                                wire:model="hb_genotype">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Rhesus</label>
                                            <input type="text" class="form-control form-control-sm"
                                                wire:model="rhesus">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Kahn (VDRL)</label>
                                            <input type="text" class="form-control form-control-sm"
                                                wire:model="kahn_vdrl">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Antimalarials & Therapy</label>
                                            <textarea class="form-control form-control-sm" rows="2" wire:model="antimalarials_therapy"></textarea>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <div class="mb-3">
                                        <h6 class="text-secondary border-bottom pb-2">
                                            <i class="bx bx-user-check me-1"></i>Officer Information
                                        </h6>
                                    </div>

                                    <div class="row g-3 mb-2">
                                        <div class="col-md-4">
                                            <label class="form-label">Officer Name</label>
                                            <input type="text" class="form-control bg-light"
                                                value="{{ $officer_name }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Officer Role</label>
                                            <input type="text" class="form-control bg-light"
                                                value="{{ $officer_role }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Officer Designation</label>
                                            <input type="text" class="form-control bg-light"
                                                value="{{ $officer_designation }}" readonly>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-4">
                                        <button type="button" class="btn btn-outline-secondary"
                                            data-bs-dismiss="modal">
                                            <i class="bx bx-x me-1"></i>Cancel
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <span wire:loading.remove wire:target="store,update">
                                                <i class="bx bx-check me-1"></i>
                                                {{ $assessment_id ? 'Update Assessment' : 'Save Assessment' }}
                                            </span>
                                            <span wire:loading wire:target="store,update">
                                                <span class="spinner-border spinner-border-sm" role="status"
                                                    aria-hidden="true"></span>
                                                Processing...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </form>
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

                .bg-clinical-dark {
                    background-color: #2c3e50;
                    color: white;
                }

                .bg-clinical-dark .modal-title {
                    color: #ffffff !important;
                }

                .form-label {
                    font-weight: 600;
                    color: #444;
                    font-size: 0.85rem;
                    margin-bottom: 3px;
                }

                .section-header {
                    border-left: 4px solid #0d6efd;
                    padding-left: 10px;
                    margin-bottom: 15px;
                    text-transform: uppercase;
                    font-size: 0.9rem;
                    letter-spacing: 1px;
                }
            </style>
        @endonce

        @push('scripts')
            <script>
                document.addEventListener('livewire:initialized', () => {
                    Livewire.on('open-main-modal', () => {
                        const modal = document.getElementById('followUpModal');
                        const inst = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                        inst.show();
                    });

                    Livewire.on('close-modals', () => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('followUpModal'));
                        if (modal) modal.hide();
                    });
                });
            </script>
        @endpush
    @endif
</div>
