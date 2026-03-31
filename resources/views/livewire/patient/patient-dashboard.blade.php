@php
    use Carbon\Carbon;
@endphp
@section('title', 'Patient Dashboard')

<div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card portal-section-card h-100">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="portal-section-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 4v16M7 9.5h10" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                        <h6 class="portal-section-title mb-0">Recent Medical Activities</h6>
                    </div>
                    <small class="text-muted">A clear timeline of your latest care interactions across the patient portal.</small>
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
                                            <small class="text-muted">{{ Carbon::parse($activity['date'])->format('M d, Y') }}</small>
                                        </div>
                                        <p class="mb-0">{{ $activity['description'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="portal-empty">
                            <i class="bx bx-calendar bx-lg mb-2"></i>
                            <p class="mb-0">No recent activities have been recorded yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card portal-section-card h-100">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="portal-section-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="5" y="5" width="14" height="14" rx="3" stroke="currentColor"
                                    stroke-width="1.8" />
                                <path d="M8.5 10.5h7M8.5 13.5h7" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                        <h6 class="portal-section-title mb-0">Care Navigator</h6>
                    </div>
                    <small class="text-muted">Move quickly between the core patient record sections using the same modern flow.</small>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3 portal-action-list">
                        <a href="{{ route('patient-attendance') }}" class="btn btn-outline-dark">
                            <span><i class="bx bx-time-five me-2"></i>Attendance Timeline</span>
                            <i class="bx bx-chevron-right"></i>
                        </a>
                        <a href="{{ route('patient-appointments') }}" class="btn btn-outline-primary">
                            <span><i class="bx bx-calendar-event me-2"></i>Appointments</span>
                            <i class="bx bx-chevron-right"></i>
                        </a>
                        <a href="{{ route('patient-visits') }}" class="btn btn-outline-secondary">
                            <span><i class="bx bx-walk me-2"></i>Visit History</span>
                            <i class="bx bx-chevron-right"></i>
                        </a>
                        <a href="{{ route('patient-antenatal') }}" class="btn btn-outline-primary">
                            <span><i class="bx bx-plus-medical me-2"></i>Antenatal Records</span>
                            <i class="bx bx-chevron-right"></i>
                        </a>
                        <a href="{{ route('patient-deliveries') }}" class="btn btn-outline-success">
                            <span><i class="bx bx-baby-carriage me-2"></i>Delivery History</span>
                            <i class="bx bx-chevron-right"></i>
                        </a>
                        <a href="{{ route('patient-postnatal') }}" class="btn btn-outline-info">
                            <span><i class="bx bx-heart me-2"></i>Postnatal Care</span>
                            <i class="bx bx-chevron-right"></i>
                        </a>
                        <a href="{{ route('patient-tetanus') }}" class="btn btn-outline-warning">
                            <span><i class="bx bx-shield-plus me-2"></i>Tetanus Records</span>
                            <i class="bx bx-chevron-right"></i>
                        </a>
                        <a href="{{ route('patient-profile') }}" class="btn btn-outline-secondary">
                            <span><i class="bx bx-user me-2"></i>Profile Details</span>
                            <i class="bx bx-chevron-right"></i>
                        </a>
                        <a href="{{ route('patient-activities') }}" class="btn btn-outline-dark">
                            <span><i class="bx bx-pulse me-2"></i>Activity Log</span>
                            <i class="bx bx-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
