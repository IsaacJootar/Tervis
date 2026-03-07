@php
    use Carbon\Carbon;
@endphp

@section('title', 'Doctor Assessments')

<div>
    @if (!$hasAccess)
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body text-center py-5">
                        <div class="mb-4"><i class="bx bx-error-circle text-danger" style="font-size: 5rem;"></i></div>
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
        <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Doctor Assessments</span></div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width:64px;height:64px;font-weight:700;">
                    {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class='bx bx-stethoscope me-1'></i>Clinical Assessment & Findings</h4>
                    <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                        <span class="badge bg-label-secondary">Patient: {{ $first_name }} {{ $last_name }}</span>
                    </div>
                </div>
                <button wire:click="backToDashboard" type="button" class="btn btn-primary" wire:loading.attr="disabled" wire:target="backToDashboard">
                    <span wire:loading.remove wire:target="backToDashboard"><i class="bx bx-arrow-back me-1"></i>Back to Workspace</span>
                    <span wire:loading wire:target="backToDashboard"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-clinical-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">{{ $record_id ? 'Edit Doctor Assessment' : 'Doctor Assessment' }}</h5>
                <button type="button" class="btn btn-sm btn-outline-light" wire:click="openCreate" wire:loading.attr="disabled" wire:target="openCreate">
                    <span wire:loading.remove wire:target="openCreate">New Entry</span>
                    <span wire:loading wire:target="openCreate"><span class="spinner-border spinner-border-sm me-1"></span>Preparing...</span>
                </button>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="{{ $record_id ? 'update' : 'store' }}">
                    @csrf

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong>Validation errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="card mb-3">
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-calendar me-1'></i>Visit Context</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">Visit Date</label><input type="date" class="form-control" wire:model="visit_date"></div>
                                <div class="col-md-4"><label class="form-label">Month Bucket</label><input type="text" class="form-control bg-light" value="{{ $month_year ? Carbon::parse($month_year)->format('F Y') : 'N/A' }}" readonly></div>
                                <div class="col-md-4"><label class="form-label">Patient Age / Sex</label><input type="text" class="form-control bg-light" value="{{ $patient_age ?? 'N/A' }} yrs / {{ strtoupper(substr((string) $patient_gender, 0, 1)) ?: 'N/A' }}" readonly></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-note me-1'></i>Clinical Notes (Letter Style)</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Chief Complaints</label><textarea class="form-control" rows="4" wire:model="chief_complaints" placeholder="Main complaints at presentation"></textarea></div>
                                <div class="col-md-6"><label class="form-label">History of Present Illness</label><textarea class="form-control" rows="4" wire:model="history_of_present_illness" placeholder="History, onset, duration, associated symptoms"></textarea></div>
                                <div class="col-md-6"><label class="form-label">Vital Signs</label><textarea class="form-control" rows="3" wire:model="vital_signs" placeholder="BP, Pulse, Temp, RR, SpO2"></textarea></div>
                                <div class="col-md-6"><label class="form-label">Physical Examination</label><textarea class="form-control" rows="3" wire:model="physical_examination" placeholder="General and systemic examination"></textarea></div>
                                <div class="col-md-12"><label class="form-label">Clinical Findings</label><textarea class="form-control" rows="4" wire:model="clinical_findings" placeholder="Objective findings, interpretation"></textarea></div>
                                <div class="col-md-6"><label class="form-label">Provisional Diagnosis</label><input type="text" class="form-control" wire:model="provisional_diagnosis" placeholder="Initial diagnosis"></div>
                                <div class="col-md-6"><label class="form-label">Final Diagnosis</label><input type="text" class="form-control" wire:model="final_diagnosis" placeholder="Confirmed diagnosis"></div>
                                <div class="col-md-12">
                                    <label class="form-label">Assessment / Findings Letter <span class="text-danger">*</span></label>
                                    <textarea class="form-control letter-box" rows="10" wire:model="assessment_note" placeholder="Write detailed narrative assessment in letter format:
- Patient presented with...
- Clinical findings indicate...
- Impression...
- Plan..."></textarea>
                                </div>
                                <div class="col-md-6"><label class="form-label">Management Plan</label><textarea class="form-control" rows="4" wire:model="management_plan" placeholder="Immediate and ongoing plan"></textarea></div>
                                <div class="col-md-6"><label class="form-label">Follow-up Instructions</label><textarea class="form-control" rows="4" wire:model="follow_up_instructions" placeholder="When to return, monitoring advice"></textarea></div>
                                <div class="col-md-6"><label class="form-label">Advice to Patient / Guardian</label><textarea class="form-control" rows="3" wire:model="advice_to_patient" placeholder="Counselling and home advice"></textarea></div>
                                <div class="col-md-6"><label class="form-label">Referral Note</label><textarea class="form-control" rows="3" wire:model="referral_note" placeholder="Referral reason and destination (if any)"></textarea></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-test-tube me-1'></i>Prescribed Tests (Optional)</h6></div>
                        <div class="card-body">
                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-md-3"><label class="form-label">Test Name</label><input type="text" class="form-control" wire:model="test_entry_name" placeholder="e.g. FBC"></div>
                                <div class="col-md-2"><label class="form-label">Specimen</label><input type="text" class="form-control" wire:model="test_entry_specimen" placeholder="Blood"></div>
                                <div class="col-md-2"><label class="form-label">Priority</label><select class="form-select" wire:model="test_entry_priority">@foreach ($priorityOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach</select></div>
                                <div class="col-md-4"><label class="form-label">Instructions</label><input type="text" class="form-control" wire:model="test_entry_instructions" placeholder="Fasting, timing, remarks"></div>
                                <div class="col-md-1 d-grid"><button type="button" class="btn btn-primary" wire:click="addTestOrder" wire:loading.attr="disabled" wire:target="addTestOrder">+</button></div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light"><tr><th>Test</th><th>Specimen</th><th>Priority</th><th>Instructions</th><th>Action</th></tr></thead>
                                    <tbody>
                                        @forelse ($test_orders as $index => $entry)
                                            <tr>
                                                <td>{{ $entry['test_name'] ?? '-' }}</td>
                                                <td>{{ $entry['specimen'] ?? '-' }}</td>
                                                <td><span class="badge bg-label-{{ ($entry['priority'] ?? 'Routine') === 'STAT' ? 'danger' : (($entry['priority'] ?? 'Routine') === 'Urgent' ? 'warning' : 'primary') }}">{{ $entry['priority'] ?? 'Routine' }}</span></td>
                                                <td>{{ $entry['instructions'] ?? '-' }}</td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeTestOrder({{ $index }})">Remove</button></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-3">No tests prescribed.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info small">
                        <i class="bx bx-info-circle me-1"></i>
                        Any prescribed tests are routed to <strong>Tests & Laboratory</strong> as pending requests.
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" wire:click="openCreate" wire:loading.attr="disabled" wire:target="openCreate">
                            <span wire:loading.remove wire:target="openCreate">Clear</span>
                            <span wire:loading wire:target="openCreate"><span class="spinner-border spinner-border-sm me-1"></span>Clearing...</span>
                        </button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store,update">
                            <span wire:loading.remove wire:target="store,update">{{ $record_id ? 'Update Assessment' : 'Save Assessment' }}</span>
                            <span wire:loading wire:target="store,update"><span class="spinner-border spinner-border-sm me-1"></span>Processing...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5 class="mb-0">Assessment Records <small class="text-muted">({{ count($records) }} Total)</small></h5></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark"><tr><th>Date</th><th>Provisional</th><th>Final</th><th>Pending Tests</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr wire:key="assessment-record-{{ $record->id }}">
                                <td>{{ $record->visit_date?->format('M d, Y') }}</td>
                                <td>{{ $record->provisional_diagnosis ?: 'N/A' }}</td>
                                <td>{{ $record->final_diagnosis ?: 'N/A' }}</td>
                                <td><span class="badge bg-label-primary">{{ $record->pending_tests_count }}</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light text-dark border" wire:click="edit({{ $record->id }})" wire:loading.attr="disabled" wire:target="edit({{ $record->id }})">
                                            <span wire:loading.remove wire:target="edit({{ $record->id }})">Edit</span>
                                            <span wire:loading wire:target="edit({{ $record->id }})"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light text-dark border" wire:click="delete({{ $record->id }})" wire:loading.attr="disabled" wire:target="delete({{ $record->id }})">
                                            <span wire:loading.remove wire:target="delete({{ $record->id }})">Delete</span>
                                            <span wire:loading wire:target="delete({{ $record->id }})"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No doctor assessments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@once
    <style>
        .bg-clinical-dark {
            background-color: #2c3e50 !important;
        }

        .letter-box {
            font-family: "Times New Roman", Georgia, serif;
            line-height: 1.55;
            font-size: 0.98rem;
        }

        .form-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: #64748b;
        }
    </style>
@endonce