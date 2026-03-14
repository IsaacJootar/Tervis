@php
    use Carbon\Carbon;
@endphp

@section('title', 'Vaccination Schedule')

<div>
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
        <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
            <button wire:click="backToImmunizations" type="button" class="btn btn-outline-primary" wire:loading.attr="disabled" wire:target="backToImmunizations">
                <span wire:loading.remove wire:target="backToImmunizations"><i class="bx bx-injection me-1"></i>Back to Immunizations</span>
                <span wire:loading wire:target="backToImmunizations"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
            </button>
            <button wire:click="backToDashboard" type="button" class="btn btn-primary" wire:loading.attr="disabled" wire:target="backToDashboard">
                <span wire:loading.remove wire:target="backToDashboard"><i class="bx bx-arrow-back me-1"></i>Back to Workspace</span>
                <span wire:loading wire:target="backToDashboard"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
            </button>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-clinical-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">{{ $record_id ? 'Edit Vaccination Schedule' : 'Vaccination Schedule' }}</h5>
                @if ($record_id)
                    <button wire:click="exit" type="button" class="btn btn-sm btn-outline-light" wire:loading.attr="disabled" wire:target="exit">
                        <span wire:loading.remove wire:target="exit">New Entry</span>
                        <span wire:loading wire:target="exit"><span class="spinner-border spinner-border-sm me-1"></span>Resetting...</span>
                    </button>
                @endif
            </div>
            <div class="card-body px-3 px-lg-4">
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

                    <ul class="nav nav-tabs register-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ $active_tab === 'child' ? 'active' : '' }}" wire:click="setActiveTab('child')">
                                <span aria-hidden="true">&#128100;</span> Child Info
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ $active_tab === 'vaccination' ? 'active' : '' }}" wire:click="setActiveTab('vaccination')">
                                <span aria-hidden="true">&#128137;</span> Vaccinations
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ $active_tab === 'weight' ? 'active' : '' }}" wire:click="setActiveTab('weight')">
                                <span aria-hidden="true">&#9878;</span> Weight Monitoring
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ $active_tab === 'breastfeeding' ? 'active' : '' }}" wire:click="setActiveTab('breastfeeding')">
                                <span aria-hidden="true">&#129329;</span> Breastfeeding
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ $active_tab === 'aefi' ? 'active' : '' }}" wire:click="setActiveTab('aefi')">
                                <span aria-hidden="true">&#9888;</span> AEFI
                            </button>
                        </li>
                    </ul>

                    <div class="card mb-3">
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
                                            <option value="{{ $child->id }}">{{ $child->full_name ?: 'Child #' . $child->linked_child_id }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Month Bucket</label>
                                    <input type="text" class="form-control bg-light" value="{{ $month_year ? \Carbon\Carbon::parse($month_year)->format('F Y') : 'N/A' }}" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="register-section {{ $active_tab === 'child' ? 'active' : '' }}">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><span class="badge bg-label-primary text-uppercase">Child Information</span></h6>
                            </div>
                            <div class="card-body">
                                <div class="child-info-grid">
                                    <div class="info-tile"><span class="info-label">Child Name</span><span class="info-value">{{ $currentChild?->full_name ?: 'N/A' }}</span></div>
                                    <div class="info-tile"><span class="info-label">Linked Child ID</span><span class="info-value">{{ $currentChild?->linked_child_id ?: 'N/A' }}</span></div>
                                    <div class="info-tile"><span class="info-label">Date of Birth</span><span class="info-value">{{ $currentChild?->formatted_date_of_birth ?? 'N/A' }}</span></div>
                                    <div class="info-tile"><span class="info-label">Age</span><span class="info-value">{{ $currentChild?->age_display ?? 'N/A' }}</span></div>
                                    <div class="info-tile"><span class="info-label">Gender</span><span class="info-value">{{ $currentChild?->gender ?? 'N/A' }}</span></div>
                                    <div class="info-tile"><span class="info-label">Birth Weight</span><span class="info-value">{{ $currentChild?->birth_weight ? $currentChild->birth_weight . ' kg' : 'N/A' }}</span></div>
                                    <div class="info-tile"><span class="info-label">Patient/Guardian Name</span><span class="info-value">{{ trim(implode(' ', array_filter([$first_name, $middle_name, $last_name]))) ?: 'N/A' }}</span></div>
                                    <div class="info-tile"><span class="info-label">Mother Phone</span><span class="info-value">{{ $patient_phone ?: 'N/A' }}</span></div>
                                    <div class="info-tile"><span class="info-label">DIN</span><span class="info-value">{{ $patient_din ?: 'N/A' }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="register-section {{ $active_tab === 'vaccination' ? 'active' : '' }}">
                        @php
                            $completedVaccines = collect($vaccination_dates)->filter(fn($v) => !empty($v))->count();
                            $totalVaccines = count($vaccineSchedule);
                            $progressPct = $totalVaccines > 0 ? (int) round(($completedVaccines / $totalVaccines) * 100) : 0;
                        @endphp
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Vaccination Progress</h6>
                                    <span class="badge bg-label-success">{{ $completedVaccines }} / {{ $totalVaccines }}</span>
                                </div>
                                <div class="progress" style="height: 8px;"><div class="progress-bar" role="progressbar" style="width: {{ $progressPct }}%"></div></div>
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0"><span class="badge bg-label-info text-uppercase">Routine Immunization Schedule</span></h6></div>
                            <div class="card-body p-0">
                                <div class="card-datatable table-responsive pt-0">
                                    <table class="table">
                                        <thead class="table-dark"><tr><th>Antigen</th><th>Age</th><th>Date Given</th><th>Status</th><th>Notes</th></tr></thead>
                                        <tbody>
                                            @foreach ($vaccineSchedule as $vaccine)
                                                @php $vaccineDate = $vaccination_dates[$vaccine['id']] ?? null; @endphp
                                                <tr>
                                                    <td class="fw-semibold">{{ $vaccine['name'] }}</td>
                                                    <td>{{ $vaccine['age'] }}</td>
                                                    <td><input type="date" class="form-control form-control-sm" wire:model.live="vaccination_dates.{{ $vaccine['id'] }}"></td>
                                                    <td>@if (!empty($vaccineDate))<span class="badge bg-label-success">Done</span>@else<span class="badge bg-label-warning">Pending</span>@endif</td>
                                                    <td><input type="text" class="form-control form-control-sm" placeholder="Batch no / remarks" wire:model.live="vaccination_notes.{{ $vaccine['id'] }}"></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="register-section {{ $active_tab === 'weight' ? 'active' : '' }}">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0"><span class="badge bg-label-primary text-uppercase">Add Weight Measurement</span></h6></div>
                            <div class="card-body">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3"><label class="form-label">Date of Visit</label><input type="date" class="form-control" wire:model="weight_entry_date"></div>
                                    <div class="col-md-3"><label class="form-label">Age (months)</label><input type="number" class="form-control" min="0" max="60" step="0.1" wire:model="weight_entry_age_months"></div>
                                    <div class="col-md-3"><label class="form-label">Weight (kg)</label><input type="number" class="form-control" min="0.5" max="40" step="0.1" wire:model="weight_entry_kg"></div>
                                    <div class="col-md-3 d-grid"><button type="button" class="btn btn-primary" wire:click="addWeightEntry" wire:loading.attr="disabled" wire:target="addWeightEntry">
                                        <span wire:loading.remove wire:target="addWeightEntry">Add Record</span>
                                        <span wire:loading wire:target="addWeightEntry"><span class="spinner-border spinner-border-sm me-1"></span>Adding...</span>
                                    </button></div>
                                    <div class="col-12"><label class="form-label">Notes</label><input type="text" class="form-control" placeholder="Clinical remarks" wire:model="weight_entry_notes"></div>
                                    <div class="col-12"><small class="text-muted">Workflow: 1) Click Add Record. 2) Click Save/Update Record to persist. Chart plots saved database records for this child.</small></div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0"><span class="badge bg-label-info text-uppercase">Weight-for-Age Growth Chart</span></h6></div>
                            <div class="card-body">
                                <div class="weight-chart-wrap"><canvas id="activityWeightChart" data-entries="@json($chartWeightEntries ?? [])"></canvas></div>
                                <div class="chart-legend mt-3">
                                    <span><i class="legend-line child"></i> Child's Weight</span>
                                    <span><i class="legend-line median"></i> Boys Normal (Median)</span>
                                    <span><i class="legend-line danger"></i> Danger Line (-2SD)</span>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0"><span class="badge bg-label-warning text-uppercase">Weight History (Saved Records)</span></h6></div>
                            <div class="card-body p-0">
                                <div class="card-datatable table-responsive pt-0">
                                    <table class="table">
                                        <thead class="table-dark"><tr><th>Date</th><th>Age (months)</th><th>Weight (kg)</th><th>Status</th><th>Notes</th></tr></thead>
                                        <tbody>
                                            @forelse (($chartWeightEntries ?? []) as $entry)
                                                @php
                                                    $ageIndex = min((int) round((float) ($entry['age'] ?? 0)), 24);
                                                    $medianAtAge = $whoBoysMedian[$ageIndex] ?? 10;
                                                    $minus2AtAge = $whoBoysMinus2[$ageIndex] ?? 7;
                                                    $kgValue = (float) ($entry['kg'] ?? 0);
                                                    if ($kgValue >= $medianAtAge * 0.85) {
                                                        $statusLabel = 'Good';
                                                        $statusClass = 'success';
                                                    } elseif ($kgValue >= $minus2AtAge) {
                                                        $statusLabel = 'Watch';
                                                        $statusClass = 'warning';
                                                    } else {
                                                        $statusLabel = 'Danger';
                                                        $statusClass = 'danger';
                                                    }
                                                @endphp
                                                <tr>
                                                    <td>{{ $entry['date'] ?? 'N/A' }}</td>
                                                    <td>{{ $entry['age'] ?? 'N/A' }}</td>
                                                    <td>{{ $entry['kg'] ?? 'N/A' }}</td>
                                                    <td><span class="badge bg-label-{{ $statusClass }}">{{ $statusLabel }}</span></td>
                                                    <td>{{ $entry['notes'] ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="text-center py-4 text-muted">No weight records yet.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="register-section {{ $active_tab === 'breastfeeding' ? 'active' : '' }}">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0"><span class="badge bg-label-success text-uppercase">Breastfeeding Log</span></h6></div>
                            <div class="card-body">
                                <p class="small text-muted mb-3">E = Exclusive Breast Feeding | P = Partial Breast Feeding | BW = Breast Feeding with Water | NO = No Breast Feeding</p>
                                <div class="bf-grid">
                                    @foreach (range(1, 24) as $month)
                                        <div class="bf-entry">
                                            <label class="form-label mb-1">Month {{ $month }}</label>
                                            <select class="form-select form-select-sm" wire:model.live="breastfeeding_entries.{{ (string) $month }}">
                                                <option value="">Select</option>
                                                <option value="E">E</option>
                                                <option value="P">P</option>
                                                <option value="BW">BW</option>
                                                <option value="NO">NO</option>
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                                        <div class="register-section {{ $active_tab === 'aefi' ? 'active' : '' }}">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0"><span class="badge bg-label-dark text-uppercase">AEFI - Adverse Events Following Immunization</span></h6></div>
                            <div class="card-body">
                                <div class="aefi-codes-box mt-2 mb-3">
                                    <div class="fw-semibold mb-1">Reaction Type Codes (1-28)</div>
                                    <div class="small text-muted">Outcome Codes: 1=Recovered, 2=Hospitalized, 3=Disability, 4=Died.</div>
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
                                        <thead class="table-dark">
                                            <tr class="text-center">
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
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pb-2">
                        <button wire:click="exit" type="button" class="btn btn-outline-secondary" wire:loading.attr="disabled" wire:target="exit">
                            <span wire:loading.remove wire:target="exit">Cancel</span>
                            <span wire:loading wire:target="exit"><span class="spinner-border spinner-border-sm me-1"></span>Closing...</span>
                        </button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store,update">
                            <span wire:loading.remove wire:target="store,update">{{ $record_id ? 'Update Record' : 'Save Record' }}</span>
                            <span wire:loading wire:target="store,update"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Processing...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0">Activity Records</h5>
                        <small class="text-muted">{{ count($records) }} Total</small>
                    </div>
                    <button type="button" class="btn btn-success" wire:click="openCreateModal" wire:loading.attr="disabled" wire:target="openCreateModal">
                        <span wire:loading.remove wire:target="openCreateModal"><i class="bx bx-plus me-1"></i>New Vaccination Schedule</span>
                        <span wire:loading wire:target="openCreateModal"><span class="spinner-border spinner-border-sm me-1"></span>Preparing...</span>
                    </button>
                </div>
            </div>
            <div class="card-datatable table-responsive pt-0">
                <table class="table">
                    <thead class="table-dark">
                        <tr><th>Visit Date</th><th>Child</th><th>Vaccines</th><th>Weight Logs</th><th>BF Months</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr wire:key="activity-bottom-record-{{ $record->id }}">
                                <td>{{ $record->visit_date?->format('M d, Y') }}</td>
                                <td>{{ $record->linkedChild?->full_name ?: 'N/A' }}</td>
                                <td>{{ $record->completed_vaccines_count }}</td>
                                <td>{{ $record->weight_entries_count }}</td>
                                <td>{{ $record->breastfeeding_months_logged }}</td>
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
                            <tr><td class="text-center py-4" colspan="6"><div class="text-muted"><i class="bx bx-info-circle me-1"></i>No child activity records yet.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @once
            <style>
                .bg-clinical-dark { background-color: #2c3e50; }
                .register-tabs { position: sticky; top: 0; background: #fff; z-index: 10; border-bottom: 1px solid #dbeafe; }
                .register-tabs .nav-link { font-weight: 600; color: #334155; border: none; border-bottom: 2px solid transparent; border-radius: 0; }
                .register-tabs .nav-link:hover { color: #1d4ed8; border-bottom-color: #93c5fd; }
                .register-tabs .nav-link.active { color: #1d4ed8; background: #eff6ff; border-bottom-color: #2563eb; }
                .register-section { display: none; }
                .register-section.active { display: block; }
                .vax-table th, .vax-table td { white-space: nowrap; vertical-align: middle; }
                .weight-chart-wrap { height: 320px; }
                .bf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px; }
                .bf-entry { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
                .aefi-codes-box { border: 1px solid #dbeafe; background: #f8fbff; border-radius: 10px; padding: 10px 12px; }
                .aefi-entry-table th, .aefi-entry-table td { white-space: nowrap; min-width: 120px; vertical-align: middle; }
                .chart-legend { display: flex; flex-wrap: wrap; gap: 18px; font-size: 12px; color: #6b7280; }
                .legend-line { display: inline-block; width: 20px; height: 2px; margin-right: 6px; vertical-align: middle; }
                .legend-line.child { background: #0d9488; }
                .legend-line.median { border-top: 2px dashed #22c55e; }
                .legend-line.danger { border-top: 2px dashed #f59e0b; }
                .child-info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
                .info-tile { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; background: #f8fafc; }
                .info-label { display: block; font-size: 11px; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; letter-spacing: .4px; }
                .info-value { display: block; font-size: 14px; font-weight: 600; color: #111827; }
            </style>
        @endonce

        @once
            <script src="{{ asset('assets/vendor/libs/chartjs/chart.umd.js') }}"></script>
            <script>
                (function () {
                    let weightChart = null;
                    const whoBoysMedian = @json($whoBoysMedian);
                    const whoBoysMinus2 = @json($whoBoysMinus2);
                    const labels = Array.from({ length: 25 }, (_, i) => (i === 0 ? 'Birth' : i + 'm'));
                    let latestEntries = [];

                    function getCanvas() {
                        return document.getElementById('activityWeightChart');
                    }

                    function readEntriesFromDom() {
                        const canvas = getCanvas();
                        if (!canvas) return [];

                        try {
                            return JSON.parse(canvas.dataset.entries || '[]');
                        } catch (e) {
                            return [];
                        }
                    }

                    function ensureChart(forceRecreate = false) {
                        const canvas = getCanvas();
                        if (!canvas || !window.Chart) return null;

                        if (forceRecreate && weightChart) {
                            weightChart.destroy();
                            weightChart = null;
                        }

                        if (weightChart && weightChart.canvas !== canvas) {
                            weightChart.destroy();
                            weightChart = null;
                        }

                        if (!weightChart) {
                            const ctx = canvas.getContext('2d');
                            weightChart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels,
                                    datasets: [
                                        { label: "Child's Weight", data: Array(25).fill(null), borderColor: '#0d9488', backgroundColor: 'rgba(13,148,136,0.08)', borderWidth: 2.4, tension: 0.35, spanGaps: true, pointRadius: 5, pointHoverRadius: 7, pointBackgroundColor: '#0d9488' },
                                        { label: 'Boys Normal (Median)', data: whoBoysMedian, borderColor: '#22c55e', borderWidth: 1.4, borderDash: [6, 4], pointRadius: 0 },
                                        { label: 'Danger Line (-2SD)', data: whoBoysMinus2, borderColor: '#f59e0b', borderWidth: 1.4, borderDash: [4, 4], pointRadius: 0 }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        x: { title: { display: true, text: 'Age' } },
                                        y: { title: { display: true, text: 'Weight (kg)' }, min: 0, max: 20 }
                                    }
                                }
                            });
                        }

                        return weightChart;
                    }
                    function syncEntriesFromDom() {
                        const entries = readEntriesFromDom();
                        latestEntries = Array.isArray(entries) ? entries : [];
                        return latestEntries;
                    }

                    function updateWeightChart(entries, forceRecreate = false) {
                        const chart = ensureChart(forceRecreate);
                        if (!chart) return;

                        if (Array.isArray(entries) && entries.length) {
                            latestEntries = entries;
                        }

                        const safeEntries = latestEntries.length ? latestEntries : syncEntriesFromDom();
                        const chartData = Array(25).fill(null);

                        safeEntries.forEach(function (entry) {
                            const age = Number(entry.age || 0);
                            const kg = Number(entry.kg || 0);
                            if (Number.isNaN(age) || Number.isNaN(kg)) return;
                            const idx = Math.min(Math.max(Math.round(age), 0), 24);
                            chartData[idx] = kg;
                        });

                        chart.data.datasets[0].data = chartData;
                        chart.update();
                        chart.resize();
                    }

                    window.refreshActivityWeightChart = function (forceRecreate = false) {
                        updateWeightChart(latestEntries.length ? latestEntries : syncEntriesFromDom(), forceRecreate);
                    };

                    function bindWeightEvents() {
                        if (!window.Livewire || window.__activityRegisterWeightBound) return;
                        window.__activityRegisterWeightBound = true;

                        Livewire.on('activity-weight-data-updated', function (payload) {
                            const eventPayload = Array.isArray(payload) ? (payload[0] || {}) : (payload || {});
                            const entries = Array.isArray(eventPayload.entries) ? eventPayload.entries : [];
                            latestEntries = entries;
                            setTimeout(function () { updateWeightChart(entries); }, 80);
                        });

                        Livewire.on('activity-weight-tab-opened', function () {
                            setTimeout(function () {
                                updateWeightChart(latestEntries.length ? latestEntries : syncEntriesFromDom(), true);
                            }, 180);
                        });
                    }

                    document.addEventListener('livewire:init', bindWeightEvents);
                    document.addEventListener('livewire:initialized', function () {
                        bindWeightEvents();
                        setTimeout(function () { updateWeightChart(syncEntriesFromDom()); }, 120);

                        if (window.Livewire && typeof Livewire.hook === 'function') {
                            Livewire.hook('message.processed', function () {
                                setTimeout(function () {
                                    if (document.querySelector('.register-section.active .weight-chart-wrap')) {
                                        updateWeightChart(syncEntriesFromDom());
                                    }
                                }, 60);
                            });
                        }
                    });

                    window.addEventListener('load', function () {
                        setTimeout(function () { updateWeightChart(syncEntriesFromDom()); }, 200);
                    });
                })();
            </script>
        @endonce
    @endif
</div>
























