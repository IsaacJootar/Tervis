@section('title', 'Real-time Dashboard')
@php
    use Carbon\Carbon;
@endphp
<div>
    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 28px;">
                            <i class='bx bx-line-chart me-2'></i>
                            Real-time Analytics Dashboard
                        </h4>

                        <div class="hero-stats">
                            <span class="hero-stat">
                                <i class="bx bx-group"></i>
                                {{ number_format($metrics['total_patients'] ?? 0) }} Total Patients
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-calendar-check"></i>
                                {{ array_sum($metrics['today_visits'] ?? []) }} Today's Visits
                            </span>
                            @if (isset($metrics['facility_info']))
                                <span class="hero-stat">
                                    <i class="bx bx-building"></i>
                                    @if ($metrics['facility_info']['facility_count'] == 1 && isset($metrics['facility_info']['name']))
                                        {{ $metrics['facility_info']['name'] }}
                                    @else
                                        {{ $metrics['facility_info']['scope'] ?? 'Multi-facility' }}
                                    @endif
                                </span>
                            @endif
                            <span class="hero-stat">
                                <i class="bx bx-time"></i>
                                {{ Carbon::now('Africa/Lagos')->format('h:i A') }}
                            </span>
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
    <!-- Facility Filter (only show if multiple facilities) -->
    @if ($facilities->count() > 1)
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Filter by Facility (Optional)</label>
                <select wire:model.live="selectedFacilityId" class="form-select">
                    <option value="">All Facilities in Your Scope</option>
                    @foreach ($facilities as $facility)
                        <option value="{{ $facility->id }}">
                            {{ $facility->name }} ({{ $facility->lga }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button wire:click="toggleAutoRefresh"
                    class="btn btn-outline-{{ $autoRefresh ? 'success' : 'secondary' }} me-2">
                    <i class="bx bx-{{ $autoRefresh ? 'pause' : 'play' }}-circle"></i>
                    Auto-refresh {{ $autoRefresh ? 'ON' : 'OFF' }}
                </button>
                <button wire:click="refreshData" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove><i class="bx bx-refresh me-1"></i>Refresh</span>
                    <span wire:loading><span class="spinner-border spinner-border-sm me-1"></span>Loading...</span>
                </button>
            </div>
        </div>
    @else
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-end gap-2">
                <button wire:click="toggleAutoRefresh"
                    class="btn btn-outline-{{ $autoRefresh ? 'success' : 'secondary' }}">
                    <i class="bx bx-{{ $autoRefresh ? 'pause' : 'play' }}-circle"></i>
                    Auto-refresh {{ $autoRefresh ? 'ON' : 'OFF' }}
                </button>
                <button wire:click="refreshData" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove><i class="bx bx-refresh me-1"></i>Refresh</span>
                    <span wire:loading><span class="spinner-border spinner-border-sm me-1"></span>Loading...</span>
                </button>
            </div>
        </div>
    @endif
    <!-- Today's Services Row -->
    <div class="row mb-4">
        @if (isset($metrics['today_visits']))
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card" wire:click="viewServiceDetails('antenatal')" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-primary">
                                    <i class="bx bx-plus-medical bx-sm text-white"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $metrics['today_visits']['antenatal'] }}</h5>
                                <small class="text-muted">Antenatal Visits</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card" wire:click="viewServiceDetails('delivery')" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-success">
                                    <i class="bx bx-baby-carriage bx-sm text-white"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $metrics['today_visits']['delivery'] }}</h5>
                                <small class="text-muted">Today's Deliveries</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card" wire:click="viewServiceDetails('postnatal')" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-info">
                                    <i class="bx bx-heart bx-sm text-white"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $metrics['today_visits']['postnatal'] }}</h5>
                                <small class="text-muted">Postnatal Visits</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card" wire:click="viewServiceDetails('tetanus')" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-warning">
                                    <i class="bx bx-shield-plus bx-sm text-white"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $metrics['today_visits']['tetanus'] }}</h5>
                                <small class="text-muted">Tetanus Vaccines</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-secondary">
                                    <i class="bx bx-calendar-check bx-sm text-white"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $metrics['today_visits']['attendance'] }}</h5>
                                <small class="text-muted">Daily Attendance</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-dark">
                                    <i class="bx bx-file-blank bx-sm text-white"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $metrics['today_visits']['clinical_notes'] }}</h5>
                                <small class="text-muted">Clinical Notes</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Key Performance Indicators Row -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-danger">
                                <i class="bx bx-error-circle bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ count($metrics['high_risk_pregnancies'] ?? []) }}</h5>
                            <small class="text-muted">High-Risk Pregnancies</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-primary">
                                <i class="bx bx-calendar-event bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ count($metrics['upcoming_deliveries'] ?? []) }}</h5>
                            <small class="text-muted">Deliveries Next 7 Days</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-success">
                                <i class="bx bx-plus-medical bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $metrics['clinical_outcomes']['cesarean_rate'] ?? 0 }}%</h5>
                            <small class="text-muted">Cesarean Rate</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-warning">
                                <i class="bx bx-shield-plus bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $metrics['vaccination_coverage']['full_protection_rate'] ?? 0 }}%
                            </h5>
                            <small class="text-muted">TT Full Protection</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Coverage and Patient Journey Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Service Coverage Overview</h5>
                    <small class="text-muted">Total patients served by service type</small>
                </div>
                <div class="card-body">
                    @if (isset($metrics['service_coverage']))
                        <canvas id="serviceCoverageChart" style="max-height: 250px;"></canvas>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Patient Care Journey</h5>
                    <small class="text-muted">Completeness of maternal health services</small>
                </div>
                <div class="card-body">
                    @if (isset($metrics['patient_journey']))
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-success">
                                            <i class="bx bx-check-double text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $metrics['patient_journey']['complete_journey'] }}</h6>
                                        <small class="text-muted">Complete Journey</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-warning">
                                            <i class="bx bx-plus-medical text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $metrics['patient_journey']['antenatal_only'] }}</h6>
                                        <small class="text-muted">Antenatal Only</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-info">
                                            <i class="bx bx-baby-carriage text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $metrics['patient_journey']['missing_delivery'] }}</h6>
                                        <small class="text-muted">Missing Delivery</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-danger">
                                            <i class="bx bx-female text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $metrics['patient_journey']['missing_postnatal'] }}</h6>
                                        <small class="text-muted">Missing Postnatal</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- AI Predictions Summary -->
    @if (isset($metrics['ai_predictions']) && $metrics['ai_predictions']['total_predictions'] > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-brain me-2"></i>
                            AI Risk Predictions (Last 30 Days)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Patient</th>
                                                <th>Risk Level</th>
                                                <th>Score</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($metrics['ai_predictions']['latest_predictions'] as $prediction)
                                                <tr>
                                                    <td>
                                                        {{ $prediction->user->first_name }}
                                                        {{ $prediction->user->last_name }}
                                                        <br><small
                                                            class="text-muted">{{ $prediction->user->DIN }}</small>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge bg-{{ $prediction->risk_level === 'critical' ? 'danger' : ($prediction->risk_level === 'high' ? 'warning' : ($prediction->risk_level === 'moderate' ? 'info' : 'success')) }}">
                                                            {{ ucfirst($prediction->risk_level) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $prediction->total_risk_score }}</td>
                                                    <td>{{ $prediction->assessment_date->format('M d') }}</td>
                                                    <td>
                                                        @if ($prediction->is_overdue)
                                                            <span class="text-danger">Overdue</span>
                                                        @else
                                                            <span class="text-success">Current</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6>AI Performance</h6>
                                <p><strong>Total Assessments:</strong>
                                    {{ $metrics['ai_predictions']['total_predictions'] }}</p>
                                <p><strong>Average Confidence:</strong>
                                    {{ round($metrics['ai_predictions']['average_confidence'], 1) }}%</p>
                                <p><strong>Overdue Assessments:</strong>
                                    {{ $metrics['ai_predictions']['overdue_assessments'] }}</p>

                                <h6 class="mt-3">Risk Distribution</h6>
                                @foreach ($metrics['ai_predictions']['risk_distribution'] as $level => $count)
                                    <div class="d-flex justify-content-between">
                                        <span>{{ ucfirst($level) }}:</span>
                                        <span>{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Charts and Alerts Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">6-Month Health Services Trends</h5>
                    <small class="text-muted">Number of services provided per month</small>
                </div>
                <div class="card-body">
                    @if (isset($metrics['monthly_trends']))
                        <canvas id="trendsChart" style="max-height: 300px;"></canvas>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Care Alerts</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown">
                            Filter: {{ ucfirst($alertFilter) }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" wire:click="filterAlerts('all')">All
                                    Alerts</a></li>
                            <li><a class="dropdown-item" href="#"
                                    wire:click="filterAlerts('overdue_antenatal')">Overdue Antenatal</a></li>
                            <li><a class="dropdown-item" href="#"
                                    wire:click="filterAlerts('overdue_tetanus')">Overdue Tetanus</a></li>
                            <li><a class="dropdown-item" href="#"
                                    wire:click="filterAlerts('critical_delivery')">Critical Deliveries</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    @if (isset($filteredAlerts) && count($filteredAlerts) > 0)
                        <div class="alert-container" style="max-height: 400px; overflow-y: auto;">
                            @foreach ($filteredAlerts as $alert)
                                <div
                                    class="alert alert-{{ $alert['priority'] === 'critical' ? 'danger' : ($alert['priority'] === 'high' ? 'warning' : 'info') }} p-2 mb-2">
                                    <small>
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong>{{ $alert['message'] }}</strong><br>
                                                <span class="text-muted">DIN: {{ $alert['patient_din'] }}</span><br>
                                                <span class="text-muted">Service: {{ $alert['service'] }}</span>
                                            </div>
                                            <span
                                                class="badge bg-{{ $alert['priority'] === 'critical' ? 'danger' : ($alert['priority'] === 'high' ? 'warning' : 'info') }}">
                                                {{ ucfirst($alert['priority']) }}
                                            </span>
                                        </div>
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bx bx-check-circle bx-lg text-success mb-2"></i>
                            <p class="text-muted">No {{ $alertFilter === 'all' ? 'active' : $alertFilter }} alerts</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (isset($metrics['high_risk_pregnancies']) && count($metrics['high_risk_pregnancies']) > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">High-Risk Pregnancies</h5>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="dataTable" class="table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Patient Name</th>
                                    <th>DIN</th>
                                    <th>Age</th>
                                    <th>Risk Factors</th>
                                    <th>EDD</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($metrics['high_risk_pregnancies'] as $pregnancy)
                                    <tr>
                                        <td>{{ $pregnancy->user->first_name }} {{ $pregnancy->user->last_name }}</td>
                                        <td><span class="badge bg-label-info">{{ $pregnancy->user->DIN }}</span></td>
                                        <td>{{ $pregnancy->age }} years</td>
                                        <td>
                                            @php
                                                $riskFactors = [];
                                                if ($pregnancy->age < 18) {
                                                    $riskFactors[] = 'Teen pregnancy';
                                                }
                                                if ($pregnancy->age > 35) {
                                                    $riskFactors[] = 'Advanced maternal age';
                                                }
                                                if ($pregnancy->heart_disease) {
                                                    $riskFactors[] = 'Heart disease';
                                                }
                                                if ($pregnancy->kidney_disease) {
                                                    $riskFactors[] = 'Kidney disease';
                                                }
                                                if ($pregnancy->family_hypertension) {
                                                    $riskFactors[] = 'Family hypertension';
                                                }
                                                if ($pregnancy->bleeding) {
                                                    $riskFactors[] = 'Bleeding';
                                                }
                                            @endphp
                                            @foreach ($riskFactors as $factor)
                                                <span class="badge bg-label-warning mb-1">{{ $factor }}</span>
                                            @endforeach
                                        </td>
                                        <td>{{ Carbon::parse($pregnancy->edd)->format('M d, Y') }}</td>
                                        <td>
                                            <button wire:click="viewRiskDetails({{ $pregnancy->id }})"
                                                class="btn btn-sm btn-outline-primary">
                                                View Details
                                            </button>
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

    @if ($showServiceModal && $selectedService)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bx bx-chart-line me-2"></i>
                            {{ ucfirst($selectedService['name']) }} Service Analytics
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeServiceModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-primary">
                                    <div class="card-body text-white">
                                        <h3 class="mb-1">{{ $selectedService['data']['total_count'] ?? 0 }}</h3>
                                        <h6 class="mb-0">Total {{ ucfirst($selectedService['name']) }}</h6>
                                        <small>All time records</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-success">
                                    <div class="card-body text-white">
                                        <h3 class="mb-1">{{ $selectedService['data']['today_count'] ?? 0 }}</h3>
                                        <h6 class="mb-0">Today's {{ ucfirst($selectedService['name']) }}</h6>
                                        <small>{{ Carbon::now()->format('M d, Y') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($selectedService['name'] === 'delivery')
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card bg-warning">
                                        <div class="card-body text-white">
                                            <h4 class="mb-1">{{ $selectedService['data']['cesarean_rate'] ?? 0 }}%
                                            </h4>
                                            <h6 class="mb-0">Cesarean Rate</h6>
                                            <small>Of total deliveries</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-info">
                                        <div class="card-body text-white">
                                            <h4 class="mb-1">
                                                {{ 100 - ($selectedService['data']['cesarean_rate'] ?? 0) }}%</h4>
                                            <h6 class="mb-0">Normal Deliveries</h6>
                                            <small>Vaginal deliveries</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($selectedService['name'] === 'tetanus')
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="card bg-warning">
                                        <div class="card-body text-white">
                                            <h4 class="mb-1">{{ $selectedService['data']['protection_rate'] ?? 0 }}%
                                            </h4>
                                            <h6 class="mb-0">Full Protection Rate</h6>
                                            <small>Patients with complete TT series</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="mt-3">
                            <h6 class="text-muted">Service Description</h6>
                            <p class="mb-0">
                                {{ $selectedService['data']['description'] ?? 'No description available' }}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            wire:click="closeServiceModal">Close</button>
                        <button type="button" class="btn btn-primary">View Detailed Report</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    @if ($showRiskModal && $selectedRiskPatient)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">High-Risk Patient Details</h5>
                        <button type="button" class="btn-close" wire:click="closeRiskModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">Patient Information</h6>
                                <p><strong>Name:</strong> {{ $selectedRiskPatient['patient_name'] }}</p>
                                <p><strong>DIN:</strong> {{ $selectedRiskPatient['din'] }}</p>
                                <p><strong>Age:</strong> {{ $selectedRiskPatient['age'] }} years</p>
                                <p><strong>Phone:</strong> {{ $selectedRiskPatient['phone'] ?? 'N/A' }}</p>
                                <p><strong>Address:</strong> {{ $selectedRiskPatient['address'] }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary">Pregnancy Details</h6>
                                <p><strong>LMP:</strong>
                                    {{ Carbon::parse($selectedRiskPatient['lmp'])->format('M d, Y') }}</p>
                                <p><strong>EDD:</strong>
                                    {{ Carbon::parse($selectedRiskPatient['edd'])->format('M d, Y') }}</p>
                                <p><strong>Last Visit:</strong>
                                    {{ $selectedRiskPatient['last_visit'] ? Carbon::parse($selectedRiskPatient['last_visit'])->format('M d, Y') : 'N/A' }}
                                </p>
                                <p><strong>Next Visit:</strong>
                                    {{ $selectedRiskPatient['next_visit'] ? Carbon::parse($selectedRiskPatient['next_visit'])->format('M d, Y') : 'N/A' }}
                                </p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-danger">Risk Factors</h6>
                                @foreach ($selectedRiskPatient['risk_factors'] as $factor)
                                    <span class="badge bg-danger me-1 mb-1">{{ $factor }}</span>
                                @endforeach
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-warning">Vital Signs</h6>
                                <p><strong>Height:</strong> {{ $selectedRiskPatient['vitals']['height'] }} cm</p>
                                <p><strong>Weight:</strong> {{ $selectedRiskPatient['vitals']['weight'] }} kg</p>
                                <p><strong>BP:</strong> {{ $selectedRiskPatient['vitals']['blood_pressure'] }}</p>
                                <p><strong>Hemoglobin:</strong> {{ $selectedRiskPatient['vitals']['hemoglobin'] }} g/dL
                                </p>
                                <p><strong>Genotype:</strong> {{ $selectedRiskPatient['vitals']['genotype'] }}</p>
                                <p><strong>Blood Group:</strong>
                                    {{ $selectedRiskPatient['vitals']['blood_group_rhesus'] }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info">Medical History</h6>
                                <p><strong>Heart Disease:</strong>
                                    {{ $selectedRiskPatient['medical_history']['heart_disease'] ? 'Yes' : 'No' }}</p>
                                <p><strong>Chest Disease:</strong>
                                    {{ $selectedRiskPatient['medical_history']['chest_disease'] ? 'Yes' : 'No' }}</p>
                                <p><strong>Kidney Disease:</strong>
                                    {{ $selectedRiskPatient['medical_history']['kidney_disease'] ? 'Yes' : 'No' }}</p>
                                <p><strong>Blood Transfusion:</strong>
                                    {{ $selectedRiskPatient['medical_history']['blood_transfusion'] ? 'Yes' : 'No' }}
                                </p>
                                @if ($selectedRiskPatient['medical_history']['other_medical_history'])
                                    <p><strong>Other:</strong>
                                        {{ $selectedRiskPatient['medical_history']['other_medical_history'] }}</p>
                                @endif
                            </div>
                        </div>
                        @if (array_filter($selectedRiskPatient['current_pregnancy']))
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-success">Current Pregnancy Symptoms</h6>
                                    @if ($selectedRiskPatient['current_pregnancy']['bleeding'])
                                        <span class="badge bg-danger me-1">Bleeding</span>
                                    @endif
                                    @if ($selectedRiskPatient['current_pregnancy']['discharge'])
                                        <span class="badge bg-warning me-1">Discharge</span>
                                    @endif
                                    @if ($selectedRiskPatient['current_pregnancy']['swelling_ankles'])
                                        <span class="badge bg-info me-1">Swelling Ankles</span>
                                    @endif
                                    @if ($selectedRiskPatient['current_pregnancy']['other_symptoms'])
                                        <p class="mt-2"><strong>Other Symptoms:</strong>
                                            {{ $selectedRiskPatient['current_pregnancy']['other_symptoms'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeRiskModal">Close</button>
                        <button type="button" class="btn btn-primary">Schedule Follow-up</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let trendsChart = null;
        let serviceCoverageChart = null;

        function initializeCharts() {
            try {
                if (trendsChart) {
                    trendsChart.destroy();
                    trendsChart = null;
                }
                if (serviceCoverageChart) {
                    serviceCoverageChart.destroy();
                    serviceCoverageChart = null;
                }

                @if (isset($metrics['monthly_trends']) && count($metrics['monthly_trends']) > 0)
                    const trendsCtx = document.getElementById('trendsChart');
                    if (trendsCtx) {
                        const trendsData = @json($metrics['monthly_trends']);

                        trendsChart = new Chart(trendsCtx, {
                            type: 'line',
                            data: {
                                labels: trendsData.map(item => item.month),
                                datasets: [{
                                    label: 'Antenatal',
                                    data: trendsData.map(item => item.antenatal_registrations || 0),
                                    borderColor: '#667eea',
                                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4,
                                    pointBackgroundColor: '#667eea',
                                    pointBorderColor: '#fff',
                                    pointBorderWidth: 2,
                                    pointRadius: 6,
                                    pointHoverRadius: 8,
                                }, {
                                    label: 'Deliveries',
                                    data: trendsData.map(item => item.deliveries || 0),
                                    borderColor: '#f093fb',
                                    backgroundColor: 'rgba(240, 147, 251, 0.1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4,
                                    pointBackgroundColor: '#f093fb',
                                    pointBorderColor: '#fff',
                                    pointBorderWidth: 2,
                                    pointRadius: 6,
                                    pointHoverRadius: 8,
                                }, {
                                    label: 'Postnatal',
                                    data: trendsData.map(item => item.postnatal_visits || 0),
                                    borderColor: '#4facfe',
                                    backgroundColor: 'rgba(79, 172, 254, 0.1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4,
                                    pointBackgroundColor: '#4facfe',
                                    pointBorderColor: '#fff',
                                    pointBorderWidth: 2,
                                    pointRadius: 6,
                                    pointHoverRadius: 8,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 20,
                                            font: {
                                                size: 12,
                                                weight: '500'
                                            }
                                        }
                                    },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        borderColor: 'rgba(255, 255, 255, 0.1)',
                                        borderWidth: 1,
                                        cornerRadius: 8,
                                        displayColors: true,
                                    }
                                },
                                interaction: {
                                    mode: 'nearest',
                                    axis: 'x',
                                    intersect: false
                                },
                                scales: {
                                    x: {
                                        display: true,
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            font: {
                                                size: 11
                                            }
                                        }
                                    },
                                    y: {
                                        display: true,
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(67, 89, 113, 0.1)'
                                        },
                                        ticks: {
                                            font: {
                                                size: 11
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                @endif

                @if (isset($metrics['service_coverage']))
                    const serviceCoverageCtx = document.getElementById('serviceCoverageChart');
                    if (serviceCoverageCtx) {
                        const serviceCoverageData = @json($metrics['service_coverage']);

                        serviceCoverageChart = new Chart(serviceCoverageCtx, {
                            type: 'bar',
                            data: {
                                labels: ['Antenatal', 'Deliveries', 'Postnatal', 'Tetanus'],
                                datasets: [{
                                    label: 'Patients Served',
                                    data: [
                                        serviceCoverageData.antenatal_coverage || 0,
                                        serviceCoverageData.delivery_coverage || 0,
                                        serviceCoverageData.postnatal_coverage || 0,
                                        serviceCoverageData.tetanus_coverage || 0
                                    ],
                                    backgroundColor: [
                                        'rgba(102, 126, 234, 0.8)',
                                        'rgba(240, 147, 251, 0.8)',
                                        'rgba(79, 172, 254, 0.8)',
                                        'rgba(255, 171, 0, 0.8)'
                                    ],
                                    borderColor: ['#667eea', '#f093fb', '#4facfe', '#ffab00'],
                                    borderWidth: 2,
                                    borderRadius: 8,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        borderColor: 'rgba(255, 255, 255, 0.1)',
                                        borderWidth: 1,
                                        cornerRadius: 8,
                                        displayColors: true,
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            font: {
                                                size: 11
                                            }
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(67, 89, 113, 0.1)'
                                        },
                                        ticks: {
                                            font: {
                                                size: 11
                                            }
                                        }
                                    }
                                }
                            }
                        });
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
            Livewire.on('metrics-updated', () => {
                setTimeout(() => {
                    initializeCharts();
                }, 100);
            });
        });
    </script>

    <style>
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

        .alert-container {
            scrollbar-width: thin;
            scrollbar-color: #ccc transparent;
        }

        .alert-container::-webkit-scrollbar {
            width: 6px;
        }

        .alert-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .alert-container::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }
        }
    </style>
    @include('_partials.datatables-init')
</div>
