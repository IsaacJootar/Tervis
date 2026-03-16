@php
    use Carbon\Carbon;
@endphp

@section('title', 'Reminders & Alerts')

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
        <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Reminders & Alerts</span></div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                    style="width:64px;height:64px;font-weight:700;">
                    {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class="bx bx-bell me-1"></i>Patient Reminder Collation</h4>
                    <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                        <span class="badge bg-label-secondary">Patient: {{ $first_name }} {{ $last_name }}</span>
                        <span class="badge bg-label-info">Phone: {{ $patient_phone ?: 'N/A' }}</span>
                        <span class="badge bg-label-warning">Email: {{ $patient_email ?: 'N/A' }}</span>
                    </div>
                </div>
                <button wire:click="backToDashboard" type="button" class="btn btn-primary" wire:loading.attr="disabled" wire:target="backToDashboard">
                    <span wire:loading.remove wire:target="backToDashboard"><i class="bx bx-arrow-back me-1"></i>Back to Workspace</span>
                    <span wire:loading wire:target="backToDashboard"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-slate h-100">
                    <div class="metric-label">Total</div>
                    <div class="metric-value">{{ $summary['total'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-sky h-100">
                    <div class="metric-label">Pending</div>
                    <div class="metric-value">{{ $summary['pending'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-emerald h-100">
                    <div class="metric-label">Sent</div>
                    <div class="metric-value">{{ $summary['sent'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-rose h-100">
                    <div class="metric-label">Failed</div>
                    <div class="metric-value">{{ $summary['failed'] }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="small text-muted">
                    Dispatch is handled by the central reminders hub/scheduler. This page is a patient-level collation view.
                </div>
                <button type="button" class="btn btn-outline-primary" wire:click="syncFromModules" wire:loading.attr="disabled" wire:target="syncFromModules">
                    <span wire:loading.remove wire:target="syncFromModules"><i class="bx bx-refresh me-1"></i>Refresh Collation</span>
                    <span wire:loading wire:target="syncFromModules"><span class="spinner-border spinner-border-sm me-1"></span>Syncing...</span>
                </button>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Reminders <small class="text-muted">({{ $reminders->total() }} Total)</small></h5></div>
            <div class="card-datatable table-responsive pt-0">
                <table class="table align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Source</th>
                            <th>Channels</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reminders as $reminder)
                            @php
                                $statusClass = match ($reminder->status) {
                                    'sent' => 'success',
                                    'failed' => 'danger',
                                    'canceled' => 'secondary',
                                    'queued' => 'warning',
                                    default => 'primary',
                                };
                            @endphp
                            <tr wire:key="reminder-row-{{ $reminder->id }}">
                                <td>
                                    {{ $reminder->reminder_date?->format('M d, Y') ?: 'N/A' }}
                                    @if ($reminder->reminder_time)
                                        <br><small class="text-muted">{{ $reminder->reminder_time }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $reminder->title }}</span>
                                    <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($reminder->message, 90) }}</small>
                                </td>
                                <td>{{ $reminder->source_module ? ucwords(str_replace('_', ' ', $reminder->source_module)) : 'Manual' }}</td>
                                <td>{{ strtoupper(implode(', ', (array) ($reminder->channels ?? []))) ?: 'N/A' }}</td>
                                <td><span class="badge bg-label-{{ $statusClass }}">{{ ucfirst($reminder->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No reminders found from linked module dates yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">Page {{ $reminders->currentPage() }} of {{ $reminders->lastPage() }} | Total {{ $reminders->total() }}</small>
                <div>{{ $reminders->links() }}</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Dispatch Log <small class="text-muted">({{ $dispatchLogs->total() }} Total)</small></h5></div>
            <div class="card-datatable table-responsive pt-0">
                <table class="table align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Time</th>
                            <th>Reminder</th>
                            <th>Channel</th>
                            <th>Recipient</th>
                            <th>Status</th>
                            <th>Provider Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dispatchLogs as $log)
                            @php
                                $statusClass = match ($log->status) {
                                    'sent' => 'success',
                                    'failed' => 'danger',
                                    'skipped' => 'secondary',
                                    default => 'warning',
                                };
                            @endphp
                            <tr wire:key="dispatch-log-{{ $log->id }}">
                                <td>{{ $log->created_at?->format('M d, Y h:i A') ?: 'N/A' }}</td>
                                <td>#{{ $log->reminder_id }}</td>
                                <td>{{ strtoupper($log->channel) }}</td>
                                <td>{{ $log->recipient ?: 'N/A' }}</td>
                                <td><span class="badge bg-label-{{ $statusClass }}">{{ ucfirst($log->status) }}</span></td>
                                <td>{{ $log->provider_message ?: 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No dispatch logs yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">Page {{ $dispatchLogs->currentPage() }} of {{ $dispatchLogs->lastPage() }} | Total {{ $dispatchLogs->total() }}</small>
                <div>{{ $dispatchLogs->links() }}</div>
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
</div>




