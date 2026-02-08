@php
    use Carbon\Carbon;
@endphp
@section('title', 'Postnatal - ANC Workspace')
<div x-data="dataTable()">
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
            <span class="badge bg-label-primary text-uppercase">Postnatal</span>
        </div>

        <div class="card mb-4 tt-hero">
            <div class="tt-hero-cover"></div>
            <div class="card-body tt-hero-body">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="tt-avatar">
                        {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-1">ANC Postnatal</h4>
                        <div class="text-muted small">
                            {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
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
                            <div class="text-muted small">Total Records</div>
                            <div class="fw-semibold">{{ count($posts) }}</div>
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
                                <div class="text-muted small">Checked In: {{ $activation_time }}</div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Phone</span>
                                <span class="fw-semibold">{{ $patient_phone ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">DOB</span>
                                <span class="fw-semibold">{{ $patient_dob ?? 'N/A' }}</span>
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
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Pregnancy #</span>
                                <span class="fw-semibold">{{ $pregnancy_number ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Today's Visits</span>
                                <span class="fw-semibold">
                                    {{ $posts->filter(fn($post) => $post->visit_date && Carbon::parse($post->visit_date)->isToday())->count() }}
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
                                <h5 class="mb-0">Postnatal Records</h5>
                                <small class="text-muted">{{ count($posts) }} Total</small>
                            </div>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#postNatalModal">
                                <i class="bx bx-plus me-1"></i>Record Postnatal
                            </button>
                        </div>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="dataTable" class="table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Patient DIN</th>
                                    <th>State</th>
                                    <th>Visit Date</th>
                                    <th>Attendance</th>
                                    <th>Child Sex</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($posts as $post)
                                    <tr wire:key="{{ $post->id }}">
                                        <td>
                                            <span class="badge bg-label-info">{{ $post->patient->din ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <i class="bx bx-map me-1"></i>
                                            {{ $post->state->name ?? 'N/A' }}
                                        </td>
                                        <td>{{ $post->visit_date ? Carbon::parse($post->visit_date)->format('M d, Y') : 'N/A' }}</td>
                                        <td>{{ $post->attendance ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge {{ $post->child_sex === 'Male' ? 'bg-label-primary' : 'bg-label-success' }}">
                                                {{ $post->child_sex ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                    data-bs-toggle="dropdown">
                                                    <i class="icon-base ti tabler-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="javascript:void(0)"
                                                        data-bs-toggle="modal" data-bs-target="#postNatalModal"
                                                        wire:click="edit({{ $post->id }})">
                                                        <i class="icon-base ti tabler-pencil me-1"></i> Edit
                                                    </a>
                                                    <a class="dropdown-item" href="javascript:void(0)"
                                                        wire:click="delete({{ $post->id }})">
                                                        <i class="icon-base ti tabler-trash me-1"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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

    
    <!-- Postnatal Modal -->
    <div wire:ignore.self class="modal fade" id="postNatalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-body">
                    <button wire:click='exit' type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close" onclick="setTimeout(() => location.reload(), 300)"></button>
                    <div class="text-center mb-4">
                        <h4 class="mb-2">
                            {{ $post_id ? 'Edit Postnatal Registration' : 'Postnatal Registration' }}
                        </h4>
                        <p class="text-muted"><span class="badge bg-info">Postnatal Record Tracking</span></p>
                    </div>
                    <form wire:submit.prevent="{{ $modal_flag ? 'update' : 'store' }}">
                        @csrf
                        <!-- Facility Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-primary">Facility
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">State <span class="text-danger">*</span></label>
                                        <input wire:model='state_name' type="text" class="form-control" readonly>
                                        <input wire:model='state_id' type="hidden">
                                        @error('state_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror


                                      </div>
                                    <div class="col-md-4">
                                        <label class="form-label">LGA</label>
                                        <input wire:model='lga_name' type="text" class="form-control" readonly>
                                        <input wire:model='lga_id' type="hidden">
                                        @error('lga_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ward</label>
                                        <input wire:model='ward_name' type="text" class="form-control" readonly>
                                        <input wire:model='ward_id' type="hidden">
                                        @error('ward_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Health Facility <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='facility_name' type="text" class="form-control"
                                            readonly>
                                        <input wire:model='facility_id' type="hidden">
                                        @error('facility_id')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Month/Day/Year</label>
                                        <input wire:model='month_year' readonly type="date" class="form-control">
                                        @error('month_year')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Patient Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-secondary">Patient
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">First Name <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='first_name' type="text" class="form-control" readonly>
                                        @error('first_name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input wire:model='last_name' type="text" class="form-control" readonly>
                                        @error('last_name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Visit Date <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='visit_date' type="date" class="form-control">
                                        @error('visit_date')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Delivery Date <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='delivery_date' type="date" class="form-control">
                                        @error('delivery_date')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Age Range</label>
                                        <select wire:model='age_range' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="11 - 14 years">11 - 14 years</option>
                                            <option value="15 - 19 years">15 - 19 years</option>
                                            <option value="20 - 24 years">20 - 24 years</option>
                                            <option value="25 - 29 years">25 - 29 years</option>
                                            <option value="30 - 34 years">30 - 34 years</option>
                                            <option value="35 - 49 years">35 - 49 years</option>
                                            <option value="50 + years">50 + years</option>
                                        </select>
                                        @error('age_range')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Client Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-info">Client
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Parity Count</label>
                                        <input wire:model='parity_count' type="number" class="form-control"
                                            min="0" max="20">
                                        @error('parity_count')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Attendance</label>
                                        <select wire:model='attendance' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="1st Visit">1st Visit</option>
                                            <option value="2nd Visit">2nd Visit</option>
                                            <option value="3rd Visit">3rd Visit</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        @error('attendance')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Associated Problems</label>
                                        <textarea wire:model='associated_problems' class="form-control" rows="3"></textarea>
                                        @error('associated_problems')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mother Days Postpartum</label>
                                        <input wire:model='mother_days' type="number" class="form-control"
                                            min="0" max="365">
                                        @error('mother_days')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Child Days</label>
                                        <input wire:model='child_days' type="number" class="form-control"
                                            min="0" max="365">
                                        @error('child_days')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Child Sex</label>
                                        <select wire:model='child_sex' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                        @error('child_sex')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nutrition Counseling</label>
                                        <select wire:model='nutrition_counseling' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                            <option value="Counseled">Counseled</option>
                                        </select>
                                        @error('nutrition_counseling')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Breast Examination</label>
                                        <select wire:model='breast_examination' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Normal">Normal</option>
                                            <option value="Abnormal">Abnormal</option>
                                            <option value="Not Done">Not Done</option>
                                        </select>
                                        @error('breast_examination')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Breastfeeding Status</label>
                                        <select wire:model='breastfeeding_status' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Exclusive">Exclusive</option>
                                            <option value="Mixed">Mixed</option>
                                            <option value="Not Breastfeeding">Not Breastfeeding</option>
                                        </select>
                                        @error('breastfeeding_status')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Family Planning</label>
                                        <select wire:model='family_planning' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Counseled">Counseled</option>
                                            <option value="Accepted">Accepted</option>
                                            <option value="Declined">Declined</option>
                                        </select>
                                        @error('family_planning')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Female Genital Mutilation</label>
                                        <select wire:model='female_genital_mutilation' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                            <option value="Suspected">Suspected</option>
                                        </select>
                                        @error('female_genital_mutilation')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Vaginal Examination</label>
                                        <select wire:model='vaginal_examination' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Normal">Normal</option>
                                            <option value="Abnormal">Abnormal</option>
                                            <option value="Not Done">Not Done</option>
                                        </select>
                                        @error('vaginal_examination')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Packed Cell Volume</label>
                                        <input wire:model='packed_cell_volume' type="text" class="form-control"
                                            placeholder="e.g., 33%">
                                        @error('packed_cell_volume')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Urine Test Results</label>
                                        <input wire:model='urine_test_results' type="text" class="form-control">
                                        @error('urine_test_results')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Newborn Care</label>
                                        <select wire:model='newborn_care' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Provided">Provided</option>
                                            <option value="Not Provided">Not Provided</option>
                                            <option value="Referred">Referred</option>
                                        </select>
                                        @error('newborn_care')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Kangaroo Mother Care</label>
                                        <select wire:model='kangaroo_mother_care' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                            <option value="Not Applicable">Not Applicable</option>
                                        </select>
                                        @error('kangaroo_mother_care')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Visit Outcome</label>
                                        <select wire:model='visit_outcome' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Stable">Stable</option>
                                            <option value="Referred">Referred</option>
                                            <option value="Admitted">Admitted</option>
                                            <option value="Discharged">Discharged</option>
                                        </select>
                                        @error('visit_outcome')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Systolic BP (mmHg)</label>
                                        <input wire:model='systolic_bp' type="number" class="form-control"
                                            min="50" max="250">
                                        @error('systolic_bp')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Diastolic BP (mmHg)</label>
                                        <input wire:model='diastolic_bp' type="number" class="form-control"
                                            min="30" max="150">
                                        @error('diastolic_bp')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Newborn Weight (kg)</label>
                                        <input wire:model='newborn_weight' type="number" class="form-control"
                                            step="0.1" min="0.5" max="6.0">
                                        @error('newborn_weight')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Officer Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-warning">Officer
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Officer Name <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='officer_name' type="text" class="form-control"
                                            readonly>
                                        @error('officer_name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Officer Role <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='officer_role' type="text" class="form-control"
                                            readonly>
                                        @error('officer_role')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Officer Designation <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='officer_designation' type="text" class="form-control"
                                            readonly>
                                        @error('officer_designation')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-12 text-center">
                            @if ($modal_flag)
                                <button type="submit" class="btn btn-primary" id="update-btn">
                                    <span wire:loading.remove wire:target="update">
                                        <i class="bx bx-check me-1"></i>Update Register
                                    </span>
                                    <span wire:loading wire:target="update">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"
                                            aria-hidden="true"></span>
                                        Updating...
                                    </span>
                                </button>
                                <button wire:click="exit" type="button" class="btn btn-label-secondary"
                                    data-bs-dismiss="modal" aria-label="Close">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </button>
                            @else
                                <button type="submit" class="btn btn-primary" id="record-btn">
                                    <span wire:loading.remove wire:target="store">
                                        <i class="bx bx-plus me-1"></i>Register Postnatal Visit
                                    </span>
                                    <span wire:loading wire:target="store">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"
                                            aria-hidden="true"></span>
                                        Saving...
                                    </span>
                                </button>
                                <button wire:click="exit" type="button" class="btn btn-label-secondary"
                                    data-bs-dismiss="modal" aria-label="Close">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--/ Postnatal Modal -->

    @endif

    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('open-main-modal', () => {
                    const mainModal = document.getElementById('postNatalModal');
                    const inst = bootstrap.Modal.getInstance(mainModal) || new bootstrap.Modal(mainModal);
                    inst.show();
                });

                Livewire.on('close-modals', () => {
                    const mainModal = bootstrap.Modal.getInstance(document.getElementById('postNatalModal'));
                    if (mainModal) mainModal.hide();
                });
            });
        </script>
    @endpush

    @include('_partials.datatables-init')
</div>





