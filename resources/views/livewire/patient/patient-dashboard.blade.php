@php
    use Carbon\Carbon;
@endphp
@section('title', 'Patient Dashboard')

<div>
    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 32px;">
                            <i class='bx bx-user-circle me-2'></i>
                            Welcome, {{ $user->first_name }} {{ $user->last_name }}
                        </h4>
                        <div class="hero-info mb-2">
                            <p class="hero-subtitle">{{ Carbon::today()->format('l, F j, Y') }}</p>
                            <div class="hero-stats">
                                <span class="hero-stat">
                                    <i class="bx bx-id-card"></i>
                                    DIN: {{ $user->DIN }}
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-calendar-check"></i>
                                    {{ $next_appointments }} Total Visits
                                </span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-3 text-white mb-1">
                            <span>
                                <i class="bx bx-building me-1"></i>
                                <strong>Registration Facility:</strong> {{ $registration_facility_name }}
                            </span>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('patient-antenatal') }}"
                                class="btn btn-light btn-lg rounded-pill shadow-sm d-inline-flex align-items-center me-2"
                                style="border: 1px solid #ddd; padding: 12px 24px;">
                                <i class="bx bx-plus-medical me-2" style="font-size: 20px;"></i>
                                View Antenatal Records
                            </a>
                            <a href="{{ route('patient-deliveries') }}"
                                class="btn btn-light btn-lg rounded-pill shadow-sm d-inline-flex align-items-center"
                                style="border: 1px solid #ddd; padding: 12px 24px;">
                                <i class="bx bx-baby-carriage me-2" style="font-size: 20px;"></i>
                                View Deliveries
                            </a>
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

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-12 mb-4">
            <div class="card h-100 bg-primary">
                <div class="card-body text-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="avatar flex-shrink-0 bg-white bg-opacity-20 rounded">
                            <i class="bx bx-plus-medical bx-sm text-white p-2"></i>
                        </div>
                        <h3 class="card-title mb-0 text-white">{{ $antenatal_count }}</h3>
                    </div>
                    <span class="fw-semibold text-white-50">Antenatal Records</span>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-12 mb-4">
            <div class="card h-100 bg-success">
                <div class="card-body text-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="avatar flex-shrink-0 bg-white bg-opacity-20 rounded">
                            <i class="bx bx-baby-carriage bx-sm text-white p-2"></i>
                        </div>
                        <h3 class="card-title mb-0 text-white">{{ $delivery_count }}</h3>
                    </div>
                    <span class="fw-semibold text-white-50">Deliveries</span>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-12 mb-4">
            <div class="card h-100 bg-info">
                <div class="card-body text-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="avatar flex-shrink-0 bg-white bg-opacity-20 rounded">
                            <i class="bx bx-heart bx-sm text-white p-2"></i>
                        </div>
                        <h3 class="card-title mb-0 text-white">{{ $postnatal_count }}</h3>
                    </div>
                    <span class="fw-semibold text-white-50">Postnatal Visits</span>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-12 mb-4">
            <div class="card h-100 bg-warning">
                <div class="card-body text-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="avatar flex-shrink-0 bg-white bg-opacity-20 rounded">
                            <i class="bx bx-shield-plus bx-sm text-white p-2"></i>
                        </div>
                        <h3 class="card-title mb-0 text-white">{{ $tetanus_count }}/5</h3>
                    </div>
                    <span class="fw-semibold text-white-50">Tetanus Doses</span>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-12 mb-4">
            <div class="card h-100 bg-secondary">
                <div class="card-body text-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="avatar flex-shrink-0 bg-white bg-opacity-20 rounded">
                            <i class="bx bx-calendar-check bx-sm text-white p-2"></i>
                        </div>
                        <h3 class="card-title mb-0 text-white">{{ $attendance_count }}</h3>
                    </div>
                    <span class="fw-semibold text-white-50">Total Visits</span>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-12 mb-4">
            <div class="card h-100 bg-{{ $protection_status['color'] }}">
                <div class="card-body text-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="avatar flex-shrink-0 bg-white bg-opacity-20 rounded">
                            <i class="bx bx-shield-check bx-sm text-white p-2"></i>
                        </div>
                        <div class="text-end">
                            <div class="progress mb-2" style="height: 6px; background: rgba(255,255,255,0.3);">
                                <div class="progress-bar bg-white" role="progressbar"
                                    style="width: {{ $protection_status['percentage'] }}%"
                                    aria-valuenow="{{ $protection_status['percentage'] }}" aria-valuemin="0"
                                    aria-valuemax="100"></div>
                            </div>
                            <small class="text-white">{{ $protection_status['percentage'] }}%</small>
                        </div>
                    </div>
                    <span class="fw-semibold text-white-50">{{ $protection_status['status'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Medical Activities</h5>
                </div>
                <div class="card-body">
                    @if ($recent_activities && $recent_activities->count() > 0)
                        <ul class="timeline">
                            @foreach ($recent_activities as $activity)
                                <li class="timeline-item">
                                    <span class="timeline-indicator bg-{{ $activity['color'] }}">
                                        <i class="bx {{ $activity['icon'] }}"></i>
                                    </span>
                                    <div class="timeline-event">
                                        <div class="timeline-header border-bottom pb-2 mb-2">
                                            <h6 class="mb-1">{{ $activity['title'] }}</h6>
                                            <small
                                                class="text-muted">{{ Carbon::parse($activity['date'])->format('M d, Y') }}</small>
                                        </div>
                                        <p class="mb-2">{{ $activity['description'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-4">
                            <i class="bx bx-calendar bx-lg text-muted mb-2"></i>
                            <p class="text-muted">No recent activities found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="{{ route('patient-antenatal') }}" class="btn btn-outline-primary">
                            <i class="bx bx-plus-medical me-2"></i>
                            View Antenatal Records
                        </a>
                        <a href="{{ route('patient-deliveries') }}" class="btn btn-outline-success">
                            <i class="bx bx-baby-carriage me-2"></i>
                            View Delivery History
                        </a>
                        <a href="{{ route('patient-postnatal') }}" class="btn btn-outline-info">
                            <i class="bx bx-heart me-2"></i>
                            View Postnatal Care
                        </a>
                        <a href="{{ route('patient-tetanus') }}" class="btn btn-outline-warning">
                            <i class="bx bx-shield-plus me-2"></i>
                            View Tetanus Records
                        </a>
                        <a href="{{ route('patient-profile') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-user me-2"></i>
                            View Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
