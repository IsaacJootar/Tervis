<div>
    @php
        use Carbon\Carbon;
        use Illuminate\Support\Str;
    @endphp
    @section('title', 'Facility Reports')

    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 28px;">
                            <i class='bx bx-chart-line me-2'></i>
                            Facility Reports & Analytics
                        </h4>

                        <div class="hero-stats">
                            <span class="hero-stat">
                                <i class="bx bx-file-text"></i>
                                {{ count($available_reports) }} Report Types
                            </span>

                            @if ($show_results)
                                <span class="hero-stat">
                                    <i class="bx bx-data"></i>
                                    {{ count($report_data) }} Records Found
                                </span>
                            @endif

                            <span class="hero-stat">
                                <i class="bx bx-building"></i>
                                @if ($selectedFacilityId)
                                    Single Facility
                                @else
                                    {{ $scopeInfo['scope_type'] === 'state' ? 'State-wide' : ($scopeInfo['scope_type'] === 'lga' ? 'LGA-wide' : 'Single Facility') }}
                                    ({{ count($scopeInfo['facility_ids']) }}
                                    {{ count($scopeInfo['facility_ids']) == 1 ? 'facility' : 'facilities' }})
                                @endif
                            </span>

                            <span class="hero-stat">
                                <i class="bx bx-time"></i>
                                {{ Carbon::now('Africa/Lagos')->format('h:i A') }}
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

    <!-- Facility Filter (only show if multiple facilities) -->
    @if (count($availableFacilities) > 0)
        <div class="row mb-4">
            <div class="col-md-8">
                <label class="form-label">
                    <i class="bx bx-buildings me-1"></i>
                    Filter by Facility
                </label>
                <select wire:model.live="selectedFacilityId" class="form-select form-select-lg">
                    <option value="">All Facilities
                        ({{ $scopeInfo['scope_type'] === 'state' ? 'State-wide' : 'LGA-wide' }})</option>
                    @foreach ($availableFacilities as $facility)
                        <option value="{{ $facility['id'] }}">
                            {{ $facility['name'] }} - {{ $facility['lga'] }} ({{ $facility['ward'] }})
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">
                    @if ($selectedFacilityId)
                        Showing data for selected facility only
                    @else
                        Showing aggregated data across {{ count($scopeInfo['facility_ids']) }} facilities
                    @endif
                </small>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                @if ($selectedFacilityId)
                    <button wire:click="resetToScope" class="btn btn-outline-secondary btn-lg w-100">
                        <i class="bx bx-reset me-1"></i>
                        View All Facilities
                    </button>
                @endif
            </div>
        </div>
    @endif

    <!-- Report Configuration -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-filter me-2"></i>Report Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <!-- Report Type Selection -->
                        <div class="col-md-3">
                            <label class="form-label">Select Report Type <span class="text-danger">*</span></label>
                            <select wire:model.live="selected_report" class="form-select">
                                <option value="">--Choose Report--</option>
                                @foreach ($available_reports as $key => $title)
                                    <option value="{{ $key }}">{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date From -->
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input wire:model="date_from" type="date" class="form-select">
                        </div>

                        <!-- Date To -->
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input wire:model="date_to" type="date" class="form-select">
                        </div>

                        <!-- Generate Button -->
                        <div class="col-md-3">
                            <button wire:click="generateReport" class="btn btn-primary w-100"
                                @if (!$selected_report) disabled @endif wire:loading.attr="disabled">
                                <span wire:loading wire:target="generateReport">
                                    <span class="spinner-border spinner-border-sm me-1"></span>
                                    Generating...
                                </span>
                                <span wire:loading.remove wire:target="generateReport">
                                    <i class="bx bx-play me-1"></i>Generate
                                </span>
                            </button>
                        </div>
                    </div>

                    @if ($selected_report)
                        <div class="alert alert-info mt-3" role="alert">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>{{ $available_reports[$selected_report] }}</strong> -
                            {{ $this->getReportDescription($selected_report) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Report Results -->
    @if ($show_results && !empty($report_data))
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-chart-line me-2"></i>{{ $report_title }}
                </h5>
            </div>
            <div class="card-datatable table-responsive pt-0">
                <table id="dataTable" class="table">
                    <thead class="table-dark">
                        <tr>
                            @if ($selected_report === 'patient_summary')
                                <th>Patient Name</th>
                                <th>DIN</th>
                                <th>Phone</th>
                                @if (!$selectedFacilityId)
                                    <th>Facility</th>
                                @endif
                                <th>Total Visits</th>
                                <th>Antenatal Records</th>
                                <th>Delivery Records</th>
                                <th>Last Visit</th>
                            @elseif($selected_report === 'antenatal_bookings')
                                <th>Patient Name</th>
                                <th>DIN</th>
                                <th>Phone</th>
                                @if (!$selectedFacilityId)
                                    <th>Facility</th>
                                @endif
                                <th>Booking Date</th>
                                <th>LMP</th>
                                <th>EDD</th>
                                <th>Age</th>
                                <th>Blood Group</th>
                                <th>Genotype</th>
                                <th>Hemoglobin</th>
                                <th>Next Visit</th>
                            @elseif($selected_report === 'delivery_summary')
                                <th>Patient Name</th>
                                <th>DIN</th>
                                @if (!$selectedFacilityId)
                                    <th>Facility</th>
                                @endif
                                <th>Delivery Date</th>
                                <th>Mode of Delivery</th>
                                <th>Baby Weight</th>
                                <th>Baby Sex</th>
                                <th>Alive</th>
                                <th>Still Birth</th>
                                <th>Complications</th>
                                <th>Officer</th>
                            @elseif($selected_report === 'tetanus_vaccination')
                                <th>Patient Name</th>
                                <th>DIN</th>
                                @if (!$selectedFacilityId)
                                    <th>Facility</th>
                                @endif
                                <th>Dose Date</th>
                                <th>TT Dose</th>
                                <th>Protection Status</th>
                                <th>Next Appointment</th>
                                <th>Vaccination Site</th>
                                <th>Adverse Event</th>
                                <th>Officer</th>
                            @elseif($selected_report === 'clinical_notes')
                                <th>Patient Name</th>
                                <th>DIN</th>
                                @if (!$selectedFacilityId)
                                    <th>Facility</th>
                                @endif
                                <th>Visit Date</th>
                                <th>Section</th>
                                <th>Note</th>
                                <th>Officer</th>
                                <th>Designation</th>
                            @elseif($selected_report === 'attendance_summary')
                                <th>Patient Name</th>
                                <th>DIN</th>
                                @if (!$selectedFacilityId)
                                    <th>Facility</th>
                                @endif
                                <th>Visit Date</th>
                                <th>Gender</th>
                                <th>Age Group</th>
                                <th>First Contact</th>
                                <th>Address</th>
                                <th>Phone</th>
                            @elseif($selected_report === 'appointment_tracking')
                                <th>Patient Name</th>
                                <th>DIN</th>
                                @if (!$selectedFacilityId)
                                    <th>Facility</th>
                                @endif
                                <th>Appointment Date</th>
                                <th>Appointment Type</th>
                                <th>Status</th>
                            @elseif($selected_report === 'maternal_outcomes')
                                <th>Patient Name</th>
                                <th>DIN</th>
                                @if (!$selectedFacilityId)
                                    <th>Facility</th>
                                @endif
                                <th>Delivery Date</th>
                                <th>Mode of Delivery</th>
                                <th>Baby Weight</th>
                                <th>Alive</th>
                                <th>Still Birth</th>
                                <th>Complications</th>
                                <th>Age</th>
                                <th>Previous Pregnancies</th>
                                <th>Hemoglobin</th>
                                <th>Blood Pressure</th>
                            @elseif($selected_report === 'department_utilization')
                                <th>Department</th>
                                @if (!$selectedFacilityId)
                                    <th>Facility</th>
                                @endif
                                <th>Details</th>
                                <th>Total Notes</th>
                                <th>Unique Patients</th>
                            @elseif($selected_report === 'staff_productivity')
                                <th>Officer Name</th>
                                <th>Designation</th>
                                @if (!$selectedFacilityId)
                                    <th>Facility</th>
                                @endif
                                <th>Total Activities</th>
                                <th>Activity Breakdown</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report_data as $row)
                            <tr>
                                @if ($selected_report === 'patient_summary')
                                    <td>{{ $row->first_name }} {{ $row->last_name }}</td>
                                    <td>{{ $row->DIN ?? 'N/A' }}</td>
                                    <td>{{ $row->phone ?? 'N/A' }}</td>
                                    @if (!$selectedFacilityId)
                                        <td><small class="text-muted">{{ $row->facility_name ?? 'N/A' }}</small></td>
                                    @endif
                                    <td><span class="badge bg-primary">{{ $row->total_visits }}</span></td>
                                    <td><span class="badge bg-success">{{ $row->antenatal_records }}</span></td>
                                    <td><span class="badge bg-info">{{ $row->delivery_records }}</span></td>
                                    <td>{{ $row->last_visit ? Carbon::parse($row->last_visit)->format('M d, Y') : 'N/A' }}
                                    </td>
                                @elseif($selected_report === 'antenatal_bookings')
                                    <td>{{ $row->first_name }} {{ $row->last_name }}</td>
                                    <td>{{ $row->DIN ?? 'N/A' }}</td>
                                    <td>{{ $row->phone ?? 'N/A' }}</td>
                                    @if (!$selectedFacilityId)
                                        <td><small class="text-muted">{{ $row->facility_name ?? 'N/A' }}</small></td>
                                    @endif
                                    <td>{{ $row->date_of_booking ? Carbon::parse($row->date_of_booking)->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td>{{ $row->lmp ? Carbon::parse($row->lmp)->format('M d, Y') : 'N/A' }}</td>
                                    <td>{{ $row->edd ? Carbon::parse($row->edd)->format('M d, Y') : 'N/A' }}</td>
                                    <td>{{ $row->age ?? 'N/A' }} years</td>
                                    <td><span
                                            class="badge bg-label-info">{{ $row->blood_group_rhesus ?? 'N/A' }}</span>
                                    </td>
                                    <td><span class="badge bg-label-warning">{{ $row->genotype ?? 'N/A' }}</span>
                                    </td>
                                    <td>{{ $row->hemoglobin ?? 'N/A' }} g/dL</td>
                                    <td>{{ $row->follow_up_next_visit ? Carbon::parse($row->follow_up_next_visit)->format('M d, Y') : 'N/A' }}
                                    </td>
                                @elseif($selected_report === 'delivery_summary')
                                    <td>{{ $row->first_name }} {{ $row->last_name }}</td>
                                    <td>{{ $row->DIN ?? 'N/A' }}</td>
                                    @if (!$selectedFacilityId)
                                        <td><small class="text-muted">{{ $row->facility_name ?? 'N/A' }}</small></td>
                                    @endif
                                    <td>{{ $row->dodel ? Carbon::parse($row->dodel)->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td>{{ $row->mod ?? 'N/A' }}</td>
                                    <td>{{ $row->weight ? $row->weight . ' kg' : 'N/A' }}</td>
                                    <td>{{ $row->baby_sex ?? 'N/A' }}</td>
                                    <td><span
                                            class="badge {{ $row->alive === 'Yes' ? 'bg-success' : 'bg-danger' }}">{{ $row->alive ?? 'N/A' }}</span>
                                    </td>
                                    <td><span
                                            class="badge {{ $row->still_birth === 'Yes' ? 'bg-danger' : 'bg-success' }}">{{ $row->still_birth ?? 'No' }}</span>
                                    </td>
                                    <td>{{ $row->complications ?? 'None' }}</td>
                                    <td>{{ $row->officer_name ?? 'N/A' }}</td>
                                @elseif($selected_report === 'tetanus_vaccination')
                                    <td>{{ $row->first_name }} {{ $row->last_name }}</td>
                                    <td>{{ $row->DIN ?? 'N/A' }}</td>
                                    @if (!$selectedFacilityId)
                                        <td><small class="text-muted">{{ $row->facility_name ?? 'N/A' }}</small></td>
                                    @endif
                                    <td>{{ $row->dose_date ? Carbon::parse($row->dose_date)->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td><span class="badge bg-warning">{{ $row->current_tt_dose ?? 'N/A' }}</span>
                                    </td>
                                    <td><span class="badge bg-info">{{ $row->protection_status ?? 'N/A' }}</span>
                                    </td>
                                    <td>{{ $row->next_appointment_date ? Carbon::parse($row->next_appointment_date)->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td>{{ $row->vaccination_site ?? 'N/A' }}</td>
                                    <td>{{ $row->adverse_event ?? 'None' }}</td>
                                    <td>{{ $row->officer_name ?? 'N/A' }}</td>
                                @elseif($selected_report === 'clinical_notes')
                                    <td>{{ $row->first_name }} {{ $row->last_name }}</td>
                                    <td>{{ $row->DIN ?? 'N/A' }}</td>
                                    @if (!$selectedFacilityId)
                                        <td><small class="text-muted">{{ $row->facility_name ?? 'N/A' }}</small></td>
                                    @endif
                                    <td>{{ $row->date_of_visit ? Carbon::parse($row->date_of_visit)->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td><span class="badge bg-primary">{{ $row->section ?? 'N/A' }}</span></td>
                                    <td>{{ $row->note ? Str::limit($row->note, 100) : 'N/A' }}</td>
                                    <td>{{ $row->officer_name ?? 'N/A' }}</td>
                                    <td>{{ $row->officer_designation ?? 'N/A' }}</td>
                                @elseif($selected_report === 'attendance_summary')
                                    <td>{{ $row->first_name }} {{ $row->last_name }}</td>
                                    <td>{{ $row->DIN ?? 'N/A' }}</td>
                                    @if (!$selectedFacilityId)
                                        <td><small class="text-muted">{{ $row->facility_name ?? 'N/A' }}</small></td>
                                    @endif
                                    <td>{{ $row->visit_date ? Carbon::parse($row->visit_date)->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td><span
                                            class="badge {{ $row->gender === 'Female' ? 'bg-pink' : 'bg-blue' }}">{{ $row->gender ?? 'N/A' }}</span>
                                    </td>
                                    <td>{{ $row->age_group ?? 'N/A' }}</td>
                                    <td><span
                                            class="badge {{ $row->first_contact ? 'bg-success' : 'bg-secondary' }}">{{ $row->first_contact ? 'Yes' : 'No' }}</span>
                                    </td>
                                    <td>{{ $row->address ? Str::limit($row->address, 50) : 'N/A' }}</td>
                                    <td>{{ $row->phone ?? 'N/A' }}</td>
                                @elseif($selected_report === 'appointment_tracking')
                                    <td>{{ $row->first_name }} {{ $row->last_name }}</td>
                                    <td>{{ $row->DIN ?? 'N/A' }}</td>
                                    @if (!$selectedFacilityId)
                                        <td><small class="text-muted">{{ $row->facility_name ?? 'N/A' }}</small></td>
                                    @endif
                                    <td>{{ $row->appointment_date ? Carbon::parse($row->appointment_date)->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td><span class="badge bg-primary">{{ $row->appointment_type ?? 'N/A' }}</span>
                                    </td>
                                    <td><span
                                            class="badge {{ $row->status === 'Fulfilled' ? 'bg-success' : 'bg-danger' }}">{{ $row->status ?? 'N/A' }}</span>
                                    </td>
                                @elseif($selected_report === 'maternal_outcomes')
                                    <td>{{ $row->first_name }} {{ $row->last_name }}</td>
                                    <td>{{ $row->DIN ?? 'N/A' }}</td>
                                    @if (!$selectedFacilityId)
                                        <td><small class="text-muted">{{ $row->facility_name ?? 'N/A' }}</small></td>
                                    @endif
                                    <td>{{ $row->dodel ? Carbon::parse($row->dodel)->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td>{{ $row->mod ?? 'N/A' }}</td>
                                    <td>{{ $row->weight ? $row->weight . ' kg' : 'N/A' }}</td>
                                    <td><span
                                            class="badge {{ $row->alive === 'Yes' ? 'bg-success' : 'bg-danger' }}">{{ $row->alive ?? 'N/A' }}</span>
                                    </td>
                                    <td><span
                                            class="badge {{ $row->still_birth === 'Yes' ? 'bg-danger' : 'bg-success' }}">{{ $row->still_birth ?? 'No' }}</span>
                                    </td>
                                    <td>{{ $row->complications ?? 'None' }}</td>
                                    <td>{{ $row->age ?? 'N/A' }} years</td>
                                    <td>{{ $row->previous_pregnancies ?? 'N/A' }}</td>
                                    <td>{{ $row->hemoglobin ?? 'N/A' }} g/dL</td>
                                    <td>{{ $row->blood_pressure ?? 'N/A' }}</td>
                                @elseif($selected_report === 'department_utilization')
                                    <td><strong>{{ $row->department_name ?? 'N/A' }}</strong></td>
                                    @if (!$selectedFacilityId)
                                        <td><small class="text-muted">{{ $row->facility_name ?? 'N/A' }}</small></td>
                                    @endif
                                    <td>{{ $row->details ? Str::limit($row->details, 100) : 'No details' }}</td>
                                    <td><span class="badge bg-primary">{{ $row->total_notes ?? 0 }}</span></td>
                                    <td><span class="badge bg-success">{{ $row->unique_patients ?? 0 }}</span></td>
                                @elseif($selected_report === 'staff_productivity')
                                    <td><strong>{{ $row->officer_name ?? 'N/A' }}</strong></td>
                                    <td>{{ $row->officer_designation ?? 'N/A' }}</td>
                                    @if (!$selectedFacilityId)
                                        <td><small class="text-muted">{{ $row->facility_name ?? 'N/A' }}</small></td>
                                    @endif
                                    <td><span class="badge bg-dark fs-6">{{ $row->total_activities ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach ($row->activity_breakdown ?? [] as $type => $count)
                                                <span class="badge bg-label-secondary" style="font-size: 0.7rem;">
                                                    {{ Str::limit($type, 10) }}: {{ $count }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="100%" class="text-center">
                                <strong>Total Records: {{ count($report_data) }}</strong>
                                <span class="text-muted">| Generated on
                                    {{ Carbon::now()->format('M d, Y h:i A') }}</span>
                                @if (!$selectedFacilityId && count($scopeInfo['facility_ids']) > 1)
                                    <span class="text-muted">| Across
                                        {{ count($scopeInfo['facility_ids']) }} facilities</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @elseif($show_results && empty($report_data))
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bx bx-search text-muted" style="font-size: 4rem;"></i>
                <h5 class="mt-3">No Data Found</h5>
                <p class="text-muted">No records found for the selected criteria. Try adjusting your date range or
                    report type.</p>
                <button wire:click="generateReport" class="btn btn-primary">
                    <i class="bx bx-refresh me-1"></i>Regenerate Report
                </button>
            </div>
        </div>
    @endif

    <!-- Available Reports Info -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="bx bx-info-circle me-2"></i>Available Reports
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Patient & Clinical Reports</h6>
                            <ul class="list-unstyled">
                                <li><small><i class="bx bx-check me-1"></i><strong>Patient Summary:</strong>
                                        Overall patient statistics and visit patterns</small></li>
                                <li><small><i class="bx bx-check me-1"></i><strong>Antenatal Bookings:</strong>
                                        Pregnancy registrations and follow-ups</small></li>
                                <li><small><i class="bx bx-check me-1"></i><strong>Delivery Summary:</strong> Birth
                                        records and outcomes</small></li>
                                <li><small><i class="bx bx-check me-1"></i><strong>Clinical Notes:</strong> Medical
                                        notes and lab results</small></li>
                                <li><small><i class="bx bx-check me-1"></i><strong>Daily Attendance:</strong>
                                        Patient visit records</small></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">Operational & Analytics Reports</h6>
                            <ul class="list-unstyled">
                                <li><small><i class="bx bx-check me-1"></i><strong>Tetanus Vaccination:</strong>
                                        Immunization tracking and schedules</small></li>
                                <li><small><i class="bx bx-check me-1"></i><strong>Appointment Tracking:</strong>
                                        Appointment compliance and missed visits</small></li>
                                <li><small><i class="bx bx-check me-1"></i><strong>Maternal Outcomes:</strong>
                                        Pregnancy and delivery health outcomes</small></li>
                                <li><small><i class="bx bx-check me-1"></i><strong>Department Utilization:</strong>
                                        Department-wise service usage</small></li>
                                <li><small><i class="bx bx-check me-1"></i><strong>Staff Productivity:</strong>
                                        Healthcare worker activity analysis</small></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('_partials.datatables-init')

    <style>
        .hero-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            min-height: 220px;
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
            color: rgba(255, 255, 255, 0.95);
            font-weight: 500;
            font-size: 14px;
        }

        .hero-stat i {
            margin-right: 0.5rem;
            font-size: 18px;
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

        @media (max-width: 768px) {
            .hero-stats {
                gap: 1rem;
            }

            .hero-stat {
                font-size: 12px;
            }

            .card-body {
                padding: 1rem;
            }
        }
    </style>
</div>
