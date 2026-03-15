@php
    use Carbon\Carbon;
@endphp

@section('title', 'Referrals')

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
        <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Referrals</span></div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                    style="width:64px;height:64px;font-weight:700;">
                    {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class="bx bx-transfer me-1"></i>Patient Referral Form</h4>
                    <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                        <span class="badge bg-label-secondary">Patient: {{ $first_name }} {{ $last_name }}</span>
                        <span class="badge bg-label-info">{{ $patient_age ?? 'N/A' }} yrs / {{ $patient_gender ?? 'N/A' }}</span>
                    </div>
                </div>
                <button wire:click="backToDashboard" type="button" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="backToDashboard">
                    <span wire:loading.remove wire:target="backToDashboard"><i class="bx bx-arrow-back me-1"></i>Back to Workspace</span>
                    <span wire:loading wire:target="backToDashboard"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-clinical-dark text-white">
                <h5 class="mb-0 text-white">{{ $record_id ? 'Edit Referral Record' : 'Referral Record' }}</h5>
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
                        <div class="card-header"><h6 class="mb-0"><span class="badge bg-label-primary text-uppercase"><i class="bx bx-calendar me-1"></i>Referral Context</span></h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">Referral Date</label><input type="date" class="form-control" wire:model="referral_date"></div>
                                <div class="col-md-4"><label class="form-label">Month Bucket</label><input type="text" class="form-control bg-light" value="{{ $month_year ? Carbon::parse($month_year)->format('F Y') : 'N/A' }}" readonly></div>
                                <div class="col-md-4"><label class="form-label">Facility</label><input type="text" class="form-control bg-light" value="{{ $facility_name ?? 'N/A' }}" readonly></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0"><span class="badge bg-label-info text-uppercase"><i class="bx bx-share-alt me-1"></i>Referral Details</span></h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">Referred From</label><input type="text" class="form-control" wire:model="referred_from"></div>
                                <div class="col-md-4"><label class="form-label">Referred To</label><input type="text" class="form-control" wire:model="referred_to" placeholder="Destination facility"></div>
                                <div class="col-md-4"><label class="form-label">Requested Service (Code)</label><input type="text" class="form-control" wire:model="requested_service_code" placeholder="Service code(s)"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0"><span class="badge bg-label-warning text-uppercase"><i class="bx bx-check-square me-1"></i>Services Requested</span></h6></div>
                        <div class="card-body">
                            <div class="row g-2">
                                @foreach ($serviceOptions as $value => $label)
                                    @php
                                        $serviceId = 'service_' . $value;
                                        $isChecked = in_array($value, (array) $services_selected, true);
                                    @endphp
                                    <div class="col-md-4">
                                        <label for="{{ $serviceId }}" class="d-flex align-items-start gap-2 service-check w-100 {{ $isChecked ? 'service-check-active' : '' }}">
                                            <input id="{{ $serviceId }}" type="checkbox" wire:model="services_selected" value="{{ $value }}" class="form-check-input mt-1">
                                            <span>
                                                <span class="fw-semibold">{{ strtoupper($value) }}</span>
                                                <span class="text-muted"> - {{ $label }}</span>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @if (in_array('svc26', (array) $services_selected, true))
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Others (Specify)</label>
                                        <input type="text" class="form-control" wire:model="services_other" placeholder="Specify other service">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0"><span class="badge bg-label-success text-uppercase"><i class="bx bx-clipboard me-1"></i>Outcome & Transport</span></h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Service Provided?</label>
                                    <select class="form-select" wire:model="service_provided">
                                        <option value="">Select</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                                <div class="col-md-3"><label class="form-label">Date Completed</label><input type="date" class="form-control" wire:model="date_completed"></div>
                                <div class="col-md-3">
                                    <label class="form-label">Follow-up Needed?</label>
                                    <select class="form-select" wire:model="follow_up_needed">
                                        <option value="">Select</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Transport</label>
                                    <select class="form-select" wire:model="transport_mode">
                                        <option value="">Select</option>
                                        @foreach ($transportOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3"><label class="form-label">Time In</label><input type="time" class="form-control" wire:model="time_in"></div>
                                <div class="col-md-3"><label class="form-label">Time Out</label><input type="time" class="form-control" wire:model="time_out"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0"><span class="badge bg-label-secondary text-uppercase"><i class="bx bx-user-check me-1"></i>Authorization</span></h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3"><label class="form-label">Completed By</label><input type="text" class="form-control" wire:model="completed_by"></div>
                                <div class="col-md-3"><label class="form-label">Designation</label><input type="text" class="form-control" wire:model="completed_designation"></div>
                                <div class="col-md-3"><label class="form-label">Completed Date</label><input type="date" class="form-control" wire:model="completed_date"></div>
                                <div class="col-md-3"><label class="form-label">Referral Focal Person</label><input type="text" class="form-control" wire:model="focal_person"></div>
                                <div class="col-md-3"><label class="form-label">Focal Date</label><input type="date" class="form-control" wire:model="focal_date"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" wire:click="openCreate" wire:loading.attr="disabled" wire:target="openCreate">
                            <span wire:loading.remove wire:target="openCreate">Clear</span>
                            <span wire:loading wire:target="openCreate"><span class="spinner-border spinner-border-sm me-1"></span>Clearing...</span>
                        </button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store,update">
                            <span wire:loading.remove wire:target="store,update">{{ $record_id ? 'Update Referral' : 'Save Referral' }}</span>
                            <span wire:loading wire:target="store,update"><span class="spinner-border spinner-border-sm me-1"></span>Processing...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5 class="mb-0">Referral Records <small class="text-muted">({{ $records->count() }} Total)</small></h5></div>
            <div class="card-body p-0">
                <div class="card-datatable table-responsive pt-0">
                    <table id="referralRecordsTable" class="table align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Referred To</th>
                            <th>Service Provided</th>
                            <th>Follow-up</th>
                            <th>Services Count</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr wire:key="referral-record-{{ $record->id }}">
                                <td>{{ $record->referral_date?->format('M d, Y') }}</td>
                                <td>{{ $record->referred_to ?: 'N/A' }}</td>
                                <td>{{ $record->service_provided ?: 'N/A' }}</td>
                                <td>{{ $record->follow_up_needed ?: 'N/A' }}</td>
                                <td>{{ count((array) ($record->services_selected ?? [])) }}</td>
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
                            <tr><td colspan="6" class="text-center py-4 text-muted">No referral records yet.</td></tr>
                        @endforelse
                    </tbody>
                    </table>
                </div>
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

        .service-check {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px 10px;
            min-height: 56px;
            cursor: pointer;
            user-select: none;
            transition: border-color 0.15s ease, background-color 0.15s ease;
        }

        .service-check:hover {
            border-color: #93c5fd;
            background: #f8fafc;
        }

        .service-check-active {
            border-color: #2563eb;
            background: #eff6ff;
        }
    </style>
</div>

@include('_partials.datatables-init-multi', [
    'tableIds' => ['referralRecordsTable'],
    'orders' => [
        'referralRecordsTable' => [0, 'desc'],
    ],
])
