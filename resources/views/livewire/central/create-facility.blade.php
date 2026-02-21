@php use Illuminate\Support\Str; @endphp
@section('title', 'Manage Facilities')

<div x-data="{ state: @entangle('state').live, lga: @entangle('lga').live, ward: @entangle('ward').live }">

    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 32px;">
                            <i class='bx bx-buildings me-2'></i>
                            Manage Facilities
                        </h4>
                        <p class="hero-subtitle">{{ \Carbon\Carbon::today()->format('l, F j, Y') }}</p>
                        <div class="hero-stats">
                            <span class="hero-stat">
                                <i class="bx bx-buildings"></i>
                                {{ count($facilities) }} Total Facilities
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-map"></i>
                                {{ $facilities->pluck('state')->unique()->count() }} States Covered
                            </span>
                            <span class="hero-stat">
                                <i class="bx bx-location-plus"></i>
                                {{ $facilities->pluck('lga')->unique()->count() }} LGAs Covered
                            </span>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-light btn-lg rounded-pill shadow-sm d-inline-flex align-items-center"
                                style="border: 1px solid #ddd; padding: 12px 24px;" data-bs-toggle="modal"
                                data-bs-target="#facilityModal" type="button" title="Create New Facility">
                                <i class="bx bx-buildings me-2" style="font-size: 20px;"></i>
                                Create New Facility
                            </button>
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

    <!-- DataTable with Buttons -->
    <div class="card">
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="dataTable" class="table">
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
                            <td>{{ $facility->name }}</td>
                            <td>{{ $facility->state }}</td>
                            <td>{{ $facility->lga }}</td>
                            <td>{{ $facility->ward }}</td>
                            <td>{{ Str::limit($facility->address, 30) }}</td>
                            <td>{{ $facility->phone }}</td>
                            <td>{{ $facility->email ?? 'N/A' }}</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal"
                                            data-bs-target="#facilityModal" wire:click="edit({{ $facility->id }})">
                                            <i class="icon-base ti tabler-pencil me-1"></i> Edit
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                            wire:click="delete({{ $facility->id }})">
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

    <!-- Facility Registration Modal -->
    <div wire:ignore.self class="modal fade" id="facilityModal" tabindex="-1" aria-labelledby="facilityModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-simple modal-add-new-cc">
            <div class="modal-content">
                <div class="modal-body">
                    <button wire:click="exit" type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                    <div class="row">
                        <div class="col-12">
                            <h5 class="modal-heading mb-0">
                                <i class="bx bx-buildings me-2"></i>
                                @if ($modal_flag)
                                    Update Facility
                                @else
                                    Register New Facility
                                @endif
                            </h5>
                            <hr class="mt-2">
                        </div>
                    </div>
                    <form id="facilityForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Facility Name <span class="text-danger">*</span></label>
                                <input wire:model.live="name" type="text" class="form-control"
                                    placeholder="Enter facility name" required>
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <select wire:model.live="state" class="form-select" required x-model="state"
                                    :disabled="modal_flag">
                                    <option value="">Select State</option>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                                    @endforeach
                                </select>
                                @error('state')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">LGA <span class="text-danger">*</span></label>
                                <select wire:model.live="lga" class="form-select" required x-model="lga"
                                    :disabled="!state">
                                    <option value="">Select LGA</option>
                                    @foreach ($lgas as $lga)
                                        <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                    @endforeach
                                </select>
                                @error('lga')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ward <span class="text-danger">*</span></label>
                                <select wire:model.live="ward" class="form-select" required x-model="ward"
                                    :disabled="!lga">
                                    <option value="">Select Ward</option>
                                    @foreach ($wards as $ward)
                                        <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                                    @endforeach
                                </select>
                                @error('ward')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address <span class="text-danger">*</span></label>
                                <input wire:model.live="address" type="text" class="form-control"
                                    placeholder="Enter full address" required>
                                @error('address')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input wire:model.live="phone" type="text" class="form-control"
                                    placeholder="Enter phone number" required>
                                @error('phone')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Facility Email (Optional)</label>
                                <input wire:model.live="email" type="email" class="form-control"
                                    placeholder="Enter email">
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <!-- Action Buttons -->
                        <div class="col-12 text-center mt-4 pt-2">
                            @if ($modal_flag)
                                <x-app-loader />
                                <button wire:click="update" type="button" class="btn btn-primary"
                                    :disabled="!state || !lga || !ward">
                                    <i class="bx bx-check me-1"></i>Update Facility
                                </button>
                                <button wire:click="exit" type="button" class="btn btn-label-secondary"
                                    data-bs-dismiss="modal" aria-label="Close">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </button>
                            @else
                                <x-app-loader />
                                <button wire:click="store" type="button" class="btn btn-primary"
                                    :disabled="!state || !lga || !ward"
                                    x-bind:class="!state || !lga || !ward ? 'btn-secondary' : 'btn-primary'">
                                    <i class="bx bx-plus me-1"></i>Register Facility
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
    <!--/ Facility Registration Modal -->

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const facilityModal = document.getElementById('facilityModal');

        // Listen for Livewire event to close modal
        Livewire.on('close-modal', () => {
            const modal = bootstrap.Modal.getInstance(facilityModal);
            if (modal) {
                modal.hide();
            }
        });

        // Handle modal close events (both backdrop click and ESC key)
        facilityModal.addEventListener('hidden.bs.modal', function() {
            // Trigger the exit method when modal is closed by any means
            @this.call('exit');
        });

        // Additional validation before form submission
        document.addEventListener('click', function(e) {
            if (e.target.closest('[wire\\:click="store"]') || e.target.closest(
                    '[wire\\:click="update"]')) {
                const stateValue = document.querySelector('[wire\\:model\\.live="state"]').value;
                const lgaValue = document.querySelector('[wire\\:model\\.live="lga"]').value;
                const wardValue = document.querySelector('[wire\\:model\\.live="ward"]').value;

                if (!stateValue) {
                    e.preventDefault();
                    alert('Please select a state before submitting the form.');
                    return false;
                }

                if (!lgaValue) {
                    e.preventDefault();
                    alert('Please select an LGA before submitting the form.');
                    return false;
                }

                if (!wardValue) {
                    e.preventDefault();
                    alert('Please select a ward before submitting the form.');
                    return false;
                }
            }
        });
    });
</script>

@include('_partials.datatables-init')
