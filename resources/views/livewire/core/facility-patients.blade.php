<div>
    @php use Carbon\Carbon; @endphp
    @section('title', 'Facility Patients')
    <div>

        <!-- Hero Card Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="hero-card">
                    <div class="hero-content">
                        <div class="hero-text">
                            <h4 class="hero-title" style="color: white; font-size: 28px;">
                                <i class='bx bx-user-heart me-2'></i>
                                Facility Patients Registry <small class="text-muted">Patients Medical Records
                                    Summary</small>
                            </h4>

                            <div class="hero-stats">
                                <span class="hero-stat">
                                    <i class="bx bx-group"></i>
                                    {{ count($patients) }} Total Patients
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-calendar-check"></i>
                                    {{ $patients->where('last_visit_date', '!=', null)->count() }} Active Patients
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-stats"></i>
                                    {{ $patients->sum('total_visits') }} Total Visits
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

        <!-- Patients DataTable -->
        <div class="card">
            <div>


            </div>
            <div class="card-datatable table-responsive pt-0" wire:ignore>
                <table id="dataTable" class="table">
                    <thead class="table-dark">
                        <tr>
                            <th>Patient Info</th>
                            <th>Contact</th>
                            <th class="text-center">Attendance</th>
                            <th class="text-center">Antenatal</th>
                            <th class="text-center">TT Vaccination</th>
                            <th class="text-center">Postnatal</th>
                            <th class="text-center">Delivery</th>
                            <th class="text-center">Clinical Notes</th>
                            <th class="text-center">Total Visits</th>
                            <th>Last Visit</th>
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

                                <!-- Attendance Count -->
                                <td class="text-center">
                                    <span class="badge bg-label-primary fs-6">
                                        {{ $patient->attendance_count }}
                                    </span>
                                </td>

                                <!-- Antenatal Count -->
                                <td class="text-center">
                                    <span class="badge bg-label-success fs-6">
                                        {{ $patient->antenatal_count }}
                                    </span>
                                </td>

                                <!-- TT Vaccination Count -->
                                <td class="text-center">
                                    <span class="badge bg-label-warning fs-6">
                                        {{ $patient->tetanus_count }}
                                    </span>
                                </td>

                                <!-- Postnatal Count -->
                                <td class="text-center">
                                    <span class="badge bg-label-info fs-6">
                                        {{ $patient->postnatal_count }}
                                    </span>
                                </td>

                                <!-- Delivery Count -->
                                <td class="text-center">
                                    <span class="badge bg-label-danger fs-6">
                                        {{ $patient->delivery_count }}
                                    </span>
                                </td>

                                <!-- Clinical Notes Count -->
                                <td class="text-center">
                                    <span class="badge bg-label-secondary fs-6">
                                        {{ $patient->clinical_notes_count }}
                                    </span>
                                </td>

                                <!-- Total Visits -->
                                <td class="text-center">
                                    <span class="badge bg-label-dark fs-6 fw-bold">
                                        {{ $patient->total_visits }}
                                    </span>
                                </td>

                                <!-- Last Visit Date -->
                                <td>
                                    @if ($patient->last_visit_date)
                                        <span class="text-success">
                                            {{ Carbon::parse($patient->last_visit_date)->format('M d, Y') }}
                                        </span>
                                        <small class="d-block text-muted">
                                            {{ Carbon::parse($patient->last_visit_date)->diffForHumans() }}
                                        </small>
                                    @else
                                        <span class="text-muted">No visits</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2" class="text-end"><strong>Totals:</strong></th>
                            <th class="text-center">
                                <span class="badge bg-primary">
                                    {{ $patients->sum('attendance_count') }}
                                </span>
                            </th>
                            <th class="text-center">
                                <span class="badge bg-success">
                                    {{ $patients->sum('antenatal_count') }}
                                </span>
                            </th>
                            <th class="text-center">
                                <span class="badge bg-warning">
                                    {{ $patients->sum('tetanus_count') }}
                                </span>
                            </th>
                            <th class="text-center">
                                <span class="badge bg-info">
                                    {{ $patients->sum('postnatal_count') }}
                                </span>
                            </th>
                            <th class="text-center">
                                <span class="badge bg-danger">
                                    {{ $patients->sum('delivery_count') }}
                                </span>
                            </th>
                            <th class="text-center">
                                <span class="badge bg-secondary">
                                    {{ $patients->sum('clinical_notes_count') }}
                                </span>
                            </th>
                            <th class="text-center">
                                <span class="badge bg-dark fw-bold">
                                    {{ $patients->sum('total_visits') }}
                                </span>
                            </th>
                            <th class="text-center">
                                <small class="text-muted">All Records</small>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Legend Card -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="bx bx-info-circle me-2"></i>Records Legend
                        </h6>
                        <div class="row">
                            <div class="col-md-2 mb-2">
                                <div>
                                    <span class="badge bg-label-primary">Attendance</span>
                                    <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">Daily
                                        visits</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div>
                                    <span class="badge bg-label-success">Antenatal</span>
                                    <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">Pregnancy
                                        care</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div>
                                    <span class="badge bg-label-warning">TT Vaccination</span>
                                    <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">Tetanus
                                        shots</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div>
                                    <span class="badge bg-label-info">Postnatal</span>
                                    <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">After
                                        delivery</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div>
                                    <span class="badge bg-label-danger">Delivery</span>
                                    <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">Birth
                                        records</small>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div>
                                    <span class="badge bg-label-secondary">Clinical Notes</span>
                                    <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">Medical
                                        notes</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @include('_partials.datatables-init')
</div>
