@php
    use App\Services\Security\RolePermissionService;
    use Carbon\Carbon;
    $authUser = auth()->user();
    $canManageSections = RolePermissionService::can($authUser, 'core.sections.manage');
@endphp

@section('title', 'Facility Sections')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Facility Sections</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-layer me-1"></i>Facility Ward/Section Management</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Manage sections once, then Bed Management uses them.</div>
            </div>
            @if ($canManageSections)
                <button wire:click="openCreateModal" type="button" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="openCreateModal,saveSection">
                    <span wire:loading.remove wire:target="openCreateModal,saveSection"><i class="bx bx-plus me-1"></i>Add
                        Section</span>
                    <span wire:loading wire:target="openCreateModal,saveSection"><span
                            class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
            @else
                <span class="badge bg-label-secondary">View Only</span>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Total</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Active</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                            <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['active'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
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
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">With Beds</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 14h16v4H4z" stroke="currentColor" stroke-width="1.8" />
                            <path d="M7 14V9h10v5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['with_beds'] }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="dataTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Section Name</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Beds</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sections as $section)
                        <tr wire:key="facility-section-row-{{ $section->id }}">
                            <td class="fw-semibold">{{ $section->name }}</td>
                            <td>{{ $section->details ?: 'N/A' }}</td>
                            <td>
                                <span class="badge bg-label-{{ $section->is_active ? 'success' : 'secondary' }}">
                                    {{ $section->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $section->beds_count }}</td>
                            <td>
                                @if ($canManageSections)
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="icon-base ti tabler-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript:void(0)"
                                                wire:click="openEditModal({{ $section->id }})">
                                                <i class="icon-base ti tabler-edit me-1"></i>Edit
                                            </a>
                                            <a class="dropdown-item" href="javascript:void(0)"
                                                wire:click="toggleStatus({{ $section->id }})">
                                                <i
                                                    class="icon-base ti tabler-power me-1"></i>{{ $section->is_active ? 'Deactivate' : 'Activate' }}
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="javascript:void(0)"
                                                wire:click="deleteSection({{ $section->id }})"
                                                wire:confirm="Delete this section? Beds must not be linked to it.">
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

    <div wire:ignore.self class="modal fade" id="facilitySectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <button wire:click="exit" type="button" class="btn-close" aria-label="Close"></button>
                    <div class="text-center mb-4">
                        <h4 class="mb-2">{{ $edit_mode ? 'Edit Section' : 'Add Section' }}</h4>
                        <p class="text-muted">Create and maintain sections for bed allocation.</p>
                    </div>

                    <form onsubmit="return false">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Section Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model.live="name"
                                    placeholder="Maternity Ward">
                                @error('name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" id="facility_section_active"
                                        wire:model.live="is_active">
                                    <label class="form-check-label" for="facility_section_active">Active section</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Details</label>
                                <textarea class="form-control" rows="3" wire:model.live="details"
                                    placeholder="Optional description"></textarea>
                                @error('details')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 text-center mt-4">
                            @if ($canManageSections)
                                <button wire:click="saveSection" type="button" class="btn btn-primary"
                                    wire:loading.attr="disabled" wire:target="saveSection">
                                    <span wire:loading.remove wire:target="saveSection">{{ $edit_mode ? 'Update Section' : 'Save Section' }}</span>
                                    <span wire:loading wire:target="saveSection"><span
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
            const modalEl = document.getElementById('facilitySectionModal');
            let modalInstance = null;

            const getModal = () => {
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(modalEl);
                }
                return modalInstance;
            };

            const cleanupModalArtifacts = () => {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
            };

            Livewire.on('open-facility-section-modal', () => {
                getModal().show();
            });

            Livewire.on('close-facility-section-modal', () => {
                if (modalInstance) {
                    modalInstance.hide();
                }
            });

            modalEl.addEventListener('hidden.bs.modal', function() {
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
