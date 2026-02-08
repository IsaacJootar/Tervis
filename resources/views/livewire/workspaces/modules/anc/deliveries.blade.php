@php
    use Carbon\Carbon;
@endphp
@section('title', 'Delivery - ANC Workspace')
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
            <span class="badge bg-label-primary text-uppercase">Deliveries</span>
        </div>

        <div class="card mb-4 tt-hero">
            <div class="tt-hero-cover"></div>
            <div class="card-body tt-hero-body">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="tt-avatar">
                        {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-1">ANC Deliveries</h4>
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
                            <div class="fw-semibold">{{ count($deliveries) }}</div>
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
                                <span class="text-muted">Total Deliveries</span>
                                <span class="fw-semibold">{{ count($deliveries) }}</span>
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
                                <h5 class="mb-0">Delivery Records</h5>
                                <small class="text-muted">{{ count($deliveries) }} Total</small>
                            </div>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#deliveryModal">
                                <i class="bx bx-plus me-1"></i>Record Delivery
                            </button>
                        </div>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="dataTable" class="table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Patient DIN</th>
                                    <th>Patient Name</th>
                                    <th>State</th>
                                    <th>Date of Delivery</th>
                                    <th>Mode of Delivery</th>
                                    <th>Baby Sex</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($deliveries as $delivery)
                                    <tr wire:key="{{ $delivery->id }}">
                                        <td>
                                            <span class="badge bg-label-info">{{ $delivery->patient->din ?? 'N/A' }}</span>
                                        </td>
                                        <td>{{ $delivery->patient->first_name . ' ' . $delivery->patient->last_name ?? 'N/A' }}</td>
                                        <td>
                                            <i class="bx bx-map me-1"></i>
                                            {{ $delivery->state->name ?? 'N/A' }}
                                        </td>
                                        <td>{{ $delivery->formatted_delivery_date ?? 'N/A' }}</td>
                                        <td>{{ $delivery->mod ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-label-{{ $delivery->baby_sex_badge_color }}">
                                                {{ $delivery->baby_sex ?? 'N/A' }}
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
                                                        data-bs-toggle="modal" data-bs-target="#deliveryModal"
                                                        wire:click="edit({{ $delivery->id }})">
                                                        <i class="icon-base ti tabler-pencil me-1"></i> Edit
                                                    </a>
                                                    <a class="dropdown-item" href="javascript:void(0)"
                                                        wire:click="delete({{ $delivery->id }})">
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

    
    <!-- Delivery Modal -->
    <div wire:ignore.self class="modal fade" id="deliveryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-body">
                    <button wire:click='exit' type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close" onclick="setTimeout(() => location.reload(), 300)"></button>
                    <div class="text-center mb-4">
                        <h4 class="mb-2">
                            {{ $modal_flag ? 'Edit Delivery Registration' : 'Delivery Registration' }}
                        </h4>
                        <p class="text-muted"><span class="badge bg-info">Delivery Record Tracking</span></p>
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

                                    <div class="col-md-6">
                                        <label class="form-label">Age Range <span class="text-danger">*</span></label>
                                        <select wire:model='cl_sex' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="11 - 14 years">11 - 14 years</option>
                                            <option value="15 - 19 years">15 - 19 years</option>
                                            <option value="20 - 24 years">20 - 24 years</option>
                                            <option value="25 - 29 years">25 - 29 years</option>
                                            <option value="30 - 34 years">30 - 34 years</option>
                                            <option value="35 - 49 years">35 - 49 years</option>
                                            <option value="50 + years">50 + years</option>
                                        </select>
                                        @error('cl_sex')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Patient Phone</label>
                                        <input wire:model='cl_phone' type="text" class="form-control"
                                            placeholder="Enter phone number">
                                        @error('cl_phone')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-info">Delivery
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Date of Delivery <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='dodel' type="date" class="form-control">
                                        @error('dodel')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Time of Delivery <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='time_of_delivery' type="time" class="form-control">
                                        @error('time_of_delivery')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Type of Patient <span
                                                class="text-danger">*</span></label>
                                        <select wire:model='toc' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Booked">Booked</option>
                                            <option value="Unbooked">Unbooked</option>
                                        </select>
                                        @error('toc')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mode of Delivery <span
                                                class="text-danger">*</span></label>
                                        <select wire:model='mod' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="SVD">Spontaneous Vaginal Delivery</option>
                                            <option value="CS">Cesarean Section</option>
                                            <option value="AD">Assisted Delivery</option>
                                        </select>
                                        @error('mod')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Seeking Care Within <span
                                                class="text-danger">*</span></label>
                                        <select wire:model='seeking_care' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="less24">Less than 24 hours</option>
                                            <option value="more24">More than 24 hours</option>
                                        </select>
                                        @error('seeking_care')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Transportation <span
                                                class="text-danger">*</span></label>
                                        <select wire:model='transportation' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="vehicle">Vehicle</option>
                                            <option value="ambulance">Ambulance</option>
                                            <option value="others">Others</option>
                                        </select>
                                        @error('transportation')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Parity</label>
                                        <input wire:model='parity' type="text" class="form-control"
                                            placeholder="Enter parity">
                                        @error('parity')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mother Transportation <span
                                                class="text-danger">*</span></label>
                                        <select wire:model='mother_transportation' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="vehicle">Vehicle</option>
                                            <option value="ambulance">Ambulance</option>
                                            <option value="others">Others</option>
                                        </select>
                                        @error('mother_transportation')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Medical Interventions -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-warning">Medical
                                        Interventions</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Partograph Used <span
                                                class="text-danger">*</span></label>
                                        <select wire:model='partograph' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('partograph')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Oxytocin Used <span
                                                class="text-danger">*</span></label>
                                        <select wire:model='oxytocin' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('oxytocin')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Misoprostol Used <span
                                                class="text-danger">*</span></label>
                                        <select wire:model='misoprostol' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('misoprostol')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Baby Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-success">Baby
                                        Information</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Sex of Baby <span
                                                class="text-danger">*</span></label>
                                        <select wire:model='baby_sex' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                        @error('baby_sex')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Live Birth Weight (kg) <span
                                                class="text-danger">*</span></label>
                                        <input wire:model='weight' type="number" class="form-control"
                                            placeholder="e.g., 3.5" step="0.1" min="0.5" max="6">
                                        @error('weight')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Still Birth</label>
                                        <select wire:model='still_birth' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="fresh">Fresh</option>
                                            <option value="macerated">Macerated</option>
                                        </select>
                                        @error('still_birth')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Pre Term</label>
                                        <select wire:model='pre_term' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('pre_term')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Not Breathing/Not Crying at Birth</label>
                                        <select wire:model='breathing' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('breathing')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Temperature at 2 Hours (°C)</label>
                                        <input wire:model='temperature' type="number" class="form-control"
                                            placeholder="e.g., 36.5" step="0.1" min="30" max="40">
                                        @error('temperature')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Newborn Care -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-dark">Newborn Care</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Immediate Newborn Care Provided</label>
                                        <select wire:model='newborn_care' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('newborn_care')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Time Cord Was Clamped</label>
                                        <select wire:model='clamped' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="immediate">Immediate</option>
                                            <option value="delayed">Delayed (1-3 minutes)</option>
                                        </select>
                                        @error('clamped')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">4% CKX Gel Applied to Cord</label>
                                        <select wire:model='CKX_gel' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('CKX_gel')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Baby Put to Breast</label>
                                        <select wire:model='breast' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('breast')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Counseling & Education -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-primary">Counseling &
                                        Education</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Exclusive Breastfeeding <span
                                                class="text-danger">*</span></label>
                                        <select wire:model='breastfeeding' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="counseled">Counseled</option>
                                            <option value="accepted">Accepted</option>
                                        </select>
                                        @error('breastfeeding')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Postpartum Family Planning <span
                                                class="text-danger">*</span></label>
                                        <select wire:model='postpartum' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="counseled">Counseled</option>
                                            <option value="accepted">Accepted</option>
                                        </select>
                                        @error('postpartum')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mother Outcomes -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-warning">Mother
                                        Outcomes</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Alive</label>
                                        <select wire:model='alive' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('alive')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Admitted</label>
                                        <select wire:model='admitted' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('admitted')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Discharged</label>
                                        <select wire:model='discharged' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('discharged')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Referred Out</label>
                                        <select wire:model='referred_out' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('referred_out')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">PAC</label>
                                        <select wire:model='pac' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('pac')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Dead</label>
                                        <select wire:model='dead' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('dead')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Abortion</label>
                                        <select wire:model='abortion' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('abortion')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Baby Outcomes -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-info">Baby Outcomes</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Baby Dead</label>
                                        <select wire:model='baby_dead' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('baby_dead')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Live Births (HIV Positive Woman Only)</label>
                                        <select wire:model='live_births' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                            <option value="N/A">N/A</option>
                                        </select>
                                        @error('live_births')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Personnel -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-secondary">Delivery
                                        Personnel</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Who Took Delivery</label>
                                        <input wire:model='took_delivery' type="text" class="form-control"
                                            placeholder="Enter name">
                                        @error('took_delivery')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Professional Category</label>
                                        <select wire:model='doctor' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Doctor">Doctor</option>
                                            <option value="Midwife">Midwife</option>
                                            <option value="Nurse">Nurse</option>
                                            <option value="MLSS">MLSS</option>
                                            <option value="Trained CHEW">Trained CHEW</option>
                                            <option value="Others">Others</option>
                                        </select>
                                        @error('doctor')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Name of Person Who Took Delivery</label>
                                        <input wire:model='took_del' type="text" class="form-control"
                                            placeholder="Enter full name">
                                        @error('took_del')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- MDA Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><span class="badge text-bg-dark">MDA Information</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">MDA Conducted</label>
                                        <select wire:model='MDA_conducted' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('MDA_conducted')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">MDA Not Conducted</label>
                                        <select wire:model='MDA_not_conducted' class="form-select">
                                            <option value="">Select...</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        @error('MDA_not_conducted')
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
                                        <i class="bx bx-plus me-1"></i>Register Delivery
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
    <!--/ Delivery Modal -->
    @endif

@push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('open-main-modal', () => {
                    const mainModal = document.getElementById('deliveryModal');
                    const inst = bootstrap.Modal.getInstance(mainModal) || new bootstrap.Modal(mainModal);
                    inst.show();
                });

                Livewire.on('close-modals', () => {
                    const mainModal = bootstrap.Modal.getInstance(document.getElementById('deliveryModal'));
                    if (mainModal) mainModal.hide();
                });
            });
        </script>
    @endpush

    @include('_partials.datatables-init')
</div>




