@php
    use Carbon\Carbon;
@endphp

@section('title', 'Central Audit Trail')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Governance</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-history me-1"></i>Central Audit Trail</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Track staff and facility administration changes across facilities.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ url('/central/central-admin-dashboard') }}" class="btn btn-outline-dark">
                    <i class="bx bx-arrow-back me-1"></i>Back To Dashboard
                </a>
                <a href="{{ url('/central/platform-notifications') }}" class="btn btn-dark">
                    <i class="bx bx-bell me-1"></i>Platform Notifications
                </a>
            </div>
        </div>
    </div>

    @if (!$tablesReady)
        <div class="alert alert-warning">
            Audit tables are not available yet. Run `php artisan migrate` and refresh.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-slate h-100">
                <div class="metric-label"><i class="bx bx-list-ul me-1"></i>Total Events</div>
                <div class="metric-value">{{ number_format((int) $summary['total_rows']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="metric-label"><i class="bx bx-buildings me-1"></i>Facility Admin Events</div>
                <div class="metric-value">{{ number_format((int) $summary['facility_admin_rows']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="metric-label"><i class="bx bx-user-pin me-1"></i>Staff Events</div>
                <div class="metric-value">{{ number_format((int) $summary['staff_rows']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-violet h-100">
                <div class="metric-label"><i class="bx bx-time-five me-1"></i>Last Event</div>
                <div class="metric-value metric-value-sm">{{ $summary['last_event_at'] }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Facility</label>
                    <select wire:model.live="selected_facility_id" class="form-select">
                        <option value="">All Facilities</option>
                        @foreach ($facilityRows as $facility)
                            <option value="{{ $facility->id }}">{{ $facility->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Source</label>
                    <select wire:model.live="selected_source" class="form-select">
                        <option value="all">All Sources</option>
                        <option value="facility_admin">Facility Administration</option>
                        <option value="staff_management">Staff Management</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Action Filter</label>
                    <input wire:model.live.debounce.300ms="selected_action" type="text" class="form-control"
                        list="centralAuditActionList" placeholder="Filter by action keyword">
                    <datalist id="centralAuditActionList">
                        @foreach ($actions as $action)
                            <option value="{{ $action }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Audit Events</h5>
        </div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="centralAuditTrailTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Source</th>
                        <th>Facility</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Changed By</th>
                        <th>Notes</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['source'] }}</td>
                            <td class="fw-semibold">{{ $row['facility'] }}</td>
                            <td>{{ $row['action'] }}</td>
                            <td>{{ $row['target'] }}</td>
                            <td>{{ $row['changed_by'] }}</td>
                            <td>{{ $row['notes'] !== '' ? $row['notes'] : 'N/A' }}</td>
                            <td data-order="{{ optional($row['created_at'])->format('Y-m-d H:i:s') }}">
                                {{ optional($row['created_at'])->format('M d, Y h:i A') ?: 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No audit events found for current filters.</td>
                        </tr>
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

        .metric-value-sm {
            font-size: .95rem;
            line-height: 1.35;
        }

        .metric-card-slate {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
        }

        .metric-card-sky {
            border-color: #bae6fd;
            background: #f0f9ff;
            color: #075985;
        }

        .metric-card-emerald {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #065f46;
        }

        .metric-card-violet {
            border-color: #ddd6fe;
            background: #f5f3ff;
            color: #5b21b6;
        }
    </style>

    @include('_partials.datatables-init', [
        'tableId' => 'centralAuditTrailTable',
        'order' => [6, 'desc'],
    ])
</div>

