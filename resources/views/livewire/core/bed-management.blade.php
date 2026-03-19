@php
    use App\Services\Security\RolePermissionService;
    use Carbon\Carbon;
    $authUser = auth()->user();
    $canManageBeds = RolePermissionService::can($authUser, 'core.beds.manage');
@endphp

@section('title', 'Bed Management')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Bed Management</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-bed me-1"></i>Facility Bed Management</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
            </div>
            @if ($canManageBeds)
                <button wire:click="openCreateModal" type="button" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="openCreateModal,saveBed">
                    <span wire:loading.remove wire:target="openCreateModal,saveBed"><i class="bx bx-plus me-1"></i>Add
                        Bed</span>
                    <span wire:loading wire:target="openCreateModal,saveBed"><span
                            class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
            @else
                <span class="badge bg-label-secondary">View Only</span>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Total</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 12h16M4 16h16M6 8h12" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Available</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                            <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['available'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Occupied</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 14h16v4H4z" stroke="currentColor" stroke-width="1.8" />
                            <path d="M7 14V9h10v5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['occupied'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-amber h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Maintenance</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M8 6l3 3-5 5-3-3zM13 11l5 5" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['maintenance'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-rose h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Inactive</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                            <path d="M8 8l8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['inactive'] }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="dataTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Bed Code</th>
                        <th>Section / Room</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Occupant</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($beds as $bed)
                        @php
                            $statusClass = match ($bed->status) {
                                'available' => 'success',
                                'occupied' => 'primary',
                                'maintenance' => 'warning',
                                default => 'secondary',
                            };
                        @endphp
                        <tr wire:key="bed-row-{{ $bed->id }}">
                            <td class="fw-semibold">{{ $bed->bed_code }}</td>
                            <td>
                                <div>{{ $bed->section?->name ?? $bed->ward_section }}</div>
                                <small class="text-muted">Room: {{ $bed->room_label ?: 'N/A' }}</small>
                            </td>
                            <td>{{ ucfirst($bed->bed_type) }}</td>
                            <td>
                                <span class="badge bg-label-{{ $statusClass }}">{{ ucfirst($bed->status) }}</span>
                                @if (!$bed->is_active)
                                    <span class="badge bg-label-dark ms-1">Disabled</span>
                                @endif
                            </td>
                            <td>
                                @if ($bed->occupiedByPatient)
                                    {{ trim(($bed->occupiedByPatient->first_name ?? '') . ' ' . ($bed->occupiedByPatient->last_name ?? '')) }}
                                    <br><small class="text-muted">DIN: {{ $bed->occupiedByPatient->din ?: 'N/A' }}</small>
                                @else
                                    <span class="text-muted">Unassigned</span>
                                @endif
                            </td>
                            <td>{{ $bed->updated_at?->format('M d, Y h:i A') }}</td>
                            <td>
                                @if ($canManageBeds)
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="icon-base ti tabler-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript:void(0)"
                                                wire:click="openEditModal({{ $bed->id }})">
                                                <i class="icon-base ti tabler-edit me-1"></i>Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript:void(0)"
                                                wire:click="setStatus({{ $bed->id }}, 'available')">
                                                <i class="icon-base ti tabler-check me-1"></i>Mark Available
                                            </a>
                                            <a class="dropdown-item" href="javascript:void(0)"
                                                wire:click="setStatus({{ $bed->id }}, 'occupied')">
                                                <i class="icon-base ti tabler-bed me-1"></i>Mark Occupied
                                            </a>
                                            <a class="dropdown-item" href="javascript:void(0)"
                                                wire:click="setStatus({{ $bed->id }}, 'maintenance')">
                                                <i class="icon-base ti tabler-tool me-1"></i>Mark Maintenance
                                            </a>
                                            <a class="dropdown-item" href="javascript:void(0)"
                                                wire:click="toggleActive({{ $bed->id }})">
                                                <i class="icon-base ti tabler-power me-1"></i>{{ $bed->is_active ? 'Deactivate' : 'Activate' }}
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="javascript:void(0)"
                                                wire:click="deleteBed({{ $bed->id }})"
                                                wire:confirm="Delete this bed? This action cannot be undone.">
                                                <i class="icon-base ti tabler-trash me-1"></i>Delete
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <span class="badge bg-label-secondary">View Only</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="bedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <button wire:click="exit" type="button" class="btn-close" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h4 class="mb-2">{{ $edit_mode ? 'Edit Bed' : 'Add Bed' }}</h4>
                        <p class="text-muted">Leave Bed Code blank to auto-generate.</p>
                    </div>

                    <form onsubmit="return false">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Bed Code (Optional)</label>
                                <input type="text" class="form-control" wire:model.live="bed_code"
                                    placeholder="BED-0001 (auto-generated if blank)">
                                @error('bed_code')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ward / Section <span class="text-danger">*</span></label>
                                <select class="form-select" wire:model.live="bed_section_id">
                                    <option value="">Select section...</option>
                                    @foreach ($sectionOptions as $sectionOption)
                                        <option value="{{ $sectionOption->id }}">{{ $sectionOption->name }}</option>
                                    @endforeach
                                </select>
                                @error('bed_section_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Room</label>
                                <input type="text" class="form-control" wire:model.live="room_label"
                                    placeholder="Room A">
                                @error('room_label')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bed Type <span class="text-danger">*</span></label>
                                <select class="form-select" wire:model.live="bed_type">
                                    @foreach ($bedTypes as $type)
                                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                                @error('bed_type')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" wire:model.live="status">
                                    @foreach ($bedStatuses as $statusOption)
                                        <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" id="bed_is_active"
                                        wire:model.live="is_active">
                                    <label class="form-check-label" for="bed_is_active">Active bed</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" rows="3" wire:model.live="notes"
                                    placeholder="Optional notes for this bed"></textarea>
                                @error('notes')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 text-center mt-4">
                            @if ($canManageBeds)
                                <button wire:click="saveBed" type="button" class="btn btn-primary"
                                    wire:loading.attr="disabled" wire:target="saveBed">
                                    <span wire:loading.remove wire:target="saveBed">{{ $edit_mode ? 'Update Bed' : 'Save Bed' }}</span>
                                    <span wire:loading wire:target="saveBed"><span
                                            class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                                </button>
                            @endif
                            <button wire:click="exit" type="button" class="btn btn-label-secondary" aria-label="Close">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bedModalEl = document.getElementById('bedModal');
            let bedModalInstance = null;

            const getBedModal = () => {
                if (!bedModalInstance) {
                    bedModalInstance = new bootstrap.Modal(bedModalEl);
                }
                return bedModalInstance;
            };

            const cleanupModalArtifacts = () => {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
            };

            Livewire.on('open-bed-modal', () => {
                getBedModal().show();
            });

            Livewire.on('close-bed-modal', () => {
                if (bedModalInstance) {
                    bedModalInstance.hide();
                }
            });

            bedModalEl.addEventListener('hidden.bs.modal', function() {
                @this.call('resetModalState');
                cleanupModalArtifacts();
                setTimeout(() => window.location.reload(), 150);
            });
        });
    </script>

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

        .metric-card-amber {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .form-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: #64748b;
        }
    </style>

    @include('_partials.datatables-init')
</div>
