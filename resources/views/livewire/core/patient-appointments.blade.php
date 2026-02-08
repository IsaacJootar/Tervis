<div>
    @php
        use Carbon\Carbon;
        use Illuminate\Support\Str;
    @endphp
    @section('title', 'Patient Appointments')
    <div>

        <!-- Hero Card Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="hero-card">
                    <div class="hero-content">
                        <div class="hero-text">
                            <h4 class="hero-title" style="color: white; font-size: 28px;">
                                <i class='bx bx-calendar-heart me-2'></i>
                                Patient Appointments Tracking <small class="text-muted">Appointments and
                                    fulfillment status</small>
                            </h4>

                            <div class="hero-stats">
                                <span class="hero-stat">
                                    <i class="bx bx-group"></i>
                                    {{ count($patients) }} Patients with Appointments
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-calendar-check"></i>
                                    {{ $patients->sum('upcoming_appointments') }} Upcoming
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-calendar-x"></i>
                                    {{ $patients->sum('missed_appointments') }} Missed
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-check-circle"></i>
                                    {{ $patients->sum(function ($p) {return $p->appointments->where('status', 'Fulfilled')->count();}) }}
                                    Fulfilled
                                </span>
                                {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
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

        <!-- Appointments DataTable -->
        <div class="card">

            <div class="card-datatable table-responsive pt-0" wire:ignore>
                <table id="dataTable" class="table">
                    <thead class="table-dark">
                        <tr>
                            <th>Patient Info</th>
                            <th>Contact</th>
                            <th>Next Appointment</th>
                            <th>Appointment Type</th>
                            <th>Days Until/Overdue</th>
                            <th>Status</th>
                            <th class="text-center">Total Appointments</th>
                            <th class="text-center">Missed</th>
                            <th>All Appointments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($patients as $patient)
                            <tr wire:key="{{ $patient->id }}">
                                <!-- Patient Info -->
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-wrapper">
                                            <div class="avatar avatar-sm me-3">
                                                <span class="avatar-initial rounded-circle bg-label-info">
                                                    {{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $patient->first_name }} {{ $patient->last_name }}</h6>
                                            <small class="text-muted">
                                                @if ($patient->DIN)
                                                    DIN: {{ $patient->DIN }}
                                                @else
                                                    ID: {{ $patient->id }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact -->
                                <td>
                                    <div>
                                        @if ($patient->phone)
                                            <small class="d-block">
                                                <i class="bx bx-phone me-1"></i>{{ $patient->phone }}
                                            </small>
                                        @endif
                                        @if ($patient->email)
                                            <small class="d-block text-muted">
                                                <i class="bx bx-envelope me-1"></i>{{ $patient->email }}
                                            </small>
                                        @endif
                                    </div>
                                </td>

                                <!-- Next Appointment Date -->
                                <td>
                                    @if ($patient->next_appointment)
                                        <div>
                                            <span class="fw-bold">
                                                {{ $patient->next_appointment['date']->format('M d, Y') }}
                                            </span>
                                            <small class="d-block text-muted">
                                                {{ $patient->next_appointment['date']->format('l') }}
                                            </small>
                                        </div>
                                    @else
                                        <span class="text-muted">No appointments</span>
                                    @endif
                                </td>

                                <!-- Appointment Type -->
                                <td>
                                    @if ($patient->next_appointment)
                                        <span class="badge bg-label-primary">
                                            {{ $patient->next_appointment['type'] }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Days Until/Overdue -->
                                <td>
                                    @if ($patient->next_appointment)
                                        @php
                                            $daysUntil = $patient->next_appointment['days_until'];
                                            $isOverdue = $daysUntil > 0;
                                            $absdays = abs($daysUntil);
                                        @endphp

                                        @if ($patient->next_appointment['status'] === 'Fulfilled')
                                            <span class="badge bg-label-success">Completed</span>
                                        @elseif($isOverdue)
                                            <span class="badge bg-label-danger">
                                                @if ($absdays == 0)
                                                    Due today
                                                @elseif($absdays == 1)
                                                    1 day overdue
                                                @else
                                                    {{ $absdays }} days overdue
                                                @endif
                                            </span>
                                        @else
                                            <span class="badge bg-label-info">
                                                @if ($absdays == 0)
                                                    Due today
                                                @elseif($absdays == 1)
                                                    1 day left
                                                @else
                                                    {{ $absdays }} days left
                                                @endif
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td>
                                    @if ($patient->next_appointment)
                                        <span class="badge {{ $patient->next_appointment['color_class'] }}">
                                            {{ $patient->next_appointment['status'] }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Total Appointments -->
                                <td class="text-center">
                                    <span class="badge bg-label-dark fs-6">
                                        {{ $patient->total_appointments }}
                                    </span>
                                </td>

                                <!-- Missed Appointments -->
                                <td class="text-center">
                                    <span class="badge bg-label-danger fs-6">
                                        {{ $patient->missed_appointments }}
                                    </span>
                                </td>

                                <!-- All Appointments -->
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($patient->appointments->take(3) as $appointment)
                                            <span class="badge {{ $appointment['color_class'] }}"
                                                style="font-size: 0.7rem;"
                                                title="{{ $appointment['type'] }} - {{ $appointment['date']->format('M d') }} - {{ $appointment['status'] }}">
                                                {{ substr($appointment['type'], 0, 3) }}
                                            </span>
                                        @endforeach
                                        @if ($patient->appointments->count() > 3)
                                            <span class="badge bg-label-secondary" style="font-size: 0.7rem;"
                                                title="Total {{ $patient->appointments->count() }} appointments">
                                                +{{ $patient->appointments->count() - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>



        <!-- Due Soon Notifications Modal -->
        <div wire:ignore.self class="modal fade" id="dueSoonModal" tabindex="-1" aria-labelledby="dueSoonModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dueSoonModalLabel">
                            <i class="bx bx-bell-ring me-2"></i>Appointments Due Soon (Within 3 Days)
                        </h5>
                        <button wire:click="closeModal" type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if (!empty($due_soon_patients))
                            <div class="alert alert-warning" role="alert">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>{{ count($due_soon_patients) }} patients</strong> have appointments due within
                                the next 3 days.
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Patient</th>
                                            <th>Phone</th>
                                            <th>Appointment Type</th>
                                            <th>Date</th>
                                            <th>Urgency</th>
                                            <th>SMS Message Preview</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($due_soon_patients as $patient)
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong>{{ $patient['name'] }}</strong>
                                                        @if ($patient['din'])
                                                            <small class="d-block text-muted">DIN:
                                                                {{ $patient['din'] }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($patient['phone'])
                                                        <span class="badge bg-label-success">
                                                            <i class="bx bx-phone me-1"></i>{{ $patient['phone'] }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-label-danger">No Phone</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-label-primary">{{ $patient['appointment_type'] }}</span>
                                                </td>
                                                <td>{{ $patient['appointment_date'] }}</td>
                                                <td>
                                                    @if ($patient['days_until'] === 0)
                                                        <span class="badge bg-danger">Due Today</span>
                                                    @elseif($patient['days_until'] === 1)
                                                        <span class="badge bg-warning">Tomorrow</span>
                                                    @else
                                                        <span
                                                            class="badge bg-info">{{ $patient['urgency_level'] }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <small class="text-muted" style="max-width: 300px; display: block;">
                                                        {{ Str::limit($patient['message'], 90) }}
                                                    </small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bx bx-check-circle text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No Urgent Appointments</h5>
                                <p class="text-muted">All appointments are either fulfilled or more than 3 days away.
                                </p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        @if (!empty($due_soon_patients))
                            <div class="d-flex justify-content-between w-100">
                                <div class="text-muted">
                                    <small>
                                        <i class="bx bx-info-circle me-1"></i>
                                        {{ count(array_filter($due_soon_patients, function ($p) {return !empty($p['phone']);})) }}
                                        patients have phone numbers for SMS
                                    </small>
                                </div>
                                <div>
                                    <button wire:click="closeModal" type="button"
                                        class="btn btn-secondary">Close</button>
                                    <button wire:click="sendNotifications" type="button" class="btn btn-warning">
                                        <i class="bx bx-message-dots me-2"></i>Send SMS Notifications
                                    </button>
                                </div>
                            </div>
                        @else
                            <button wire:click="closeModal" type="button" class="btn btn-secondary">Close</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-green">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="bx bx-info-circle me-2"></i>Send SMS notifications for appointments
                        </h6>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>

                                        <small class="text-muted">Send SMS notifications for appointments due within 3
                                            days</small>
                                    </div>
                                    <button wire:click="openDueSoonModal" class="btn btn-warning">
                                        <i class="bx bx-message-dots me-2"></i>
                                        View Due Soon & Send Notifications
                                        @php
                                            $dueSoonCount = $patients->sum(function ($patient) {
                                                return $patient->appointments
                                                    ->filter(function ($appointment) {
                                                        return $appointment['status'] === 'Upcoming' &&
                                                            abs($appointment['days_until']) <= 3;
                                                    })
                                                    ->count();
                                            });
                                        @endphp
                                        @if ($dueSoonCount > 0)
                                            <span class="badge bg-danger ms-2">{{ $dueSoonCount }}</span>
                                        @endif
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend Card -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="bx bx-info-circle me-2"></i>Appointment Status Legend
                        </h6>
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <div>
                                    <span class="badge bg-label-success">Fulfilled</span>
                                    <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">Patient
                                        attended after appointment date</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div>
                                    <span class="badge bg-label-info">Upcoming</span>
                                    <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">Future
                                        appointment scheduled</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div>
                                    <span class="badge bg-label-warning">Due Soon</span>
                                    <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">Appointment
                                        within 3 days</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div>
                                    <span class="badge bg-label-danger">Missed</span>
                                    <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">No attendance
                                        after appointment date</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dueSoonModal = document.getElementById('dueSoonModal');

            // Listen for Livewire events to open/close modal
            Livewire.on('open-due-soon-modal', () => {
                const modal = new bootstrap.Modal(dueSoonModal);
                modal.show();
            });

            Livewire.on('close-due-soon-modal', () => {
                const modal = bootstrap.Modal.getInstance(dueSoonModal);
                if (modal) {
                    modal.hide();
                }
            });

            // Handle modal close events
            dueSoonModal.addEventListener('hidden.bs.modal', function() {
                @this.call('closeModal');
            });
        });
    </script>

    @include('_partials.datatables-init')
</div>
