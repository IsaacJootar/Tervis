@section('title', 'Real-time Dashboard')
@php
    use Carbon\Carbon;
@endphp
<div class="analytics-page">
    @include('livewire.analytics._template-style')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h4 class="mb-1"><i class='bx bx-line-chart me-2'></i>Real-time Analytics Dashboard</h4>
                        <p class="mb-0 text-muted">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-label-primary">{{ number_format($metrics['total_patients'] ?? 0) }} Patients</span>
                        <span class="badge bg-label-info">{{ array_sum($metrics['today_visits'] ?? []) }} Today's Visits</span>
                        @if (isset($metrics['facility_info']))
                            <span class="badge bg-label-secondary">
                                @if (($metrics['facility_info']['facility_count'] ?? 0) == 1 && isset($metrics['facility_info']['name']))
                                    {{ $metrics['facility_info']['name'] }}
                                @else
                                    {{ $metrics['facility_info']['scope'] ?? 'Multi-facility' }}
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Facility Filter (only show if multiple facilities) -->
    @if ($facilities->count() > 1)
        <div class="row mb-3">
            <div class="col-md-8">
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
            <div class="col-md-4 d-flex align-items-end justify-content-md-end">
                <button wire:click="refreshData" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove><i class="bx bx-refresh me-1"></i>Refresh Data</span>
                    <span wire:loading><span class="spinner-border spinner-border-sm me-1"></span>Loading...</span>
                </button>
            </div>
        </div>
    @else
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-end">
                <button wire:click="refreshData" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove><i class="bx bx-refresh me-1"></i>Refresh Data</span>
                    <span wire:loading><span class="spinner-border spinner-border-sm me-1"></span>Loading...</span>
                </button>
            </div>
        </div>
    @endif
    <!-- Today's Services Row -->
    <div class="row mb-4">
        @if (isset($metrics['today_visits']))
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="metric-card metric-card-sky h-100" wire:click="viewServiceDetails('antenatal')"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Antenatal</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $metrics['today_visits']['antenatal'] }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="metric-card metric-card-emerald h-100" wire:click="viewServiceDetails('delivery')"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Deliveries</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M6 13h10a3 3 0 0 0 0-6H9" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                                <circle cx="9" cy="18" r="1.8" stroke="currentColor" stroke-width="1.6" />
                                <circle cx="16" cy="18" r="1.8" stroke="currentColor" stroke-width="1.6" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $metrics['today_visits']['delivery'] }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="metric-card metric-card-violet h-100" wire:click="viewServiceDetails('postnatal')"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Postnatal</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 20s-6-3.8-8-7.2C2.2 9.7 4 6 7.7 6c1.8 0 3.1.9 4.3 2.4C13.2 6.9 14.5 6 16.3 6 20 6 21.8 9.7 20 12.8 18 16.2 12 20 12 20z"
                                    stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $metrics['today_visits']['postnatal'] }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="metric-card metric-card-amber h-100" wire:click="viewServiceDetails('tetanus')"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Tetanus</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 4l7 3v5c0 4.4-2.8 6.9-7 8-4.2-1.1-7-3.6-7-8V7l7-3z"
                                    stroke="currentColor" stroke-width="1.7" />
                                <path d="M12 9v6M9 12h6" stroke="currentColor" stroke-width="1.7"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $metrics['today_visits']['tetanus'] }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="metric-card metric-card-slate h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Attendance</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="4.5" y="5.5" width="15" height="14" rx="2" stroke="currentColor"
                                    stroke-width="1.6" />
                                <path d="M8 3.8v3M16 3.8v3M8.5 12.2l2.1 2.1 4.4-4.4" stroke="currentColor"
                                    stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $metrics['today_visits']['attendance'] }}</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="metric-card metric-card-rose h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Clinical Notes</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M8 4.5h6l3 3v12H8z" stroke="currentColor" stroke-width="1.6"
                                    stroke-linejoin="round" />
                                <path d="M10 11h5M10 14h5" stroke="currentColor" stroke-width="1.6"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $metrics['today_visits']['clinical_notes'] }}</div>
                </div>
            </div>
        @endif
    </div>

    <!-- Key Performance Indicators Row -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card metric-card-rose h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">High-Risk</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.6" />
                            <path d="M12 8v5M12 16.4h.01" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ count($metrics['high_risk_pregnancies'] ?? []) }}</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Next 7 Days</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="4.5" y="5.5" width="15" height="14" rx="2" stroke="currentColor"
                                stroke-width="1.6" />
                            <path d="M8 3.8v3M16 3.8v3M8 13h8M12 9.8v6.4" stroke="currentColor"
                                stroke-width="1.6" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ count($metrics['upcoming_deliveries'] ?? []) }}</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Cesarean Rate</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $metrics['clinical_outcomes']['cesarean_rate'] ?? 0 }}%</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card metric-card-amber h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">TT Full Protection</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 4l7 3v5c0 4.4-2.8 6.9-7 8-4.2-1.1-7-3.6-7-8V7l7-3z"
                                stroke="currentColor" stroke-width="1.7" />
                            <path d="M9 12.2l2.1 2.1 3.9-3.9" stroke="currentColor" stroke-width="1.7"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $metrics['vaccination_coverage']['full_protection_rate'] ?? 0 }}%</div>
            </div>
        </div>
    </div>

    <!-- Service Coverage and Patient Journey Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Service Snapshot</h5>
                    <small class="text-muted">Simple current totals for this scope</small>
                </div>
                <div class="card-body">
                    @php
                        $coverage = $metrics['service_coverage'] ?? [];
                        $today = $metrics['today_visits'] ?? [];
                        $clinical = $metrics['clinical_outcomes'] ?? [];
                        $vaccination = $metrics['vaccination_coverage'] ?? [];
                    @endphp
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Unique Patients</span>
                        <strong>{{ $coverage['total_unique_patients'] ?? 0 }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Today Visits (All Services)</span>
                        <strong>{{ array_sum($today) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Total Deliveries</span>
                        <strong>{{ $clinical['total_deliveries'] ?? 0 }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Complete Journey Rate</span>
                        <strong>{{ $coverage['coverage_percentage'] ?? 0 }}%</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="text-muted">TT Full Protection</span>
                        <strong>{{ $vaccination['full_protection_rate'] ?? 0 }}%</strong>
                    </div>
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

    <!-- Trends Row -->
    <div class="row g-4 mb-4">
        <div class="col-12">
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
                                        <td>{{ $pregnancy->patient_name ?? 'Unknown Patient' }}</td>
                                        <td><span class="badge bg-label-info">{{ $pregnancy->patient_din ?? 'N/A' }}</span></td>
                                        <td>{{ $pregnancy->patient_age ?? 'N/A' }}{{ isset($pregnancy->patient_age) ? ' years' : '' }}</td>
                                        <td>
                                            @foreach (array_slice((array) ($pregnancy->risk_factors ?? []), 0, 3) as $factor)
                                                <span class="badge bg-label-warning mb-1">{{ $factor }}</span>
                                            @endforeach
                                            @if (($pregnancy->risk_factor_count ?? 0) > 3)
                                                <span class="badge bg-label-secondary mb-1">
                                                    +{{ ($pregnancy->risk_factor_count ?? 0) - 3 }} more
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $pregnancy->edd ? Carbon::parse($pregnancy->edd)->format('M d, Y') : 'N/A' }}</td>
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
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    @if ($showRiskModal && $selectedRiskPatient)
        <div class="modal fade show" style="display: block;" tabindex="-1" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title d-flex align-items-center gap-2">
                            <i class="bx bx-shield-quarter text-danger"></i>
                            High-Risk Patient Details
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeRiskModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3 col-6">
                                <div class="metric-card metric-card-slate h-100">
                                    <div class="metric-label">Patient</div>
                                    <div class="metric-value" style="font-size: 1rem;">{{ $selectedRiskPatient['patient_name'] }}</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="metric-card metric-card-sky h-100">
                                    <div class="metric-label">DIN</div>
                                    <div class="metric-value" style="font-size: 1rem;">{{ $selectedRiskPatient['din'] }}</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="metric-card metric-card-violet h-100">
                                    <div class="metric-label">Age</div>
                                    <div class="metric-value">{{ $selectedRiskPatient['age'] ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="metric-card metric-card-amber h-100">
                                    <div class="metric-label">Risk Signals</div>
                                    <div class="metric-value">{{ count($selectedRiskPatient['risk_factors'] ?? []) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0">Pregnancy Timeline</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2"><strong>LMP:</strong> {{ $selectedRiskPatient['lmp'] ? Carbon::parse($selectedRiskPatient['lmp'])->format('M d, Y') : 'N/A' }}</div>
                                        <div class="mb-2"><strong>EDD:</strong> {{ $selectedRiskPatient['edd'] ? Carbon::parse($selectedRiskPatient['edd'])->format('M d, Y') : 'N/A' }}</div>
                                        <div class="mb-2"><strong>Last Visit:</strong> {{ $selectedRiskPatient['last_visit'] ? Carbon::parse($selectedRiskPatient['last_visit'])->format('M d, Y') : 'N/A' }}</div>
                                        <div><strong>Next Visit:</strong> {{ $selectedRiskPatient['next_visit'] ? Carbon::parse($selectedRiskPatient['next_visit'])->format('M d, Y') : 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0">Clinical Vitals</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2"><strong>Height:</strong> {{ $selectedRiskPatient['vitals']['height'] ?? 'N/A' }} cm</div>
                                        <div class="mb-2"><strong>Weight:</strong> {{ $selectedRiskPatient['vitals']['weight'] ?? 'N/A' }} kg</div>
                                        <div class="mb-2"><strong>Blood Pressure:</strong> {{ $selectedRiskPatient['vitals']['blood_pressure'] ?? 'N/A' }}</div>
                                        <div class="mb-2"><strong>Hemoglobin:</strong> {{ $selectedRiskPatient['vitals']['hemoglobin'] ?? 'N/A' }} g/dL</div>
                                        <div class="mb-2"><strong>Genotype:</strong> {{ $selectedRiskPatient['vitals']['genotype'] ?? 'N/A' }}</div>
                                        <div><strong>Blood Group:</strong> {{ $selectedRiskPatient['vitals']['blood_group_rhesus'] ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0">Risk Assessment Signals</h6>
                                        <small class="text-muted">Structured from ANC, delivery, and postnatal records.</small>
                                    </div>
                                    <div class="card-body">
                                        @forelse ($selectedRiskPatient['risk_factors'] as $factor)
                                            <span class="badge bg-label-danger me-1 mb-1">{{ $factor }}</span>
                                        @empty
                                            <span class="text-muted">No structured risk factors identified.</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0">Medical & Current Symptoms</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2"><strong>Heart disease:</strong> {{ ($selectedRiskPatient['medical_history']['heart_disease'] ?? false) ? 'Yes' : 'No' }}</div>
                                        <div class="mb-2"><strong>Kidney disease:</strong> {{ ($selectedRiskPatient['medical_history']['kidney_disease'] ?? false) ? 'Yes' : 'No' }}</div>
                                        <div class="mb-2"><strong>Family hypertension:</strong> {{ ($selectedRiskPatient['medical_history']['family_hypertension'] ?? false) ? 'Yes' : 'No' }}</div>
                                        <div class="mb-2"><strong>Bleeding:</strong> {{ ($selectedRiskPatient['current_pregnancy']['bleeding'] ?? false) ? 'Yes' : 'No' }}</div>
                                        <div class="mb-2"><strong>Discharge:</strong> {{ ($selectedRiskPatient['current_pregnancy']['discharge'] ?? false) ? 'Yes' : 'No' }}</div>
                                        <div><strong>Other symptoms:</strong> {{ $selectedRiskPatient['current_pregnancy']['other_symptoms'] ?: 'None recorded' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeRiskModal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let trendsChart = null;

        function initializeCharts() {
            try {
                if (trendsChart) {
                    trendsChart.destroy();
                    trendsChart = null;
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

            } catch (error) {
                console.error('Chart initialization failed:', error);
            }
        }

        function scheduleRealTimeChartsInit() {
            setTimeout(() => {
                initializeCharts();
            }, 100);
        }

        if (!window.__realTimeAnalyticsChartsBound) {
            window.__realTimeAnalyticsChartsBound = true;

            document.addEventListener('DOMContentLoaded', scheduleRealTimeChartsInit);
            document.addEventListener('livewire:navigated', scheduleRealTimeChartsInit);

            document.addEventListener('livewire:initialized', () => {
                Livewire.on('metrics-updated', scheduleRealTimeChartsInit);
            });
        }

        scheduleRealTimeChartsInit();
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

        .modal-body {
            background: #fff;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }
        }
    </style>
    @include('_partials.datatables-init')
</div>
