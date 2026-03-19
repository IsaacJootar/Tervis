<div class="analytics-page">
    @include('livewire.analytics._template-style')
    @section('title', 'AI Diagnostic Assistant')
    @php
        use Carbon\Carbon;
        $aiMetrics = $this->getAIMetrics();
    @endphp

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h4 class="mb-1"><i class='bx bx-analyse me-2'></i>AI Diagnostic Assistant</h4>
                        <p class="mb-0 text-muted">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-label-primary">{{ count($highRiskPatients) }} In Queue</span>
                        <span class="badge bg-label-danger">{{ collect($highRiskPatients)->filter(fn($p) => (int) ($p['risk_factor_count'] ?? 0) >= 3)->count() }} Critical Signals</span>
                        <span class="badge bg-label-info">{{ $aiMetrics['total_assessments'] }} Total Assessments</span>
                        <span class="badge bg-label-success">{{ $aiMetrics['average_confidence'] }}% Confidence</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (count($availableFacilities) > 0)
        <div class="row mb-4">
            <div class="col-md-8">
                <label class="form-label">
                    <i class="bx bx-buildings me-1"></i>
                    Filter by Facility
                </label>
                <select wire:model.live="selectedFacilityId" class="form-select form-select-lg">
                    <option value="">All Facilities
                        ({{ $scopeInfo['scope_type'] === 'state' ? 'State-wide' : 'LGA-wide' }})</option>
                    @foreach ($availableFacilities as $facility)
                        <option value="{{ $facility['id'] }}">
                            {{ $facility['name'] }} - {{ $facility['lga'] }} ({{ $facility['ward'] }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end justify-content-md-end gap-2">
                @if ($selectedFacilityId)
                    <button wire:click="resetToScope" class="btn btn-outline-secondary btn-lg">
                        <i class="bx bx-reset me-1"></i>View All Facilities
                    </button>
                @endif
                <button wire:click="refreshData" class="btn btn-primary btn-lg" wire:loading.attr="disabled" wire:target="refreshData">
                    <span wire:loading.remove wire:target="refreshData">
                        <i class="bx bx-refresh me-1"></i>Refresh Data
                    </span>
                    <span wire:loading wire:target="refreshData">
                        <span class="spinner-border spinner-border-sm me-1"></span>Refreshing...
                    </span>
                </button>
            </div>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="metric-card metric-card-violet h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Queue Size</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M6 7h12M6 12h12M6 17h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ count($highRiskPatients) }}</div>
                <div class="small">Patients pending assistant review</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="metric-card metric-card-rose h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Critical Signals</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 4.5l8 14H4l8-14z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                            <path d="M12 9v4M12 15.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ collect($highRiskPatients)->filter(fn($p) => (int) ($p['risk_factor_count'] ?? 0) >= 3)->count() }}</div>
                <div class="small">Risk factor count >= 3</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="metric-card metric-card-amber h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">This Week</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="4.5" y="5.5" width="15" height="14" rx="2" stroke="currentColor" stroke-width="1.6" />
                            <path d="M8 3.8v3M16 3.8v3M8 11h8M8 14h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $aiMetrics['this_week'] }}</div>
                <div class="small">New AI assessments</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">AI Confidence</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7" />
                            <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $aiMetrics['average_confidence'] }}%</div>
                <div class="small">Average model confidence</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bx bx-user-check me-2"></i>Diagnostic Queue</h5>
                    <small class="text-muted">Generate full diagnostic guidance for patient review</small>
                </div>
                <div class="card-datatable table-responsive pt-0">
                    <table class="table">
                        <thead class="table-dark">
                            <tr>
                                <th>Patient Name</th>
                                <th>DIN</th>
                                <th>Age</th>
                                <th>Gestational Age</th>
                                <th>Risk Signals</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($highRiskPatients as $patient)
                                <tr>
                                    <td>{{ $patient['patient_name'] ?? 'Unknown Patient' }}</td>
                                    <td><span class="badge bg-label-info">{{ $patient['patient_din'] ?? 'N/A' }}</span></td>
                                    <td>{{ $patient['patient_age'] ?? 'N/A' }}{{ ($patient['patient_age'] ?? 'N/A') !== 'N/A' ? ' years' : '' }}</td>
                                    <td>{{ $patient['gestational_age'] ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge {{ (int) ($patient['risk_factor_count'] ?? 0) >= 3 ? 'bg-danger' : 'bg-warning' }}">
                                            {{ $patient['risk_factor_count'] ?? 0 }}
                                        </span>
                                    </td>
                                    <td>
                                        <button wire:click="viewDiagnosticSummary({{ (int) ($patient['patient_id'] ?? 0) }})" class="btn btn-sm btn-dark">
                                            <i class="bx bx-analyse me-1"></i>Generate Summary
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No high-risk patient records available for this scope yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($showDiagnosticModal && $diagnosticSummary)
        <div class="modal fade show" style="display: block;" tabindex="-1" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header modal-header-clean">
                        <h5 class="modal-title">
                            <i class="bx bx-analyse me-2"></i>
                            Clinical Diagnostic Summary - {{ $diagnosticSummary['patient_info']['name'] }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeDiagnosticModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Patient Information</h6>
                                        <p class="mb-1"><strong>Name:</strong> {{ $diagnosticSummary['patient_info']['name'] }}</p>
                                        <p class="mb-1"><strong>DIN:</strong> {{ $diagnosticSummary['patient_info']['din'] }}</p>
                                        <p class="mb-1"><strong>Age:</strong> {{ $diagnosticSummary['patient_info']['age'] }} years</p>
                                        <p class="mb-0"><strong>Phone:</strong> {{ $diagnosticSummary['patient_info']['phone'] ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Current Pregnancy Status</h6>
                                        <p class="mb-1"><strong>Gestational Age:</strong> {{ $diagnosticSummary['clinical_snapshot']['gestational_age'] ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>Trimester:</strong> {{ $diagnosticSummary['clinical_snapshot']['trimester'] ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>EDD:</strong> {{ !empty($diagnosticSummary['clinical_snapshot']['edd']) ? Carbon::parse($diagnosticSummary['clinical_snapshot']['edd'])->format('M d, Y') : 'N/A' }}</p>
                                        <p class="mb-0"><strong>Days Until EDD:</strong> {{ $diagnosticSummary['clinical_snapshot']['days_until_edd'] ?? 'N/A' }} days</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="bx bx-error-circle me-2"></i>Overall Risk Assessment</h6>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <h3 class="mb-0">{{ ucfirst($diagnosticSummary['clinical_snapshot']['overall_risk']['level']) }} Risk</h3>
                                        <small class="text-muted">Risk Score: {{ $diagnosticSummary['clinical_snapshot']['overall_risk']['score'] }}/200</small>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="progress" style="height: 30px;">
                                            <div class="progress-bar bg-{{ $diagnosticSummary['clinical_snapshot']['overall_risk']['level'] === 'critical' ? 'danger' : ($diagnosticSummary['clinical_snapshot']['overall_risk']['level'] === 'high' ? 'warning' : 'info') }}"
                                                role="progressbar"
                                                style="width: {{ $diagnosticSummary['clinical_snapshot']['overall_risk']['percentage'] }}%"
                                                aria-valuenow="{{ $diagnosticSummary['clinical_snapshot']['overall_risk']['percentage'] }}"
                                                aria-valuemin="0" aria-valuemax="100">
                                                {{ round($diagnosticSummary['clinical_snapshot']['overall_risk']['percentage']) }}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if (!empty($diagnosticSummary['primary_concerns']))
                            <div class="card mb-3">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0"><i class="bx bx-error-circle me-2"></i>Primary Clinical Concerns</h6>
                                </div>
                                <div class="card-body">
                                    @foreach ($diagnosticSummary['primary_concerns'] as $concern)
                                        <div class="alert alert-{{ $concern['severity'] === 'Critical' ? 'danger' : 'warning' }} mb-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="alert-heading mb-1">{{ $concern['concern'] }}</h6>
                                                    <p class="mb-1"><strong>Severity:</strong> <span class="badge bg-{{ $concern['severity'] === 'Critical' ? 'danger' : 'warning' }}">{{ $concern['severity'] }}</span></p>
                                                    <p class="mb-1"><strong>Category:</strong> {{ ucfirst(str_replace('_', ' ', $concern['category'])) }}</p>
                                                    <p class="mb-0"><strong>Clinical Impact:</strong> {{ $concern['clinical_impact'] }}</p>
                                                </div>
                                                <span class="badge bg-light text-dark">{{ round($concern['confidence'] * 100) }}% confidence</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="card mb-3">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="bx bx-brain me-2"></i>Clinical Reasoning & Analysis</h6>
                            </div>
                            <div class="card-body">
                                @foreach ($diagnosticSummary['clinical_reasoning'] as $index => $reasoning)
                                    <div class="border-start border-primary border-3 ps-3 mb-3 pb-3 {{ $index < count($diagnosticSummary['clinical_reasoning']) - 1 ? 'border-bottom' : '' }}">
                                        <h6 class="text-primary mb-2">{{ $reasoning['description'] }}</h6>
                                        <div class="mb-2">
                                            <strong class="text-muted">Why This Was Flagged:</strong>
                                            <p class="mb-0">{{ $reasoning['why_flagged'] }}</p>
                                        </div>
                                        <div class="mb-2">
                                            <strong class="text-muted">Clinical Significance:</strong>
                                            <p class="mb-0">{{ $reasoning['clinical_significance'] }}</p>
                                        </div>
                                        <div class="mb-0">
                                            <strong class="text-muted">Potential Complications:</strong>
                                            <ul class="mb-0 mt-1">
                                                @foreach ($reasoning['potential_complications'] as $complication)
                                                    <li>{{ $complication }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="card mb-3 bg-light">
                            <div class="card-body p-2">
                                <small class="text-muted">
                                    <strong>Report Generated:</strong> {{ $diagnosticSummary['metadata']['generated_at']->format('M d, Y h:i A') }} |
                                    <strong>Assessment Date:</strong> {{ !empty($diagnosticSummary['metadata']['assessment_date']) ? Carbon::parse($diagnosticSummary['metadata']['assessment_date'])->format('M d, Y') : 'N/A' }} |
                                    <strong>Model Version:</strong> {{ $diagnosticSummary['metadata']['model_version'] ?? 'N/A' }} |
                                    <strong>Overall Confidence:</strong> {{ $diagnosticSummary['metadata']['confidence'] ?? 0 }}%
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bx bx-shield-quarter me-1"></i>Clinician must review before final decision</small>
                            <div>
                                <button type="button" class="btn btn-secondary me-2" wire:click="closeDiagnosticModal">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                                <button type="button" class="btn btn-primary" onclick="window.print()">
                                    <i class="bx bx-printer me-1"></i>Print Summary
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <style>
        .modal.show { background-color: rgba(0, 0, 0, 0.5); }
        .modal-content {
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 18px;
            box-shadow: 0 22px 45px -32px rgba(15, 23, 42, 0.55);
        }
        .modal-header {
            border-bottom: 1px solid rgba(148, 163, 184, 0.22);
            background: #fff;
            color: #111827;
        }
        .modal-header-clean {
            background: #fff;
            color: #111827;
        }
        .modal-footer { border-top: 1px solid rgba(148, 163, 184, 0.22); }
        .modal-body .card-header {
            border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        }
    </style>
</div>
