<div class="analytics-page">
    @include('livewire.analytics._template-style')
    @section('title', 'AI Batch Diagnostic Assistant')
    <div class="batch-diagnostic-container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1"><i class='bx bx-analyse me-2'></i>AI Batch Diagnostic Assistant</h4>
                            <p class="mb-0 text-muted">Generate diagnostic summaries for high-risk patients across facilities.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-label-primary">{{ $user->first_name }} {{ $user->last_name }}</span>
                            <span class="badge bg-label-info">{{ $facilityCount }} Facilities</span>
                            <span class="badge bg-label-secondary">{{ \Carbon\Carbon::now('Africa/Lagos')->format('h:i A') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @php
            $attentionFacilities = collect($facilityStats)->where('needs_attention', true)->count();
            $highRiskTotal = collect($facilityStats)->sum('high_risk_patients');
        @endphp
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="metric-card metric-card-violet h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Facilities</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4.5 19.5V6.8c0-.7.6-1.3 1.3-1.3h4.4c.7 0 1.3.6 1.3 1.3v12.7M13.5 19.5V4.8c0-.7.6-1.3 1.3-1.3h3.4c.7 0 1.3.6 1.3 1.3v14.7M8 9h.01M8 12h.01M8 15h.01M17 8h.01M17 11h.01M17 14h.01"
                                    stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $facilityCount }}</div>
                    <div class="small">Within your scope</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="metric-card metric-card-rose h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Need Attention</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 4.5l8 14H4l8-14z" stroke="currentColor" stroke-width="1.7"
                                    stroke-linejoin="round" />
                                <path d="M12 9v4M12 15.5h.01" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $attentionFacilities }}</div>
                    <div class="small">Facilities with high-risk queue</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="metric-card metric-card-amber h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">High-Risk Patients</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" stroke="currentColor"
                                    stroke-width="1.7" />
                                <path d="M5 19.2c1.4-2.4 4-3.7 7-3.7s5.6 1.3 7 3.7" stroke="currentColor"
                                    stroke-width="1.7" stroke-linecap="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $highRiskTotal }}</div>
                    <div class="small">Current batch queue</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="metric-card metric-card-sky h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Lookback Window</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="4.5" y="5.5" width="15" height="14" rx="2" stroke="currentColor"
                                    stroke-width="1.6" />
                                <path d="M8 3.8v3M16 3.8v3M8 11h8M8 14h5" stroke="currentColor"
                                    stroke-width="1.6" stroke-linecap="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ (int) $daysBack }}</div>
                    <div class="small">Days analyzed</div>
                </div>
            </div>
        </div>
        <!-- Filters -->
        <div class="row mb-4 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Risk Level Filter</label>
                <select wire:model.live="selectedRiskLevel" class="form-select" multiple size="2">
                    <option value="critical">Critical Risk</option>
                    <option value="high">High Risk</option>
                </select>
                <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Look Back Period</label>
                <select wire:model.live="daysBack" class="form-select">
                    <option value="7">Last 7 Days</option>
                    <option value="14">Last 14 Days</option>
                    <option value="30">Last 30 Days</option>
                    <option value="60">Last 60 Days</option>
                    <option value="90">Last 90 Days</option>
                </select>
            </div>
            <div class="col-md-6">
                <button wire:click="runBatchDiagnostics()" class="btn btn-primary w-100" wire:loading.attr="disabled"
                    wire:target="runBatchDiagnostics">
                    <span wire:loading.remove wire:target="runBatchDiagnostics">
                        <i class="bx bx-play-circle me-1"></i>
                        Run for All Facilities
                    </span>
                    <span wire:loading wire:target="runBatchDiagnostics">
                        <span class="spinner-border spinner-border-sm me-1"></span>
                        Processing...
                    </span>
                </button>
            </div>
        </div>
        <!-- Facility Stats Cards -->
        @if (!empty($facilityStats))
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-buildings me-2"></i>
                                Facilities with High-Risk Patients
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($facilityStats as $stat)
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <div
                                            class="card border {{ $stat['needs_attention'] ? 'border-warning' : 'border-success' }}">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h6 class="mb-1">{{ $stat['facility_name'] }}</h6>
                                                        <small class="text-muted">
                                                            <i class="bx bx-map me-1"></i>{{ $stat['lga'] }}
                                                        </small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="metric-icon mb-2" aria-hidden="true">
                                                            @if ($stat['needs_attention'])
                                                                <svg viewBox="0 0 24 24" fill="none">
                                                                    <path d="M12 4.5l8 14H4l8-14z" stroke="currentColor"
                                                                        stroke-width="1.7" stroke-linejoin="round" />
                                                                    <path d="M12 9v4M12 15.5h.01" stroke="currentColor"
                                                                        stroke-width="1.8" stroke-linecap="round" />
                                                                </svg>
                                                            @else
                                                                <svg viewBox="0 0 24 24" fill="none">
                                                                    <circle cx="12" cy="12" r="8.5"
                                                                        stroke="currentColor" stroke-width="1.7" />
                                                                    <path d="M8.5 12.5l2.5 2.5 4.5-5"
                                                                        stroke="currentColor" stroke-width="1.8"
                                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                                </svg>
                                                            @endif
                                                        </span>
                                                        <div>
                                                            <span
                                                                class="badge {{ $stat['needs_attention'] ? 'bg-label-warning' : 'bg-label-success' }} fs-6">
                                                                {{ $stat['high_risk_patients'] }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <p class="mb-3 text-muted small">
                                                    @if ($stat['high_risk_patients'] > 0)
                                                        {{ $stat['high_risk_patients'] }} high-risk patient(s) need
                                                        diagnostic review
                                                    @else
                                                        No high-risk patients in this period
                                                    @endif
                                                </p>

                                                @if ($stat['high_risk_patients'] > 0)
                                                    <button
                                                        wire:click="runBatchDiagnostics({{ $stat['facility_id'] }})"
                                                        class="btn btn-sm btn-outline-primary w-100"
                                                        wire:loading.attr="disabled" wire:target="runBatchDiagnostics">
                                                        <i class="bx bx-analyse me-1"></i>
                                                        Generate Diagnostics
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm btn-outline-secondary w-100" disabled>
                                                        <i class="bx bx-check-circle me-1"></i>
                                                        No Action Needed
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        No facilities found in your scope or no high-risk patients in the selected period.
                    </div>
                </div>
            </div>
        @endif

        <!-- Batch Results -->
        @if (!empty($batchResults) && $batchResults['success'])
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-success">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-check-circle me-2"></i>
                                Batch Diagnostic Results
                            </h5>
                            <button wire:click="clearResults" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-x me-1"></i>Clear Results
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Summary Stats -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="metric-card metric-card-violet h-100">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="metric-label">Total Patients</div>
                                            <span class="metric-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none">
                                                    <path d="M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"
                                                        stroke="currentColor" stroke-width="1.7" />
                                                    <path d="M5 19.2c1.4-2.4 4-3.7 7-3.7s5.6 1.3 7 3.7"
                                                        stroke="currentColor" stroke-width="1.7"
                                                        stroke-linecap="round" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="metric-value">{{ $batchResults['total_patients'] }}</div>
                                        <div class="small">Processed in this run</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="metric-card metric-card-emerald h-100">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="metric-label">Successful</div>
                                            <span class="metric-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none">
                                                    <circle cx="12" cy="12" r="8.5" stroke="currentColor"
                                                        stroke-width="1.7" />
                                                    <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor"
                                                        stroke-width="1.8" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="metric-value">{{ $batchResults['success_count'] }}</div>
                                        <div class="small">Generated summaries</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="metric-card metric-card-amber h-100">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="metric-label">Failed</div>
                                            <span class="metric-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none">
                                                    <circle cx="12" cy="12" r="8.5" stroke="currentColor"
                                                        stroke-width="1.7" />
                                                    <path d="M9 9l6 6M15 9l-6 6" stroke="currentColor"
                                                        stroke-width="1.8" stroke-linecap="round" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="metric-value">{{ $batchResults['failure_count'] }}</div>
                                        <div class="small">Generation failures</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="metric-card metric-card-sky h-100">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="metric-label">Facilities</div>
                                            <span class="metric-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none">
                                                    <path d="M4.5 19.5V6.8c0-.7.6-1.3 1.3-1.3h4.4c.7 0 1.3.6 1.3 1.3v12.7M13.5 19.5V4.8c0-.7.6-1.3 1.3-1.3h3.4c.7 0 1.3.6 1.3 1.3v14.7M8 9h.01M8 12h.01M8 15h.01M17 8h.01M17 11h.01M17 14h.01"
                                                        stroke="currentColor" stroke-width="1.6"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="metric-value">{{ $batchResults['facility_count'] }}</div>
                                        <div class="small">Covered by this run</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Facility Breakdown -->
                            @if (!empty($batchResults['facility_breakdown']))
                                <div class="mb-4">
                                    <h6 class="mb-3">Breakdown by Facility</h6>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Facility</th>
                                                    <th class="text-center">Total Patients</th>
                                                    <th class="text-center">Critical</th>
                                                    <th class="text-center">High Risk</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($batchResults['facility_breakdown'] as $facilityId => $breakdown)
                                                    <tr>
                                                        <td><strong>{{ $breakdown['facility_name'] }}</strong></td>
                                                        <td class="text-center">{{ $breakdown['patient_count'] }}</td>
                                                        <td class="text-center">
                                                            <span
                                                                class="badge bg-label-danger">{{ $breakdown['critical_count'] }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span
                                                                class="badge bg-label-warning">{{ $breakdown['high_count'] }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            <!-- Patient Summaries -->
                            <div>
                                <h6 class="mb-3">Patient Diagnostic Summaries</h6>
                                <div class="list-group">
                                    @foreach ($batchResults['summaries'] as $index => $summary)
                                        <div class="list-group-item list-group-item-action"
                                            wire:click="viewSummary({{ $index }})" style="cursor: pointer;">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">{{ $summary['patient_info']['name'] }}</h6>
                                                    <small class="text-muted">DIN:
                                                        {{ $summary['patient_info']['din'] }}</small>
                                                    <span class="mx-2">|</span>
                                                    <small class="text-muted">Age:
                                                        {{ $summary['patient_info']['age'] }}</small>
                                                    <span class="mx-2">|</span>
                                                    <small class="text-muted">GA:
                                                        {{ $summary['clinical_snapshot']['gestational_age'] }}</small>
                                                </div>
                                                <span
                                                    class="badge {{ $summary['risk_level'] === 'critical' ? 'bg-danger' : 'bg-warning' }}">
                                                    {{ strtoupper($summary['risk_level']) }}
                                                </span>
                                            </div>
                                            @if (!empty($summary['primary_concerns']))
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <strong>Primary Concerns:</strong>
                                                        @foreach (array_slice($summary['primary_concerns'], 0, 2) as $concern)
                                                            <span
                                                                class="badge bg-label-secondary me-1">{{ $concern['concern'] }}</span>
                                                        @endforeach
                                                        @if (count($summary['primary_concerns']) > 2)
                                                            <span
                                                                class="badge bg-label-secondary">+{{ count($summary['primary_concerns']) - 2 }}
                                                                more</span>
                                                        @endif
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Summary Modal -->
        @if ($viewingSummary && $currentSummary)
            <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                Diagnostic Summary: {{ $currentSummary['patient_info']['name'] }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="closeSummary"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Patient Info -->
                            <div class="card mb-3">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0">Patient Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>DIN:</strong> {{ $currentSummary['patient_info']['din'] }}</p>
                                            <p><strong>Age:</strong> {{ $currentSummary['patient_info']['age'] }} years
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Gestational Age:</strong>
                                                {{ $currentSummary['clinical_snapshot']['gestational_age'] }}</p>
                                            <p><strong>EDD:</strong>
                                                {{ $currentSummary['clinical_snapshot']['edd'] ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Vitals -->
                            <div class="card mb-3">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0">Clinical Snapshot</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><strong>Blood Pressure:</strong>
                                                {{ $currentSummary['clinical_snapshot']['vitals']['blood_pressure'] ?? 'N/A' }}
                                            </p>
                                            <small class="text-muted">Status:
                                                {{ $currentSummary['clinical_snapshot']['vitals']['bp_status'] }}</small>
                                        </div>
                                        <div class="col-md-4">
                                            <p><strong>Hemoglobin:</strong>
                                                {{ $currentSummary['clinical_snapshot']['vitals']['hemoglobin'] ?? 'N/A' }}
                                                g/dL</p>
                                            <small class="text-muted">Status:
                                                {{ $currentSummary['clinical_snapshot']['vitals']['hb_status'] }}</small>
                                        </div>
                                        <div class="col-md-4">
                                            <p><strong>BMI:</strong>
                                                {{ $currentSummary['clinical_snapshot']['vitals']['bmi'] ?? 'N/A' }}
                                            </p>
                                            <small
                                                class="text-muted">{{ $currentSummary['clinical_snapshot']['vitals']['bmi_category'] }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Primary Concerns -->
                            @if (!empty($currentSummary['primary_concerns']))
                                <div class="card mb-3">
                                    <div class="card-header bg-white">
                                        <h6 class="mb-0">Primary Concerns</h6>
                                    </div>
                                    <div class="card-body">
                                        @foreach ($currentSummary['primary_concerns'] as $concern)
                                            <div
                                                class="alert alert-{{ $concern['severity'] === 'Critical' ? 'danger' : 'warning' }} mb-2">
                                                <strong>{{ $concern['concern'] }}</strong>
                                                <p class="mb-0 small">{{ $concern['clinical_impact'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Immediate Actions -->
                            @if (!empty($currentSummary['immediate_actions']))
                                <div class="card">
                                    <div class="card-header bg-white">
                                        <h6 class="mb-0">Immediate Actions Required</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            @foreach ($currentSummary['immediate_actions'] as $action)
                                                <li class="list-group-item">
                                                    <span
                                                        class="badge bg-{{ $action['priority'] === 'urgent' ? 'danger' : 'warning' }} me-2">
                                                        {{ strtoupper($action['priority']) }}
                                                    </span>
                                                    <strong>{{ $action['action'] }}</strong>
                                                    <br>
                                                    <small class="text-muted">Timeframe:
                                                        {{ $action['timeframe'] }}</small>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeSummary">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <style>
            .modal-content {
                border: 1px solid rgba(148, 163, 184, 0.22);
                border-radius: 18px;
                box-shadow: 0 22px 45px -32px rgba(15, 23, 42, 0.55);
            }

            .modal-header {
                border-bottom: 1px solid rgba(148, 163, 184, 0.22);
            }

            .modal-footer {
                border-top: 1px solid rgba(148, 163, 184, 0.22);
            }

            .list-group-item-action:hover {
                background-color: rgba(67, 89, 113, 0.05);
            }
        </style>
    </div>
</div>

