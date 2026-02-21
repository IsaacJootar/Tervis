@php
    use Carbon\Carbon;
@endphp

@section('title', 'Child Nutrition')

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
            <span class="badge bg-label-primary text-uppercase">Child Nutrition Register</span>
        </div>

        <div class="card mb-4 tt-hero">
            <div class="tt-hero-cover"></div>
            <div class="card-body tt-hero-body">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="tt-avatar">
                        {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-1">Child Nutrition / Growth Monitoring</h4>
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
                                <span class="text-muted">Age Group</span>
                                <span class="fw-semibold">{{ $age_group ?? 'N/A' }}</span>
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
                                <h5 class="mb-0">Nutrition Records</h5>
                                <small class="text-muted">{{ count($records) }} Total</small>
                            </div>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#nutritionModal">
                                <i class="bx bx-plus me-1"></i>Record Nutrition
                            </button>
                        </div>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="dataTable" class="table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Visit Date</th>
                                    <th>Child</th>
                                    <th>Age Group</th>
                                    <th>MUAC</th>
                                    <th>Growth</th>
                                    <th>MNP</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $record)
                                    <tr wire:key="nutrition-{{ $record->id }}">
                                        <td>{{ $record->visit_date?->format('M d, Y') }}</td>
                                        <td>{{ $record->linkedChild?->full_name ?: 'N/A' }}</td>
                                        <td>{{ $record->age_group }}</td>
                                        <td>{{ $record->muac_value_mm ?? 'N/A' }} {{ $record->muac_class ? '(' . $record->muac_class . ')' : '' }}</td>
                                        <td>{{ $record->growth_status ?? 'N/A' }}</td>
                                        <td>{{ $record->mnp_given ? 'Yes' : 'No' }}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-light text-dark border"
                                                    data-bs-toggle="modal" data-bs-target="#nutritionModal"
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
                                                No nutrition records yet.
                                            </div>
                                        </td>
                                        <td></td>
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

        <div wire:ignore.self class="modal fade" id="nutritionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-clinical-dark text-white">
                        <h5 class="modal-title text-white">
                            {{ $record_id ? 'Edit Nutrition Record' : 'Record Child Nutrition' }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="{{ $record_id ? 'update' : 'store' }}">
                            @csrf
                            <div class="card">
                                <div class="card-header bg-clinical-dark">
                                    <h6 class="mb-0 text-white">Nutrition Assessment Entry</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Visit Date</label>
                                            <input type="date" class="form-control" wire:model="visit_date">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Linked Child</label>
                                            <select class="form-select" wire:model="linked_child_id">
                                                @foreach ($linkedChildren as $child)
                                                    <option value="{{ $child->id }}">
                                                        {{ $child->full_name ?: ('Child #' . $child->linked_child_id) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Age Group</label>
                                            <input type="text" class="form-control bg-light" wire:model="age_group"
                                                readonly>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Infant Feeding (0-5 months)</label>
                                            <select class="form-select" wire:model="infant_feeding">
                                                <option value="">Select</option>
                                                <option value="Exclusive BF">Exclusive BF</option>
                                                <option value="BF + Water">BF + Water</option>
                                                <option value="BF with other foods">BF with other foods</option>
                                                <option value="Not BF">Not BF</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Complementary Feeding (6-23 months)</label>
                                            <select class="form-select" wire:model="complementary_feeding">
                                                <option value="">Select</option>
                                                <option value="BF + Other foods">BF + Other foods</option>
                                                <option value="Other foods only">Other foods only</option>
                                                <option value="Not started CF">Not started CF</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Growth Status</label>
                                            <select class="form-select" wire:model="growth_status">
                                                <option value="">Select</option>
                                                <option value="Growing Well">Growing Well</option>
                                                <option value="Not Growing Well">Not Growing Well</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Height/Length (cm)</label>
                                            <input type="number" step="0.1" class="form-control" wire:model="height_cm">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Weight (kg)</label>
                                            <input type="number" step="0.01" class="form-control" wire:model="weight_kg">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Oedema</label>
                                            <select class="form-select" wire:model="oedema">
                                                <option value="">Select</option>
                                                <option value="0">0</option>
                                                <option value="+">+</option>
                                                <option value="++">++</option>
                                                <option value="+++">+++</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">MUAC Value (mm)</label>
                                            <input type="number" class="form-control" wire:model.live="muac_value_mm">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">MUAC Class (Auto)</label>
                                            <input type="text" class="form-control bg-light" wire:model="muac_class"
                                                readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Support Group Referral</label>
                                            <select class="form-select" wire:model="support_group_referred">
                                                <option value="0">No</option>
                                                <option value="1">Yes</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label d-block">Counselling Topics</label>
                                            <div class="d-flex flex-wrap gap-3">
                                                <label><input type="checkbox" wire:model="counselling_topics"
                                                        value="Maternal Nutrition"> Maternal Nutrition</label>
                                                <label><input type="checkbox" wire:model="counselling_topics"
                                                        value="Exclusive Breastfeeding"> Exclusive Breastfeeding</label>
                                                <label><input type="checkbox" wire:model="counselling_topics"
                                                        value="Complementary Feeding"> Complementary Feeding</label>
                                                <label><input type="checkbox" wire:model="counselling_topics"
                                                        value="Water Sanitation Hygiene"> WASH</label>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label d-block">Supplementary Feeding Group</label>
                                            <div class="d-flex flex-wrap gap-3">
                                                <label><input type="checkbox" wire:model="supplementary_feeding_groups"
                                                        value="6-11 months"> 6-11 months</label>
                                                <label><input type="checkbox" wire:model="supplementary_feeding_groups"
                                                        value="12-19 months"> 12-19 months</label>
                                                <label><input type="checkbox" wire:model="supplementary_feeding_groups"
                                                        value="12-23 months"> 12-23 months</label>
                                                <label><input type="checkbox" wire:model="supplementary_feeding_groups"
                                                        value="24-59 months"> 24-59 months</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">MNP Given</label>
                                            <select class="form-select" wire:model="mnp_given">
                                                <option value="0">No</option>
                                                <option value="1">Yes</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">OTP Provider</label>
                                            <select class="form-select" wire:model="otp_provider">
                                                <option value="">Select</option>
                                                <option value="Self">Self</option>
                                                <option value="HH">HH</option>
                                                <option value="Not Providing OTP">Not Providing OTP</option>
                                                <option value="Community Volunteer/CHIPS">Community Volunteer/CHIPS
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Admission Status</label>
                                            <select class="form-select" wire:model="admission_status">
                                                <option value="">Select</option>
                                                <option value="Admitted HP OTP">Admitted HP OTP</option>
                                                <option value="Transferred in from another OTP/SC">Transferred in from
                                                    another OTP/SC</option>
                                                <option value="Referred to SC">Referred to SC</option>
                                                <option value="Does not meet OTP Admission Criteria">Does not meet OTP
                                                    Admission Criteria</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Outcome Status</label>
                                            <select class="form-select" wire:model="outcome_status">
                                                <option value="">Select</option>
                                                <option value="Transferred out to another OTP/SC">Transferred out to
                                                    another OTP/SC</option>
                                                <option value="Recovered">Recovered</option>
                                                <option value="Defaulted">Defaulted</option>
                                                <option value="Died">Died</option>
                                                <option value="Non-recovered">Non-recovered</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Remarks</label>
                                            <textarea class="form-control" rows="2" wire:model="remarks"></textarea>
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
                                            Summary Mapping: this record contributes to monthly child-health summary keys
                                            (`exclusive_breastfeeding`, `sam_admissions`, `mam_cases`, `mnp_given`,
                                            `support_group_referred`).
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
                        const modal = document.getElementById('nutritionModal');
                        const inst = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                        inst.show();
                    });

                    Livewire.on('close-modals', () => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('nutritionModal'));
                        if (modal) modal.hide();
                    });
                });
            </script>
        @endpush
    @endif

    @include('_partials.datatables-init')
</div>
