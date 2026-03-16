@php
    use Carbon\Carbon;
@endphp

@section('title', 'Visits')

<div>
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
            <span class="badge bg-label-primary text-uppercase">Visits</span>
        </div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                    style="width:64px;height:64px;font-weight:700;">
                    {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class="bx bx-calendar-heart me-1"></i>Visits Overview</h4>
                    <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                        <span class="badge bg-label-secondary">Patient: {{ $first_name }} {{ $last_name }}</span>
                        <span class="badge bg-label-info">Facility: {{ $facility_name }}</span>
                    </div>
                    <div class="small text-muted mt-2">Visits update automatically from attendance and activity entries.</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" wire:click="backToDashboard"
                        wire:loading.attr="disabled" wire:target="backToDashboard">
                        <span wire:loading.remove wire:target="backToDashboard"><i class="bx bx-arrow-back me-1"></i>Back
                            to Workspace</span>
                        <span wire:loading wire:target="backToDashboard"><span
                                class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-slate h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Total Visits</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M7 3v3M17 3v3M4 9h16" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                                <rect x="4" y="5" width="16" height="15" rx="2.5" stroke="currentColor"
                                    stroke-width="1.8" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $summary['total_visits'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-sky h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Visit Events</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M3 12h4l2-4 4 8 2-4h6" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $summary['total_events'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-emerald h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Open Visits</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 20h12V4H4z" stroke="currentColor" stroke-width="1.8" />
                                <path d="M16 12h4M18 10l2 2-2 2" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $summary['open_visits'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="metric-card metric-card-rose h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">This Month</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M7 3v3M17 3v3M4 9h16" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                                <rect x="4" y="5" width="16" height="15" rx="2.5" stroke="currentColor"
                                    stroke-width="1.8" />
                                <circle cx="12" cy="14" r="2.2" stroke="currentColor" stroke-width="1.8" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $summary['this_month'] }}</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Visit Dates</h5>
                    </div>
                    <div class="card-datatable table-responsive pt-0">
                        <table class="table align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Check-in</th>
                                    <th>Status</th>
                                    <th>Events</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($visits as $visit)
                                    <tr class="{{ (int) $selectedVisitId === (int) $visit->id ? 'table-active' : '' }}">
                                        <td>{{ $visit->visit_date?->format('M d, Y') }}</td>
                                        <td>{{ $visit->check_in_display ?: 'N/A' }}</td>
                                        <td>
                                            <span
                                                class="badge bg-label-{{ $visit->status === 'open' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($visit->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $visit->total_events }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                wire:click="selectVisit({{ $visit->id }})">
                                                Open
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No visits available yet. Click "Sync Visits" to collate records.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Page {{ $visits->currentPage() }} of {{ $visits->lastPage() }} | Total
                            {{ $visits->total() }}</small>
                        <div>{{ $visits->links() }}</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Visit Events</h5>
                    </div>
                    <div class="card-body border-bottom">
                        @if ($selectedVisit)
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <span class="badge bg-label-primary">
                                    Date: {{ $selectedVisit->visit_date?->format('M d, Y') }}
                                </span>
                                <span class="badge bg-label-info">
                                    Check-in: {{ $selectedVisit->check_in_display ?: 'N/A' }}
                                </span>
                                <span
                                    class="badge bg-label-{{ $selectedVisit->status === 'open' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($selectedVisit->status) }}
                                </span>
                            </div>
                            @php
                                $moduleBreakdown = data_get($selectedVisit->modules_summary, 'by_module', []);
                            @endphp
                            <div class="mt-3 d-flex flex-wrap gap-2">
                                @forelse ($moduleBreakdown as $module => $count)
                                    <span class="badge bg-label-dark">
                                        <i class="{{ $this->sectionIcon($module) }} me-1"></i>{{ $this->sectionLabel($module) }}:
                                        {{ $count }}
                                    </span>
                                @empty
                                    <span class="text-muted small">No section breakdown available yet.</span>
                                @endforelse
                            </div>
                        @else
                            <div class="text-muted">Select a visit date to view detailed events.</div>
                        @endif
                    </div>

                    <div class="card-datatable table-responsive pt-0">
                        <table class="table align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Time</th>
                                    <th>Section</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($events as $event)
                                    <tr>
                                        <td>{{ $event->event_time?->format('h:i A') }}</td>
                                        <td>
                                            <span class="badge bg-label-dark">
                                                <i class="{{ $this->sectionIcon($event->module) }} me-1"></i>{{ $this->sectionLabel($event->module) }}
                                            </span>
                                        </td>
                                        <td>{{ $event->action ?: 'N/A' }}</td>
                                        <td>{{ $event->description ?: 'N/A' }}</td>
                                        <td>{{ $event->performed_by ?: 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No events found for this visit.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Page {{ $events->currentPage() }} of {{ $events->lastPage() }} | Total
                            {{ $events->total() }}</small>
                        <div>{{ $events->links() }}</div>
                    </div>
                </div>
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
