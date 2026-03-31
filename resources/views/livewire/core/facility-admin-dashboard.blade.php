<div class="facility-dashboard-page" wire:init="loadDeferredDashboardData">
    @php
        $registerCards = [
            [
                'title' => 'Antenatal Register',
                'icon' => 'bx-notepad',
                'stats' => $antenatalStats,
                'periodClass' => 'text-primary',
            ],
            [
                'title' => 'Delivery Register',
                'icon' => 'bx-plus-medical',
                'stats' => $deliveryStats,
                'periodClass' => 'text-success',
            ],
            [
                'title' => 'Postnatal Register',
                'icon' => 'bx-baby-carriage',
                'stats' => $postnatalStats,
                'periodClass' => 'text-info',
            ],
            [
                'title' => 'Tetanus Register',
                'icon' => 'bx-injection',
                'stats' => $tetanusStats,
                'periodClass' => 'text-warning',
            ],
            [
                'title' => 'Daily Attendance',
                'icon' => 'bx-calendar-check',
                'stats' => $attendanceStats,
                'periodClass' => 'text-dark',
            ],
        ];
    @endphp

    <div class="card section-card mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h5 class="mb-1 d-flex align-items-center gap-2">
                        <i class="bx bx-buildings fs-4 text-primary"></i>
                        Facility Admin Dashboard
                    </h5>
                    <div class="text-muted small d-flex flex-wrap gap-3">
                        <span><strong>Facility:</strong> {{ $facility_name ?? 'N/A' }}</span>
                        <span><strong>State:</strong> {{ $state_name ?? 'N/A' }}</span>
                        <span><strong>LGA:</strong> {{ $lga_name ?? 'N/A' }}</span>
                        <span><strong>Ward:</strong> {{ $ward_name ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="text-lg-end">
                    <div class="small text-muted">Welcome, {{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) }}</div>
                    <div class="small text-muted">{{ now('Africa/Lagos')->format('D, M j, Y h:i A') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card section-card mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Time Period</label>
                    <select wire:model.live="selectedTimeframe" class="form-select">
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="90">Last 3 Months</option>
                        <option value="365">Last 12 Months</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Focus Register</label>
                    <select wire:model.live="selectedRegister" class="form-select">
                        <option value="all">All Registers</option>
                        <option value="antenatal">Antenatal</option>
                        <option value="delivery">Delivery</option>
                        <option value="postnatal">Postnatal</option>
                        <option value="tetanus">Tetanus</option>
                        <option value="attendance">Daily Attendance</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <button wire:click="refreshData" wire:loading.attr="disabled" wire:target="refreshData,forceRefresh"
                            class="btn btn-outline-dark">
                            <span wire:loading.remove wire:target="refreshData"><i class="bx bx-refresh me-1"></i>Refresh</span>
                            <span wire:loading wire:target="refreshData"><i
                                    class="bx bx-loader-alt bx-spin me-1"></i>Refreshing...</span>
                        </button>
                        <button wire:click="forceRefresh" wire:loading.attr="disabled" wire:target="refreshData,forceRefresh"
                            class="btn btn-dark">
                            <span wire:loading.remove wire:target="forceRefresh"><i class="bx bx-reset me-1"></i>Force Refresh</span>
                            <span wire:loading wire:target="forceRefresh"><i
                                    class="bx bx-loader-alt bx-spin me-1"></i>Refreshing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (!$deferredMetricsReady)
        <div class="alert alert-info py-2 mb-4">
            <i class="bx bx-loader-alt bx-spin me-1"></i>
            Loading detailed analytics and risk metrics...
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4 col-6">
            <div class="metric-card metric-card-slate h-100">
                <div class="metric-card-title"><i class="bx bx-group me-1"></i>Total Patients</div>
                <div class="metric-card-value">{{ number_format((int) $totalPatients) }}</div>
                <div class="metric-card-meta">Unique records</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="metric-card metric-card-sky h-100">
                <div class="metric-card-title"><i class="bx bx-trending-up me-1"></i>New Registrations</div>
                <div class="metric-card-value">{{ number_format((int) $newRegistrations) }}</div>
                <div class="metric-card-meta">Selected period</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="metric-card metric-card-emerald h-100">
                <div class="metric-card-title"><i class="bx bx-plus-medical me-1"></i>Deliveries</div>
                <div class="metric-card-value">{{ number_format((int) $totalDeliveries) }}</div>
                <div class="metric-card-meta">Facility total</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="metric-card metric-card-violet h-100">
                <div class="metric-card-title"><i class="bx bx-heart-circle me-1"></i>Active Pregnancies</div>
                <div class="metric-card-value">{{ number_format((int) $activePregnancies) }}</div>
                <div class="metric-card-meta">EDD not yet reached</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="metric-card metric-card-amber h-100">
                <div class="metric-card-title"><i class="bx bx-calendar-check me-1"></i>Today's Visits</div>
                <div class="metric-card-value">{{ number_format((int) $todaysAttendance) }}</div>
                <div class="metric-card-meta">{{ now('Africa/Lagos')->format('M j') }}</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="metric-card metric-card-rose h-100">
                <div class="metric-card-title"><i class="bx bx-error-circle me-1"></i>High Risk</div>
                <div class="metric-card-value">
                    @if ($deferredMetricsReady)
                        {{ number_format((int) $highRiskCases) }}
                    @else
                        ...
                    @endif
                </div>
                <div class="metric-card-meta">Needs close follow-up</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card section-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center bg-white">
                    <h6 class="mb-0"><i class="bx bx-line-chart me-1 text-primary"></i>Trends Over Time</h6>
                    <small class="text-muted">Last {{ (int) $selectedTimeframe }} days</small>
                </div>
                <div class="card-body">
                    @if (!empty($trendChartData['labels']))
                        <canvas id="facilityTrendsChart" height="300"></canvas>
                    @else
                        <div class="chart-empty-state">
                            <i class="bx bx-line-chart-down"></i>
                            <p class="mb-0">No trend data in the selected period.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card section-card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bx bx-doughnut-chart me-1 text-info"></i>Age Group Distribution</h6>
                </div>
                <div class="card-body">
                    @if (!empty($ageGroupChartData))
                        <canvas id="facilityAgeGroupChart" height="300"></canvas>
                    @else
                        <div class="chart-empty-state">
                            <i class="bx bx-pie-chart-alt"></i>
                            <p class="mb-0">No age-group distribution available yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card section-card mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bx bx-table me-1 text-dark"></i>Register Statistics</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach ($registerCards as $registerCard)
                    @php
                        $trend = (float) ($registerCard['stats']['trend'] ?? 0);
                    @endphp
                    <div class="col-lg-4 col-md-6">
                        <div class="border rounded-3 p-3 h-100 register-stat-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-semibold d-flex align-items-center gap-1">
                                    <i class="bx {{ $registerCard['icon'] }}"></i>
                                    {{ $registerCard['title'] }}
                                </div>
                                @if ($trend > 0)
                                    <span class="badge bg-label-success">+{{ $trend }}%</span>
                                @elseif ($trend < 0)
                                    <span class="badge bg-label-danger">{{ $trend }}%</span>
                                @else
                                    <span class="badge bg-label-secondary">0%</span>
                                @endif
                            </div>
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <div class="h5 mb-0">{{ number_format((int) ($registerCard['stats']['total'] ?? 0)) }}</div>
                                    <small class="text-muted">Total records</small>
                                </div>
                                <div class="text-end">
                                    <div class="h6 mb-0 {{ $registerCard['periodClass'] }}">
                                        {{ number_format((int) ($registerCard['stats']['this_period'] ?? 0)) }}</div>
                                    <small class="text-muted">This period</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card section-card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bx bx-shield-quarter me-1 text-danger"></i>Risk Alerts</h6>
                </div>
                <div class="card-body">
                    @if (!empty($riskAlerts))
                        @foreach ($riskAlerts as $alert)
                            <div class="alert alert-{{ $alert['type'] }} d-flex align-items-start mb-2">
                                <i class="bx {{ $alert['icon'] }} me-2 mt-1 fs-5"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $alert['title'] }}</div>
                                    <div class="small">{{ $alert['message'] }}</div>
                                </div>
                                <span class="badge bg-{{ $alert['type'] }}">{{ (int) $alert['count'] }}</span>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-tile">
                            <i class="bx bx-check-shield text-success"></i>
                            <div>
                                <div class="fw-semibold">No active risk alert</div>
                                <small class="text-muted">No immediate issues detected in current scope.</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card section-card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bx bx-trending-up me-1 text-primary"></i>Performance Metrics</h6>
                </div>
                <div class="card-body">
                    <div class="metric-line">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Antenatal Coverage</span>
                            <strong>{{ $performanceMetrics['antenatal_coverage'] ?? 0 }}%</strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" style="width: {{ $performanceMetrics['antenatal_coverage'] ?? 0 }}%">
                            </div>
                        </div>
                    </div>
                    <div class="metric-line">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Average Daily Attendance</span>
                            <strong>{{ $performanceMetrics['avg_daily_attendance'] ?? 0 }}</strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success"
                                style="width: {{ min(($performanceMetrics['avg_daily_attendance'] ?? 0) * 10, 100) }}%"></div>
                        </div>
                    </div>
                    <div class="metric-line mb-0">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Facility Efficiency</span>
                            <strong>{{ $performanceMetrics['facility_efficiency'] ?? 0 }}%</strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-info" style="width: {{ $performanceMetrics['facility_efficiency'] ?? 0 }}%">
                            </div>
                        </div>
                    </div>
                    @php
                        $avgScore =
                            (($performanceMetrics['antenatal_coverage'] ?? 0) +
                                ($performanceMetrics['facility_efficiency'] ?? 0)) /
                            2;
                    @endphp
                    <div class="alert alert-light border mt-3 mb-0 small">
                        <strong>Summary:</strong>
                        @if ($avgScore >= 75)
                            Facility performance is strong across coverage and follow-up.
                        @elseif($avgScore >= 50)
                            Facility performance is moderate with room to improve follow-up.
                        @else
                            Facility performance needs attention in coverage and continuity of care.
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .facility-dashboard-page {
            position: relative;
        }

        .dashboard-loading {
            opacity: .72;
            pointer-events: none;
        }

        .section-card {
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 16px;
            box-shadow: 0 10px 26px -22px rgba(15, 23, 42, .45);
        }

        .metric-card {
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .25);
            padding: 12px 14px;
            min-height: 104px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 26px -22px rgba(15, 23, 42, .45);
        }

        .metric-card-title {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .metric-card-value {
            font-size: 1.4rem;
            font-weight: 800;
            line-height: 1.15;
        }

        .metric-card-meta {
            font-size: .76rem;
            opacity: .82;
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

        .metric-card-amber {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .metric-card-rose {
            border-color: #fecdd3;
            background: #fff1f2;
            color: #9f1239;
        }

        .chart-empty-state {
            min-height: 300px;
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            background: #f8fafc;
            color: #64748b;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-align: center;
        }

        .chart-empty-state i {
            font-size: 2rem;
        }

        .register-stat-card {
            border-color: rgba(148, 163, 184, .3) !important;
            background: #fff;
        }

        .metric-line {
            margin-bottom: .9rem;
        }

        .metric-line .progress {
            height: 8px;
            background-color: rgba(148, 163, 184, .2);
        }

        .empty-tile {
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
        }

        .empty-tile i {
            font-size: 1.5rem;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            let trendsChart = null;
            let ageGroupChart = null;
            let trendPayload = @json($trendChartData);
            let agePayload = @json($ageGroupChartData);

            const normalizePayload = (payload) => Array.isArray(payload) ? (payload[0] || {}) : (payload || {});

            const setLoading = (isLoading) => {
                const page = document.querySelector('.facility-dashboard-page');
                if (!page) {
                    return;
                }
                page.classList.toggle('dashboard-loading', isLoading);
            };

            const destroyCharts = () => {
                if (trendsChart) {
                    trendsChart.destroy();
                    trendsChart = null;
                }
                if (ageGroupChart) {
                    ageGroupChart.destroy();
                    ageGroupChart = null;
                }
            };

            const renderCharts = () => {
                if (typeof Chart === 'undefined') {
                    return;
                }

                destroyCharts();

                const trendCanvas = document.getElementById('facilityTrendsChart');
                if (trendCanvas && Array.isArray(trendPayload.labels) && trendPayload.labels.length > 0) {
                    trendsChart = new Chart(trendCanvas.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: trendPayload.labels || [],
                            datasets: [{
                                    label: 'Antenatal',
                                    data: trendPayload.antenatal || [],
                                    borderColor: '#0ea5e9',
                                    backgroundColor: 'rgba(14,165,233,.12)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: .35
                                },
                                {
                                    label: 'Delivery',
                                    data: trendPayload.delivery || [],
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16,185,129,.12)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: .35
                                },
                                {
                                    label: 'Attendance',
                                    data: trendPayload.attendance || [],
                                    borderColor: '#f59e0b',
                                    backgroundColor: 'rgba(245,158,11,.12)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: .35
                                }
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }

                const ageCanvas = document.getElementById('facilityAgeGroupChart');
                if (ageCanvas && agePayload && Object.keys(agePayload).length > 0) {
                    ageGroupChart = new Chart(ageCanvas.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: Object.keys(agePayload),
                            datasets: [{
                                data: Object.values(agePayload),
                                backgroundColor: ['#0ea5e9', '#10b981', '#a855f7', '#f59e0b', '#f43f5e'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%'
                        }
                    });
                }
            };

            document.addEventListener('DOMContentLoaded', () => setTimeout(renderCharts, 60));

            document.addEventListener('livewire:initialized', () => {
                if (!window.__facilityAdminDashboardHooksBound) {
                    window.__facilityAdminDashboardHooksBound = true;

                    Livewire.on('loading', () => setLoading(true));
                    Livewire.on('loaded', () => {
                        setLoading(false);
                        setTimeout(renderCharts, 80);
                    });

                    Livewire.on('dashboard-data-updated', (payload) => {
                        const normalized = normalizePayload(payload);
                        trendPayload = normalized.trendData || {
                            labels: [],
                            antenatal: [],
                            delivery: [],
                            attendance: []
                        };
                        agePayload = normalized.ageGroupData || {};
                        setTimeout(renderCharts, 80);
                    });
                }

                setTimeout(renderCharts, 80);
            });
        })();
    </script>
</div>
