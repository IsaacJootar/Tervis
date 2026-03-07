@php
    use Carbon\Carbon;

    $toggleMeta = [
        'Positive' => ['class' => 'btn-outline-danger', 'text' => '+ Pos'],
        'Negative' => ['class' => 'btn-outline-success', 'text' => '- Neg'],
        'Reactive' => ['class' => 'btn-outline-danger', 'text' => 'Reactive'],
        'Non-Reactive' => ['class' => 'btn-outline-success', 'text' => 'Non-Reactive'],
        'N/A' => ['class' => 'btn-outline-secondary', 'text' => 'N/A'],
    ];

    $urinalysisLabels = [
        'colour' => 'Colour',
        'app' => 'APP (Appearance)',
        'ph' => 'pH',
        'glucose' => 'Glucose',
        'nitrite' => 'Nitrite',
        'protein' => 'Protein',
        'bilirubin' => 'Bilirubin',
        'urobilinogen' => 'Urobilinogen',
        'ascorbic' => 'Ascorbic',
        'specific_gravity' => 'Specific Gravity',
        'blood' => 'Blood',
        'others' => 'Others / Leukocytes',
    ];
@endphp

@section('title', 'Laboratory Register')

<div class="lab-register-page">
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
        <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Laboratory</span></div>

        <div class="card mb-4 border-primary-subtle">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width:64px;height:64px;font-weight:700;">
                    {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class='bx bx-test-tube me-1'></i>Laboratory Request / Report Form</h4>
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
                <h5 class="mb-0 text-white">{{ $record_id ? 'Edit Laboratory Record' : 'Laboratory Register' }}</h5>
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

                    @if (($pendingTestOrders ?? collect())->count())
                        <div class="card mb-3 border-warning-subtle">
                            <div class="card-header bg-label-warning"><h6 class="mb-0"><i class='bx bx-list-check me-1'></i>Pending Requested Tests from Doctor Assessment</h6></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 48px;">Do</th>
                                                <th>Test</th>
                                                <th>Specimen</th>
                                                <th>Priority</th>
                                                <th>Requested By</th>
                                                <th>Instructions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($pendingTestOrders as $order)
                                                <tr wire:key="pending-test-order-{{ $order->id }}">
                                                    <td>
                                                        <input class="form-check-input" type="checkbox" value="{{ $order->id }}" wire:model="selected_test_order_ids">
                                                    </td>
                                                    <td class="fw-semibold">{{ $order->test_name }}</td>
                                                    <td>{{ $order->specimen ?: '-' }}</td>
                                                    <td>
                                                        <span class="badge bg-label-{{ $order->priority === 'STAT' ? 'danger' : ($order->priority === 'Urgent' ? 'warning' : 'primary') }}">{{ $order->priority }}</span>
                                                    </td>
                                                    <td>{{ $order->requested_by ?: 'N/A' }}<br><small class="text-muted">{{ $order->requested_at?->format('d M Y, h:i A') ?: '' }}</small></td>
                                                    <td>{{ $order->instructions ?: '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer small text-muted">
                                Selected tests will be marked <strong>completed</strong> after saving this laboratory record.
                            </div>
                        </div>
                    @endif

                    <div class="card mb-3">
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-user me-1'></i>Patient Information</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">Surname</label><input type="text" class="form-control" value="{{ $last_name }}" readonly></div>
                                <div class="col-md-5"><label class="form-label">Other Name(s)</label><input type="text" class="form-control" value="{{ trim($first_name . ' ' . $middle_name) }}" readonly></div>
                                <div class="col-md-3"><label class="form-label">Lab No.</label><input type="text" class="form-control" wire:model="lab_no" placeholder="e.g. LAB/2026/0042"></div>
                                <div class="col-md-4"><label class="form-label">Specimen(s)</label><input type="text" class="form-control" wire:model="specimen" placeholder="e.g. Blood, Urine, Stool"></div>
                                <div class="col-md-5"><label class="form-label">Clinician Diagnosis</label><input type="text" class="form-control" wire:model="clinician_diagnosis" placeholder="e.g. Typhoid Fever / UTI"></div>
                                <div class="col-md-3"><label class="form-label">Date</label><input type="date" class="form-control" wire:model="visit_date"></div>
                                <div class="col-md-4"><label class="form-label">Age / Sex</label><input type="text" class="form-control" wire:model="age_sex" placeholder="e.g. 34yrs / M"></div>
                                <div class="col-md-8"><label class="form-label">Examination</label><input type="text" class="form-control" wire:model="examination" placeholder="e.g. FBC, Widal, Urinalysis, MCS"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-vial me-1'></i>Reports</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3"><label class="form-label">FBS (mg/dL)</label><input class="form-control" wire:model="report_values.fbs" placeholder="e.g. 95 mg/dL (Fasting Normal: 70-110)"></div>
                                <div class="col-md-3"><label class="form-label">RBS (mg/dL)</label><input class="form-control" wire:model="report_values.rbs" placeholder="e.g. 130 mg/dL (Random Normal: <140)"></div>
                                <div class="col-md-3"><label class="form-label">PCV (%)</label><input class="form-control" wire:model="report_values.pcv" placeholder="e.g. 37% (Normal: M 40-54%, F 36-48%)"></div>
                                <div class="col-md-3"><label class="form-label">Hb (g/dL)</label><input class="form-control" wire:model="report_values.hb" placeholder="e.g. 11.2 g/dL"></div>
                                <div class="col-md-4">
                                    <label class="form-label">Mp (Malaria)</label>
                                    <div class="btn-group w-100" role="group">
                                        @foreach ($reportToggleFields['mp'] as $value)
                                            @php($meta = $toggleMeta[$value])
                                            <button type="button" class="btn btn-sm {{ $meta['class'] }} {{ data_get($report_values, 'mp') === $value ? 'selected-choice' : '' }}" style="{{ data_get($report_values, 'mp') === $value ? 'font-weight:900;border-width:2px;box-shadow:0 0 0 3px rgba(15,23,42,.18);transform:scale(1.04);' : '' }}"
                                                wire:click="setSelection('report_values', 'mp', '{{ $value }}')">
                                                @if (data_get($report_values, 'mp') === $value)<i class='bx bx-check me-1'></i>@endif{{ $meta['text'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-4"><label class="form-label">TWBC (IMM)</label><input class="form-control" wire:model="report_values.twbc" placeholder="e.g. 8,400 /mm3"></div>
                                <div class="col-md-2"><label class="form-label">Diff. N (%)</label><input class="form-control" wire:model="report_values.diff_n" placeholder="e.g. 65%"></div>
                                <div class="col-md-2"><label class="form-label">E (%)</label><input class="form-control" wire:model="report_values.diff_e" placeholder="e.g. 4%"></div>
                                <div class="col-md-2"><label class="form-label">B (%)</label><input class="form-control" wire:model="report_values.diff_b" placeholder="e.g. 1%"></div>
                                <div class="col-md-2"><label class="form-label">L (%)</label><input class="form-control" wire:model="report_values.diff_l" placeholder="e.g. 30%"></div>
                                <div class="col-md-2"><label class="form-label">ESR (/Hr)</label><input class="form-control" wire:model="report_values.esr" placeholder="e.g. 22 /Hr"></div>
                                <div class="col-md-4">
                                    <label class="form-label">Pregnancy Test</label>
                                    <div class="btn-group w-100" role="group">
                                        @foreach ($reportToggleFields['preg'] as $value)
                                            @php($meta = $toggleMeta[$value])
                                            <button type="button" class="btn btn-sm {{ $meta['class'] }} {{ data_get($report_values, 'preg') === $value ? 'selected-choice' : '' }}" style="{{ data_get($report_values, 'preg') === $value ? 'font-weight:900;border-width:2px;box-shadow:0 0 0 3px rgba(15,23,42,.18);transform:scale(1.04);' : '' }}"
                                                wire:click="setSelection('report_values', 'preg', '{{ $value }}')">
                                                @if (data_get($report_values, 'preg') === $value)<i class='bx bx-check me-1'></i>@endif{{ $meta['text'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-4"><label class="form-label">Blood Group</label><select class="form-select" wire:model="report_values.blood_group"><option value="">- Group</option>@foreach ($bloodGroupOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                                <div class="col-md-2"><label class="form-label">RhD</label><select class="form-select" wire:model="report_values.rhd"><option value="">RhD</option>@foreach ($rhdOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                                <div class="col-md-2"><label class="form-label">HB Genotype</label><select class="form-select" wire:model="report_values.hb_geno"><option value="">Select</option>@foreach ($hbGenotypeOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-flask me-1'></i>WIDAL Test</h6></div>
                        <div class="card-body">
                            <p class="text-muted small">Enter titre values e.g. 1/80, 1/160, 1/40 or leave blank if not done.</p>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light"><tr><th>Antigen</th><th>a</th><th>b</th><th>c</th><th>d</th></tr></thead>
                                    <tbody>
                                        <tr><td>O</td><td><input class="form-control form-control-sm" wire:model="widal_values.Oa" placeholder="e.g. 1/80"></td><td><input class="form-control form-control-sm" wire:model="widal_values.Ob" placeholder="e.g. 1/80"></td><td><input class="form-control form-control-sm" wire:model="widal_values.Oc" placeholder="e.g. 1/80"></td><td><input class="form-control form-control-sm" wire:model="widal_values.Od" placeholder="e.g. 1/80"></td></tr>
                                        <tr><td>H</td><td><input class="form-control form-control-sm" wire:model="widal_values.Ha" placeholder="e.g. 1/80"></td><td><input class="form-control form-control-sm" wire:model="widal_values.Hb" placeholder="e.g. 1/80"></td><td><input class="form-control form-control-sm" wire:model="widal_values.Hc" placeholder="e.g. 1/80"></td><td><input class="form-control form-control-sm" wire:model="widal_values.Hd" placeholder="e.g. 1/80"></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-lg-6">
                            <div class="card h-100 mb-0">
                                <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-atom me-1'></i>Stool Analysis</h6></div>
                                <div class="card-body">
                                    <div class="mb-2"><label class="form-label">APP</label><input class="form-control" wire:model="stool_values.app" placeholder="e.g. Brown, Formed"></div>
                                    <div class="mb-2"><label class="form-label">Microscopy</label><input class="form-control" wire:model="stool_values.micro" placeholder="e.g. Ova of Ascaris seen"></div>
                                    <label class="form-label">Stool U/S</label>
                                    <div class="btn-group w-100" role="group">
                                        @foreach ($mcsToggleFields['rvs'] as $value)
                                            @php($meta = $toggleMeta[$value])
                                            <button type="button" class="btn btn-sm {{ $meta['class'] }} {{ data_get($stool_values, 'us') === $value ? 'selected-choice' : '' }}" style="{{ data_get($stool_values, 'us') === $value ? 'font-weight:900;border-width:2px;box-shadow:0 0 0 3px rgba(15,23,42,.18);transform:scale(1.04);' : '' }}"
                                                wire:click="setSelection('stool_values', 'us', '{{ $value }}')">
                                                @if (data_get($stool_values, 'us') === $value)<i class='bx bx-check me-1'></i>@endif{{ $meta['text'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card h-100 mb-0">
                                <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-dna me-1'></i>M/C/S Tests</h6></div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        @foreach ($mcsLabels as $field => $label)
                                            <div class="col-12">
                                                <div class="border rounded p-2">
                                                    <div class="small text-muted text-uppercase fw-semibold">{{ $label }}</div>
                                                    <div class="btn-group w-100 mt-1" role="group">
                                                        @foreach ($mcsToggleFields[$field] as $value)
                                                            @php($meta = $toggleMeta[$value])
                                                            <button type="button" class="btn btn-sm {{ $meta['class'] }} {{ data_get($mcs_results, $field) === $value ? 'selected-choice' : '' }}" style="{{ data_get($mcs_results, $field) === $value ? 'font-weight:900;border-width:2px;box-shadow:0 0 0 3px rgba(15,23,42,.18);transform:scale(1.04);' : '' }}"
                                                                wire:click="setSelection('mcs_results', '{{ $field }}', '{{ $value }}')">
                                                                @if (data_get($mcs_results, $field) === $value)<i class='bx bx-check me-1'></i>@endif{{ $meta['text'] }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-vial me-1'></i>Urinalysis</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach ($urinalysisLabels as $field => $label)
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label">{{ $label }}</label>
                                        @if (isset($urinalysisSelectOptions[$field]))
                                            <select class="form-select" wire:model="urinalysis_results.{{ $field }}">
                                                <option value="">-</option>
                                                @foreach ($urinalysisSelectOptions[$field] as $option)
                                                    <option value="{{ $option }}">{{ $option }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input class="form-control" wire:model="urinalysis_results.{{ $field }}"
                                                placeholder="{{ in_array($field, ['ph','specific_gravity']) ? 'Enter measured value' : 'Enter observations' }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-search-alt-2 me-1'></i>Microscopy</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach ($microscopyLabels as $field => $label)
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label">{{ $label }}</label>
                                        <div class="btn-group w-100" role="group">
                                            @foreach ($microscopyOptions[$field] as $value)
                                                <button type="button" class="btn btn-sm btn-outline-warning {{ data_get($microscopy_results, $field) === $value ? 'selected-choice' : '' }}" style="{{ data_get($microscopy_results, $field) === $value ? 'font-weight:900;border-width:2px;box-shadow:0 0 0 3px rgba(15,23,42,.18);transform:scale(1.04);' : '' }}"
                                                    wire:click="setSelection('microscopy_results', '{{ $field }}', '{{ $value }}')">
                                                    @if (data_get($microscopy_results, $field) === $value)<i class='bx bx-check me-1'></i>@endif{{ $value }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                                <div class="col-md-3 col-sm-6"><div class="h-100 d-flex flex-column justify-content-end"><label class="form-label">Others</label><input class="form-control" wire:model="microscopy_results.others" placeholder="e.g. Mucus threads"></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-capsule me-1'></i>Sensitivity</h6></div>
                        <div class="card-body">
                            <p class="small text-muted mb-2"><span class="badge bg-label-success">S = Sensitive</span> <span class="badge bg-label-danger">R = Resistant</span> <span class="badge bg-label-warning">I = Intermediate</span></p>
                            <div class="row g-3">
                                @foreach ($sensitivityLabels as $field => $label)
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label">{{ $label }}</label>
                                        <div class="btn-group w-100" role="group">
                                            @foreach ($sensitivityValues as $value)
                                                @php($btnClass = $value === 'S' ? 'btn-outline-success' : ($value === 'R' ? 'btn-outline-danger' : 'btn-outline-warning'))
                                                <button type="button" class="btn btn-sm {{ $btnClass }} {{ data_get($sensitivity_results, $field) === $value ? 'selected-choice' : '' }}" style="{{ data_get($sensitivity_results, $field) === $value ? 'font-weight:900;border-width:2px;box-shadow:0 0 0 3px rgba(15,23,42,.18);transform:scale(1.04);' : '' }}"
                                                    wire:click="setSelection('sensitivity_results', '{{ $field }}', '{{ $value }}')">
                                                    @if (data_get($sensitivity_results, $field) === $value)<i class='bx bx-check me-1'></i>@endif{{ $value }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                                <div class="col-md-6"><label class="form-label">Others</label><input class="form-control" wire:model="sensitivity_results.others" placeholder="e.g. Augmentin - S, Ceftriaxone - S"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0"><i class='bx bx-edit-alt me-1'></i>Comment & Sign-off</h6></div>
                        <div class="card-body">
                            <div class="mb-2"><label class="form-label">Comment</label><textarea class="form-control" rows="3" wire:model="comment" placeholder="e.g. Significant Widal titre 1:160. Suggestive of Typhoid Fever."></textarea></div>
                            <div class="row g-3">
                                <div class="col-md-8"><label class="form-label">MLT Sign</label><input class="form-control" wire:model="mlt_sign" placeholder="e.g. Ibrahim Usman MLT"></div>
                                <div class="col-md-4"><label class="form-label">Date</label><input type="date" class="form-control" wire:model="sign_date"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" wire:click="openCreate" wire:loading.attr="disabled" wire:target="openCreate">
                            <span wire:loading.remove wire:target="openCreate"><i class='bx bx-trash me-1'></i>Clear</span>
                            <span wire:loading wire:target="openCreate"><span class="spinner-border spinner-border-sm me-1"></span>Clearing...</span>
                        </button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store,update">
                            <span wire:loading.remove wire:target="store,update"><i class='bx bx-save me-1'></i>{{ $record_id ? 'Update Record' : 'Save Record' }}</span>
                            <span wire:loading wire:target="store,update"><span class="spinner-border spinner-border-sm me-1"></span>Processing...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5 class="mb-0">Laboratory Records <small class="text-muted">({{ count($records) }} Total)</small></h5></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr><th>Visit Date</th><th>Lab No.</th><th>Specimen</th><th>Diagnosis</th><th>Sign-off</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr wire:key="lab-record-{{ $record->id }}">
                                <td>{{ $record->visit_date?->format('M d, Y') }}</td>
                                <td>{{ $record->lab_no ?: 'N/A' }}</td>
                                <td>{{ $record->specimen ?: 'N/A' }}</td>
                                <td>{{ $record->clinician_diagnosis ?: 'N/A' }}</td>
                                <td>{{ $record->mlt_sign ?: 'N/A' }}</td>
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
                            <tr><td colspan="6" class="text-center py-4 text-muted"><i class="bx bx-info-circle me-1"></i>No laboratory records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@once
    <style>
        .lab-register-page {
            --lab-dark: #2c3e50;
            --lab-soft: #fff7ed;
            --lab-border: #fed7aa;
            --lab-muted: #64748b;
        }

        .lab-register-page .card {
            border-color: var(--lab-border);
        }

        .lab-register-page .card-header.bg-clinical-dark {
            background-color: #2c3e50 !important;
            border-bottom: 1px solid #223242;
        }

        .lab-register-page .card .card-header.bg-label-primary {
            background-color: #ffedd5 !important;
            color: #9a3412 !important;
            border-bottom: 1px solid #fdba74 !important;
        }

        .lab-register-page .card-header h6 {
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .lab-register-page .card .card-header.bg-label-primary h6 {
            color: #7c2d12 !important;
            font-weight: 700;
        }
        .lab-register-page .form-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
            color: var(--lab-muted);
            margin-bottom: 0.35rem;
        }

        .lab-register-page .form-control,
        .lab-register-page .form-select {
            border-width: 1.5px;
        }

        .lab-register-page .form-control:focus,
        .lab-register-page .form-select:focus {
            border-color: #fdba74;
            box-shadow: 0 0 0 0.2rem rgba(249, 115, 22, 0.14);
        }

        .lab-register-page .btn-group .btn {
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 0.01em;
            border-width: 1.5px;
        }

        .lab-register-page .btn-group .btn.active {
            font-weight: 900;
            letter-spacing: 0.03em;
            border-width: 2px;
            box-shadow: inset 0 0 0 2px currentColor, 0 0 0 0.15rem rgba(15, 23, 42, 0.12);
        }

        .lab-register-page .btn-group .btn.active i {
            font-weight: 900;
        }

        .lab-register-page .btn-group .btn.selected-choice {
            font-weight: 900 !important;
            border-width: 2px !important;
            letter-spacing: 0.02em;
            box-shadow: 0 0 0 0.18rem rgba(15, 23, 42, 0.14);
            transform: scale(1.03);
        }

        .lab-register-page .table thead.table-dark th {
            background: #2c3e50;
            border-color: #223242;
        }

        .lab-register-page .table thead.table-light th {
            background: #fff7ed;
            color: #7c2d12;
        }

        .lab-register-page .table td,
        .lab-register-page .table th {
            vertical-align: middle;
        }

        .lab-register-page .rounded-circle.bg-primary {
            box-shadow: 0 10px 22px rgba(29, 78, 216, 0.25);
        }

        @media (max-width: 767.98px) {
            .lab-register-page .card-body {
                padding: 0.9rem;
            }

            .lab-register-page .btn-group {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
    </style>
@endonce




