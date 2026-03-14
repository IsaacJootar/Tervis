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
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">Total</div>
                        <div class="h4 mb-0">{{ $summary['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">Upcoming</div>
                        <div class="h4 mb-0 text-primary">{{ $summary['upcoming'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">Missed</div>
                        <div class="h4 mb-0 text-danger">{{ $summary['missed'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small">Fulfilled</div>
                        <div class="h4 mb-0 text-success">{{ $summary['fulfilled'] }}</div>
                    </div>
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
</div>
