@php
    use Carbon\Carbon;
@endphp

@section('title', 'Child Immunization')

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
                        <a href="{{ route('workspace-dashboard', ['patientId' => $patientId]) }}" class="btn btn-primary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Workspace
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-3">
            <span class="badge bg-label-primary text-uppercase">Child Immunization</span>
        </div>

        <div class="card mb-4 tt-hero">
            <div class="tt-hero-cover"></div>
            <div class="card-body tt-hero-body">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="tt-avatar">
                        {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-1">Vaccinations / Immunization</h4>
                        <div class="text-muted small">
                            {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                            <span class="badge bg-label-secondary">Mother: {{ $first_name }} {{ $last_name }}</span>
                        </div>
                    </div>
                    <div class="ms-lg-auto">
                        <button wire:click="backToDashboard" type="button" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center" wire:loading.attr="disabled" wire:target="backToDashboard">
                            <span wire:loading.remove wire:target="backToDashboard"><i class="bx bx-arrow-back me-2"></i>Back to Workspace</span>
                            <span wire:loading wire:target="backToDashboard"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="card h-100 tt-panel">
                    <div class="card-body">
                        <h5 class="mb-3">Child Context</h5>
                        <div class="mb-3">
                            <label class="form-label">Linked Child</label>
                            <select class="form-select" wire:model.live="linked_child_id">
                                @foreach ($linkedChildren as $child)
                                    <option value="{{ $child->id }}">
                                        {{ $child->full_name ?: ('Child #' . $child->linked_child_id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">DOB</span>
                                <span class="fw-semibold">{{ $currentChild?->formatted_date_of_birth ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Age</span>
                                <span class="fw-semibold">{{ $currentChild?->age_display ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Gender</span>
                                <span class="fw-semibold">{{ $currentChild?->gender ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Birth Cohort</span>
                                <span
                                    class="fw-semibold">{{ $currentChild?->date_of_birth?->format('M Y') ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0">Immunization Records</h5>
                                <small class="text-muted">{{ count($records) }} Total</small>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('workspaces-child-health-vaccination-schedule', ['patientId' => $patientId]) }}"
                                    class="btn btn-outline-primary">
                                    <i class="bx bx-list-check me-1"></i>Vaccination Schedule</a>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#immunizationModal" wire:click="exit" wire:loading.attr="disabled" wire:target="exit">
                                    <span wire:loading.remove wire:target="exit"><i class="bx bx-plus me-1"></i>Immunization Register</span>
                                    <span wire:loading wire:target="exit"><span class="spinner-border spinner-border-sm me-1"></span>Preparing...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="dataTable" class="table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Visit Date</th>
                                    <th>Child</th>
                                    <th>Card No.</th>
                                    <th>Vaccines Given</th>
                                    <th>Last Antigen</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $record)
                                    <tr wire:key="immunization-{{ $record->id }}">
                                        <td>{{ $record->visit_date?->format('M d, Y') }}</td>
                                        <td>{{ $record->linkedChild?->full_name ?: 'N/A' }}</td>
                                        <td>{{ $record->immunization_card_no ?: 'N/A' }}</td>
                                        <td>{{ $record->given_vaccines_count }}</td>
                                        <td>{{ $record->last_antigen ?: 'N/A' }}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-light text-dark border"
                                                    data-bs-toggle="modal" data-bs-target="#immunizationModal"
                                                    wire:click="edit({{ $record->id }})" wire:loading.attr="disabled" wire:target="edit({{ $record->id }})">
                                                    <span wire:loading.remove wire:target="edit({{ $record->id }})">Edit</span>
                                                    <span wire:loading wire:target="edit({{ $record->id }})"><span class="spinner-border spinner-border-sm"></span></span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light text-dark border"
                                                    wire:click="delete({{ $record->id }})" wire:loading.attr="disabled" wire:target="delete({{ $record->id }})">
                                                    <span wire:loading.remove wire:target="delete({{ $record->id }})">Delete</span>
                                                    <span wire:loading wire:target="delete({{ $record->id }})"><span class="spinner-border spinner-border-sm"></span></span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>
                                                No immunization records yet.
                                            </div>
                                        </td>
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

        <div wire:ignore.self class="modal fade" id="immunizationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-clinical-dark text-white">
                        <h5 class="modal-title text-white">
                            {{ $record_id ? 'Edit Immunization Record' : 'Immunization Register' }}
                        </h5>
                        <button wire:click="exit" type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="setTimeout(() => location.reload(), 300)" wire:loading.attr="disabled" wire:target="exit"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="{{ $record_id ? 'update' : 'store' }}">
                            @csrf
                            <div class="card">
                                <div class="card-header bg-clinical-dark">
                                    <h6 class="mb-0 text-white">Immunization Entry</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Visit Date</label>
                                            <input type="date" class="form-control" wire:model="visit_date">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label">Linked Child</label>
                                            <select class="form-select" wire:model.live="linked_child_id">
                                                @foreach ($linkedChildren as $child)
                                                    <option value="{{ $child->id }}">
                                                        {{ $child->full_name ?: ('Child #' . $child->linked_child_id) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Immunization Card No.</label>
                                            <input type="text" class="form-control" wire:model="immunization_card_no">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Child DOB</label>
                                            <input type="text" class="form-control bg-light"
                                                value="{{ $currentChild?->formatted_date_of_birth ?? 'N/A' }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Child Gender</label>
                                            <input type="text" class="form-control bg-light"
                                                value="{{ $currentChild?->gender ?? 'N/A' }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Birth Cohort</label>
                                            <input type="text" class="form-control bg-light"
                                                value="{{ $currentChild?->date_of_birth?->format('F Y') ?? 'N/A' }}" readonly>
                                        </div>

                                        <div class="col-md-8">
                                            <label class="form-label">Follow-Up Address</label>
                                            <input type="text" class="form-control" wire:model="follow_up_address">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Follow-Up Phone</label>
                                            <input type="text" class="form-control" wire:model="follow_up_phone">
                                        </div>
                                    </div>

                                    <ul class="nav nav-tabs mt-4 mb-3" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#imm-vaccine-tab" role="tab">
                                                <i class="bx bx-injection me-1"></i>Vaccine Dates
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#imm-aefi-tab" role="tab">
                                                <i class="bx bx-error-circle me-1"></i>AEFI
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="imm-vaccine-tab" role="tabpanel">
                                            <div class="mb-2">
                                                <h6 class="text-secondary border-bottom pb-2">
                                                    <i class="bx bx-injection me-1"></i>Vaccine Dates
                                                </h6>
                                            </div>

                                    <div class="card-datatable table-responsive pt-0">
                                        <table class="table">
                                            <thead>
                                                <tr class="table-dark text-center">
                                                    <th colspan="2">At Birth</th>
                                                    <th colspan="5">6 Weeks</th>
                                                    <th colspan="5">10 Weeks</th>
                                                    <th colspan="5">14 Weeks</th>
                                                    <th colspan="4">9 Months</th>
                                                    <th colspan="2">Vit A</th>
                                                    <th colspan="3">Extra</th>
                                                </tr>
                                                <tr class="text-center">
                                                    <th>HepB0</th>
                                                    <th>OPV0</th>
                                                    <th>BCG</th>
                                                    <th>OPV1</th>
                                                    <th>PENTA1</th>
                                                    <th>PCV1</th>
                                                    <th>ROTA1</th>
                                                    <th>OPV2</th>
                                                    <th>PENTA2</th>
                                                    <th>PCV2</th>
                                                    <th>ROTA2</th>
                                                    <th>IPV1</th>
                                                    <th>OPV3</th>
                                                    <th>PENTA3</th>
                                                    <th>PCV3</th>
                                                    <th>MR1</th>
                                                    <th>YF</th>
                                                    <th>MR2</th>
                                                    <th>MenA</th>
                                                    <th>YF2</th>
                                                    <th>SLEA</th>
                                                    <th>VitA1</th>
                                                    <th>VitA2</th>
                                                    <th>IPV2</th>
                                                    <th>ROTA3</th>
                                                    <th>HPV</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="hepb0_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="opv0_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="bcg_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="opv1_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="penta1_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="pcv1_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="rota1_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="opv2_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="penta2_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="pcv2_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="rota2_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="ipv1_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="opv3_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="penta3_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="pcv3_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="mr1_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="yf_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="mr2_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="mena_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="yf2_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="slea_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="vita1_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="vita2_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="ipv2_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="rota3_date"></td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model="hpv_date"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <small class="text-muted d-block mb-2">
                                        Register-aligned schedule: enter only vaccine dates already administered.
                                    </small>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Comments</label>
                                            <textarea class="form-control" rows="2" wire:model="comments"></textarea>
                                        </div>
                                    </div>

                                        </div>

                                        <div class="tab-pane fade" id="imm-aefi-tab" role="tabpanel">
                                            <div class="mb-2">
                                                <h6 class="text-secondary border-bottom pb-2">
                                                    <i class="bx bx-error-circle me-1"></i>AEFI - Adverse Events Following Immunization
                                                </h6>
                                            </div>

                                            <div class="aefi-codes-box mb-3">
                                                <div class="fw-semibold mb-2">Reaction Type Codes (1-28)</div>
                                                <div class="small text-muted mb-1">
                                                    1=Anaphylaxis, 2=Anaphylactic Shock, 3=Dizziness, 4=Headache, 5=Fainting/Syncope, 6=Seizures/Convulsion, 7=Loss of Vision, 8=Local Reaction, 9=Site Induration, 10=Abscess at Injection Site,
                                                    11=Rash/Urticaria, 12=Lymph Node Enlargement, 13=Abd Cramps, 14=Vomiting, 15=Diarrhoea, 16=Bleeding, 17=Muscle Pain, 18=Joint Pain, 19=Fever (&lt;38c), 20=Fever (&gt;=38c),
                                                    21=Persistent Cries (&gt;3hrs), 22=AFP, 23=Unconsciousness, 24=Sepsis, 25=Encephalopathy, 26=Neck Stiffness, 27=Facial Paralysis, 28=Others.
                                                </div>
                                                <div class="small text-muted">
                                                    Outcome Codes: 1=Recovered, 2=Hospitalized, 3=Disability, 4=Died.
                                                </div>
                                            </div>

                                            <div class="row g-3 mb-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Period of Reporting</label>
                                                    <input type="text" class="form-control" wire:model="aefi_period" placeholder="e.g. Jan - Mar 2026">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Routine Immunization / SIA</label>
                                                    <select class="form-select" wire:model="aefi_type">
                                                        <option value="">Select</option>
                                                        <option value="Routine Immunization">Routine Immunization</option>
                                                        <option value="SIA">SIA</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">SIA Campaign (if applicable)</label>
                                                    <input type="text" class="form-control" wire:model="aefi_sia_campaign" placeholder="Campaign name">
                                                </div>
                                            </div>

                                            <div class="card-datatable table-responsive pt-0">
                                                <table class="table">
                                                    <thead>
                                                        <tr class="table-dark text-center">
                                                            <th>Case</th>
                                                            <th>Age Y</th>
                                                            <th>Age M</th>
                                                            <th>Last Immunization Date</th>
                                                            <th>Reaction Code</th>
                                                            <th>Type</th>
                                                            <th>Outcome</th>
                                                            <th>Suspect Vaccine</th>
                                                            <th>Vaccine Batch</th>
                                                            <th>Diluent Batch</th>
                                                            <th>Onset Interval</th>
                                                            <th>Reported Date</th>
                                                            <th>Notes</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach (range(1, 8) as $slot)
                                                            @php $slotIndex = $slot - 1; @endphp
                                                            <tr>
                                                                <td class="fw-semibold">{{ $slot }}</td>
                                                                <td><input type="number" min="0" max="18" class="form-control form-control-sm" wire:model="aefi_cases.{{ $slotIndex }}.age_y"></td>
                                                                <td><input type="number" min="0" max="11" class="form-control form-control-sm" wire:model="aefi_cases.{{ $slotIndex }}.age_m"></td>
                                                                <td><input type="date" class="form-control form-control-sm" wire:model="aefi_cases.{{ $slotIndex }}.last_immunization_date"></td>
                                                                <td>
                                                                    <select class="form-select form-select-sm" wire:model="aefi_cases.{{ $slotIndex }}.reaction_code">
                                                                        <option value="">Code</option>
                                                                        @foreach ($aefiReactionCodes as $code => $label)
                                                                            <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select class="form-select form-select-sm" wire:model="aefi_cases.{{ $slotIndex }}.type">
                                                                        <option value="">Select</option>
                                                                        <option value="Minor">Minor</option>
                                                                        <option value="Serious">Serious</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select class="form-select form-select-sm" wire:model="aefi_cases.{{ $slotIndex }}.outcome_code">
                                                                        <option value="">Code</option>
                                                                        @foreach ($aefiOutcomeCodes as $code => $label)
                                                                            <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td><input type="text" class="form-control form-control-sm" wire:model="aefi_cases.{{ $slotIndex }}.vaccine"></td>
                                                                <td><input type="text" class="form-control form-control-sm" wire:model="aefi_cases.{{ $slotIndex }}.vaccine_batch_no"></td>
                                                                <td><input type="text" class="form-control form-control-sm" wire:model="aefi_cases.{{ $slotIndex }}.diluent_batch_no"></td>
                                                                <td><input type="text" class="form-control form-control-sm" wire:model="aefi_cases.{{ $slotIndex }}.onset_interval"></td>
                                                                <td><input type="date" class="form-control form-control-sm" wire:model="aefi_cases.{{ $slotIndex }}.reported_date"></td>
                                                                <td><input type="text" class="form-control form-control-sm" wire:model="aefi_cases.{{ $slotIndex }}.notes"></td>
                                                            </tr>
                                                        @endforeach
</tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    <div class="mb-3">
                                        <h6 class="text-secondary border-bottom pb-2">
                                            <i class="bx bx-user-check me-1"></i>Officer Information
                                        </h6>
                                    </div>
                                    <div class="row g-3">
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

                                    <div class="mt-3">
                                        <small class="text-muted">
                                            Summary Mapping: this record contributes to monthly immunization keys
                                            (BCG/OPV/PENTA/PCV/IPV/MCV/YF/HepB0 and related totals).
                                        </small>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-4">
                                        <button wire:click="exit" type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="setTimeout(() => location.reload(), 300)" wire:loading.attr="disabled" wire:target="exit">
                                            <span wire:loading.remove wire:target="exit">Cancel</span>
                                            <span wire:loading wire:target="exit"><span class="spinner-border spinner-border-sm me-1"></span>Closing...</span>
                                        </button>
                                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store,update">
                                            <span wire:loading.remove wire:target="store,update">
                                                {{ $record_id ? 'Update Record' : 'Save Record' }}
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

                .tt-panel {
                    background: #f8fafc;
                    border: 1px solid #e5e7eb;
                }

                .bg-clinical-dark {
                    background-color: #2c3e50;
                }

                .register-tabs .nav-link {
                    font-weight: 600;
                }

                .register-tabs .nav-link.active {
                    color: #7c2d12;
                    border-color: #fed7aa #fed7aa #fff;
                    background: #fff7ed;
                }

                .vax-entry-table th,
                .vax-entry-table td {
                    white-space: nowrap;
                    min-width: 110px;
                    text-align: center;
                    vertical-align: middle;
                }

                .aefi-codes-box {
                    border: 1px solid #fed7aa;
                    background: #fff7ed;
                    border-radius: 10px;
                    padding: 12px 14px;
                }

                .aefi-entry-table th,
                .aefi-entry-table td {
                    white-space: nowrap;
                    min-width: 120px;
                    vertical-align: middle;
                }
            </style>
        @endonce

        @push('scripts')
            <script>
                document.addEventListener('livewire:initialized', () => {
                    const modalElement = document.getElementById('immunizationModal');

                    Livewire.on('open-main-modal', () => {
                        const inst = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                        inst.show();
                    });

                    Livewire.on('close-modals', () => {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();
                    });

                });
            </script>
        @endpush
    @endif

    @include('_partials.datatables-init')
</div>













