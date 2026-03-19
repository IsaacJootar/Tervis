@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;
@endphp

@section('title', 'Manage Facilities')

<div x-data="{ state: @entangle('state').live, lga: @entangle('lga').live, ward: @entangle('ward').live }">
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Central Admin</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-buildings me-1"></i>Facility Management</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Create, update, and manage tenant facilities.</div>
            </div>
            <div class="ms-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#facilityModal" type="button">
                    <i class="bx bx-plus me-1"></i>New Facility
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Total Facilities</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 19V5h14v14M9 9h2M13 9h2M9 13h2M13 13h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ count($facilities) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">States Covered</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M3 7.5 9 5l6 2.5L21 5v11.5L15 19l-6-2.5L3 19V7.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M9 5v11.5M15 7.5V19" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $facilities->pluck('state')->filter()->unique()->count() }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">LGAs Covered</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <circle cx="12" cy="11" r="2.2" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $facilities->pluck('lga')->filter()->unique()->count() }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Facility List</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="dataTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>State</th>
                        <th>LGA</th>
                        <th>Ward</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($facilities as $facility)
                        <tr>
                            <td>{{ $facility->id }}</td>
                            <td class="fw-semibold">{{ $facility->name }}</td>
                            <td>{{ $facility->state }}</td>
                            <td>{{ $facility->lga }}</td>
                            <td>{{ $facility->ward }}</td>
                            <td>{{ Str::limit($facility->address, 40) }}</td>
                            <td>{{ $facility->phone }}</td>
                            <td>{{ $facility->email ?: 'N/A' }}</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#facilityModal" wire:click="edit({{ $facility->id }})">
                                            <i class="icon-base ti tabler-pencil me-1"></i>Edit
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0)" wire:click="delete({{ $facility->id }})">
                                            <i class="icon-base ti tabler-trash me-1"></i>Delete
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

    <div wire:ignore.self class="modal fade" id="facilityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $modal_flag ? 'Update Facility' : 'Register New Facility' }}
                    </h5>
                    <button wire:click="exit" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Facility Name <span class="text-danger">*</span></label>
                            <input wire:model.live="name" type="text" class="form-control" placeholder="Enter facility name">
                            @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">State <span class="text-danger">*</span></label>
                            <select wire:model.live="state" class="form-select" x-model="state" :disabled="modal_flag">
                                <option value="">Select State</option>
                                @foreach ($states as $stateRow)
                                    <option value="{{ $stateRow->id }}">{{ $stateRow->name }}</option>
                                @endforeach
                            </select>
                            @error('state') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">LGA <span class="text-danger">*</span></label>
                            <select wire:model.live="lga" class="form-select" x-model="lga" :disabled="!state">
                                <option value="">Select LGA</option>
                                @foreach ($lgas as $lgaRow)
                                    <option value="{{ $lgaRow->id }}">{{ $lgaRow->name }}</option>
                                @endforeach
                            </select>
                            @error('lga') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ward <span class="text-danger">*</span></label>
                            <select wire:model.live="ward" class="form-select" x-model="ward" :disabled="!lga">
                                <option value="">Select Ward</option>
                                @foreach ($wards as $wardRow)
                                    <option value="{{ $wardRow->id }}">{{ $wardRow->name }}</option>
                                @endforeach
                            </select>
                            @error('ward') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input wire:model.live="address" type="text" class="form-control" placeholder="Enter address">
                            @error('address') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input wire:model.live="phone" type="text" class="form-control" placeholder="Enter phone">
                            @error('phone') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email (Optional)</label>
                            <input wire:model.live="email" type="email" class="form-control" placeholder="Enter email">
                            @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button wire:click="exit" type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    @if ($modal_flag)
                        <button wire:click="update" type="button" class="btn btn-primary" wire:loading.attr="disabled" wire:target="update">
                            <span wire:loading.remove wire:target="update">Update Facility</span>
                            <span wire:loading wire:target="update"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                        </button>
                    @else
                        <button wire:click="store" type="button" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store">
                            <span wire:loading.remove wire:target="store">Register Facility</span>
                            <span wire:loading wire:target="store"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .metric-card { border-radius: 18px; border: 1px solid rgba(148,163,184,.25); padding: 14px 16px; min-height: 108px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 10px 26px -22px rgba(15,23,42,.45); }
        .metric-label { font-size: 11px; text-transform: uppercase; letter-spacing: .14em; font-weight: 700; }
        .metric-value { margin-top: 6px; font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
        .metric-icon { width: 32px; height: 32px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: rgba(15,23,42,.08); font-size: 18px; }
        .metric-icon svg { width: 18px; height: 18px; }
        .metric-card-slate { border-color: #cbd5e1; background: #f8fafc; color: #0f172a; }
        .metric-card-sky { border-color: #bae6fd; background: #f0f9ff; color: #075985; }
        .metric-card-emerald { border-color: #a7f3d0; background: #ecfdf5; color: #065f46; }
    </style>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const facilityModal = document.getElementById('facilityModal');

        Livewire.on('close-modal', () => {
            const modal = bootstrap.Modal.getInstance(facilityModal);
            if (modal) modal.hide();
        });

        facilityModal.addEventListener('hidden.bs.modal', function() {
            @this.call('exit');
        });
    });
</script>

@include('_partials.datatables-init')
