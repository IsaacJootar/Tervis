@php
    use Carbon\Carbon;
@endphp

@section('title', 'Child Immunization')

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
                        <a href="{{ route('workspace-dashboard', ['patientId' => $patientId]) }}" class="btn btn-primary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Workspace
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-3">
            <span class="badge bg-label-primary text-uppercase">Child Immunization Register</span>
        </div>

        <div class="card mb-4 tt-hero">
            <div class="tt-hero-cover"></div>
            <div class="card-body tt-hero-body">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="tt-avatar">
                        {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-1">Vaccinations / Immunization</h4>
                        <div class="text-muted small">
                            {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                            <span class="badge bg-label-secondary">Mother: {{ $first_name }} {{ $last_name }}</span>
                        </div>
                    </div>
                    <div class="ms-lg-auto">
                        <button wire:click="backToDashboard" type="button"
                            class="btn btn-primary px-4 py-2 d-inline-flex align-items-center">
                            <i class="bx bx-arrow-back me-2"></i>
                            Back to Workspace
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="card h-100 tt-panel">
                    <div class="card-body">
                        <h5 class="mb-3">Child Context</h5>
                        <div class="mb-3">
                            <label class="form-label">Linked Child</label>
                            <select class="form-select" wire:model.live="linked_child_id">
                                @foreach ($linkedChildren as $child)
                                    <option value="{{ $child->id }}">
                                        {{ $child->full_name ?: ('Child #' . $child->linked_child_id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">DOB</span>
                                <span class="fw-semibold">{{ $currentChild?->formatted_date_of_birth ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Age</span>
                                <span class="fw-semibold">{{ $currentChild?->age_display ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Gender</span>
                                <span class="fw-semibold">{{ $currentChild?->gender ?? 'N/A' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Birth Cohort</span>
                                <span
                                    class="fw-semibold">{{ $currentChild?->date_of_birth?->format('M Y') ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0">Immunization Records</h5>
                                <small class="text-muted">{{ count($records) }} Total</small>
                            </div>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#immunizationModal">
                                <i class="bx bx-plus me-1"></i>Record Immunization
                            </button>
                        </div>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="dataTable" class="table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Visit Date</th>
                                    <th>Child</th>
                                    <th>Card No.</th>
                                    <th>Vaccines Given</th>
                                    <th>Last Antigen</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $record)
                                    <tr wire:key="immunization-{{ $record->id }}">
                                        <td>{{ $record->visit_date?->format('M d, Y') }}</td>
                                        <td>{{ $record->linkedChild?->full_name ?: 'N/A' }}</td>
                                        <td>{{ $record->immunization_card_no ?: 'N/A' }}</td>
                                        <td>{{ $record->given_vaccines_count }}</td>
                                        <td>{{ $record->last_antigen ?: 'N/A' }}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-light text-dark border"
                                                    data-bs-toggle="modal" data-bs-target="#immunizationModal"
                                                    wire:click="edit({{ $record->id }})">Edit</button>
                                                <button type="button" class="btn btn-sm btn-light text-dark border"
                                                    wire:click="delete({{ $record->id }})">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>
                                                No immunization records yet.
                                            </div>
                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div wire:ignore.self class="modal fade" id="immunizationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-clinical-dark text-white">
                        <h5 class="modal-title text-white">
                            {{ $record_id ? 'Edit Immunization Record' : 'Record Child Immunization' }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="{{ $record_id ? 'update' : 'store' }}">
                            @csrf
                            <div class="card">
                                <div class="card-header bg-clinical-dark">
                                    <h6 class="mb-0 text-white">Immunization Entry</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Visit Date</label>
                                            <input type="date" class="form-control" wire:model="visit_date">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label">Linked Child</label>
                                            <select class="form-select" wire:model.live="linked_child_id">
                                                @foreach ($linkedChildren as $child)
                                                    <option value="{{ $child->id }}">
                                                        {{ $child->full_name ?: ('Child #' . $child->linked_child_id) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Immunization Card No.</label>
                                            <input type="text" class="form-control" wire:model="immunization_card_no">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Child DOB</label>
                                            <input type="text" class="form-control bg-light"
                                                value="{{ $currentChild?->formatted_date_of_birth ?? 'N/A' }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Child Gender</label>
                                            <input type="text" class="form-control bg-light"
                                                value="{{ $currentChild?->gender ?? 'N/A' }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Birth Cohort</label>
                                            <input type="text" class="form-control bg-light"
                                                value="{{ $currentChild?->date_of_birth?->format('F Y') ?? 'N/A' }}" readonly>
                                        </div>

                                        <div class="col-md-8">
                                            <label class="form-label">Follow-Up Address</label>
                                            <input type="text" class="form-control" wire:model="follow_up_address">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Follow-Up Phone</label>
                                            <input type="text" class="form-control" wire:model="follow_up_phone">
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    <div class="mb-2">
                                        <h6 class="text-secondary border-bottom pb-2">
                                            <i class="bx bx-injection me-1"></i>Vaccine Dates
                                        </h6>
                                    </div>

                                    @php
                                        $groups = [
                                            'At Birth' => [['hepb0_date', 'HepB0'], ['opv0_date', 'OPV0']],
                                            '6 Weeks' => [['bcg_date', 'BCG'], ['opv1_date', 'OPV1'], ['penta1_date', 'PENTA1'], ['pcv1_date', 'PCV1'], ['rota1_date', 'ROTA1']],
                                            '10 Weeks' => [['opv2_date', 'OPV2'], ['penta2_date', 'PENTA2'], ['pcv2_date', 'PCV2'], ['rota2_date', 'ROTA2'], ['ipv1_date', 'IPV1']],
                                            '14 Weeks' => [['opv3_date', 'OPV3'], ['penta3_date', 'PENTA3'], ['pcv3_date', 'PCV3'], ['mr1_date', 'MR1'], ['yf_date', 'YF']],
                                            '9 Months' => [['mr2_date', 'MR2'], ['mena_date', 'MenA'], ['yf2_date', 'YF2'], ['slea_date', 'SLEA']],
                                            'Vitamin A' => [['vita1_date', 'VitA1'], ['vita2_date', 'VitA2']],
                                            'Other' => [['ipv2_date', 'IPV2']],
                                        ];
                                    @endphp

                                    @foreach ($groups as $groupLabel => $fields)
                                        <div class="mb-3">
                                            <span class="badge bg-label-primary">{{ $groupLabel }}</span>
                                            <div class="row g-3 mt-0">
                                                @foreach ($fields as $field)
                                                    <div class="col-md-3">
                                                        <label class="form-label">{{ $field[1] }}</label>
                                                        <input type="date" class="form-control"
                                                            wire:model="{{ $field[0] }}">
                                                        @error($field[0])
                                                            <small class="text-danger">{{ $message }}</small>
                                                        @enderror
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Comments</label>
                                            <textarea class="form-control" rows="2" wire:model="comments"></textarea>
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    <div class="mb-3">
                                        <h6 class="text-secondary border-bottom pb-2">
                                            <i class="bx bx-user-check me-1"></i>Officer Information
                                        </h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Officer Name</label>
                                            <input type="text" class="form-control bg-light"
                                                value="{{ $officer_name }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Officer Role</label>
                                            <input type="text" class="form-control bg-light"
                                                value="{{ $officer_role }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Officer Designation</label>
                                            <input type="text" class="form-control bg-light"
                                                value="{{ $officer_designation }}" readonly>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <small class="text-muted">
                                            Summary Mapping: this record contributes to monthly immunization keys
                                            (BCG/OPV/PENTA/PCV/IPV/MCV/YF/HepB0 and related totals).
                                        </small>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-4">
                                        <button type="button" class="btn btn-outline-secondary"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">
                                            <span wire:loading.remove wire:target="store,update">
                                                {{ $record_id ? 'Update Record' : 'Save Record' }}
                                            </span>
                                            <span wire:loading wire:target="store,update">
                                                <span class="spinner-border spinner-border-sm" role="status"
                                                    aria-hidden="true"></span>
                                                Processing...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
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

                .tt-panel {
                    background: #f8fafc;
                    border: 1px solid #e5e7eb;
                }

                .bg-clinical-dark {
                    background-color: #2c3e50;
                }
            </style>
        @endonce

        @push('scripts')
            <script>
                document.addEventListener('livewire:initialized', () => {
                    Livewire.on('open-main-modal', () => {
                        const modal = document.getElementById('immunizationModal');
                        const inst = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                        inst.show();
                    });

                    Livewire.on('close-modals', () => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('immunizationModal'));
                        if (modal) modal.hide();
                    });
                });
            </script>
        @endpush
    @endif

    @include('_partials.datatables-init')
</div>
