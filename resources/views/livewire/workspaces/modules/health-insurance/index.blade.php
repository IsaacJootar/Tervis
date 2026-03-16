@php
    use Carbon\Carbon;
@endphp

@section('title', 'Health Insurance')

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
        <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Health Insurance</span></div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                    style="width:64px;height:64px;font-weight:700;">
                    {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class="bx bx-shield-plus me-1"></i>Patient Insurance Management</h4>
                    <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                        <span class="badge bg-label-secondary">Patient: {{ $first_name }} {{ $last_name }}</span>
                        <span class="badge bg-label-info">Phone: {{ $patient_phone ?: 'N/A' }}</span>
                        <span class="badge bg-label-{{ $is_nhis_subscriber ? 'success' : 'warning' }}">
                            {{ $is_nhis_subscriber ? 'NHIS Active' : 'NHIS Inactive' }}
                        </span>
                    </div>
                </div>
                <button wire:click="backToDashboard" type="button" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="backToDashboard">
                    <span wire:loading.remove wire:target="backToDashboard"><i class="bx bx-arrow-back me-1"></i>Back to
                        Workspace</span>
                    <span wire:loading wire:target="backToDashboard"><span
                            class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-slate h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Total Changes</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M5 7h14M5 12h14M5 17h9" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $summary['total_changes'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-emerald h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Activations</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 3l7 3v5c0 4.2-2.7 8-7 10-4.3-2-7-5.8-7-10V6l7-3z"
                                    stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $summary['activations'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-sky h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Updates</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M5 19l3.5-.8L19 7.7 16.3 5 5.8 15.5 5 19z" stroke="currentColor"
                                    stroke-width="1.8" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $summary['updates'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-rose h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Deactivations</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 3l7 3v5c0 4.2-2.7 8-7 10-4.3-2-7-5.8-7-10V6l7-3z"
                                    stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                <path d="M9 9l6 6M15 9l-6 6" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $summary['deactivations'] }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Insurance Profile</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="insurance_subscriber_toggle"
                                wire:model.live="is_nhis_subscriber">
                            <label class="form-check-label" for="insurance_subscriber_toggle">
                                Is NHIS Subscriber?
                            </label>
                        </div>
                    </div>
                </div>

                @if ($is_nhis_subscriber)
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NHIS Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="nhis_number"
                                placeholder="Enter NHIS number">
                            @error('nhis_number')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NHIS Provider <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="nhis_provider"
                                placeholder="Enter NHIS provider">
                            @error('nhis_provider')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NHIS Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" wire:model="nhis_expiry_date">
                            @error('nhis_expiry_date')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NHIS Plan Type <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="nhis_plan_type">
                                <option value="">--Select Plan Type--</option>
                                <option value="Individual">Individual</option>
                                <option value="Family">Family</option>
                                <option value="Corporate">Corporate</option>
                            </select>
                            @error('nhis_plan_type')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        @if (in_array($nhis_plan_type, ['Family', 'Corporate']))
                            <div class="col-md-6">
                                <label class="form-label">Principal Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="nhis_principal_name"
                                    placeholder="Enter principal name">
                                @error('nhis_principal_name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Principal Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="nhis_principal_number"
                                    placeholder="Enter principal number">
                                @error('nhis_principal_number')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        @endif
                    </div>
                @endif

                <div class="d-flex justify-content-end gap-2 mt-4">
                    @if ($is_nhis_subscriber)
                        <button type="button" class="btn btn-outline-danger" wire:click="deactivateCoverage"
                            wire:loading.attr="disabled" wire:target="deactivateCoverage,saveInsurance">
                            <span wire:loading.remove wire:target="deactivateCoverage,saveInsurance">Deactivate</span>
                            <span wire:loading wire:target="deactivateCoverage,saveInsurance"><span
                                    class="spinner-border spinner-border-sm me-1"></span>Processing...</span>
                        </button>
                    @endif
                    <button type="button" class="btn btn-primary" wire:click="saveInsurance"
                        wire:loading.attr="disabled" wire:target="saveInsurance,deactivateCoverage">
                        <span wire:loading.remove wire:target="saveInsurance,deactivateCoverage">Save Insurance</span>
                        <span wire:loading wire:target="saveInsurance,deactivateCoverage"><span
                                class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Insurance Change History <small class="text-muted">({{ $history->total() }}
                        Total)</small></h5>
            </div>
            <div class="card-datatable table-responsive pt-0">
                <table class="table align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Provider / Plan</th>
                            <th>Performed By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($history as $entry)
                            @php
                                $statusClass = match ($entry->action) {
                                    'activate' => 'success',
                                    'deactivate' => 'danger',
                                    default => 'info',
                                };
                                $after = $entry->meta['after'] ?? [];
                            @endphp
                            <tr wire:key="insurance-history-{{ $entry->id }}">
                                <td>{{ $entry->created_at?->format('M d, Y h:i A') ?: 'N/A' }}</td>
                                <td><span class="badge bg-label-{{ $statusClass }}">{{ ucfirst($entry->action) }}</span></td>
                                <td>
                                    <div>{{ $after['nhis_provider'] ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $after['nhis_plan_type'] ?? 'N/A' }}</small>
                                </td>
                                <td>{{ $entry->performed_by ?: 'N/A' }}</td>
                                <td>{{ $entry->description }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No insurance updates logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">Page {{ $history->currentPage() }} of {{ $history->lastPage() }} | Total
                    {{ $history->total() }}</small>
                <div>{{ $history->links() }}</div>
            </div>
        </div>
    @endif

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
    </style>
</div>
