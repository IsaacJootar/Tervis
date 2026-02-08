@php
    use Carbon\Carbon;
@endphp

@section('title', 'Attendance & Verifications')

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
                            <button type="button" class="btn btn-primary" wire:click="backToDashboard">
                                <i class="bx bx-arrow-back me-1"></i>Back to Dashboard
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-3">
            <span class="badge bg-label-primary text-uppercase">Attendance & Verifications</span>
        </div>

        {{-- ============================================ --}}
        {{-- PROFILE HEADER --}}
        {{-- ============================================ --}}
        <div class="card mb-4 attendance-hero">
            <div class="attendance-hero-cover"></div>
            <div class="card-body attendance-hero-body">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="attendance-avatar">
                        {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small">
                            {{ Carbon::now('Africa/Lagos')->format('l, F j, Y') }}
                            | Checked in at {{ $activation_time }}
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                            <span class="badge bg-label-{{ $patient_gender === 'Female' ? 'danger' : 'info' }}">
                                {{ $patient_gender }}
                            </span>
                            <span class="badge bg-label-secondary">{{ $patient_age }} years</span>
                            <span class="badge bg-label-secondary">DOB: {{ $patient_dob ?? 'N/A' }}</span>
                            <span class="badge bg-label-{{ $patient_nhis_status ? 'success' : 'warning' }}">
                                {{ $patient_nhis_status ? 'NHIS Subscriber' : 'NHIS Non-Subscriber' }}
                            </span>
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
                    <div class="col-6 col-lg-3">
                        <div class="attendance-stat">
                            <div class="text-muted small">Facility</div>
                            <div class="fw-semibold">{{ $facility_name ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="attendance-stat">
                            <div class="text-muted small">Location</div>
                            <div class="fw-semibold">{{ $facility_lga ?? 'N/A' }}, {{ $facility_state ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="attendance-stat">
                            <div class="text-muted small">Total Visits</div>
                            <div class="fw-semibold">{{ $total_visits }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="attendance-stat">
                            <div class="text-muted small">Visits This Month</div>
                            <div class="fw-semibold">{{ $visits_this_month }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Patient Overview --}}
            <div class="col-12 col-lg-4">
                <div class="card h-100 attendance-panel">
                    <div class="card-body">
                        <h5 class="mb-3">Patient Overview</h5>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="attendance-avatar attendance-avatar-sm">
                                {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-semibold attendance-patient-name">
                                    {{ $first_name }} {{ $middle_name }} {{ $last_name }}
                                </div>
                                <div class="text-muted small">Last Visit: {{ $last_visit_date }}</div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Phone</span>
                                <span class="fw-semibold">{{ $patient_phone ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Gender</span>
                                <span class="fw-semibold">{{ $patient_gender ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Age</span>
                                <span class="fw-semibold">{{ $patient_age ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Checked In</span>
                                <span class="fw-semibold">{{ $activation_time }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Data Officer</span>
                                <span class="fw-semibold">{{ $officer_name ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Attendance Table --}}
            <div class="col-12 col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0">Attendance History</h5>
                                <small class="text-muted">All check-ins for this patient at this facility</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="dataTable" class="table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Visit Date</th>
                                    <th>Check-In Time</th>
                                    <th>Facility</th>
                                    <th>Officer</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activations as $activation)
                                    <tr wire:key="activation-{{ $activation->id }}">
                                        <td>{{ $activation->formatted_visit_date ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-label-success">
                                                <i class="bx bx-time-five me-1"></i>
                                                {{ $activation->formatted_check_in_time ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>{{ $facility_name ?? 'N/A' }}</td>
                                        <td>{{ $activation->officer_name ?? 'N/A' }}</td>
                                        <td>{{ $activation->officer_designation ?? 'N/A' }}</td>
                                        <td>
                                            @if ($activation->is_today)
                                                <span class="badge bg-label-success">Today</span>
                                            @else
                                                <span class="badge bg-label-secondary">Past Visit</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No attendance records found.
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
                .attendance-hero {
                    overflow: hidden;
                    border: 1px solid #e5e7eb;
                }

                .attendance-hero-cover {
                    height: 24px;
                    background: #ffffff;
                }

                .attendance-hero-body {
                    margin-top: 0;
                }

                .attendance-avatar {
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

                .attendance-avatar-sm {
                    width: 44px;
                    height: 44px;
                    font-size: 0.95rem;
                }

                .attendance-stat {
                    border: 1px solid #e5e7eb;
                    border-radius: 10px;
                    padding: 10px 12px;
                    background: #f8fafc;
                    height: 100%;
                }

                .attendance-panel {
                    background: #f8fafc;
                    border: 1px solid #e5e7eb;
                }

                .attendance-patient-name {
                    font-size: 1.1rem;
                }
            </style>
        @endonce
    @endif
</div>
