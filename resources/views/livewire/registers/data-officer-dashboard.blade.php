@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Auth;
@endphp
@section('title', 'Data Officer Dashboard')
<div>
    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 30px;">
                            <i class='bx bx-user-circle me-2'></i>
                            Welcome, {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                        </h4>

                        <div class="d-flex flex-wrap gap-3 text-white mb-1">
                            <span>
                                <i class="bx bx-building me-1"></i>
                                <strong>Facility:</strong> {{ Auth::user()->facility->name ?? 'N/A' }}
                            </span>
                            <span>
                                <i class="bx bx-time me-1"></i>
                                <strong>Time:</strong>
                                {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                            </span>
                        </div>

                        <div class="hero-stats">
                            <span class="hero-stat">
                                <i class="bx bx-calendar-check"></i>
                                Total Visits ({{ $attendance_count }})
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-trending-up"></i>
                                Recent Activities ({{ $recent_activity_count }})
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-user"></i>
                                Active Patients ({{ $active_patients }})
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

    <!-- Overview Stats Cards (Compact Version) -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-primary">
                                <i class="bx bx-plus-medical bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $antenatal_count }}</h5>
                            <small class="text-muted">Antenatal</small>
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
                            <span class="avatar-initial rounded bg-success">
                                <i class="bx bx-baby-carriage bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $delivery_count }}</h5>
                            <small class="text-muted">Deliveries</small>
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
                            <span class="avatar-initial rounded bg-info">
                                <i class="bx bx-heart bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $postnatal_count }}</h5>
                            <small class="text-muted">Postnatal</small>
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
                            <span class="avatar-initial rounded bg-warning">
                                <i class="bx bx-shield-plus bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $tetanus_count }}</h5>
                            <small class="text-muted">Tetanus</small>
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
                            <h5 class="mb-0">{{ $attendance_count }}</h5>
                            <small class="text-muted">Attendance</small>
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
                                <i class="bx bx-user bx-sm text-white"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $active_patients }}</h5>
                            <small class="text-muted">Active</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities and Quick Actions -->
    <div class="row g-4">
        <!-- Recent Activities -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-time-five me-2"></i>Recent Medical Activities
                    </h5>
                    <small class="text-muted">Last 30 days</small>
                </div>
                <div class="card-body">
                    @if ($recent_activities && $recent_activities->count() > 0)
                        <ul class="list-unstyled mb-0">
                            @foreach ($recent_activities as $activity)
                                <li class="mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar flex-shrink-0 me-2">
                                            <span class="avatar-initial-small rounded bg-{{ $activity['color'] }}">
                                                <i class="bx {{ $activity['icon'] }} text-white"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="fw-semibold mb-0">{{ $activity['title'] }}</small>
                                                <small
                                                    class="text-muted">{{ Carbon::parse($activity['date'])->format('M d') }}</small>
                                            </div>
                                            <small class="text-muted d-block">description</small>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-5">
                            <i class="bx bx-calendar bx-lg text-muted mb-3"></i>
                            <p class="text-muted">No recent activities found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-rocket me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('antenatal-register') }}"
                            class="btn btn-primary d-flex align-items-center justify-content-center">
                            <i class="bx bx-plus-medical me-2"></i>
                            <span>Antenatal Records</span>
                        </a>
                        <a href="{{ route('delivery-register') }}"
                            class="btn btn-success d-flex align-items-center justify-content-center">
                            <i class="bx bx-baby-carriage me-2"></i>
                            <span>Delivery History</span>
                        </a>
                        <a href="{{ route('post-natal-register') }}"
                            class="btn btn-info d-flex align-items-center justify-content-center">
                            <i class="bx bx-heart me-2"></i>
                            <span>Postnatal Care</span>
                        </a>
                        <a href="{{ route('tetanus-register') }}"
                            class="btn btn-warning d-flex align-items-center justify-content-center">
                            <i class="bx bx-shield-plus me-2"></i>
                            <span>Tetanus Records</span>
                        </a>
                        <a href="{{ route('daily-attendance-register') }}"
                            class="btn btn-secondary d-flex align-items-center justify-content-center">
                            <i class="bx bx-calendar-check me-2"></i>
                            <span>Daily Attendance</span>
                        </a>
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

        .avatar {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-initial {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .avatar-initial-small {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            font-size: 0.875rem;
        }

        .text-muted {
            color: #a7acb2 !important;
        }

        .bg-label-primary {
            background-color: rgba(102, 126, 234, 0.1) !important;
            color: #667eea !important;
        }

        .bg-label-success {
            background-color: rgba(40, 167, 69, 0.1) !important;
            color: #28a745 !important;
        }

        .bg-label-info {
            background-color: rgba(23, 162, 184, 0.1) !important;
            color: #17a2b8 !important;
        }

        .bg-label-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
            color: #ffc107 !important;
        }

        .bg-label-secondary {
            background-color: rgba(108, 117, 125, 0.1) !important;
            color: #6c757d !important;
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
    </style>
</div>
