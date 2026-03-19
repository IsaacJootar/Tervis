<div class="analytics-page">
    @include('livewire.analytics._template-style')
    @section('title', 'AI Risk Assessment Dashboard')
    @php
        use Illuminate\Support\Facades\Auth;
        use Carbon\Carbon;

    @endphp

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h4 class="mb-1"><i class='bx bx-shield-alt-2 me-2'></i>AI Risk Assessment & Diagnostic Assistant</h4>
                        <p class="mb-0 text-muted">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-label-primary">{{ $facilityRiskSummary['total_patients'] ?? 0 }} Patients</span>
                        <span class="badge bg-label-danger">{{ ($facilityRiskSummary['high_risk'] ?? 0) + ($facilityRiskSummary['critical_risk'] ?? 0) }} High Risk</span>
                        <span class="badge bg-label-info">{{ $facilityRiskSummary['service_utilization']['with_deliveries'] ?? 0 }} Deliveries</span>
                        <span class="badge bg-label-warning">{{ $facilityRiskSummary['service_utilization']['with_postnatal'] ?? 0 }} Postnatal</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Facility Filter for Multi-facility Users -->
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
                <small class="text-muted">
                    @if ($selectedFacilityId)
                        Showing data for selected facility only
                    @else
                        Showing aggregated data across {{ count($scopeInfo['facility_ids']) }} facilities
                    @endif
                </small>
            </div>
            <div class="col-md-4 d-flex align-items-end justify-content-md-end gap-2">
                @if ($selectedFacilityId)
                    <button wire:click="resetToScope" class="btn btn-outline-secondary btn-lg">
                        <i class="bx bx-reset me-1"></i>
                        View All Facilities
                    </button>
                @endif
                <button wire:click="refreshData" class="btn btn-primary btn-lg" wire:loading.attr="disabled"
                    wire:target="refreshData">
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


    <!-- Risk Distribution Cards -->
    @if (!empty($facilityRiskSummary['note'] ?? null))
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    <i class="bx bx-info-circle me-1"></i>{{ $facilityRiskSummary['note'] }}
                </div>
            </div>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Low Risk</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7" />
                            <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $facilityRiskSummary['low_risk'] ?? 0 }}</div>
                <div class="small">{{ $facilityRiskSummary['risk_distribution']['low'] ?? 0 }}%</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Moderate Risk</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7" />
                            <path d="M12 8v5M12 16.4h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $facilityRiskSummary['moderate_risk'] ?? 0 }}</div>
                <div class="small">{{ $facilityRiskSummary['risk_distribution']['moderate'] ?? 0 }}%</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="metric-card metric-card-amber h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">High Risk</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 4.5l8 14H4l8-14z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                            <path d="M12 9v4M12 15.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $facilityRiskSummary['high_risk'] ?? 0 }}</div>
                <div class="small">{{ $facilityRiskSummary['risk_distribution']['high'] ?? 0 }}%</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="metric-card metric-card-rose h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Critical Risk</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7" />
                            <path d="M9 9l6 6M15 9l-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $facilityRiskSummary['critical_risk'] ?? 0 }}</div>
                <div class="small">{{ $facilityRiskSummary['risk_distribution']['critical'] ?? 0 }}%</div>
            </div>
        </div>
    </div>

    <!-- AI Performance Metrics Row -->
    <div class="row mb-4">
        @php $aiMetrics = $this->getAIMetrics(); @endphp

        <div class="col-md-3 mb-3">
            <div class="metric-card metric-card-violet h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">AI Assessments</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="7" y="7" width="10" height="10" rx="3" stroke="currentColor" stroke-width="1.7" />
                            <path d="M12 3.5v2M12 18.5v2M3.5 12h2M18.5 12h2" stroke="currentColor" stroke-width="1.7"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $aiMetrics['total_assessments'] }}</div>
                <div class="small">Total completed</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">This Week</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="4.5" y="5.5" width="15" height="14" rx="2" stroke="currentColor"
                                stroke-width="1.6" />
                            <path d="M8 3.8v3M16 3.8v3M8 11h8M8 14h5" stroke="currentColor" stroke-width="1.6"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $aiMetrics['this_week'] }}</div>
                <div class="small">Recent assessments</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="metric-card metric-card-amber h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">High Risk</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 4l7 3v5c0 4.4-2.8 6.9-7 8-4.2-1.1-7-3.6-7-8V7l7-3z"
                                stroke="currentColor" stroke-width="1.7" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $aiMetrics['high_risk_detected'] }}</div>
                <div class="small">AI detected</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">AI Confidence</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7" />
                            <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $aiMetrics['average_confidence'] }}%</div>
                <div class="small">Average accuracy</div>
            </div>
        </div>
    </div>

    <!-- Risk Analysis and Service Utilization Row -->
    <!-- Common Risk Factors -->
    <div class="row g-4 mb-4">
        <!-- Common Risk Factors -->
        <!-- Common Risk Factors -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Risk Factors</h5>
                    <small class="text-muted">Most common clinical concerns by patient count</small>
                </div>
                <div class="card-body">
                    @if (isset($facilityRiskSummary['common_risk_factors']) && !empty($facilityRiskSummary['common_risk_factors']))
                        @php
                            // Use assessed_patients (total unique patients who have been assessed)
                            $totalAssessedPatients = $facilityRiskSummary['total_patients'] ?? 1;
                        @endphp

                        @foreach ($facilityRiskSummary['common_risk_factors'] as $factor => $patientCount)
                            @php
                                // $patientCount is already the number of unique patients with this risk factor
                                $percentage =
                                    $totalAssessedPatients > 0
                                        ? round(($patientCount / $totalAssessedPatients) * 100, 1)
                                        : 0;

                                // Determine severity color based on percentage
                                $severityClass =
                                    $percentage >= 75
                                        ? 'danger'
                                        : ($percentage >= 50
                                            ? 'warning'
                                            : ($percentage >= 25
                                                ? 'info'
                                                : 'secondary'));

                                // Pluralization
                                $patientText = $patientCount == 1 ? 'patient' : 'patients';
                                $totalPatientText = $totalAssessedPatients == 1 ? 'patient' : 'patients';
                            @endphp

                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="fw-semibold text-capitalize">
                                        {{ str_replace('_', ' ', $factor) }}
                                    </span>
                                    <span class="badge bg-{{ $severityClass }}">
                                        {{ $patientCount }} {{ $patientText }}
                                    </span>
                                </div>

                                <!-- Progress bar showing percentage -->
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $severityClass }}" role="progressbar"
                                        style="width: {{ min(100, $percentage) }}%"
                                        aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>

                                <small class="text-muted">
                                    {{ $percentage }}% of {{ $totalAssessedPatients }} assessed
                                    {{ $totalPatientText }}
                                </small>
                            </div>
                        @endforeach

                        <!-- Summary note -->
                        <div class="alert alert-light border mt-3 p-2">
                            <small class="text-muted">
                                <i class="bx bx-info-circle me-1"></i>
                                Showing unique patients per risk factor from latest assessments
                            </small>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bx bx-shield-plus bx-lg text-success mb-2"></i>
                            <p class="text-muted mb-0">No significant risk factors identified</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Service Utilization -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Service Utilization</h5>
                    <small class="text-muted">Cross-service coverage</small>
                </div>
                <div class="card-body">
                    @if (isset($facilityRiskSummary['service_utilization']))
                        @php $utilization = $facilityRiskSummary['service_utilization']; @endphp
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="metric-card metric-card-emerald h-100">
                                    <div class="metric-label">Deliveries</div>
                                    <div class="metric-value">{{ $utilization['with_deliveries'] ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-card metric-card-sky h-100">
                                    <div class="metric-label">Postnatal</div>
                                    <div class="metric-value">{{ $utilization['with_postnatal'] ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-card metric-card-amber h-100">
                                    <div class="metric-label">Tetanus</div>
                                    <div class="metric-value">{{ $utilization['with_tetanus'] ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-card metric-card-violet h-100">
                                    <div class="metric-label">Total Patients</div>
                                    <div class="metric-value">{{ $utilization['total_patients'] ?? 0 }}</div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            Service utilization data is not available for this scope yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Risk Distribution Chart -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Risk Level Distribution</h5>
                    <small class="text-muted">Visual breakdown</small>
                </div>
                <div class="card-body">
                    @php
                        $rd = $facilityRiskSummary['risk_distribution'] ?? ['low' => 0, 'moderate' => 0, 'high' => 0, 'critical' => 0];
                        $distributionTotal = ($rd['low'] ?? 0) + ($rd['moderate'] ?? 0) + ($rd['high'] ?? 0) + ($rd['critical'] ?? 0);
                    @endphp
                    @if ($distributionTotal > 0)
                        <canvas id="riskChart" style="max-height: 200px;"></canvas>
                    @else
                        <div class="text-center py-4">
                            <i class="bx bx-pie-chart-alt-2 bx-lg text-muted mb-2"></i>
                            <p class="text-muted mb-0">No risk distribution data available yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- High Risk Patients Table -->
    @if (count($highRiskPatients) > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-user-check me-2"></i>
                            High-Risk Patients Requiring Assessment
                        </h5>
                        <small class="text-muted">Patients needing immediate attention</small>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="dataTable" class="table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Patient Name</th>
                                    <th>DIN</th>
                                    <th>Age</th>
                                    <th>Gestational Age</th>
                                    <th>EDD</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($highRiskPatients as $patient)
                                    <tr>
                                        <td>{{ $patient->patient_name ?? trim(($patient->patient->first_name ?? '') . ' ' . ($patient->patient->last_name ?? '')) }}</td>
                                        <td><span class="badge bg-label-info">{{ $patient->patient_din ?? ($patient->patient->DIN ?? 'N/A') }}</span></td>
                                        <td>{{ $patient->patient_age ?? $patient->age ?? 'N/A' }}{{ isset($patient->patient_age) || isset($patient->age) ? ' years' : '' }}</td>
                                        <td>{{ $patient->gestational_age_label ?? 'N/A' }}</td>
                                        <td>{{ $patient->edd ? Carbon::parse($patient->edd)->format('M d, Y') : 'N/A' }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <button wire:click="assessPatientRisk({{ $patient->patient_id }})"
                                                    class="btn btn-sm btn-info">
                                                    <i class="bx bx-search-alt me-1"></i>
                                                    View Details
                                                </button>

                                                <button wire:click="performAIAssessment({{ $patient->patient_id }})"
                                                    class="btn btn-sm btn-secondary">
                                                    <i class="bx bx-brain me-1"></i>
                                                    Analyse with AI
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Risk Assessment Modal -->
    @if ($showAssessmentModal && $riskAssessment)
        <div class="modal fade show" style="display: block;" tabindex="-1" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bx bx-shield-alt-2 me-2"></i>
                            AI Risk Assessment - {{ $riskAssessment['patient_name'] }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Risk Score Overview -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div
                                    class="card bg-{{ $riskAssessment['risk_level'] === 'critical' ? 'danger' : ($riskAssessment['risk_level'] === 'high' ? 'warning' : ($riskAssessment['risk_level'] === 'moderate' ? 'info' : 'success')) }}">
                                    <div class="card-body text-white text-center">
                                        <h2 class="mb-1">{{ $riskAssessment['total_risk_score'] }}</h2>
                                        <h5 class="text-uppercase mb-0">{{ $riskAssessment['risk_level'] }} Risk</h5>
                                        <small>{{ round($riskAssessment['risk_percentage']) }}% Risk Score</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Patient Information</h6>
                                <p><strong>DIN:</strong> {{ $riskAssessment['din'] }}</p>
                                <p><strong>Gestational Age:</strong> {{ $riskAssessment['gestational_age'] }}</p>
                                <p><strong>BMI:</strong> {{ $riskAssessment['bmi'] ?? 'N/A' }}</p>
                                <p><strong>EDD:</strong>
                                    {{ Carbon::parse($riskAssessment['edd'])->format('M d, Y') }}</p>
                            </div>
                        </div>

                        <!-- Identified Risk Factors -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-danger">
                                    <i class="bx bx-error-circle me-2"></i>
                                    Identified Risk Factors ({{ count($riskAssessment['identified_risks']) }})
                                </h6>
                                @if (count($riskAssessment['identified_risks']) > 0)
                                    <div class="row">
                                        @foreach ($riskAssessment['identified_risks'] as $risk)
                                            <div class="col-md-6 mb-3">
                                                <div class="alert alert-warning p-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>{{ ucfirst(str_replace('_', ' ', $risk['factor'])) }}</strong><br>
                                                            <small
                                                                class="text-muted">{{ $risk['description'] }}</small>
                                                        </div>
                                                        <span class="badge bg-warning">{{ $risk['weight'] }}
                                                            pts</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="alert alert-success">
                                        <i class="bx bx-check-circle me-2"></i>
                                        No significant risk factors identified
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Service History Overview -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-info">
                                    <i class="bx bx-history me-2"></i>
                                    Service History Overview
                                </h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="card bg-primary">
                                            <div class="card-body text-white text-center py-2">
                                                <h4 class="mb-1">
                                                    {{ $riskAssessment['service_history']['antenatal_visits'] ?? 0 }}
                                                </h4>
                                                <small>Antenatal Visits</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-success">
                                            <div class="card-body text-white text-center py-2">
                                                <h4 class="mb-1">
                                                    {{ $riskAssessment['service_history']['delivery_count'] ?? 0 }}
                                                </h4>
                                                <small>Deliveries</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-info">
                                            <div class="card-body text-white text-center py-2">
                                                <h4 class="mb-1">
                                                    {{ $riskAssessment['service_history']['postnatal_visits'] ?? 0 }}
                                                </h4>
                                                <small>Postnatal Visits</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-warning">
                                            <div class="card-body text-white text-center py-2">
                                                <h4 class="mb-1">
                                                    {{ $riskAssessment['service_history']['clinical_notes'] ?? 0 }}
                                                </h4>
                                                <small>Clinical Notes</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recommendations -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary">
                                    <i class="bx bx-clipboard me-2"></i>
                                    Clinical Recommendations
                                </h6>
                                @if (count($riskAssessment['recommendations']) > 0)
                                    <div class="alert alert-info">
                                        <ul class="mb-0">
                                            @foreach ($riskAssessment['recommendations'] as $recommendation)
                                                <li>{{ $recommendation }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    <div class="alert alert-success">
                                        <i class="bx bx-check-circle me-2"></i>
                                        Continue routine antenatal care
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Next Visit Recommendation -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-success">
                                    <i class="bx bx-calendar-check me-2"></i>
                                    Next Visit Schedule
                                </h6>
                                <div class="alert alert-success">
                                    <strong>{{ $riskAssessment['next_visit_recommendation'] }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Assessment Details -->
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-info">Assessment Details</h6>
                                <p><strong>Assessment Date:</strong>
                                    {{ Carbon::parse($riskAssessment['assessment_date'])->format('M d, Y g:i A') }}
                                </p>
                                <p><strong>Risk Score:</strong> {{ $riskAssessment['total_risk_score'] }}/200</p>
                                <p><strong>Risk Percentage:</strong> {{ round($riskAssessment['risk_percentage']) }}%
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info">Risk Level Guidelines</h6>
                                <small class="text-muted">
                                    <div class="mb-1"><span class="badge bg-success me-2">Low</span> 0-14 points
                                    </div>
                                    <div class="mb-1"><span class="badge bg-info me-2">Moderate</span> 15-29 points
                                    </div>
                                    <div class="mb-1"><span class="badge bg-warning me-2">High</span> 30-49 points
                                    </div>
                                    <div class="mb-1"><span class="badge bg-danger me-2">Critical</span> 50+ points
                                    </div>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">
                            <i class="bx bx-x me-1"></i>
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let riskChart = null;

        function initializeCharts() {
            try {
                if (riskChart) {
                    riskChart.destroy();
                    riskChart = null;
                }

                @if (isset($facilityRiskSummary['risk_distribution']) && !empty($facilityRiskSummary['risk_distribution']))
                    const riskData = @json($facilityRiskSummary['risk_distribution']);
                    const hasData = riskData.low > 0 || riskData.moderate > 0 || riskData.high > 0 || riskData.critical > 0;

                    const riskCtx = document.getElementById('riskChart');
                    if (riskCtx && hasData) {
                        riskChart = new Chart(riskCtx.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: ['Low Risk', 'Moderate Risk', 'High Risk', 'Critical Risk'],
                                datasets: [{
                                    data: [riskData.low, riskData.moderate, riskData.high, riskData
                                        .critical
                                    ],
                                    backgroundColor: [
                                        'rgba(40, 199, 111, 0.8)',
                                        'rgba(3, 195, 236, 0.8)',
                                        'rgba(255, 171, 0, 0.8)',
                                        'rgba(234, 84, 85, 0.8)'
                                    ],
                                    borderColor: ['#28c76f', '#03c3ec', '#ffab00', '#ea5455'],
                                    borderWidth: 2,
                                    hoverBorderWidth: 3,
                                    hoverOffset: 10
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 15,
                                            font: {
                                                size: 11,
                                                weight: '500'
                                            }
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        borderColor: 'rgba(255, 255, 255, 0.1)',
                                        borderWidth: 1,
                                        cornerRadius: 8,
                                        displayColors: true,
                                        callbacks: {
                                            label: function(context) {
                                                return context.label + ': ' + context.parsed + '%';
                                            }
                                        }
                                    }
                                },
                                cutout: '60%'
                            }
                        });
                    } else if (riskCtx) {
                        const ctx = riskCtx.getContext('2d');
                        ctx.font = "14px Arial";
                        ctx.textAlign = "center";
                        ctx.fillStyle = "#6c757d";
                        ctx.fillText("No patient data available", riskCtx.width / 2, riskCtx.height / 2);
                    }
                @endif
            } catch (error) {
                console.error('Chart initialization failed:', error);
            }
        }

        function scheduleRiskChartsInit() {
            setTimeout(() => {
                initializeCharts();
            }, 100);
        }

        if (!window.__riskAnalyticsChartsBound) {
            window.__riskAnalyticsChartsBound = true;

            document.addEventListener('DOMContentLoaded', scheduleRiskChartsInit);
            document.addEventListener('livewire:navigated', scheduleRiskChartsInit);

            document.addEventListener('livewire:initialized', () => {
                Livewire.on('risk-data-updated', scheduleRiskChartsInit);
            });
        }

        scheduleRiskChartsInit();
    </script>

    <style>
        .modal.show {
            background-color: rgba(0, 0, 0, 0.5);
        }

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

        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }
        }

        @media print {

            .modal-header .btn-close,
            .modal-footer button {
                display: none !important;
            }
        }
    </style>

    @include('_partials.datatables-init')
</div>


