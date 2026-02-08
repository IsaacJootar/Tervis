<div>
    <div class="batch-diagnostic-container">
        <!-- Hero Card Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="hero-card">
                    <div class="hero-content">
                        <div class="hero-text">
                            <h4 class="hero-title" style="color: white; font-size: 30px;">
                                <i class='bx bx-analyse me-2'></i>
                                Batch Diagnostic Assistant
                            </h4>

                            <div class="d-flex flex-wrap gap-3 text-white mb-1">
                                <span>
                                    <i class="bx bx-user-circle me-1"></i>
                                    <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>
                                </span>
                                <span>
                                    <i class="bx bx-building-house me-1"></i>
                                    <strong>Facilities:</strong> {{ $facilityCount }}
                                </span>
                                <span>
                                    <i class="bx bx-time me-1"></i>
                                    {{ \Carbon\Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                                </span>
                            </div>

                            <p class="text-white-50 mb-0">
                                Generate comprehensive diagnostic summaries for high-risk patients across your
                                facilities
                            </p>
                        </div>
                        <div class="hero-decoration">
                            <div class="floating-shape shape-1"></div>
                            <div class="floating-shape shape-2"></div>
                            <div class="floating-shape shape-3"></div>
                        </div>
                    </div>
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
                                                    <span
                                                        class="badge {{ $stat['needs_attention'] ? 'bg-label-warning' : 'bg-label-success' }} fs-6">
                                                        {{ $stat['high_risk_patients'] }}
                                                    </span>
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
                        <div class="card-header bg-label-success d-flex justify-content-between align-items-center">
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
                                    <div class="text-center p-3 border rounded">
                                        <h3 class="mb-0 text-primary">{{ $batchResults['total_patients'] }}</h3>
                                        <small class="text-muted">Total Patients</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 border rounded">
                                        <h3 class="mb-0 text-success">{{ $batchResults['success_count'] }}</h3>
                                        <small class="text-muted">Successful</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 border rounded">
                                        <h3 class="mb-0 text-warning">{{ $batchResults['failure_count'] }}</h3>
                                        <small class="text-muted">Failed</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 border rounded">
                                        <h3 class="mb-0 text-info">{{ $batchResults['facility_count'] }}</h3>
                                        <small class="text-muted">Facilities</small>
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
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
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
                                <div class="card-header bg-label-primary">
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
                                <div class="card-header bg-label-info">
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
                                    <div class="card-header bg-label-warning">
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
                                    <div class="card-header bg-label-danger">
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
            .hero-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 20px;
                overflow: hidden;
                position: relative;
                min-height: 180px;
            }

            .hero-content {
                position: relative;
                z-index: 2;
                padding: 2rem;
            }

            .hero-decoration {
                position: absolute;
                top: 0;
                right: 0;
                width: 100%;
                height: 100%;
                overflow: hidden;
                z-index: 1;
            }

            .floating-shape {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.1);
                animation: float 6s ease-in-out infinite;
            }

            .floating-shape.shape-1 {
                width: 80px;
                height: 80px;
                top: 20%;
                right: 10%;
                animation-delay: 0s;
            }

            .floating-shape.shape-2 {
                width: 60px;
                height: 60px;
                top: 60%;
                right: 20%;
                animation-delay: 2s;
            }

            .floating-shape.shape-3 {
                width: 40px;
                height: 40px;
                top: 40%;
                right: 5%;
                animation-delay: 4s;
            }

            @keyframes float {

                0%,
                100% {
                    transform: translateY(0px) rotate(0deg);
                }

                50% {
                    transform: translateY(-20px) rotate(180deg);
                }
            }

            .card {
                box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
                border: 1px solid rgba(67, 89, 113, 0.1);
                transition: all 0.3s ease;
            }

            .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px 0 rgba(67, 89, 113, 0.16);
            }

            .list-group-item-action:hover {
                background-color: rgba(67, 89, 113, 0.05);
            }
        </style>
    </div>
</div>
