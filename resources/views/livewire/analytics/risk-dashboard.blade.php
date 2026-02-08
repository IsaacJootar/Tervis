<div>
    @section('title', 'Risk Assessment Dashboard')
    @php
        use Illuminate\Support\Facades\Auth;
        use Carbon\Carbon;

    @endphp

    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 28px;">
                            <i class='bx bx-shield-alt-2 me-2'></i>
                            AI-Powered Risk Assessment & Diagnostics Assistant Dashboard
                        </h4>
                        <p class="hero-subtitle mb-2" style="color: rgba(255,255,255,0.9);">
                            {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                        </p>
                        <div class="hero-stats">
                            <span class="hero-stat">
                                <i class="bx bx-group"></i>
                                {{ $facilityRiskSummary['total_patients'] ?? 0 }} Total Patients
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-error-circle"></i>
                                {{ ($facilityRiskSummary['high_risk'] ?? 0) + ($facilityRiskSummary['critical_risk'] ?? 0) }}
                                High Risk
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-shield-plus"></i>
                                {{ $facilityRiskSummary['service_utilization']['with_deliveries'] ?? 0 }} Deliveries
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-female"></i>
                                {{ $facilityRiskSummary['service_utilization']['with_postnatal'] ?? 0 }} Postnatal
                            </span>
                        </div>

                        @if (isset($facilityRiskSummary['facility_info']))
                            <div class="d-flex flex-wrap gap-3 text-white mb-3 mt-2" style="font-size: 14px;">
                                <span>
                                    <i class="bx bx-building me-1"></i>
                                    <strong>
                                        @if ($selectedFacilityId)
                                            Facility:
                                        @else
                                            Scope:
                                        @endif
                                    </strong>
                                    @if ($selectedFacilityId)
                                        {{ $facilityRiskSummary['facility_info']['name'] ?? 'N/A' }}
                                    @else
                                        {{ $scopeInfo['scope_type'] === 'state' ? 'State-wide' : ($scopeInfo['scope_type'] === 'lga' ? 'LGA-wide' : 'Single Facility') }}
                                        ({{ count($scopeInfo['facility_ids']) }} facilities)
                                    @endif
                                </span>

                                @if (!$selectedFacilityId && isset($facilityRiskSummary['facility_info']['state']))
                                    <span>
                                        <i class="bx bx-map me-1"></i>
                                        <strong>State:</strong>
                                        {{ $facilityRiskSummary['facility_info']['state'] }}
                                    </span>
                                @endif

                                @if ($selectedFacilityId || $scopeInfo['scope_type'] === 'lga')
                                    <span>
                                        <i class="bx bx-map-alt me-1"></i>
                                        <strong>LGA:</strong>
                                        {{ $facilityRiskSummary['facility_info']['lga'] ?? (Auth::user()->lga->name ?? 'N/A') }}
                                    </span>
                                @endif

                                @if ($selectedFacilityId)
                                    <span>
                                        <i class="bx bx-map-pin me-1"></i>
                                        <strong>Ward:</strong>
                                        {{ $facilityRiskSummary['facility_info']['ward'] ?? 'N/A' }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        <div class="mt-3">
                            <button wire:click="refreshData"
                                class="btn btn-light rounded-pill shadow-sm d-inline-flex align-items-center"
                                style="border: 1px solid rgba(255,255,255,0.3); padding: 8px 20px;">
                                <i class="bx bx-refresh me-2" style="font-size: 18px;"></i>
                                Refresh Risk Data
                            </button>
                        </div>
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
            <div class="col-md-4 d-flex align-items-end">
                @if ($selectedFacilityId)
                    <button wire:click="resetToScope" class="btn btn-outline-secondary btn-lg">
                        <i class="bx bx-reset me-1"></i>
                        View All Facilities
                    </button>
                @endif
            </div>
        </div>
    @endif


    <!-- Risk Distribution Cards -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-success">
                                <i class="bx bx-check-circle bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $facilityRiskSummary['low_risk'] ?? 0 }}</h5>
                            <small class="text-muted">Low Risk</small>
                            <div class="mt-1">
                                <small
                                    class="text-success">{{ $facilityRiskSummary['risk_distribution']['low'] ?? 0 }}%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-info">
                                <i class="bx bx-info-circle bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $facilityRiskSummary['moderate_risk'] ?? 0 }}</h5>
                            <small class="text-muted">Moderate Risk</small>
                            <div class="mt-1">
                                <small
                                    class="text-info">{{ $facilityRiskSummary['risk_distribution']['moderate'] ?? 0 }}%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-warning">
                                <i class="bx bx-error bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $facilityRiskSummary['high_risk'] ?? 0 }}</h5>
                            <small class="text-muted">High Risk</small>
                            <div class="mt-1">
                                <small
                                    class="text-warning">{{ $facilityRiskSummary['risk_distribution']['high'] ?? 0 }}%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-danger">
                                <i class="bx bx-error-circle bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $facilityRiskSummary['critical_risk'] ?? 0 }}</h5>
                            <small class="text-muted">Critical Risk</small>
                            <div class="mt-1">
                                <small
                                    class="text-danger">{{ $facilityRiskSummary['risk_distribution']['critical'] ?? 0 }}%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Performance Metrics Row -->
    <div class="row mb-4">
        @php $aiMetrics = $this->getAIMetrics(); @endphp

        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-primary">
                                <i class="bx bx-brain bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $aiMetrics['total_assessments'] }}</h5>
                            <small class="text-muted">AI Assessments</small>
                            <div class="mt-1">
                                <small class="text-primary">Total completed</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-info">
                                <i class="bx bx-calendar-week bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $aiMetrics['this_week'] }}</h5>
                            <small class="text-muted">This Week</small>
                            <div class="mt-1">
                                <small class="text-info">Recent assessments</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-warning">
                                <i class="bx bx-shield-quarter bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $aiMetrics['high_risk_detected'] }}</h5>
                            <small class="text-muted">High Risk</small>
                            <div class="mt-1">
                                <small class="text-warning">AI detected</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-success">
                                <i class="bx bx-badge-check bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $aiMetrics['average_confidence'] }}%</h5>
                            <small class="text-muted">AI Confidence</small>
                            <div class="mt-1">
                                <small class="text-success">Average accuracy</small>
                            </div>
                        </div>
                    </div>
                </div>
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
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-success">
                                            <i class="bx bx-baby-carriage text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $utilization['with_deliveries'] ?? 0 }}</h6>
                                        <small class="text-muted">With Deliveries</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-info">
                                            <i class="bx bx-female text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $utilization['with_postnatal'] ?? 0 }}</h6>
                                        <small class="text-muted">With Postnatal</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-warning">
                                            <i class="bx bx-injection text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $utilization['with_tetanus'] ?? 0 }}</h6>
                                        <small class="text-muted">With Tetanus</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-primary">
                                            <i class="bx bx-group text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $utilization['total_patients'] ?? 0 }}</h6>
                                        <small class="text-muted">Total Patients</small>
                                    </div>
                                </div>
                            </div>
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
                    <canvas id="riskChart" style="max-height: 200px;"></canvas>
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
                                        <td>{{ $patient->user->first_name }} {{ $patient->user->last_name }}</td>
                                        <td><span class="badge bg-label-info">{{ $patient->user->DIN }}</span></td>
                                        <td>{{ $patient->age }} years</td>
                                        <td>
                                            @php
                                                $lmp = Carbon::parse($patient->lmp);
                                                $now = Carbon::now();
                                                $weeks = $lmp->diffInWeeks($now);
                                                $days = $lmp->diffInDays($now) % 7;
                                            @endphp
                                            {{ $weeks }}w {{ $days }}d
                                        </td>
                                        <td>{{ Carbon::parse($patient->edd)->format('M d, Y') }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <button wire:click="assessPatientRisk({{ $patient->user_id }})"
                                                    class="btn btn-sm btn-info">
                                                    <i class="bx bx-search-alt me-1"></i>
                                                    View Details
                                                </button>

                                                <button wire:click="performAIAssessment({{ $patient->user_id }})"
                                                    class="btn btn-sm btn-secondary">
                                                    <i class="bx bx-brain me-1"></i>
                                                    Analyse with AI
                                                </button>
                                                <button wire:click="viewDiagnosticSummary({{ $patient->user_id }})"
                                                    class="btn btn-sm btn-warning">
                                                    <i class="bx bx-analyse me-1"></i>
                                                    Diagnostic Assistant
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

    {{-- Clinical Diagnostic Summary Modal --}}
    @if ($showDiagnosticModal && $diagnosticSummary)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header  text-white">
                        <h5 class="modal-title">
                            <i class="bx bx-analyse me-2"></i>
                            Clinical Diagnostic Summary - {{ $diagnosticSummary['patient_info']['name'] }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white"
                            wire:click="closeDiagnosticModal"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Patient Info --}}
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Patient Information</h6>
                                        <p class="mb-1"><strong>Name:</strong>
                                            {{ $diagnosticSummary['patient_info']['name'] }}</p>
                                        <p class="mb-1"><strong>DIN:</strong>
                                            {{ $diagnosticSummary['patient_info']['din'] }}</p>
                                        <p class="mb-1"><strong>Age:</strong>
                                            {{ $diagnosticSummary['patient_info']['age'] }} years</p>
                                        <p class="mb-0"><strong>Phone:</strong>
                                            {{ $diagnosticSummary['patient_info']['phone'] ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Current Pregnancy Status</h6>
                                        <p class="mb-1"><strong>Gestational Age:</strong>
                                            {{ $diagnosticSummary['clinical_snapshot']['gestational_age'] }}</p>
                                        <p class="mb-1"><strong>Trimester:</strong>
                                            {{ $diagnosticSummary['clinical_snapshot']['trimester'] }}</p>
                                        <p class="mb-1"><strong>EDD:</strong>
                                            {{ Carbon::parse($diagnosticSummary['clinical_snapshot']['edd'])->format('M d, Y') }}
                                        </p>
                                        <p class="mb-0"><strong>Days Until EDD:</strong>
                                            {{ $diagnosticSummary['clinical_snapshot']['days_until_edd'] ?? 'N/A' }}
                                            days</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Risk Overview --}}
                        <div
                            class="card mb-3 border-{{ $diagnosticSummary['clinical_snapshot']['overall_risk']['level'] === 'critical' ? 'danger' : ($diagnosticSummary['clinical_snapshot']['overall_risk']['level'] === 'high' ? 'warning' : 'info') }}">
                            <div
                                class="card-header bg-{{ $diagnosticSummary['clinical_snapshot']['overall_risk']['level'] === 'critical' ? 'danger' : ($diagnosticSummary['clinical_snapshot']['overall_risk']['level'] === 'high' ? 'warning' : 'info') }} text-white">
                                <h6 class="mb-0"><i class="bx bx-error-circle me-2"></i>Overall Risk Assessment</h6>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <h3 class="mb-0">
                                            {{ ucfirst($diagnosticSummary['clinical_snapshot']['overall_risk']['level']) }}
                                            Risk</h3>
                                        <small class="text-muted">Risk Score:
                                            {{ $diagnosticSummary['clinical_snapshot']['overall_risk']['score'] }}/200</small>
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

                        {{-- Primary Concerns --}}
                        @if (!empty($diagnosticSummary['primary_concerns']))
                            <div class="card mb-3">
                                <div class="card-header bg-label-warning">
                                    <h6 class="mb-0"><i class="bx bx-error-circle me-2"></i>Primary Clinical
                                        Concerns</h6>
                                </div>
                                <div class="card-body">
                                    @foreach ($diagnosticSummary['primary_concerns'] as $concern)
                                        <div
                                            class="alert alert-{{ $concern['severity'] === 'Critical' ? 'danger' : 'warning' }} mb-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="alert-heading mb-1">{{ $concern['concern'] }}</h6>
                                                    <p class="mb-1"><strong>Severity:</strong> <span
                                                            class="badge bg-{{ $concern['severity'] === 'Critical' ? 'danger' : 'warning' }}">{{ $concern['severity'] }}</span>
                                                    </p>
                                                    <p class="mb-1"><strong>Category:</strong>
                                                        {{ ucfirst(str_replace('_', ' ', $concern['category'])) }}</p>
                                                    <p class="mb-0"><strong>Clinical Impact:</strong>
                                                        {{ $concern['clinical_impact'] }}</p>
                                                </div>
                                                <span
                                                    class="badge bg-light text-dark">{{ round($concern['confidence'] * 100) }}%
                                                    confidence</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Clinical Reasoning --}}
                        <div class="card mb-3">
                            <div class="card-header bg-label-info">
                                <h6 class="mb-0"><i class="bx bx-brain me-2"></i>Clinical Reasoning & Analysis</h6>
                            </div>
                            <div class="card-body">
                                @foreach ($diagnosticSummary['clinical_reasoning'] as $index => $reasoning)
                                    <div
                                        class="border-start border-primary border-3 ps-3 mb-3 pb-3 {{ $index < count($diagnosticSummary['clinical_reasoning']) - 1 ? 'border-bottom' : '' }}">
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

                        {{-- Vital Signs Summary --}}
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bx bx-heart me-2"></i>Current Vital Signs & Clinical Data
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3">
                                            <h6 class="mb-2">Blood Pressure</h6>
                                            <p class="mb-1">
                                                <strong>{{ $diagnosticSummary['clinical_snapshot']['vitals']['blood_pressure'] ?? 'Not recorded' }}</strong>
                                            </p>
                                            <span
                                                class="badge bg-{{ $diagnosticSummary['clinical_snapshot']['vitals']['bp_status'] === 'Normal' ? 'success' : 'warning' }}">
                                                {{ $diagnosticSummary['clinical_snapshot']['vitals']['bp_status'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3">
                                            <h6 class="mb-2">Hemoglobin</h6>
                                            <p class="mb-1">
                                                <strong>{{ $diagnosticSummary['clinical_snapshot']['vitals']['hemoglobin'] ?? 'Not recorded' }}
                                                    g/dL</strong>
                                            </p>
                                            <span
                                                class="badge bg-{{ $diagnosticSummary['clinical_snapshot']['vitals']['hb_status'] === 'Normal' ? 'success' : 'warning' }}">
                                                {{ $diagnosticSummary['clinical_snapshot']['vitals']['hb_status'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3">
                                            <h6 class="mb-2">BMI</h6>
                                            <p class="mb-1">
                                                <strong>{{ $diagnosticSummary['clinical_snapshot']['vitals']['bmi'] ?? 'Not calculated' }}</strong>
                                            </p>
                                            <span
                                                class="badge bg-{{ $diagnosticSummary['clinical_snapshot']['vitals']['bmi_category'] === 'Normal' ? 'success' : 'info' }}">
                                                {{ $diagnosticSummary['clinical_snapshot']['vitals']['bmi_category'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Blood Group:</strong>
                                            {{ $diagnosticSummary['clinical_snapshot']['blood_work']['blood_group'] ?? 'Unknown' }}
                                        </p>
                                        <p class="mb-0"><strong>Genotype:</strong>
                                            {{ $diagnosticSummary['clinical_snapshot']['blood_work']['genotype'] ?? 'Unknown' }}
                                            <small
                                                class="text-muted">({{ $diagnosticSummary['clinical_snapshot']['blood_work']['genotype_risk'] }})</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Immediate Actions --}}
                        @if (!empty($diagnosticSummary['immediate_actions']))
                            <div class="card mb-3">
                                <div class="card-header bg-label-danger">
                                    <h6 class="mb-0"><i class="bx bx-list-check me-2"></i>Recommended Immediate
                                        Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="15%">Priority</th>
                                                    <th width="35%">Action Required</th>
                                                    <th width="20%">Timeframe</th>
                                                    <th width="30%">Reason</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($diagnosticSummary['immediate_actions'] as $action)
                                                    <tr>
                                                        <td>
                                                            <span
                                                                class="badge bg-{{ $action['priority'] === 'urgent' ? 'danger' : ($action['priority'] === 'high' ? 'warning' : ($action['priority'] === 'medium' ? 'info' : 'secondary')) }}">
                                                                {{ ucfirst($action['priority']) }}
                                                            </span>
                                                        </td>
                                                        <td><strong>{{ $action['action'] }}</strong></td>
                                                        <td>{{ $action['timeframe'] }}</td>
                                                        <td><small>{{ $action['reason'] }}</small></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Monitoring Plan --}}
                        <div class="card mb-3">
                            <div class="card-header bg-label-success">
                                <h6 class="mb-0"><i class="bx bx-calendar-check me-2"></i>Monitoring & Follow-up
                                    Plan</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-success">Visit Schedule</h6>
                                        <p class="mb-1"><strong>Recommended Frequency:</strong>
                                            {{ $diagnosticSummary['monitoring_plan']['visit_frequency'] }}</p>
                                        <p class="mb-3"><strong>Next Visit Due:</strong>
                                            {{ Carbon::parse($diagnosticSummary['monitoring_plan']['next_visit_due'])->format('M d, Y') }}
                                        </p>

                                        <h6 class="text-success">Parameters to Monitor</h6>
                                        <ul class="list-unstyled">
                                            @foreach ($diagnosticSummary['monitoring_plan']['monitoring_parameters'] as $param)
                                                <li class="mb-1"><i
                                                        class="bx bx-check text-success me-2"></i>{{ $param }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    <div class="col-md-6">
                                        @if (!empty($diagnosticSummary['monitoring_plan']['specialist_referrals']))
                                            <h6 class="text-success">Required Specialist Referrals</h6>
                                            @foreach ($diagnosticSummary['monitoring_plan']['specialist_referrals'] as $referral)
                                                <div class="alert alert-info mb-2 p-2">
                                                    <strong>{{ $referral['specialist'] }}</strong>
                                                    <span
                                                        class="badge bg-{{ $referral['urgency'] === 'high' ? 'danger' : 'warning' }} float-end">
                                                        {{ ucfirst($referral['urgency']) }} Priority
                                                    </span>
                                                    <br><small class="text-muted">{{ $referral['reason'] }}</small>
                                                </div>
                                            @endforeach
                                        @endif

                                        @if (!empty($diagnosticSummary['monitoring_plan']['lab_schedule']))
                                            <h6 class="text-success mt-3">Laboratory Tests Schedule</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Test</th>
                                                            <th>Timing</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($diagnosticSummary['monitoring_plan']['lab_schedule'] as $lab)
                                                            <tr>
                                                                <td>{{ $lab['test'] }}</td>
                                                                <td><small
                                                                        class="text-muted">{{ $lab['timing'] }}</small>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Risk Trajectory --}}
                        @if ($diagnosticSummary['risk_trajectory']['trend'] !== 'insufficient_data')
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i
                                            class="bx bx-trending-{{ $diagnosticSummary['risk_trajectory']['trend'] === 'increasing' ? 'up text-danger' : ($diagnosticSummary['risk_trajectory']['trend'] === 'decreasing' ? 'down text-success' : 'flat text-secondary') }} me-2"></i>
                                        Risk Trajectory Analysis
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-2">
                                                <strong>Trend:</strong>
                                                <span
                                                    class="badge bg-{{ $diagnosticSummary['risk_trajectory']['trend'] === 'increasing' ? 'danger' : ($diagnosticSummary['risk_trajectory']['trend'] === 'decreasing' ? 'success' : 'secondary') }}">
                                                    {{ ucfirst($diagnosticSummary['risk_trajectory']['trend']) }}
                                                </span>
                                            </p>
                                            <p class="mb-2"><strong>Score Change:</strong>
                                                {{ $diagnosticSummary['risk_trajectory']['score_change'] > 0 ? '+' : '' }}{{ $diagnosticSummary['risk_trajectory']['score_change'] }}
                                                points</p>
                                            <p class="mb-2"><strong>Current Score:</strong>
                                                {{ $diagnosticSummary['risk_trajectory']['current_score'] }}</p>
                                            <p class="mb-0"><strong>Previous Score:</strong>
                                                {{ $diagnosticSummary['risk_trajectory']['previous_score'] }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <div
                                                class="alert alert-{{ $diagnosticSummary['risk_trajectory']['trend'] === 'increasing' ? 'danger' : 'info' }}">
                                                <strong>Clinical Interpretation:</strong><br>
                                                {{ $diagnosticSummary['risk_trajectory']['interpretation'] }}
                                            </div>
                                            <small class="text-muted">
                                                Based on
                                                {{ $diagnosticSummary['risk_trajectory']['assessments_count'] }}
                                                assessments
                                                ({{ Carbon::parse($diagnosticSummary['risk_trajectory']['first_assessment'])->format('M d') }}
                                                -
                                                {{ Carbon::parse($diagnosticSummary['risk_trajectory']['latest_assessment'])->format('M d, Y') }})
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Care Gaps --}}
                        @if (!empty($diagnosticSummary['care_gaps']))
                            <div class="card mb-3">
                                <div class="card-header bg-label-warning">
                                    <h6 class="mb-0"><i class="bx bx-error-alt me-2"></i>Identified Gaps in Care
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @foreach ($diagnosticSummary['care_gaps'] as $gap)
                                        <div class="border-start border-warning border-3 ps-3 mb-3">
                                            <h6 class="text-warning mb-2">{{ $gap['gap'] }}</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Category:</strong>
                                                        {{ $gap['category'] }}</p>
                                                    <p class="mb-1"><strong>Current Status:</strong>
                                                        {{ $gap['current_status'] }}</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Required:</strong>
                                                        {{ $gap['required'] ?? $gap['expected'] }}</p>
                                                    <p class="mb-0"><strong>Recommended Action:</strong> <span
                                                            class="text-primary">{{ $gap['action'] }}</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Metadata --}}
                        <div class="card mb-3 bg-light">
                            <div class="card-body p-2">
                                <small class="text-muted">
                                    <strong>Report Generated:</strong>
                                    {{ $diagnosticSummary['metadata']['generated_at']->format('M d, Y h:i A') }} |
                                    <strong>Assessment Date:</strong>
                                    {{ Carbon::parse($diagnosticSummary['metadata']['assessment_date'])->format('M d, Y') }}
                                    |
                                    <strong>Model Version:</strong>
                                    {{ $diagnosticSummary['metadata']['model_version'] }} |
                                    <strong>Overall Confidence:</strong>
                                    {{ $diagnosticSummary['metadata']['confidence'] }}%
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- Legal Disclaimer Footer --}}
                    <div class="modal-footer bg-light border-top">
                        <div class="w-100">
                            <div class="alert alert-warning mb-3" role="alert">
                                <h6 class="alert-heading mb-2">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <strong>Clinical Decision Support Tool - Medical-Legal Notice</strong>
                                </h6>
                                <div class="small">
                                    <p class="mb-2"><strong>This is a clinical decision support tool and NOT a
                                            medical diagnosis or treatment plan.</strong></p>
                                    <ul class="mb-2">
                                        <li>All recommendations, assessments, and analyses require review,
                                            interpretation, and approval by a qualified healthcare provider</li>
                                        <li>This tool aids clinical decision-making but does not replace professional
                                            medical judgment or examination</li>
                                        <li>Healthcare providers must use their professional training, clinical
                                            experience, and judgment when interpreting and acting on these results</li>
                                        <li>Patient care decisions and treatment plans remain the sole responsibility of
                                            the treating clinician</li>
                                        <li>This system is designed to supplement, not substitute for, the knowledge,
                                            skills, and judgment of healthcare professionals</li>
                                    </ul>
                                    <p class="mb-0"><strong>For all emergency situations, follow established
                                            emergency protocols immediately. This tool should not delay emergency
                                            care.</strong></p>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">
                                        <i class="bx bx-shield-quarter me-1"></i>
                                        Clinician must document review and decision
                                    </small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-secondary me-2"
                                        wire:click="closeDiagnosticModal">
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
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Risk Assessment Modal -->
    @if ($showAssessmentModal && $riskAssessment)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-xl">
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
                        <button type="button" class="btn btn-primary">
                            <i class="bx bx-calendar-plus me-1"></i>
                            Schedule Follow-up
                        </button>
                        <button type="button" class="btn btn-info">
                            <i class="bx bx-printer me-1"></i>
                            Print Assessment
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

        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
        });

        document.addEventListener('livewire:initialized', () => {
            Livewire.on('risk-data-updated', () => {
                setTimeout(() => {
                    initializeCharts();
                }, 100);
            });
        });
    </script>

    <style>
        .hero-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            min-height: 220px;
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

        .hero-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: 1rem;
        }

        .hero-stat {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 500;
            font-size: 14px;
        }

        .hero-stat i {
            margin-right: 0.5rem;
            font-size: 18px;
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

        .avatar {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .avatar-initial {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .text-muted {
            color: #a7acb2 !important;
        }

        .modal.show {
            background-color: rgba(0, 0, 0, 0.5);
        }

        @media (max-width: 768px) {
            .hero-stats {
                gap: 1rem;
            }

            .hero-stat {
                font-size: 12px;
            }

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
