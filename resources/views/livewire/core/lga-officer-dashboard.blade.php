<div class="dashboard-container">
    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 30px;">
                            <i class='bx bx-user-circle me-2'></i>
                            Welcome, {{ $user->first_name }} {{ $user->last_name }}
                        </h4>

                        <div class="d-flex flex-wrap gap-3 text-white mb-1">
                            <span>
                                <i class="bx bx-map me-1"></i>
                                <strong>State:</strong> {{ $state_name ?? 'N/A' }}
                            </span>
                            <span>
                                <i class="bx bx-map-alt me-1"></i>
                                <strong>LGA Coverage:</strong> {{ $lga_name ?? 'N/A' }}
                            </span>
                            <span>
                                <i class="bx bx-building-house me-1"></i>
                                <strong>Facilities Managed:</strong> {{ $facilityCount ?? 0 }}
                            </span>
                            <span>
                                <i class="bx bx-time me-1"></i>
                                <strong>Time:</strong>
                                {{ \Carbon\Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                            </span>
                        </div>

                        <div class="hero-stats">
                            <span class="hero-stat">
                                <i class="bx bx-group"></i>
                                Total Patients ({{ number_format($totalPatients) }})
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-trending-up"></i>
                                New This Period ({{ number_format($newRegistrations) }})
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-calendar-check"></i>
                                Today's Visits ({{ number_format($todaysAttendance) }})
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

    <!-- Controls -->
    <div class="row mb-4">
        <div class="col-md-6">
            <label class="form-label">Time Period</label>
            <select wire:model.live="selectedTimeframe" class="form-select">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 3 Months</option>
                <option value="365">Last Year</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Focus Register</label>
            <select wire:model.live="selectedRegister" class="form-select">
                <option value="all">All Registers</option>
                <option value="antenatal">Antenatal</option>
                <option value="delivery">Delivery</option>
                <option value="postnatal">Postnatal</option>
                <option value="tetanus">Tetanus</option>
                <option value="attendance">Daily Attendance</option>
            </select>
        </div>
    </div>

    <!-- Overview Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-info">
                                <i class="bx bx-group bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ number_format($totalPatients) }}</h5>
                            <small class="text-muted">Total Patients</small>
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
                                <i class="bx bx-pregnant bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ number_format($activePregnancies) }}</h5>
                            <small class="text-muted">Active Pregnancies</small>
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
                                <i class="bx bx-baby-carriage bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ number_format($totalDeliveries) }}</h5>
                            <small class="text-muted">Total Deliveries</small>
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
                            <span class="avatar-initial rounded bg-danger">
                                <i class="bx bx-error-circle bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ number_format($highRiskCases) }}</h5>
                            <small class="text-muted">High Risk Cases</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">LGA-Wide Trends Over Time</h5>
                    <small class="text-muted">Last {{ $selectedTimeframe }} days across {{ $facilityCount ?? 0 }}
                        facilities</small>
                </div>
                <div class="card-body">
                    <canvas id="trendsChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Age Groups Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="ageGroupChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Ward Breakdown Section -->
    @if (!empty($wardBreakdown))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-map-pin me-2"></i>Ward Performance Breakdown
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ward</th>
                                        <th class="text-center">Facilities</th>
                                        <th class="text-center">Total Patients</th>
                                        <th class="text-center">New Registrations</th>
                                        <th class="text-center">Deliveries</th>
                                        <th class="text-center">High Risk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($wardBreakdown as $ward => $data)
                                        <tr>
                                            <td><strong>{{ $ward }}</strong></td>
                                            <td class="text-center">
                                                <span
                                                    class="badge bg-label-primary">{{ $data['facility_count'] }}</span>
                                            </td>
                                            <td class="text-center">{{ number_format($data['total_patients']) }}</td>
                                            <td class="text-center">{{ number_format($data['new_registrations']) }}
                                            </td>
                                            <td class="text-center">{{ number_format($data['total_deliveries']) }}
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge {{ $data['high_risk_cases'] > 10 ? 'bg-label-danger' : 'bg-label-warning' }}">
                                                    {{ number_format($data['high_risk_cases']) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Top and Bottom Facilities -->
    <div class="row mb-4">
        @if (!empty($topFacilities))
            <div class="col-lg-6 mb-4">
                <div class="card border-success">
                    <div class="card-header bg-label-success">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-trophy me-2"></i>Top Performing Facilities
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach ($topFacilities as $facility)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <strong>{{ $facility['name'] }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $facility['ward'] }}</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="mb-1">
                                            <small class="text-muted">Patients:</small>
                                            <span
                                                class="badge bg-label-info">{{ number_format($facility['patients']) }}</span>
                                        </div>
                                        <div>
                                            <small class="text-muted">New:</small>
                                            <span
                                                class="badge bg-label-success">{{ number_format($facility['new_registrations']) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($bottomFacilities))
            <div class="col-lg-6 mb-4">
                <div class="card border-warning">
                    <div class="card-header bg-label-warning">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-error-circle me-2"></i>Facilities Needing Support
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach ($bottomFacilities as $facility)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <strong>{{ $facility['name'] }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $facility['ward'] }}</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="mb-1">
                                            <small class="text-muted">Patients:</small>
                                            <span
                                                class="badge bg-label-secondary">{{ number_format($facility['patients']) }}</span>
                                        </div>
                                        <div>
                                            <small class="text-muted">New:</small>
                                            <span
                                                class="badge bg-label-warning">{{ number_format($facility['new_registrations']) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Register Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">LGA-Wide Register Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Antenatal Register</h6>
                                    @if ($antenatalStats['trend'] > 0)
                                        <span class="badge bg-label-success">
                                            <i class="bx bx-trending-up"></i> +{{ $antenatalStats['trend'] }}%
                                        </span>
                                    @elseif ($antenatalStats['trend'] < 0)
                                        <span class="badge bg-label-danger">
                                            <i class="bx bx-trending-down"></i> {{ $antenatalStats['trend'] }}%
                                        </span>
                                    @else
                                        <span class="badge bg-label-secondary">
                                            <i class="bx bx-minus"></i> 0%
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="mb-0">{{ number_format($antenatalStats['total']) }}</h5>
                                        <small class="text-muted">Total Records</small>
                                    </div>
                                    <div class="text-end">
                                        <h6 class="mb-0 text-primary">
                                            {{ number_format($antenatalStats['this_period']) }}</h6>
                                        <small class="text-muted">This Period</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Delivery Register</h6>
                                    @if ($deliveryStats['trend'] > 0)
                                        <span class="badge bg-label-success">
                                            <i class="bx bx-trending-up"></i> +{{ $deliveryStats['trend'] }}%
                                        </span>
                                    @elseif ($deliveryStats['trend'] < 0)
                                        <span class="badge bg-label-danger">
                                            <i class="bx bx-trending-down"></i> {{ $deliveryStats['trend'] }}%
                                        </span>
                                    @else
                                        <span class="badge bg-label-secondary">
                                            <i class="bx bx-minus"></i> 0%
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="mb-0">{{ number_format($deliveryStats['total']) }}</h5>
                                        <small class="text-muted">Total Records</small>
                                    </div>
                                    <div class="text-end">
                                        <h6 class="mb-0 text-success">
                                            {{ number_format($deliveryStats['this_period']) }}
                                        </h6>
                                        <small class="text-muted">This Period</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Postnatal Register</h6>
                                    @if ($postnatalStats['trend'] > 0)
                                        <span class="badge bg-label-success">
                                            <i class="bx bx-trending-up"></i> +{{ $postnatalStats['trend'] }}%
                                        </span>
                                    @elseif ($postnatalStats['trend'] < 0)
                                        <span class="badge bg-label-danger">
                                            <i class="bx bx-trending-down"></i> {{ $postnatalStats['trend'] }}%
                                        </span>
                                    @else
                                        <span class="badge bg-label-secondary">
                                            <i class="bx bx-minus"></i> 0%
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="mb-0">{{ number_format($postnatalStats['total']) }}</h5>
                                        <small class="text-muted">Total Records</small>
                                    </div>
                                    <div class="text-end">
                                        <h6 class="mb-0 text-info">{{ number_format($postnatalStats['this_period']) }}
                                        </h6>
                                        <small class="text-muted">This Period</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Tetanus Register</h6>
                                    @if ($tetanusStats['trend'] > 0)
                                        <span class="badge bg-label-success">
                                            <i class="bx bx-trending-up"></i> +{{ $tetanusStats['trend'] }}%
                                        </span>
                                    @elseif ($tetanusStats['trend'] < 0)
                                        <span class="badge bg-label-danger">
                                            <i class="bx bx-trending-down"></i> {{ $tetanusStats['trend'] }}%
                                        </span>
                                    @else
                                        <span class="badge bg-label-secondary">
                                            <i class="bx bx-minus"></i> 0%
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="mb-0">{{ number_format($tetanusStats['total']) }}</h5>
                                        <small class="text-muted">Total Records</small>
                                    </div>
                                    <div class="text-end">
                                        <h6 class="mb-0 text-warning">
                                            {{ number_format($tetanusStats['this_period']) }}
                                        </h6>
                                        <small class="text-muted">This Period</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Daily Attendance</h6>
                                    @if ($attendanceStats['trend'] > 0)
                                        <span class="badge bg-label-success">
                                            <i class="bx bx-trending-up"></i> +{{ $attendanceStats['trend'] }}%
                                        </span>
                                    @elseif ($attendanceStats['trend'] < 0)
                                        <span class="badge bg-label-danger">
                                            <i class="bx bx-trending-down"></i> {{ $attendanceStats['trend'] }}%
                                        </span>
                                    @else
                                        <span class="badge bg-label-secondary">
                                            <i class="bx bx-minus"></i> 0%
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="mb-0">{{ number_format($attendanceStats['total']) }}</h5>
                                        <small class="text-muted">Total Records</small>
                                    </div>
                                    <div class="text-end">
                                        <h6 class="mb-0 text-secondary">
                                            {{ number_format($attendanceStats['this_period']) }}</h6>
                                        <small class="text-muted">This Period</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Alerts -->
    @if (!empty($riskAlerts))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-label-danger">
                        <h5 class="card-title mb-0">
                            <strong><i class="bx bx-error-circle me-2"></i></strong>
                            LGA-Wide Risk Alerts & Notifications
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach ($riskAlerts as $alert)
                            <div class="alert alert-{{ $alert['type'] }} d-flex align-items-center mb-3"
                                role="alert">
                                <i class="bx {{ $alert['icon'] }} me-3" style="font-size: 1.5rem;"></i>
                                <div class="flex-grow-1">
                                    <h6 class="alert-heading mb-1">{{ $alert['title'] }}</h6>
                                    <div class="mb-0">{{ $alert['message'] }}</div>
                                </div>
                                <span
                                    class="badge bg-{{ $alert['type'] }} ms-2 fs-6">{{ number_format($alert['count']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Performance Metrics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-trending-up me-2"></i>LGA-Wide Performance Metrics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-primary">
                                        <i class="bx bx-check-circle bx-sm text-white"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">Antenatal Coverage</small>
                                            <br>
                                            <small class="text-muted" style="font-size: 0.75rem;">Active pregnancies
                                                vs total patients</small>
                                        </div>
                                        <span
                                            class="badge bg-label-primary fs-6">{{ $performanceMetrics['antenatal_coverage'] }}%</span>
                                    </div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar bg-primary" role="progressbar"
                                            style="width: {{ $performanceMetrics['antenatal_coverage'] }}%"
                                            aria-valuenow="{{ $performanceMetrics['antenatal_coverage'] }}"
                                            aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted mt-1" style="font-size: 0.7rem;">
                                        {{ $performanceMetrics['antenatal_coverage'] >= 80 ? 'Excellent coverage' : ($performanceMetrics['antenatal_coverage'] >= 60 ? 'Good coverage' : 'Needs improvement') }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-success">
                                        <i class="bx bx-calendar-check bx-sm text-white"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">Avg Daily Attendance</small>
                                            <br>
                                            <small class="text-muted" style="font-size: 0.75rem;">Average patients per
                                                day</small>
                                        </div>
                                        <span
                                            class="badge bg-label-success fs-6">{{ $performanceMetrics['avg_daily_attendance'] }}</span>
                                    </div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar"
                                            style="width: {{ min($performanceMetrics['avg_daily_attendance'] * 10, 100) }}%"
                                            aria-valuenow="{{ $performanceMetrics['avg_daily_attendance'] }}"
                                            aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                    <small class="text-muted mt-1" style="font-size: 0.7rem;">
                                        @if ($performanceMetrics['avg_daily_attendance'] >= 5)
                                            High patient volume
                                        @elseif($performanceMetrics['avg_daily_attendance'] >= 2)
                                            Moderate patient volume
                                        @elseif($performanceMetrics['avg_daily_attendance'] >= 0.5)
                                            Low patient volume
                                        @else
                                            Very low activity
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded bg-info">
                                        <i class="bx bx-chart-line bx-sm text-white"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">LGA Care Efficiency</small>
                                            <br>
                                            <small class="text-muted" style="font-size: 0.75rem;">Patient visit
                                                frequency score</small>
                                        </div>
                                        <span
                                            class="badge bg-label-info fs-6">{{ $performanceMetrics['facility_efficiency'] }}%</span>
                                    </div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar bg-info" role="progressbar"
                                            style="width: {{ $performanceMetrics['facility_efficiency'] }}%"
                                            aria-valuenow="{{ $performanceMetrics['facility_efficiency'] }}"
                                            aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted mt-1" style="font-size: 0.7rem;">
                                        @if ($performanceMetrics['facility_efficiency'] >= 80)
                                            Excellent
                                        @elseif($performanceMetrics['facility_efficiency'] >= 60)
                                            Good
                                        @elseif($performanceMetrics['facility_efficiency'] >= 40)
                                            Fair
                                        @else
                                            Poor
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <i class="bx bx-info-circle me-2"></i>
                                <div>
                                    <strong>LGA Performance Summary:</strong>
                                    @php
                                        $avgScore =
                                            ($performanceMetrics['antenatal_coverage'] +
                                                $performanceMetrics['facility_efficiency']) /
                                            2;
                                    @endphp
                                    @if ($avgScore >= 75)
                                        Your LGA is performing very well across {{ $facilityCount ?? 0 }} facilities
                                        with high coverage and strong patient engagement.
                                    @elseif($avgScore >= 50)
                                        Your LGA shows good performance with room for improvement in patient
                                        engagement across facilities.
                                    @else
                                        Consider LGA-wide strategies to improve patient coverage and visit frequency
                                        for better care outcomes.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button wire:click="refreshData"
                            class="btn btn-light btn-lg rounded-pill shadow-sm d-inline-flex align-items-center me-2"
                            style="border: 1px solid #ddd; padding: 12px 24px;" type="button"
                            title="Refresh Dashboard Data">
                            <i class="bx bx-refresh me-2" style="font-size: 20px;"></i>
                            Refresh Data
                        </button>

                        <button wire:click="forceRefresh"
                            class="btn btn-warning btn-lg rounded-pill shadow-sm d-inline-flex align-items-center"
                            style="padding: 12px 24px;" type="button" title="Force Refresh (Clear Cache)">
                            <i class="bx bx-reset me-2" style="font-size: 20px;"></i>
                            Force Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .hero-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            min-height: 200px;
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
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .hero-stat i {
            margin-right: 0.5rem;
            font-size: 1.2rem;
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

        .progress {
            background-color: rgba(67, 89, 113, 0.1);
        }

        .alert {
            border-left: 4px solid;
            border-radius: 8px;
        }

        .alert-warning {
            border-left-color: #ffab00;
            background-color: rgba(255, 171, 0, 0.1);
        }

        .alert-danger {
            border-left-color: #ff3e1d;
            background-color: rgba(255, 62, 29, 0.1);
        }

        .alert-info {
            border-left-color: #03c3ec;
            background-color: rgba(3, 195, 236, 0.1);
        }

        .badge {
            font-size: 0.875rem;
            font-weight: 500;
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

        #trendsChart,
        #ageGroupChart {
            max-height: 300px;
        }

        .list-group-item {
            border-left: 0;
            border-right: 0;
        }

        .list-group-item:first-child {
            border-top: 0;
        }

        .list-group-item:last-child {
            border-bottom: 0;
        }

        @media (max-width: 768px) {
            .hero-stats {
                gap: 1rem;
            }

            .hero-stat {
                font-size: 0.875rem;
            }

            .card-body {
                padding: 1rem;
            }
        }

        .dashboard-loading {
            position: relative;
            opacity: 0.6;
            pointer-events: none;
        }

        .dashboard-loading::after {
            content: 'Loading...';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            font-weight: 600;
            color: #667eea;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let trendsChart = null;
        let ageGroupChart = null;

        function initializeCharts() {
            try {
                if (trendsChart) {
                    trendsChart.destroy();
                    trendsChart = null;
                }
                if (ageGroupChart) {
                    ageGroupChart.destroy();
                    ageGroupChart = null;
                }

                const trendData = @json($trendChartData);

                if (trendData && trendData.labels && trendData.labels.length > 0) {
                    const trendsCtx = document.getElementById('trendsChart');

                    if (trendsCtx) {
                        trendsChart = new Chart(trendsCtx.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: trendData.labels || [],
                                datasets: [{
                                    label: 'Antenatal Registrations',
                                    data: trendData.antenatal || [],
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
                                    data: trendData.delivery || [],
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
                                    label: 'Daily Attendance',
                                    data: trendData.attendance || [],
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
                                },
                                elements: {
                                    point: {
                                        hoverBackgroundColor: '#fff'
                                    }
                                }
                            }
                        });
                    }
                } else {
                    console.warn('No trend data available for chart initialization');
                }

                const ageGroupData = @json($ageGroupChartData);

                if (ageGroupData && Object.keys(ageGroupData).length > 0) {
                    const ageGroupCtx = document.getElementById('ageGroupChart');

                    if (ageGroupCtx) {
                        ageGroupChart = new Chart(ageGroupCtx.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: Object.keys(ageGroupData),
                                datasets: [{
                                    data: Object.values(ageGroupData),
                                    backgroundColor: [
                                        'rgba(102, 126, 234, 0.8)',
                                        'rgba(240, 147, 251, 0.8)',
                                        'rgba(79, 172, 254, 0.8)',
                                        'rgba(255, 171, 0, 0.8)',
                                        'rgba(255, 62, 29, 0.8)'
                                    ],
                                    borderColor: [
                                        '#667eea',
                                        '#f093fb',
                                        '#4facfe',
                                        '#ffab00',
                                        '#ff3e1d'
                                    ],
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
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                                return context.label + ': ' + context.raw + ' (' + percentage +
                                                    '%)';
                                            }
                                        }
                                    }
                                },
                                cutout: '60%',
                                elements: {
                                    arc: {
                                        borderWidth: 2
                                    }
                                }
                            }
                        });
                    }
                } else {
                    console.warn('No age group data available for chart initialization');
                }
            } catch (error) {
                console.error('Chart initialization failed:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
        });

        document.addEventListener('livewire:initialized', () => {
            Livewire.on('loaded', () => {
                document.querySelector('.dashboard-container')?.classList.remove('dashboard-loading');
                setTimeout(() => {
                    initializeCharts();
                }, 100);
            });

            Livewire.on('loading', () => {
                document.querySelector('.dashboard-container')?.classList.add('dashboard-loading');
            });
        });
    </script>
</div>
