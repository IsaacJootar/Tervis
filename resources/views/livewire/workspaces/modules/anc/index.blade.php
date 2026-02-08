@php
    use Carbon\Carbon;
@endphp

@section('title', 'ANC Workspace')

<div>
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
            <span class="badge bg-label-primary text-uppercase">ANC Workspace</span>
        </div>

        <div class="card mb-4 tt-hero">
            <div class="tt-hero-cover"></div>
            <div class="card-body tt-hero-body">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="tt-avatar">
                        {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-1">ANC Workspace</h4>
                        <div class="text-muted small">
                            {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                            <span class="badge bg-label-{{ $patient_gender === 'Female' ? 'danger' : 'info' }}">
                                {{ $patient_gender }}
                            </span>
                            <span class="badge bg-label-secondary">{{ $patient_age }} years</span>
                            <span class="badge bg-label-secondary">Pregnancy #{{ $pregnancy_number ?? 'N/A' }}</span>
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
                        <div class="tt-stat">
                            <div class="text-muted small">Facility</div>
                            <div class="fw-semibold">{{ $facility_name ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-4">
                        <div class="tt-stat">
                            <div class="text-muted small">Location</div>
                            <div class="fw-semibold">{{ $lga_name ?? 'N/A' }}, {{ $state_name ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-4">
                        <div class="tt-stat">
                            <div class="text-muted small">Checked In</div>
                            <div class="fw-semibold">{{ $activation_time }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="card h-100 tt-panel">
                    <div class="card-body">
                        <h5 class="mb-3">Patient Overview</h5>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="tt-avatar tt-avatar-sm">
                                {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-semibold tt-patient-name">
                                    {{ $first_name }} {{ $middle_name }} {{ $last_name }}
                                </div>
                                <div class="text-muted small">DOB: {{ $patient_dob ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Phone</span>
                                <span class="fw-semibold">{{ $patient_phone ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">LMP</span>
                                <span class="fw-semibold">
                                    {{ $lmp ? Carbon::parse($lmp)->format('d M Y') : 'N/A' }}
                                </span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">EDD</span>
                                <span class="fw-semibold">
                                    {{ $edd ? Carbon::parse($edd)->format('d M Y') : 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                        <h5 class="mb-0">ANC Activities</h5>
                        <small class="text-muted">Jump into any ANC workflow</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('workspaces-antenatal-tt-vaccinations', ['patientId' => $patientId]) }}"
                                class="btn btn-outline-danger">
                                <i class="bx bx-injection me-1"></i>TT Vaccinations
                            </a>
                            <a href="{{ route('workspaces-antenatal-deliveries', ['patientId' => $patientId]) }}"
                                class="btn btn-outline-primary">
                                <i class="bx bx-plus-medical me-1"></i>Deliveries
                            </a>
                            <a href="{{ route('workspaces-antenatal-postnatal', ['patientId' => $patientId]) }}"
                                class="btn btn-outline-success">
                                <i class="bx bx-heart me-1"></i>Postnatal
                            </a>
                            <a href="{{ route('workspaces-antenatal-followup', ['patientId' => $patientId]) }}"
                                class="btn btn-outline-info">
                                <i class="bx bx-notepad me-1"></i>Follow-up Assessment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @once
            <style>
                .tt-hero {
                    overflow: hidden;
                    border: 1px solid #e5e7eb;
                }

                .tt-hero-cover {
                    height: 24px;
                    background: #ffffff;
                }

                .tt-hero-body {
                    margin-top: 0;
                }

                .tt-avatar {
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

                .tt-avatar-sm {
                    width: 44px;
                    height: 44px;
                    font-size: 0.95rem;
                }

                .tt-stat {
                    border: 1px solid #e5e7eb;
                    border-radius: 10px;
                    padding: 10px 12px;
                    background: #f8fafc;
                    height: 100%;
                }

                .tt-panel {
                    background: #f8fafc;
                    border: 1px solid #e5e7eb;
                }

                .tt-patient-name {
                    font-size: 1.1rem;
                }
            </style>
        @endonce
    @endif
</div>
