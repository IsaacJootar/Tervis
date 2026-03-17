@php
    use Carbon\Carbon;
@endphp

@section('title', 'Reports Hub')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Reports Hub</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                style="width:64px;height:64px;font-weight:700;">
                {{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name ?? 'S', 0, 1)) }}
            </div>
            <div class="flex-grow-1">
                <h4 class="mb-1"><i class="bx bx-file-find me-1"></i>Facility Reports Hub</h4>
                <div class="text-muted small">Generate reports by section, report name, and date filters.</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge bg-label-primary">Scope: {{ ucfirst($scopeInfo['scope_type'] ?? 'facility') }}</span>
                    <span class="badge bg-label-info">Facilities: {{ count($scopeInfo['facility_ids'] ?? []) }}</span>
                    <span class="badge bg-label-secondary">Now: {{ Carbon::now('Africa/Lagos')->format('M d, Y h:i A') }}</span>
                </div>
            </div>
            @if ($source_route_url)
                <a href="{{ $source_route_url }}" class="btn btn-outline-primary">
                    <i class="bx bx-link-external me-1"></i>Open Source Dashboard
                </a>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Report Filters</h5>
        </div>
        <div class="card-body">
            @if (!empty($feedback_message))
                <div class="alert alert-{{ $feedback_type === 'success' ? 'success' : 'danger' }} mb-3">
                    {{ $feedback_message }}
                </div>
            @endif

            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Section</label>
                    <select wire:model.live="selected_section" class="form-select">
                        @foreach ($sections as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Report Name</label>
                    <select wire:model.live="selected_report" class="form-select">
                        @foreach ($this->visibleReports as $key => $meta)
                            <option value="{{ $key }}">{{ $meta['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Facility Scope</label>
                    @if (count($availableFacilities) > 0)
                        <select wire:model.live="selectedFacilityId" class="form-select">
                            <option value="">All Facilities In Scope</option>
                            @foreach ($availableFacilities as $facility)
                                <option value="{{ $facility['id'] }}">{{ $facility['name'] }} - {{ $facility['lga'] }} ({{ $facility['ward'] }})</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" class="form-control bg-light" value="Current {{ ucfirst($scopeInfo['scope_type'] ?? 'facility') }} scope" readonly>
                    @endif
                </div>
            </div>

            <div class="row g-3 align-items-end mt-1">
                <div class="col-md-4">
                    <label class="form-label">From</label>
                    <input type="date" wire:model="date_from" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">To</label>
                    <input type="date" wire:model="date_to" class="form-control">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="button" class="btn btn-primary flex-grow-1" wire:click="generateReport" wire:loading.attr="disabled" wire:target="generateReport">
                        <span wire:loading.remove wire:target="generateReport"><i class="bx bx-play me-1"></i>Generate Report</span>
                        <span wire:loading wire:target="generateReport"><span class="spinner-border spinner-border-sm me-1"></span>Generating...</span>
                    </button>
                    @if ($selectedFacilityId)
                        <button type="button" class="btn btn-outline-secondary" wire:click="resetToScope" wire:loading.attr="disabled" wire:target="resetToScope">
                            <span wire:loading.remove wire:target="resetToScope">Reset</span>
                            <span wire:loading wire:target="resetToScope"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    @endif
                </div>
            </div>

            @if ($selected_report && isset($report_catalog[$selected_report]))
                <div class="alert alert-info mt-3 mb-0">
                    <strong>{{ $report_catalog[$selected_report]['name'] }}</strong><br>
                    <small>{{ $report_catalog[$selected_report]['description'] }}</small>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Reports In View</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="8" height="8" rx="2"></rect>
                            <rect x="13" y="3" width="8" height="8" rx="2"></rect>
                            <rect x="3" y="13" width="8" height="8" rx="2"></rect>
                            <rect x="13" y="13" width="8" height="8" rx="2"></rect>
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ count($this->visibleReports) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Generated Records</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <ellipse cx="12" cy="5" rx="8" ry="3"></ellipse>
                            <path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"></path>
                            <path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"></path>
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $result_count }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Facilities In Scope</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="8" height="16" rx="1"></rect>
                            <rect x="13" y="8" width="8" height="12" rx="1"></rect>
                            <path d="M7 8h.01M7 12h.01M7 16h.01M17 12h.01M17 16h.01"></path>
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ count($scopeInfo['facility_ids'] ?? []) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-violet h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Date Window</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                            <path d="M8 2v4M16 2v4M3 10h18"></path>
                        </svg>
                    </span>
                </div>
                <div class="metric-value metric-value-date">
                    {{ $date_from ? Carbon::parse($date_from)->format('d M Y') : '-' }}
                    -
                    {{ $date_to ? Carbon::parse($date_to)->format('d M Y') : '-' }}
                </div>
            </div>
        </div>
    </div>

    @if ($show_results)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $report_title }} <small class="text-muted">({{ $result_count }} records)</small></h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-dark text-white" wire:click="exportCurrentCsv" wire:loading.attr="disabled" wire:target="exportCurrentCsv" @disabled(empty($result_rows))>
                        <span wire:loading.remove wire:target="exportCurrentCsv"><i class="bx bx-download me-1"></i>Export CSV</span>
                        <span wire:loading wire:target="exportCurrentCsv"><span class="spinner-border spinner-border-sm me-1"></span>Exporting...</span>
                    </button>
                    <a href="{{ route('reports-hub-print') }}" target="_blank" class="btn btn-dark text-white {{ empty($result_rows) ? 'disabled' : '' }}" @if(empty($result_rows)) aria-disabled="true" @endif>
                        <i class="bx bx-printer me-1"></i>Printable Report
                    </a>
                    <button type="button" class="btn btn-primary" wire:click="generateReport" wire:loading.attr="disabled" wire:target="generateReport">
                        <span wire:loading.remove wire:target="generateReport"><i class="bx bx-refresh me-1"></i>Refresh</span>
                        <span wire:loading wire:target="generateReport"><span class="spinner-border spinner-border-sm me-1"></span>Refreshing...</span>
                    </button>
                </div>
            </div>
            <div class="card-body pt-3">
                <p class="text-muted mb-3">{{ $report_description }}</p>
                @if (empty($result_rows))
                    <div class="alert alert-warning py-2 mb-3">No rows matched this filter window. Export buttons are disabled until data is available.</div>
                @endif
                <div class="card-datatable table-responsive pt-0">
                    <table id="reportResultsTable" class="table align-middle">
                        <thead class="table-dark">
                            <tr>
                                @foreach ($result_columns as $column)
                                    <th>{{ $column['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($result_rows as $row)
                                <tr>
                                    @foreach ($result_columns as $column)
                                        @php $value = $row[$column['key']] ?? '-'; @endphp
                                        <td>
                                            @if (in_array($column['key'], ['status', 'service_provided', 'follow_up_needed'], true))
                                                <span class="badge bg-label-secondary">{{ $value }}</span>
                                            @elseif (in_array($column['key'], ['total_amount', 'amount_paid', 'outstanding_amount'], true))
                                                {{ is_numeric($value) ? number_format((float) $value, 2) : $value }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ max(count($result_columns), 1) }}" class="text-center py-4 text-muted">No data found for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Generation History</h5>
        </div>
        <div class="card-body p-0">
            <div class="card-datatable table-responsive pt-0">
                <table id="reportHistoryTable" class="table align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Generated At</th>
                            <th>Report</th>
                            <th>Section</th>
                            <th>Date Range</th>
                            <th>Scope</th>
                            <th>Records</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($history_rows as $item)
                            <tr>
                                <td>{{ $item['generated_at'] ?? '-' }}</td>
                                <td>{{ $item['report_name'] ?? '-' }}</td>
                                <td>{{ $item['section'] ?? '-' }}</td>
                                <td>{{ $item['date_from'] ?? '-' }} to {{ $item['date_to'] ?? '-' }}</td>
                                <td>{{ $item['scope'] ?? '-' }}</td>
                                <td>{{ $item['records'] ?? 0 }}</td>
                                <td>{{ $item['generated_by'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No report generation history yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
        color: currentColor;
    }

    .metric-value-date {
        font-size: 0.95rem;
        line-height: 1.3;
        white-space: normal;
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

    .dt-buttons .btn.btn-primary {
        background: #111827 !important;
        border-color: #111827 !important;
        color: #ffffff !important;
    }
</style>

<script>
    (function() {
        if (window.__reportsHubToastBound) {
            return;
        }

        window.__reportsHubToastBound = true;

        const bindLivewireHandlers = () => {
            if (window.__reportsHubHandlersBound || !window.Livewire || typeof Livewire.on !== 'function') {
                return;
            }
            window.__reportsHubHandlersBound = true;

            Livewire.on('app-toast', function(payload) {
                const data = Array.isArray(payload) ? payload[0] : payload;
                if (!data || !window.toastr) {
                    return;
                }

                const type = (data.type || 'error').toLowerCase();
                const message = data.message || '';
                if (type === 'success') {
                    toastr.success(message);
                } else {
                    toastr.error(message);
                }
            });
        };

        bindLivewireHandlers();
        document.addEventListener('livewire:init', bindLivewireHandlers);
        document.addEventListener('livewire:initialized', bindLivewireHandlers);
        window.addEventListener('load', bindLivewireHandlers);
    })();
</script>

@include('_partials.datatables-init-multi', [
    'tableIds' => ['reportResultsTable', 'reportHistoryTable'],
    'orders' => [
        'reportResultsTable' => [0, 'desc'],
        'reportHistoryTable' => [0, 'desc'],
    ],
])

</div>
