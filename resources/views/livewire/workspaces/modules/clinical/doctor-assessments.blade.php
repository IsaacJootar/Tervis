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
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-note me-1'></i>Clinical Assessment (Essential Fields)</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Final Diagnosis</label><input type="text" class="form-control" wire:model="final_diagnosis" placeholder="Confirmed diagnosis"></div>
                                <div class="col-md-12">
                                    <label class="form-label">Assessment / Findings <span class="text-danger">*</span></label>
                                    <textarea class="form-control letter-box" rows="8" wire:model="assessment_note" placeholder="Summarize key findings and clinical impression."></textarea>
                                </div>
                                <div class="col-md-12"><label class="form-label">Management Plan</label><textarea class="form-control" rows="4" wire:model="management_plan" placeholder="Immediate and ongoing management plan."></textarea></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-test-tube me-1'></i>Prescribed Tests (Optional)</h6></div>
                        <div class="card-body">
                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-md-5"><label class="form-label">Test Name</label><input type="text" class="form-control" wire:model="test_entry_name" placeholder="e.g. FBC"></div>
                                <div class="col-md-5"><label class="form-label">Specimen</label><input type="text" class="form-control" wire:model="test_entry_specimen" placeholder="Blood"></div>
                                <div class="col-md-2 d-grid"><button type="button" class="btn btn-primary" wire:click="addTestOrder" wire:loading.attr="disabled" wire:target="addTestOrder">+</button></div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light"><tr><th>Test</th><th>Specimen</th><th>Action</th></tr></thead>
                                    <tbody>
                                        @forelse ($test_orders as $index => $entry)
                                            <tr>
                                                <td>{{ $entry['test_name'] ?? '-' }}</td>
                                                <td>{{ $entry['specimen'] ?? '-' }}</td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeTestOrder({{ $index }})">Remove</button></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-muted py-3">No tests prescribed.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-capsule me-1'></i>Medication Prescriptions (Optional)</h6></div>
                        <div class="card-body">
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-3"><label class="form-label">Drug Name</label><input type="text" class="form-control" wire:model="drug_entry_name" placeholder="e.g. Amoxicillin"></div>
                                <div class="col-md-2"><label class="form-label">Dosage</label><input type="text" class="form-control" wire:model="drug_entry_dosage" placeholder="500mg"></div>
                                <div class="col-md-2"><label class="form-label">Frequency</label><input type="text" class="form-control" wire:model="drug_entry_frequency" placeholder="TDS"></div>
                                <div class="col-md-2"><label class="form-label">Duration</label><input type="text" class="form-control" wire:model="drug_entry_duration" placeholder="5 days"></div>
                                <div class="col-md-3"><label class="form-label">Route</label><input type="text" class="form-control" wire:model="drug_entry_route" placeholder="Oral"></div>
                                <div class="col-md-5"><label class="form-label">Instructions</label><input type="text" class="form-control" wire:model="drug_entry_instructions" placeholder="After meals"></div>
                                <div class="col-md-3"><label class="form-label">Quantity Prescribed</label><input type="number" class="form-control" min="0" step="0.1" wire:model="drug_entry_quantity_prescribed" placeholder="10"></div>
                                <div class="col-md-4 d-grid"><button type="button" class="btn btn-primary" wire:click="addDrugOrder" wire:loading.attr="disabled" wire:target="addDrugOrder">Add Medication</button></div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light"><tr><th>Drug</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Route</th><th>Instructions</th><th>Qty</th><th>Action</th></tr></thead>
                                    <tbody>
                                        @forelse ($drug_orders as $index => $entry)
                                            <tr>
                                                <td>{{ $entry['drug_name'] ?? '-' }}</td>
                                                <td>{{ $entry['dosage'] ?? '-' }}</td>
                                                <td>{{ $entry['frequency'] ?? '-' }}</td>
                                                <td>{{ $entry['duration'] ?? '-' }}</td>
                                                <td>{{ $entry['route'] ?? '-' }}</td>
                                                <td>{{ $entry['instructions'] ?? '-' }}</td>
                                                <td>{{ $entry['quantity_prescribed'] ?? '-' }}</td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeDrugOrder({{ $index }})">Remove</button></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="8" class="text-center text-muted py-3">No medications prescribed.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info small">
                        <i class="bx bx-info-circle me-1"></i>
                        Any prescribed tests are routed to <strong>Tests & Laboratory</strong> and medications to <strong>Prescriptions & Drugs</strong> as pending orders.
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
                    <thead class="table-dark"><tr><th>Date</th><th>Final</th><th>Pending Tests</th><th>Pending Prescriptions</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr wire:key="assessment-record-{{ $record->id }}">
                                <td>{{ $record->visit_date?->format('M d, Y') }}</td>
                                <td>{{ $record->final_diagnosis ?: 'N/A' }}</td>
                                <td><span class="badge bg-label-primary">{{ $record->pending_tests_count }}</span></td>
                                <td><span class="badge bg-label-warning">{{ $record->pending_prescriptions_count }}</span></td>
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
</div>
