@php
    use Carbon\Carbon;
@endphp

@section('title', 'Appointments')

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
                        <a href="{{ route('workspace-dashboard', ['patientId' => $patientId]) }}" class="btn btn-primary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Workspace
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-3">
            <span class="badge bg-label-primary text-uppercase">Appointments</span>
        </div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                    style="width:64px;height:64px;font-weight:700;">
                    {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class="bx bx-calendar-check me-1"></i>Appointment Tracker</h4>
                    <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                        <span class="badge bg-label-secondary">Patient: {{ $first_name }} {{ $last_name }}</span>
                    </div>
                </div>
                <button wire:click="backToDashboard" type="button" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="backToDashboard">
                    <span wire:loading.remove wire:target="backToDashboard"><i class="bx bx-arrow-back me-1"></i>Back to
                        Dashboard</span>
                    <span wire:loading wire:target="backToDashboard"><span
                            class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-slate h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Total</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M7 3v3M17 3v3M4 9h16" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                                <rect x="4" y="5" width="16" height="15" rx="2.5" stroke="currentColor"
                                    stroke-width="1.8" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $summary['total'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-sky h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Upcoming</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                                <path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $summary['upcoming'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-rose h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Missed</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                                <path d="M9.5 9.5l5 5M14.5 9.5l-5 5" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $summary['missed'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-emerald h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Fulfilled</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                                <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $summary['fulfilled'] }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Scheduled Appointments</h5>
            </div>
            <div class="card-datatable table-responsive pt-0">
                <table class="table">
                    <thead class="table-dark">
                        <tr>
                            <th>Appointment Date</th>
                            <th>Type</th>
                            <th>Source</th>
                            <th>Source Date</th>
                            <th>Status</th>
                            <th>Days</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointments as $appointment)
                            <tr>
                                <td>{{ $appointment['appointment_date']->format('M d, Y') }}</td>
                                <td>{{ $appointment['appointment_type'] }}</td>
                                <td>{{ $appointment['source'] }}</td>
                                <td>{{ $appointment['source_date'] ? Carbon::parse($appointment['source_date'])->format('M d, Y') : 'N/A' }}
                                </td>
                                <td>
                                    @php
                                        $statusClass = match ($appointment['status']) {
                                            'Fulfilled' => 'success',
                                            'Missed' => 'danger',
                                            default => 'primary',
                                        };
                                    @endphp
                                    <span class="badge bg-label-{{ $statusClass }}">{{ $appointment['status'] }}</span>
                                </td>
                                <td>
                                    @if ($appointment['days_from_today'] > 0)
                                        In {{ $appointment['days_from_today'] }} day(s)
                                    @elseif ($appointment['days_from_today'] < 0)
                                        {{ abs($appointment['days_from_today']) }} day(s) ago
                                    @else
                                        Today
                                    @endif
                                </td>
                                <td>{{ $appointment['details'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No appointments found from linked module dates yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <style>
        .metric-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            padding: 14px 16px;
            min-height: 108px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 26px -22px rgba(15, 23, 42, 0.45);
        }

        .metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-weight: 700;
        }

        .metric-value {
            margin-top: 6px;
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .metric-icon {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.08);
        }

        .metric-icon svg {
            width: 18px;
            height: 18px;
        }

        .metric-card-slate {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
        }

        .metric-card-sky {
            border-color: #bae6fd;
            background: #f0f9ff;
            color: #0c4a6e;
        }

        .metric-card-emerald {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #065f46;
        }

        .metric-card-rose {
            border-color: #fecdd3;
            background: #fff1f2;
            color: #9f1239;
        }
    </style>
</div>

