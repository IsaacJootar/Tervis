@php
    use Carbon\Carbon;
@endphp

@section('title', 'Pending Queues')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Data Officer Queue</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-task me-1"></i>Pending Lab, Prescription, and Reminder Queue</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Use this queue to pick pending items, then open the patient workspace.</div>
            </div>
            <a href="{{ url('/workspaces/patient-workspace') }}" class="btn btn-dark">
                <i class="bx bx-search me-1"></i>Verify DIN / Open Workspace
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="metric-card metric-card-sky h-100">
                <div class="metric-label"><i class="bx bx-test-tube me-1"></i>Pending Lab Orders</div>
                <div class="metric-value">{{ number_format((int) $summary['pending_labs']) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card metric-card-emerald h-100">
                <div class="metric-label"><i class="bx bx-capsule me-1"></i>Pending Prescriptions</div>
                <div class="metric-value">{{ number_format((int) $summary['pending_prescriptions']) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card metric-card-amber h-100">
                <div class="metric-label"><i class="bx bx-bell me-1"></i>Due Reminders</div>
                <div class="metric-value">{{ number_format((int) $summary['due_reminders']) }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Pending Lab Orders</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="pendingLabOrdersQueueTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Patient</th>
                        <th>DIN</th>
                        <th>Test</th>
                        <th>Visit Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendingLabs as $row)
                        <tr>
                            <td class="fw-semibold">{{ trim((string) ($row->patient?->first_name ?? '') . ' ' . (string) ($row->patient?->last_name ?? '')) ?: 'N/A' }}</td>
                            <td>{{ $row->patient?->din ?: 'N/A' }}</td>
                            <td>{{ $row->test_name ?: 'N/A' }}</td>
                            <td data-order="{{ optional($row->visit_date)->format('Y-m-d') }}">
                                {{ optional($row->visit_date)->format('M d, Y') ?: 'N/A' }}
                            </td>
                            <td>
                                <a href="{{ url('/workspaces/' . $row->patient_id . '/laboratory') }}" class="btn btn-sm btn-outline-dark">
                                    <i class="bx bx-right-arrow-alt me-1"></i>Open
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No pending lab orders.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Pending Prescriptions</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="pendingPrescriptionsQueueTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Patient</th>
                        <th>DIN</th>
                        <th>Drug</th>
                        <th>Prescribed Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendingPrescriptions as $row)
                        <tr>
                            <td class="fw-semibold">{{ trim((string) ($row->patient?->first_name ?? '') . ' ' . (string) ($row->patient?->last_name ?? '')) ?: 'N/A' }}</td>
                            <td>{{ $row->patient?->din ?: 'N/A' }}</td>
                            <td>{{ $row->drug_name ?: 'N/A' }}</td>
                            <td data-order="{{ optional($row->prescribed_date)->format('Y-m-d') }}">
                                {{ optional($row->prescribed_date)->format('M d, Y') ?: 'N/A' }}
                            </td>
                            <td>
                                <a href="{{ url('/workspaces/' . $row->patient_id . '/prescriptions') }}" class="btn btn-sm btn-outline-dark">
                                    <i class="bx bx-right-arrow-alt me-1"></i>Open
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No pending prescriptions.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Due Reminders</h5></div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="dueRemindersQueueTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Patient</th>
                        <th>DIN</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dueReminders as $row)
                        <tr>
                            <td class="fw-semibold">{{ trim((string) ($row->patient?->first_name ?? '') . ' ' . (string) ($row->patient?->last_name ?? '')) ?: 'N/A' }}</td>
                            <td>{{ $row->patient?->din ?: 'N/A' }}</td>
                            <td>{{ $row->title ?: 'N/A' }}</td>
                            <td data-order="{{ optional($row->reminder_date)->format('Y-m-d') }}">
                                {{ optional($row->reminder_date)->format('M d, Y') ?: 'N/A' }}
                            </td>
                            <td>
                                <a href="{{ url('/workspaces/' . $row->patient_id . '/reminders') }}" class="btn btn-sm btn-outline-dark">
                                    <i class="bx bx-right-arrow-alt me-1"></i>Open
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No due reminders.</td></tr>
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

        .metric-card-amber {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }
    </style>

    @include('_partials.datatables-init-multi', [
        'tableIds' => [
            'pendingLabOrdersQueueTable',
            'pendingPrescriptionsQueueTable',
            'dueRemindersQueueTable',
        ],
        'orders' => [
            'pendingLabOrdersQueueTable' => [3, 'desc'],
            'pendingPrescriptionsQueueTable' => [3, 'desc'],
            'dueRemindersQueueTable' => [3, 'asc'],
        ],
    ])
</div>

