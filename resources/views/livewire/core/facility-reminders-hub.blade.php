@php
    use Carbon\Carbon;
@endphp

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Facility Reminders & Notifications Hub</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-bell me-1"></i>Reminders Hub</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Manage reminder collation and dispatch for your facility only.</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" wire:click="syncFacilitySources" wire:loading.attr="disabled" wire:target="syncFacilitySources">
                    <span wire:loading.remove wire:target="syncFacilitySources"><i class="bx bx-refresh me-1"></i>Sync Facility Sources</span>
                    <span wire:loading wire:target="syncFacilitySources"><span class="spinner-border spinner-border-sm me-1"></span>Syncing...</span>
                </button>
                <button type="button" class="btn btn-primary" wire:click="dispatchDueFacility" wire:loading.attr="disabled" wire:target="dispatchDueFacility">
                    <span wire:loading.remove wire:target="dispatchDueFacility"><i class="bx bx-send me-1"></i>Dispatch Due</span>
                    <span wire:loading wire:target="dispatchDueFacility"><span class="spinner-border spinner-border-sm me-1"></span>Dispatching...</span>
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Total</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 4a5 5 0 0 0-5 5v3l-2 3h14l-2-3V9a5 5 0 0 0-5-5z"
                                stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                            <path d="M10 18a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Pending</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                            <path d="M12 8v4l3 2" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['pending'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Sent</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 12l16-7-4 14-4-5-4-2z" stroke="currentColor" stroke-width="1.8"
                                stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['sent'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-rose h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Failed</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                            <path d="M9.5 9.5l5 5M14.5 9.5l-5 5" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['failed'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-violet h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Canceled</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                            <path d="M8.5 8.5l7 7" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['canceled'] }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="DIN, patient name, title, source...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="all">All</option>
                        <option value="pending">Pending</option>
                        <option value="queued">Queued</option>
                        <option value="sent">Sent</option>
                        <option value="failed">Failed</option>
                        <option value="canceled">Canceled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Channel</label>
                    <select class="form-select" wire:model.live="channelFilter">
                        <option value="all">All</option>
                        <option value="sms">SMS</option>
                        <option value="email">Email</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" class="form-control" wire:model.live="dateFrom">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" class="form-control" wire:model.live="dateTo">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Facility Reminder Queue <small class="text-muted">({{ $reminders->count() }} Total)</small></h5></div>
        <div class="card-datatable table-responsive pt-0">
            <table id="facilityReminderQueueTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Title</th>
                        <th>Source</th>
                        <th>Channels</th>
                        <th>Status</th>
                        <th>Action</th>
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
                        <tr wire:key="facility-reminder-{{ $reminder->id }}">
                            <td data-order="{{ $reminder->reminder_date?->format('Y-m-d') }}">{{ $reminder->reminder_date?->format('M d, Y') ?: 'N/A' }}</td>
                            <td>
                                {{ trim(($reminder->patient->first_name ?? '') . ' ' . ($reminder->patient->last_name ?? '')) ?: 'N/A' }}
                                <br><small class="text-muted">DIN: {{ $reminder->patient->din ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $reminder->title }}</span>
                                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($reminder->message, 85) }}</small>
                            </td>
                            <td>{{ $reminder->source_module ? ucwords(str_replace('_', ' ', $reminder->source_module)) : 'Manual' }}</td>
                            <td>{{ strtoupper(implode(', ', (array) ($reminder->channels ?? []))) ?: 'N/A' }}</td>
                            <td><span class="badge bg-label-{{ $statusClass }}">{{ ucfirst($reminder->status) }}</span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-light text-dark border" wire:click="dispatchSingle({{ $reminder->id }})" wire:loading.attr="disabled" wire:target="dispatchSingle({{ $reminder->id }})">
                                        <span wire:loading.remove wire:target="dispatchSingle({{ $reminder->id }})">Send</span>
                                        <span wire:loading wire:target="dispatchSingle({{ $reminder->id }})"><span class="spinner-border spinner-border-sm"></span></span>
                                    </button>
                                    @if ($reminder->status !== 'canceled')
                                        <button type="button" class="btn btn-sm btn-light text-dark border" wire:click="cancelReminder({{ $reminder->id }})" wire:loading.attr="disabled" wire:target="cancelReminder({{ $reminder->id }})">
                                            <span wire:loading.remove wire:target="cancelReminder({{ $reminder->id }})">Cancel</span>
                                            <span wire:loading wire:target="cancelReminder({{ $reminder->id }})"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-light text-dark border" wire:click="requeueReminder({{ $reminder->id }})" wire:loading.attr="disabled" wire:target="requeueReminder({{ $reminder->id }})">
                                            <span wire:loading.remove wire:target="requeueReminder({{ $reminder->id }})">Requeue</span>
                                            <span wire:loading wire:target="requeueReminder({{ $reminder->id }})"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No reminders found in this facility.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Dispatch Log <small class="text-muted">({{ $dispatchLogs->count() }} Total)</small></h5></div>
        <div class="card-datatable table-responsive pt-0">
            <table id="facilityDispatchLogTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Time</th>
                        <th>Patient</th>
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
                        <tr wire:key="facility-dispatch-log-{{ $log->id }}">
                            <td data-order="{{ $log->created_at?->format('Y-m-d H:i:s') }}">{{ $log->created_at?->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td>
                                {{ trim(($log->patient->first_name ?? '') . ' ' . ($log->patient->last_name ?? '')) ?: 'N/A' }}
                                <br><small class="text-muted">DIN: {{ $log->patient->din ?? 'N/A' }}</small>
                            </td>
                            <td>{{ $log->reminder?->title ?: ('#' . $log->reminder_id) }}</td>
                            <td>{{ strtoupper($log->channel) }}</td>
                            <td>{{ $log->recipient ?: 'N/A' }}</td>
                            <td><span class="badge bg-label-{{ $statusClass }}">{{ ucfirst($log->status) }}</span></td>
                            <td>{{ $log->provider_message ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No dispatch logs found in this facility.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

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

        .metric-card-violet {
            border-color: #ddd6fe;
            background: #f5f3ff;
            color: #5b21b6;
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

@include('_partials.datatables-init-multi', [
    'tableIds' => ['facilityReminderQueueTable', 'facilityDispatchLogTable'],
    'orders' => [
        'facilityReminderQueueTable' => [0, 'desc'],
        'facilityDispatchLogTable' => [0, 'desc'],
    ],
])




