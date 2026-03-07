@php
    use Carbon\Carbon;
@endphp

@section('title', 'Prescriptions & Drugs')

<div>
    @if (!$hasAccess)
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body text-center py-5">
                        <div class="mb-4"><i class="bx bx-error-circle text-danger" style="font-size: 5rem;"></i></div>
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
        <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Prescriptions & Drugs</span></div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width:64px;height:64px;font-weight:700;">
                    {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class='bx bx-capsule me-1'></i>Drug Dispensing Workspace</h4>
                    <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                        <span class="badge bg-label-secondary">Patient: {{ $first_name }} {{ $last_name }}</span>
                    </div>
                </div>
                <button wire:click="backToDashboard" type="button" class="btn btn-primary" wire:loading.attr="disabled" wire:target="backToDashboard">
                    <span wire:loading.remove wire:target="backToDashboard"><i class="bx bx-arrow-back me-1"></i>Back to Workspace</span>
                    <span wire:loading wire:target="backToDashboard"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-clinical-dark text-white"><h5 class="mb-0 text-white">Pending Prescriptions from Doctor Assessment</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Drug</th><th>Dose/Freq/Duration</th><th>Qty Prescribed</th><th>Instructions</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse ($pendingPrescriptions as $item)
                                <tr wire:key="pending-rx-{{ $item->id }}">
                                    <td>{{ $item->prescribed_date?->format('M d, Y') ?: 'N/A' }}</td>
                                    <td class="fw-semibold">{{ $item->drug_name }}</td>
                                    <td>{{ $item->dosage ?: '-' }} | {{ $item->frequency ?: '-' }} | {{ $item->duration ?: '-' }}</td>
                                    <td>{{ $item->quantity_prescribed ?? '-' }}</td>
                                    <td>{{ $item->instructions ?: '-' }}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-primary" wire:click="startDispense({{ $item->id }})" wire:loading.attr="disabled" wire:target="startDispense({{ $item->id }})">
                                                <span wire:loading.remove wire:target="startDispense({{ $item->id }})">Dispense</span>
                                                <span wire:loading wire:target="startDispense({{ $item->id }})"><span class="spinner-border spinner-border-sm"></span></span>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="cancelPending({{ $item->id }})" wire:loading.attr="disabled" wire:target="cancelPending({{ $item->id }})">Cancel</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No pending prescriptions.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($active_dispense_id)
            <div class="card mb-4">
                <div class="card-header bg-label-primary" style="background-color:#ffedd5 !important;color:#9a3412 !important;border-bottom:1px solid #fdba74 !important;"><h6 class="mb-0">Dispense Selected Prescription</h6></div>
                <div class="card-body">
                    <form wire:submit.prevent="dispense">
                        @csrf
                        @if ($errors->any())
                            <div class="alert alert-danger py-2">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Drug</label><input type="text" class="form-control bg-light" value="{{ $activeRecord?->drug_name }}" readonly></div>
                            <div class="col-md-2"><label class="form-label">Dispense Date</label><input type="date" class="form-control" wire:model="dispensed_date"></div>
                            <div class="col-md-2"><label class="form-label">Qty Dispensed</label><input type="number" class="form-control" min="0" step="0.1" wire:model="quantity_dispensed"></div>
                            <div class="col-md-4"><label class="form-label">Dispense Notes</label><input type="text" class="form-control" wire:model="dispense_notes" placeholder="Batch, counseling notes, etc."></div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-outline-secondary" wire:click="clearDispense">Cancel</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="dispense">
                                <span wire:loading.remove wire:target="dispense">Confirm Dispense</span>
                                <span wire:loading wire:target="dispense"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Dispensing History</h5></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark"><tr><th>Prescribed</th><th>Drug</th><th>Status</th><th>Dispensed Date</th><th>Dispensed By</th><th>Notes</th></tr></thead>
                    <tbody>
                        @forelse ($history as $item)
                            <tr wire:key="rx-history-{{ $item->id }}">
                                <td>{{ $item->prescribed_date?->format('M d, Y') ?: 'N/A' }}</td>
                                <td>{{ $item->drug_name }}</td>
                                <td><span class="badge bg-label-{{ $item->status === 'dispensed' ? 'success' : 'danger' }}">{{ ucfirst($item->status) }}</span></td>
                                <td>{{ $item->dispensed_date?->format('M d, Y') ?: '-' }}</td>
                                <td>{{ $item->dispensed_by ?: '-' }}</td>
                                <td>{{ $item->dispense_notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No dispensing history yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@once
    <style>
        .bg-clinical-dark {
            background-color: #2c3e50 !important;
        }

        .form-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: #64748b;
        }
    </style>
@endonce