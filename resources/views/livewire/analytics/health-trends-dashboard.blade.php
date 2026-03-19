@section('title', 'AI Health Trends & Predictive Analytics')
@php use Illuminate\Support\Facades\Auth; @endphp

<div class="analytics-page">
    @include('livewire.analytics._template-style')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h4 class="mb-1"><i class='bx bx-trending-up me-2'></i>AI Health Trends & Predictive Analytics</h4>
                        <p class="mb-0 text-muted">{{ \Carbon\Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-label-primary">{{ $trendSummary['total_trends'] ?? 0 }} Active Trends</span>
                        <span class="badge bg-label-danger">{{ $trendSummary['urgent_alerts'] ?? 0 }} Urgent Alerts</span>
                        <span class="badge bg-label-info">{{ $trendSummary['trending_up'] ?? 0 }} Increasing</span>
                        <span class="badge bg-label-warning">{{ $trendSummary['interventions_needed'] ?? 0 }} Need Action</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Facility Filter for Multi-facility Users -->
    @if (count($availableFacilities) > 0)
        <div class="row mb-4">
            <div class="col-md-8">
                <label class="form-label">
                    <i class="bx bx-buildings me-1"></i>
                    Filter by Facility
                </label>
                <select wire:model.live="selectedFacilityId" class="form-select form-select-lg">
                    <option value="">All Facilities
                        ({{ $scopeInfo['scope_type'] === 'state' ? 'State-wide' : 'LGA-wide' }}) -
                        {{ count($scopeInfo['facility_ids']) }} facilities</option>
                    @foreach ($availableFacilities as $facility)
                        <option value="{{ $facility['id'] }}">
                            {{ $facility['name'] }} - {{ $facility['lga'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end justify-content-md-end gap-2">
                @if ($selectedFacilityId)
                    <button wire:click="resetToScope" class="btn btn-outline-secondary btn-lg">
                        <i class="bx bx-reset me-1"></i>
                        View All Facilities
                    </button>
                @endif
                <button wire:click="refreshTrends" class="btn btn-primary btn-lg" wire:loading.attr="disabled"
                    wire:target="refreshTrends">
                    <span wire:loading.remove wire:target="refreshTrends">
                        <i class="bx bx-refresh me-1"></i>Refresh Data
                    </span>
                    <span wire:loading wire:target="refreshTrends">
                        <span class="spinner-border spinner-border-sm me-1"></span>Refreshing...
                    </span>
                </button>
            </div>
        </div>
    @endif
    <!-- Trend Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Trending Up</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 15l5-5 3.5 3.5L19 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M14 8h5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $trendSummary['trending_up'] ?? 0 }}</div>
                <div class="small">Positive trend signals</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="metric-card metric-card-amber h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Trending Down</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 9l5 5 3.5-3.5L19 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M14 16h5v-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $trendSummary['trending_down'] ?? 0 }}</div>
                <div class="small">Declining trend signals</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="metric-card metric-card-rose h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Critical Trends</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 4.5l8 14H4l8-14z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                            <path d="M12 9v4M12 15.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $trendSummary['critical_trends'] ?? 0 }}</div>
                <div class="small">Immediate attention needed</div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Stable Trends</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7" />
                            <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $trendSummary['stable_trends'] ?? 0 }}</div>
                <div class="small">Stable health patterns</div>
            </div>
        </div>
    </div>

    <!-- Predictive Insights Section -->
    @if (count($predictiveInsights) > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-brain me-2"></i>
                            Predictive Insights & Forecasts
                        </h5>
                        <small class="text-muted">AI-generated predictions based on current trends</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($predictiveInsights as $insight)
                                <div class="col-md-6 mb-3">
                                    <div
                                        class="alert alert-{{ $insight['type'] === 'risk_prediction' ? 'warning' : ($insight['type'] === 'ai_performance' ? 'info' : 'primary') }} border-start border-3">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <i
                                                    class="bx {{ $insight['type'] === 'risk_prediction' ? 'bx-error-circle' : ($insight['type'] === 'ai_performance' ? 'bx-brain' : 'bx-trending-up') }} bx-lg"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="alert-heading mb-2">{{ $insight['title'] }}</h6>
                                                <p class="mb-2">{{ $insight['prediction'] }}</p>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <small class="text-muted">Confidence:
                                                        {{ $insight['confidence'] }}</small>
                                                    <span
                                                        class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $insight['type'])) }}</span>
                                                </div>
                                                <hr class="my-2">
                                                <small><strong>Recommendation:</strong>
                                                    {{ $insight['recommendation'] }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Trends Dashboard -->
    <div class="row g-4 mb-4">
        <!-- Risk Trends Chart -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Risk Distribution Trends</h5>
                    <small class="text-muted">AI-detected risk level patterns over time</small>
                </div>
                <div class="card-body">
                    @if (count($riskTrends) > 0)
                        <canvas id="riskTrendsChart" style="max-height: 300px;"></canvas>
                    @else
                        <div class="text-center py-5">
                            <i class="bx bx-line-chart bx-lg text-muted mb-3"></i>
                            <p class="text-muted mb-0">No risk trend data available for the selected period</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Alert Trends -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Active Alerts</h5>
                    <small class="text-muted">Trends requiring immediate attention</small>
                </div>
                <div class="card-body">
                    @if (count($alertTrends) > 0)
                        <div class="alert-trends-container" style="max-height: 400px; overflow-y: auto;">
                            @foreach ($alertTrends as $alert)
                                <div
                                    class="alert alert-{{ $alert['alert_level'] === 'urgent' ? 'danger' : 'warning' }} p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">{{ ucwords(str_replace('_', ' ', $alert['metric_name'])) }}
                                        </h6>
                                        <span
                                            class="badge bg-{{ $alert['alert_level'] === 'urgent' ? 'danger' : 'warning' }}">
                                            {{ ucfirst($alert['alert_level']) }}
                                        </span>
                                    </div>
                                    <p class="mb-2 small">{{ $alert['ai_interpretation'] }}</p>
                                    @if ($alert['requires_intervention'])
                                        <div class="bg-light p-2 rounded">
                                            <small><strong>Actions needed:</strong></small>
                                            @if (is_array($alert['recommended_actions']))
                                                <ul class="mb-0 small">
                                                    @foreach ($alert['recommended_actions'] as $action)
                                                        <li>{{ $action }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    @endif
                                    <small class="text-muted d-block mt-2">
                                        {{ Carbon\Carbon::parse($alert['period_start'])->format('M d, Y') }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bx bx-check-circle bx-lg text-success mb-2"></i>
                            <p class="text-muted mb-0">No critical alerts at this time</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Clinical and Operational Trends -->
    <div class="row g-4 mb-4">
        <!-- Clinical Trends -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Clinical Performance Trends</h5>
                    <small class="text-muted">Health outcomes and clinical indicators</small>
                </div>
                <div class="card-body">
                    @if (count($clinicalTrends) > 0)
                        @foreach ($clinicalTrends as $metricName => $trend)
                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded bg-light">
                                <div>
                                    <h6 class="mb-1">{{ ucwords(str_replace('_', ' ', $metricName)) }}</h6>
                                    <small class="text-muted">Current: {{ $trend['current_value'] }}</small>
                                </div>
                                <div class="text-end">
                                    <span
                                        class="badge bg-{{ $trend['trend_direction'] === 'increasing' ? 'success' : ($trend['trend_direction'] === 'decreasing' ? 'danger' : 'secondary') }}">
                                        <i
                                            class="bx bx-{{ $trend['trend_direction'] === 'increasing' ? 'trending-up' : ($trend['trend_direction'] === 'decreasing' ? 'trending-down' : 'minus') }}"></i>
                                        {{ ucfirst($trend['trend_direction']) }}
                                    </span>
                                    @if ($trend['alert_level'] !== 'none')
                                        <br><small
                                            class="text-{{ $trend['alert_level'] === 'urgent' ? 'danger' : 'warning' }}">
                                            {{ ucfirst($trend['alert_level']) }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="bx bx-health bx-lg text-muted mb-2"></i>
                            <p class="text-muted mb-0">No clinical trends data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Operational Trends -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Operational Efficiency Trends</h5>
                    <small class="text-muted">System performance and utilization metrics</small>
                </div>
                <div class="card-body">
                    @if (count($operationalTrends) > 0)
                        @foreach ($operationalTrends as $trend)
                            <div class="mb-3 p-3 rounded border" wire:click="viewTrendDetails({{ $trend['id'] }})"
                                style="cursor: pointer; transition: all 0.3s ease;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">{{ ucwords(str_replace('_', ' ', $trend['metric_name'])) }}
                                    </h6>
                                    <span
                                        class="badge bg-{{ $trend['alert_level'] === 'warning' ? 'warning' : 'secondary' }}">
                                        {{ $trend['current_value'] }}
                                    </span>
                                </div>
                                <p class="small text-muted mb-2">{{ $trend['improvement_suggestion'] }}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span
                                        class="badge bg-{{ $trend['trend_direction'] === 'increasing' ? 'success' : 'danger' }}">
                                        <i
                                            class="bx bx-{{ $trend['trend_direction'] === 'increasing' ? 'trending-up' : 'trending-down' }}"></i>
                                        {{ ucfirst($trend['trend_direction']) }}
                                    </span>
                                    <small
                                        class="text-muted">{{ Carbon\Carbon::parse($trend['period_start'])->format('M d') }}</small>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="bx bx-cog bx-lg text-muted mb-2"></i>
                            <p class="text-muted mb-0">No operational trends data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Trend Details Modal -->
    @if ($showTrendModal && $selectedTrend)
        <div class="modal fade show" style="display: block;" tabindex="-1" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bx bx-line-chart me-2"></i>
                            Trend Details: {{ ucwords(str_replace('_', ' ', $selectedTrend->metric_name)) }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeTrendModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Current Status</h6>
                                <p><strong>Value:</strong> {{ $selectedTrend->current_value }}</p>
                                <p><strong>Trend:</strong>
                                    <span
                                        class="badge bg-{{ $selectedTrend->trend_direction === 'increasing' ? 'success' : 'danger' }}">
                                        {{ ucfirst($selectedTrend->trend_direction) }}
                                    </span>
                                </p>
                                <p><strong>Alert Level:</strong>
                                    <span
                                        class="badge bg-{{ $selectedTrend->alert_level === 'urgent' ? 'danger' : ($selectedTrend->alert_level === 'warning' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($selectedTrend->alert_level) }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>Period Information</h6>
                                <p><strong>Period:</strong> {{ $selectedTrend->period_start->format('M d, Y') }} -
                                    {{ $selectedTrend->period_end->format('M d, Y') }}</p>
                                <p><strong>Sample Size:</strong> {{ $selectedTrend->sample_size }}</p>
                                <p><strong>Change:</strong>
                                    @if ($selectedTrend->percentage_change)
                                        {{ round($selectedTrend->percentage_change, 1) }}%
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if ($selectedTrend->ai_interpretation)
                            <div class="mb-4">
                                <h6>AI Analysis</h6>
                                <div class="alert alert-info">
                                    {{ $selectedTrend->ai_interpretation }}
                                </div>
                            </div>
                        @endif

                        @if ($selectedTrend->recommended_actions)
                            <div class="mb-4">
                                <h6>Recommended Actions</h6>
                                <ul class="list-group">
                                    @foreach ($selectedTrend->recommended_actions as $action)
                                        <li class="list-group-item">{{ $action }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($selectedTrend->contributing_factors)
                            <div class="mb-4">
                                <h6>Contributing Factors</h6>
                                <div class="row">
                                    @foreach ($selectedTrend->contributing_factors as $factor => $value)
                                        <div class="col-md-6 mb-2">
                                            <span
                                                class="badge bg-light text-dark">{{ ucwords(str_replace('_', ' ', $factor)) }}:
                                                {{ $value }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeTrendModal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let riskTrendsChart = null;

        function initializeCharts() {
            try {
                if (riskTrendsChart) {
                    riskTrendsChart.destroy();
                    riskTrendsChart = null;
                }

                @if (count($riskTrends) > 0)
                    const ctx = document.getElementById('riskTrendsChart');
                    if (ctx) {
                        const riskTrendsData = @json($riskTrends);

                        // Process data for chart
                        const labels = [];
                        const datasets = {};
                        const colors = {
                            'critical_risk': 'rgba(234, 84, 85, 0.8)',
                            'high_risk': 'rgba(255, 171, 0, 0.8)',
                            'moderate_risk': 'rgba(3, 195, 236, 0.8)',
                            'low_risk': 'rgba(40, 199, 111, 0.8)'
                        };

                        // Group by date first
                        const dateMap = {};
                        riskTrendsData.forEach(item => {
                            const date = new Date(item.period_start);
                            const dateStr = date.toLocaleDateString('en-US', {
                                month: 'short',
                                day: 'numeric'
                            });

                            if (!dateMap[dateStr]) {
                                dateMap[dateStr] = {};
                                labels.push(dateStr);
                            }

                            dateMap[dateStr][item.metric_name] = item.current_value;
                        });

                        // Create datasets
                        const metricNames = ['critical_risk', 'high_risk', 'moderate_risk', 'low_risk'];
                        metricNames.forEach(metricName => {
                            datasets[metricName] = {
                                label: metricName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                                data: labels.map(label => dateMap[label][metricName] || 0),
                                borderColor: colors[metricName],
                                backgroundColor: colors[metricName],
                                tension: 0.4,
                                fill: false,
                                borderWidth: 3,
                                pointBackgroundColor: colors[metricName],
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7
                            };
                        });

                        riskTrendsChart = new Chart(ctx.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: Object.values(datasets)
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 15,
                                            font: {
                                                size: 11,
                                                weight: '500'
                                            }
                                        }
                                    },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        borderColor: 'rgba(255, 255, 255, 0.1)',
                                        borderWidth: 1,
                                        cornerRadius: 8,
                                        displayColors: true
                                    }
                                },
                                interaction: {
                                    mode: 'index',
                                    intersect: false
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Number of Cases',
                                            font: {
                                                size: 12,
                                                weight: '500'
                                            }
                                        },
                                        grid: {
                                            color: 'rgba(67, 89, 113, 0.1)'
                                        }
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Time Period',
                                            font: {
                                                size: 12,
                                                weight: '500'
                                            }
                                        },
                                        grid: {
                                            display: false
                                        }
                                    }
                                }
                            }
                        });
                    }
                @endif
            } catch (error) {
                console.error('Chart initialization failed:', error);
            }
        }

        function scheduleHealthTrendsChartsInit() {
            setTimeout(() => {
                initializeCharts();
            }, 100);
        }

        if (!window.__healthTrendsChartsBound) {
            window.__healthTrendsChartsBound = true;

            document.addEventListener('DOMContentLoaded', scheduleHealthTrendsChartsInit);
            document.addEventListener('livewire:navigated', scheduleHealthTrendsChartsInit);

            document.addEventListener('livewire:initialized', () => {
                Livewire.on('trends-updated', scheduleHealthTrendsChartsInit);
            });
        }

        scheduleHealthTrendsChartsInit();
    </script>

    <style>
        .modal.show {
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 18px;
            box-shadow: 0 22px 45px -32px rgba(15, 23, 42, 0.55);
        }

        .modal-header {
            border-bottom: 1px solid rgba(148, 163, 184, 0.22);
        }

        .modal-footer {
            border-top: 1px solid rgba(148, 163, 184, 0.22);
        }

        .alert-trends-container {
            scrollbar-width: thin;
            scrollbar-color: #ccc transparent;
        }

        .alert-trends-container::-webkit-scrollbar {
            width: 6px;
        }

        .alert-trends-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .alert-trends-container::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }

        .alert {
            border-left: 4px solid;
            border-radius: 8px;
        }

        .alert-warning {
            border-left-color: #ffab00;
            background-color: rgba(255, 171, 0, 0.1);
        }

        .alert-danger {
            border-left-color: #ff3e1d;
            background-color: rgba(255, 62, 29, 0.1);
        }

        .alert-info {
            border-left-color: #03c3ec;
            background-color: rgba(3, 195, 236, 0.1);
        }

        .alert-primary {
            border-left-color: #667eea;
            background-color: rgba(102, 126, 234, 0.1);
        }

        .badge {
            font-size: 0.875rem;
            font-weight: 500;
        }

        #riskTrendsChart {
            max-height: 300px;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }
        }
    </style>
</div>

