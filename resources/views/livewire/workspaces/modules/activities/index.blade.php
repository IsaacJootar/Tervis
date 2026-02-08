@php
    use Carbon\Carbon;
@endphp

@section('title', 'Activities')

<div x-data="dataTable()">
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
                                <small class="text-muted">Most recent actions first</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="dataTable" class="table">
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
                                        <td>{{ $activity->created_at?->format('d M Y, h:i A') }}</td>
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
                                        <td class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>
                                                No activity records found yet.
                                            </div>
                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $activities->links() }}
                        </div>
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
            </style>
        @endonce
    @endif
</div>
