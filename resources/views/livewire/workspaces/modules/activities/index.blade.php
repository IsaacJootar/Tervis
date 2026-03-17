@php
    use Carbon\Carbon;
@endphp

@section('title', 'Activities')

<div>
    {{-- ============================================ --}}
    {{-- ACCESS DENIED VIEW --}}
    {{-- ============================================ --}}
    @if (!$hasAccess)
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="bx bx-error-circle text-danger" style="font-size: 5rem;"></i>
                        </div>
                        <h3 class="text-danger mb-3">Access Denied</h3>
                        <p class="text-muted mb-4">{{ $accessError }}</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('patient-workspace') }}" class="btn btn-primary">
                                <i class="bx bx-search me-1"></i>Go to Patient Workspace
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-3">
            <span class="badge bg-label-primary text-uppercase">Activities Timeline</span>
        </div>

        {{-- ============================================ --}}
        {{-- PROFILE HEADER --}}
        {{-- ============================================ --}}
        <div class="card mb-4 activity-hero">
            <div class="activity-hero-cover"></div>
            <div class="card-body activity-hero-body">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="activity-avatar">
                        {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-1">Patient Activities</h4>
                        <div class="text-muted small">
                            {{ Carbon::now('Africa/Lagos')->format('l, F j, Y') }} | Checked in at {{ $activation_time }}
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                            <span class="badge bg-label-{{ $patient_gender === 'Female' ? 'danger' : 'info' }}">
                                {{ $patient_gender }}
                            </span>
                            <span class="badge bg-label-secondary">{{ $patient_age }} years</span>
                        </div>
                    </div>
                    <div class="ms-lg-auto">
                        <button wire:click="backToDashboard" type="button"
                            class="btn btn-primary px-4 py-2 d-inline-flex align-items-center">
                            <i class="bx bx-arrow-back me-2"></i>
                            Back to Dashboard
                        </button>
                    </div>
                </div>

                <div class="row g-2 mt-3">
                    <div class="col-6 col-lg-4">
                        <div class="activity-stat">
                            <div class="text-muted small">Facility</div>
                            <div class="fw-semibold">{{ $facility_name ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-4">
                        <div class="activity-stat">
                            <div class="text-muted small">Location</div>
                            <div class="fw-semibold">{{ $facility_lga ?? 'N/A' }}, {{ $facility_state ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="activity-stat">
                            <div class="text-muted small">Total Activities</div>
                            <div class="fw-semibold">{{ $totalActivities }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-primary h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-label">Total Activities</div>
                            <div class="metric-value">{{ number_format($totalActivities) }}</div>
                        </div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M3 12h4l2-6 4 12 2-6h6"></path>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-info h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-label">Today</div>
                            <div class="metric-value">{{ number_format($activitiesToday) }}</div>
                        </div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                                <path d="M8 2v4M16 2v4M3 9h18"></path>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-success h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-label">Modules Involved</div>
                            <div class="metric-value">{{ number_format($distinctModules) }}</div>
                        </div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                                <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                                <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                                <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-warning h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-label">Last Activity</div>
                            <div class="metric-value metric-value-sm">
                                {{ $latestActivityAt ? Carbon::parse($latestActivityAt)->format('d M, h:i A') : 'N/A' }}
                            </div>
                        </div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 7v6l4 2"></path>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Patient Overview --}}
            <div class="col-12 col-lg-4">
                <div class="card h-100 activity-panel">
                    <div class="card-body">
                        <h5 class="mb-3">Patient Overview</h5>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="activity-avatar activity-avatar-sm">
                                {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-semibold activity-patient-name">
                                    {{ $first_name }} {{ $middle_name }} {{ $last_name }}
                                </div>
                                <div class="text-muted small">Phone: {{ $patient_phone ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">DIN</span>
                                <span class="fw-semibold">{{ $patient_din }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">DOB</span>
                                <span class="fw-semibold">{{ $patient_dob ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Gender</span>
                                <span class="fw-semibold">{{ $patient_gender ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Activities Table --}}
            <div class="col-12 col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0">Activity Timeline</h5>
                                <small class="text-muted">
                                    Most recent actions first
                                    @if ($isTruncated)
                                        | Showing latest 1,000 entries
                                    @endif
                                </small>
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-label-success">Create: {{ (int) ($actionsSummary['create'] ?? 0) }}</span>
                                <span class="badge bg-label-info">Update: {{ (int) ($actionsSummary['update'] ?? 0) }}</span>
                                <span class="badge bg-label-danger">Delete: {{ (int) ($actionsSummary['delete'] ?? 0) }}</span>
                                <span class="badge bg-label-secondary">View: {{ (int) ($actionsSummary['view'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-3 pt-3 pb-1 d-flex flex-wrap gap-2">
                        @forelse ($moduleSummary as $moduleItem)
                            <span class="badge bg-label-primary text-capitalize">
                                {{ str_replace('_', ' ', $moduleItem->module) }}: {{ $moduleItem->total }}
                            </span>
                        @empty
                            <span class="text-muted small">No modules recorded yet.</span>
                        @endforelse
                    </div>
                    <div class="card-datatable table-responsive pt-0">
                        <table id="activitiesTimelineTable" class="table align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Time</th>
                                    <th>Module</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>Performed By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activities as $activity)
                                    @php
                                        $actionColor = match ($activity->action) {
                                            'create' => 'success',
                                            'update' => 'info',
                                            'delete' => 'danger',
                                            'view' => 'secondary',
                                            default => 'primary',
                                        };
                                        $moduleLabel = str_replace('_', ' ', $activity->module);
                                    @endphp
                                    <tr>
                                        <td data-order="{{ $activity->created_at?->timestamp ?? 0 }}">{{ $activity->created_at?->format('d M Y, h:i A') }}</td>
                                        <td class="text-capitalize">{{ $moduleLabel }}</td>
                                        <td>
                                            <span class="badge bg-label-{{ $actionColor }}">
                                                {{ strtoupper($activity->action) }}
                                            </span>
                                        </td>
                                        <td>{{ $activity->description ?? 'N/A' }}</td>
                                        <td>{{ $activity->performed_by ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center py-4" colspan="5">
                                            <div class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>
                                                No activity records found yet.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @once
            <style>
                .activity-hero {
                    overflow: hidden;
                    border: 1px solid #e5e7eb;
                }

                .activity-hero-cover {
                    height: 24px;
                    background: #ffffff;
                }

                .activity-hero-body {
                    margin-top: 0;
                }

                .activity-avatar {
                    width: 68px;
                    height: 68px;
                    border-radius: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 700;
                    background: #ffffff;
                    color: #1e293b;
                    border: 3px solid #ffffff;
                    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
                    font-size: 1.2rem;
                }

                .activity-avatar-sm {
                    width: 44px;
                    height: 44px;
                    font-size: 0.95rem;
                }

                .activity-stat {
                    border: 1px solid #e5e7eb;
                    border-radius: 10px;
                    padding: 10px 12px;
                    background: #f8fafc;
                    height: 100%;
                }

                .activity-panel {
                    background: #f8fafc;
                    border: 1px solid #e5e7eb;
                }

                .activity-patient-name {
                    font-size: 1.1rem;
                }

                .metric-card {
                    border: 1px solid #e5e7eb;
                    border-radius: 12px;
                    padding: 12px;
                    min-height: 110px;
                }

                .metric-card-primary {
                    background: #eff6ff;
                    border-color: #bfdbfe;
                    color: #1e40af;
                }

                .metric-card-info {
                    background: #f0f9ff;
                    border-color: #bae6fd;
                    color: #075985;
                }

                .metric-card-success {
                    background: #ecfdf3;
                    border-color: #bbf7d0;
                    color: #166534;
                }

                .metric-card-warning {
                    background: #fff7ed;
                    border-color: #fed7aa;
                    color: #9a3412;
                }

                .metric-label {
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    font-weight: 700;
                    color: rgba(15, 23, 42, 0.7);
                }

                .metric-value {
                    font-size: 1.3rem;
                    font-weight: 700;
                    margin-top: 4px;
                }

                .metric-value-sm {
                    font-size: 1rem;
                    line-height: 1.25rem;
                }

                .metric-icon {
                    width: 34px;
                    height: 34px;
                    border-radius: 10px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    background: rgba(255, 255, 255, 0.85);
                    color: currentColor;
                }

                .metric-icon svg {
                    width: 18px;
                    height: 18px;
                }
            </style>
        @endonce
    @endif
</div>

@include('_partials.datatables-init-multi', [
    'tableIds' => ['activitiesTimelineTable'],
    'orders' => [
        'activitiesTimelineTable' => [0, 'desc'],
    ],
])
