@php
    use Carbon\Carbon;
@endphp

@section('title', 'Family Planning Follow-Up')

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
        <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Family Planning Follow-Up</span></div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                    style="width:64px;height:64px;font-weight:700;">
                    {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class="bx bx-heart-circle me-1"></i>Client Follow-Up</h4>
                    <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                        <span class="badge bg-label-secondary">Patient: {{ $first_name }} {{ $last_name }}</span>
                        <span class="badge bg-label-info">{{ $patient_age ?? 'N/A' }} yrs / {{ $patient_gender ?? 'N/A' }}</span>
                        <span class="badge bg-label-dark">Checked In: {{ $activation_time }}</span>
                    </div>
                </div>
                <button wire:click="backToDashboard" type="button" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="backToDashboard">
                    <span wire:loading.remove wire:target="backToDashboard"><i class="bx bx-arrow-back me-1"></i>Back to Workspace</span>
                    <span wire:loading wire:target="backToDashboard"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
            </div>
        </div>

        @if (!$hasFamilyPlanningRegistration)
            <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                <div>
                    Family Planning registration has not been done for this patient yet. Complete one-time registration first in
                    <strong>Family Planning Register</strong>, then return here for follow-up visits.
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header bg-clinical-dark text-white">
                <h5 class="mb-0 text-white">{{ $record_id ? 'Edit Follow-Up Record' : 'Follow-Up Record' }}</h5>
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
                        <div class="card-header">
                            <h6 class="mb-0"><span class="badge bg-label-primary text-uppercase"><i class="bx bx-user me-1"></i>Registration Context</span></h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Registration Date</label>
                                    <input type="text" class="form-control bg-light" value="{{ $registration_date ? Carbon::parse($registration_date)->format('M d, Y') : 'N/A' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Current Method (From Registration)</label>
                                    <input type="text" class="form-control bg-light" value="{{ $registration_method ?: 'N/A' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Current Next Appointment</label>
                                    <input type="text" class="form-control bg-light" value="{{ $registration_next_appointment ? Carbon::parse($registration_next_appointment)->format('M d, Y') : 'N/A' }}" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Month Bucket</label>
                                    <input type="text" class="form-control bg-light" value="{{ $month_year ? Carbon::parse($month_year)->format('M Y') : 'N/A' }}" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><span class="badge bg-label-info text-uppercase"><i class="bx bx-calendar me-1"></i>Visit Details</span></h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Date</label>
                                    <input type="date" class="form-control" wire:model="visit_date" @disabled(!$hasFamilyPlanningRegistration)>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Next Appointment</label>
                                    <input type="date" class="form-control" wire:model="next_appointment_date" @disabled(!$hasFamilyPlanningRegistration)>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><span class="badge bg-label-warning text-uppercase"><i class="bx bx-list-check me-1"></i>Method</span></h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Method Change?</label>
                                    <select class="form-select" wire:model="method_change" @disabled(!$hasFamilyPlanningRegistration)>
                                        <option value="">Select</option>
                                        <option value="Y">Yes</option>
                                        <option value="N">No</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Method Supplied</label>
                                    <input type="text" class="form-control" wire:model="method_supplied" placeholder="e.g. Oral contraceptive" @disabled(!$hasFamilyPlanningRegistration)>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Brand / Size / Quality</label>
                                    <input type="text" class="form-control" wire:model="brand_size_quality" placeholder="e.g. Microgynon 30, 28 tabs" @disabled(!$hasFamilyPlanningRegistration)>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><span class="badge bg-label-success text-uppercase"><i class="bx bx-pulse me-1"></i>Vitals</span></h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Blood Pressure (B/P)</label>
                                    <input type="text" class="form-control" wire:model="blood_pressure" placeholder="e.g. 120/80 mmHg" @disabled(!$hasFamilyPlanningRegistration)>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Weight (KG)</label>
                                    <input type="number" step="0.01" class="form-control" wire:model="weight" placeholder="e.g. 62.5" @disabled(!$hasFamilyPlanningRegistration)>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Pelvic Exam Performed?</label>
                                    <select class="form-select" wire:model="pelvic_exam_performed" @disabled(!$hasFamilyPlanningRegistration)>
                                        <option value="">Select</option>
                                        <option value="Y">Yes</option>
                                        <option value="N">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><span class="badge bg-label-secondary text-uppercase"><i class="bx bx-notepad me-1"></i>Observations & Notes</span></h6>
                        </div>
                        <div class="card-body">
                            <label class="form-label">Observation, Lab Result, Treatment, Method Failure</label>
                            <textarea class="form-control" rows="4" wire:model="observation_notes" placeholder="Enter follow-up notes..." @disabled(!$hasFamilyPlanningRegistration)></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" wire:click="openCreate" wire:loading.attr="disabled" wire:target="openCreate">
                            <span wire:loading.remove wire:target="openCreate">Clear</span>
                            <span wire:loading wire:target="openCreate"><span class="spinner-border spinner-border-sm me-1"></span>Clearing...</span>
                        </button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store,update" @disabled(!$hasFamilyPlanningRegistration)>
                            <span wire:loading.remove wire:target="store,update">{{ $record_id ? 'Update Follow-Up' : 'Save Follow-Up' }}</span>
                            <span wire:loading wire:target="store,update"><span class="spinner-border spinner-border-sm me-1"></span>Processing...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Follow-Up Records <small class="text-muted">({{ $records->count() }} Total)</small></h5>
            </div>
            <div class="card-datatable table-responsive pt-0">
                <table id="familyPlanningFollowUpTable" class="table align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Visit Date</th>
                            <th>Next Appointment</th>
                            <th>Method Change</th>
                            <th>Method Supplied</th>
                            <th>B/P</th>
                            <th>Weight (KG)</th>
                            <th>Pelvic Exam</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr wire:key="fp-follow-up-{{ $record->id }}">
                                <td>{{ $record->visit_date?->format('M d, Y') }}</td>
                                <td>{{ $record->next_appointment_date?->format('M d, Y') ?: 'N/A' }}</td>
                                <td>{{ $record->method_change ?: 'N/A' }}</td>
                                <td>{{ $record->method_supplied ?: 'N/A' }}</td>
                                <td>{{ $record->blood_pressure ?: 'N/A' }}</td>
                                <td>{{ $record->weight ?: 'N/A' }}</td>
                                <td>{{ $record->pelvic_exam_performed ?: 'N/A' }}</td>
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
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No family planning follow-up records yet.</td>
                            </tr>
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

        .form-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: #64748b;
        }
    </style>
</div>

@include('_partials.datatables-init-multi', [
    'tableIds' => ['familyPlanningFollowUpTable'],
    'orders' => [
        'familyPlanningFollowUpTable' => [0, 'desc'],
    ],
])
