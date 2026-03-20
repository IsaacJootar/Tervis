@php
    use Carbon\Carbon;
@endphp

@section('title', 'Platform Notifications')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Governance</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-bell-ring me-1"></i>Platform Notifications</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Monitor failed reminder delivery, due reminders, and disabled modules.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ url('/central/central-admin-dashboard') }}" class="btn btn-outline-dark">
                    <i class="bx bx-arrow-back me-1"></i>Back To Dashboard
                </a>
                <a href="{{ url('/central/audit-trail') }}" class="btn btn-dark">
                    <i class="bx bx-history me-1"></i>Audit Trail
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-rose h-100">
                <div class="metric-label"><i class="bx bx-error me-1"></i>Failed (24h)</div>
                <div class="metric-value">{{ number_format((int) $summary['failed_24h']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-slate h-100">
                <div class="metric-label"><i class="bx bx-x-circle me-1"></i>Failed (Filtered)</div>
                <div class="metric-value">{{ number_format((int) $summary['failed_total']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-amber h-100">
                <div class="metric-label"><i class="bx bx-time-five me-1"></i>Due Today</div>
                <div class="metric-value">{{ number_format((int) $summary['due_today']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-violet h-100">
                <div class="metric-label"><i class="bx bx-slider-alt me-1"></i>Facilities With Disabled Modules</div>
                <div class="metric-value">{{ number_format((int) $summary['facilities_with_disabled_modules']) }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-7">
                    <label class="form-label">Facility</label>
                    <select wire:model.live="selected_facility_id" class="form-select">
                        <option value="">All Facilities</option>
                        @foreach ($facilityRows as $facility)
                            <option value="{{ $facility->id }}">{{ $facility->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Dispatch Channel</label>
                    <select wire:model.live="selected_channel" class="form-select">
                        <option value="all">All Channels</option>
                        <option value="sms">SMS</option>
                        <option value="email">Email</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Failed Reminder Dispatches</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="centralFailedDispatchTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Facility</th>
                        <th>Channel</th>
                        <th>Recipient</th>
                        <th>Provider</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($failedDispatches as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->facility?->name ?: 'N/A' }}</td>
                            <td>{{ strtoupper((string) $row->channel) }}</td>
                            <td>{{ $row->recipient ?: 'N/A' }}</td>
                            <td>{{ $row->provider ?: 'N/A' }}</td>
                            <td><span class="badge bg-label-danger">{{ ucfirst((string) $row->status) }}</span></td>
                            <td data-order="{{ optional($row->created_at)->format('Y-m-d H:i:s') }}">
                                {{ optional($row->created_at)->format('M d, Y h:i A') ?: 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No failed dispatch rows for selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Due Reminder Queue</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="centralDueRemindersTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Facility</th>
                        <th>Patient</th>
                        <th>Title</th>
                        <th>Date/Time</th>
                        <th>Status</th>
                        <th>Channels</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dueReminders as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->facility?->name ?: 'N/A' }}</td>
                            <td>
                                {{ trim((string) ($row->patient?->first_name ?? '') . ' ' . (string) ($row->patient?->last_name ?? '')) ?: 'N/A' }}
                                @if (!empty($row->patient?->din))
                                    <div class="small text-muted">DIN: {{ $row->patient->din }}</div>
                                @endif
                            </td>
                            <td>{{ $row->title ?: 'N/A' }}</td>
                            <td data-order="{{ optional($row->reminder_date)->format('Y-m-d') }}">
                                {{ optional($row->reminder_date)->format('M d, Y') ?: 'N/A' }}
                                @if (!empty($row->reminder_time))
                                    <div class="small text-muted">{{ $row->reminder_time }}</div>
                                @endif
                            </td>
                            <td><span class="badge bg-label-warning">{{ ucfirst((string) $row->status) }}</span></td>
                            <td>{{ implode(', ', (array) ($row->channels ?? [])) ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No due reminders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Disabled Modules By Facility</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="centralDisabledModulesTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Facility</th>
                        <th>Module</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($disabledModuleRows as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->facility?->name ?: 'N/A' }}</td>
                            <td>{{ $row->module_label ?: $row->module_key }}</td>
                            <td data-order="{{ optional($row->updated_at)->format('Y-m-d H:i:s') }}">
                                {{ optional($row->updated_at)->format('M d, Y h:i A') ?: 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-4 text-muted">No disabled module records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .metric-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, .24);
            padding: 14px 16px;
            min-height: 104px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 26px -22px rgba(15, 23, 42, .45);
        }

        .metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .12em;
            font-weight: 700;
        }

        .metric-value {
            margin-top: 6px;
            font-size: 1.45rem;
            font-weight: 700;
            line-height: 1.15;
        }

        .metric-card-slate {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
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

        .metric-card-violet {
            border-color: #ddd6fe;
            background: #f5f3ff;
            color: #5b21b6;
        }
    </style>

    @include('_partials.datatables-init-multi', [
        'tableIds' => [
            'centralFailedDispatchTable',
            'centralDueRemindersTable',
            'centralDisabledModulesTable',
        ],
        'orders' => [
            'centralFailedDispatchTable' => [5, 'desc'],
            'centralDueRemindersTable' => [3, 'asc'],
            'centralDisabledModulesTable' => [2, 'desc'],
        ],
    ])
</div>

