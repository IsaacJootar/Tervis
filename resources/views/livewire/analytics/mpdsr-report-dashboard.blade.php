<div class="analytics-page">
    @include('livewire.analytics._template-style')
    <div>
        @php
            use Carbon\Carbon;
        @endphp
        @section('title', 'MPDSR Report Dashboard')

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1"><i class='bx bx-health me-2'></i>Maternal and Perinatal Death Surveillance and Response (MPDSR)</h4>
                            <p class="mb-0 text-muted">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-label-danger">{{ $totalMaternalDeaths }} Maternal Deaths</span>
                            <span class="badge bg-label-warning">{{ $totalPerinatalDeaths }} Perinatal Deaths</span>
                            <span class="badge bg-label-info">{{ $totalStillbirths }} Stillbirths</span>
                            <span class="badge bg-label-secondary">{{ $totalNeonatalDeaths }} Neonatal Deaths</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Facility Filter (only show if multiple facilities) -->
        @if (count($availableFacilities) > 0)
            <div class="row mb-4">
                <div class="col-md-8">
                    <label class="form-label">
                        <i class="bx bx-buildings me-1"></i>
                        Filter by Facility
                    </label>
                    <select wire:model.live="selectedFacilityId" class="form-select form-select-lg">
                        <option value="">All Facilities
                            ({{ $scopeInfo['scope_type'] === 'state' ? 'State-wide' : 'LGA-wide' }})</option>
                        @foreach ($availableFacilities as $facility)
                            <option value="{{ $facility['id'] }}">
                                {{ $facility['name'] }} - {{ $facility['lga'] }} ({{ $facility['ward'] }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">
                        @if ($selectedFacilityId)
                            Showing data for selected facility only
                        @else
                            Showing aggregated data across {{ count($scopeInfo['facility_ids']) }} facilities
                        @endif
                    </small>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    @if ($selectedFacilityId)
                        <button wire:click="resetToScope" class="btn btn-outline-secondary btn-lg w-100">
                            <i class="bx bx-reset me-1"></i>
                            View All Facilities
                        </button>
                    @endif
                </div>
            </div>
        @endif

        <!-- Filters Row -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-filter me-2"></i>Data Filters
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input wire:model.live="dateFrom" type="date" class="form-select">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input wire:model.live="dateTo" type="date" class="form-select">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Death Type</label>
                                <select wire:model.live="deathType" class="form-select">
                                    <option value="all">All Deaths</option>
                                    <option value="maternal">Maternal Only</option>
                                    <option value="perinatal">Perinatal Only</option>
                                    <option value="stillbirth">Stillbirths Only</option>
                                    <option value="neonatal">Neonatal Only</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button wire:click="refreshData" class="btn btn-primary w-100">
                                    <i class="bx bx-refresh me-1"></i>Refresh Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Indicators Row -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="metric-card metric-card-rose h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Maternal Deaths</div>
                        <span class="metric-icon"><i class="bx bx-female"></i></span>
                    </div>
                    <div class="metric-value">{{ $totalMaternalDeaths }}</div>
                    <div class="small">MMR: {{ $maternalMortalityRatio }} per 100,000 live births</div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="metric-card metric-card-amber h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Perinatal Deaths</div>
                        <span class="metric-icon"><i class="bx bx-baby-carriage"></i></span>
                    </div>
                    <div class="metric-value">{{ $totalPerinatalDeaths }}</div>
                    <div class="small">PMR: {{ $perinatalMortalityRate }} per 1,000 births</div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="metric-card metric-card-slate h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Stillbirths</div>
                        <span class="metric-icon"><i class="bx bx-heart-circle"></i></span>
                    </div>
                    <div class="metric-value">{{ $totalStillbirths }}</div>
                    <div class="small">{{ $totalPerinatalDeaths > 0 ? round(($totalStillbirths / $totalPerinatalDeaths) * 100, 1) : 0 }}% of perinatal deaths</div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="metric-card metric-card-sky h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Neonatal Deaths</div>
                        <span class="metric-icon"><i class="bx bx-child"></i></span>
                    </div>
                    <div class="metric-value">{{ $totalNeonatalDeaths }}</div>
                    <div class="small">{{ $totalPerinatalDeaths > 0 ? round(($totalNeonatalDeaths / $totalPerinatalDeaths) * 100, 1) : 0 }}% of perinatal deaths</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Deaths by Time Period -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Deaths Over Time</h5>
                        <small class="text-muted">Trend analysis by period</small>
                    </div>
                    <div class="card-body">
                        @if (count($deathsByTimePeriod) > 0)
                            <canvas id="deathsTrendChart" style="max-height: 300px;"></canvas>
                        @else
                            <div class="text-center py-5">
                                <i class="bx bx-line-chart bx-lg text-muted mb-2"></i>
                                <p class="text-muted mb-0">No deaths trend data available for this period.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Deaths by Cause -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Deaths by Probable Cause</h5>
                        <small class="text-muted">Top causes identified</small>
                    </div>
                    <div class="card-body">
                        @if (count($deathsByCause) > 0)
                            <canvas id="deathsByCauseChart" style="max-height: 300px;"></canvas>
                        @else
                            <div class="text-center py-5">
                                <i class="bx bx-bar-chart-alt-2 bx-lg text-muted mb-2"></i>
                                <p class="text-muted mb-0">No cause data available for this period.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Deaths by Facility -->
        @if (count($deathsByFacility) > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-building me-2"></i>
                                Deaths by Facility
                            </h5>
                            <small class="text-muted">Facility-level breakdown</small>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Facility Name</th>
                                            <th>LGA</th>
                                            <th class="text-center">Maternal Deaths</th>
                                            <th class="text-center">Perinatal Deaths</th>
                                            <th class="text-center">Total Deaths</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($deathsByFacility as $facility)
                                            <tr>
                                                <td><strong>{{ $facility['facility_name'] }}</strong></td>
                                                <td>{{ $facility['lga'] }}</td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-danger">{{ $facility['maternal_deaths'] }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-warning">{{ $facility['perinatal_deaths'] }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-dark fs-6">{{ $facility['total_deaths'] }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Maternal Deaths Table -->
        @if (count($maternalDeaths) > 0 && in_array($deathType, ['all', 'maternal']))
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-female me-2"></i>
                                Maternal Deaths - Detailed Records
                            </h5>

                        </div>
                        <div class="card-datatable table-responsive pt-0">
                            <table id="maternalDeathsTable" class="table">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>DIN</th>
                                        <th>Age Group</th>
                                        @if (!$selectedFacilityId)
                                            <th>Facility</th>
                                            <th>LGA</th>
                                        @endif
                                        <th>Death Date</th>
                                        <th>Probable Cause</th>
                                        <th>Mode of Delivery</th>
                                        <th>Place of Death</th>
                                        <th>Contributing Factors</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($maternalDeaths as $death)
                                        <tr>
                                            <td><strong>{{ $death['patient_name'] }}</strong></td>
                                            <td><span class="badge bg-label-info">{{ $death['patient_din'] }}</span>
                                            </td>
                                            <td>{{ $death['age'] }}</td>
                                            @if (!$selectedFacilityId)
                                                <td><small class="text-muted">{{ $death['facility_name'] }}</small>
                                                </td>
                                                <td><small class="text-muted">{{ $death['lga'] }}</small></td>
                                            @endif
                                            <td>{{ $death['death_date'] }}</td>
                                            <td>
                                                <span class="badge bg-danger">{{ $death['probable_cause'] }}</span>
                                            </td>
                                            <td>{{ $death['mode_of_delivery'] }}</td>
                                            <td>{{ $death['place_of_death'] }}</td>
                                            <td>
                                                @foreach ($death['contributing_factors'] as $factor)
                                                    <small
                                                        class="badge bg-label-warning mb-1 d-block">{{ $factor }}</small>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Perinatal Deaths Table -->
        @if (count($perinatalDeaths) > 0 && in_array($deathType, ['all', 'perinatal', 'stillbirth', 'neonatal']))
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-baby-carriage me-2"></i>
                                Perinatal Deaths - Detailed Records
                            </h5>
                            <small class="text-muted">Stillbirths and early neonatal deaths (within 7 days of
                                birth)</small>
                        </div>
                        <div class="card-datatable table-responsive pt-0">
                            <table id="perinatalDeathsTable" class="table">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Mother Name</th>
                                        <th>Mother DIN</th>
                                        <th>Mother Age</th>
                                        @if (!$selectedFacilityId)
                                            <th>Facility</th>
                                            <th>LGA</th>
                                        @endif
                                        <th>Death Date</th>
                                        <th>Death Type</th>
                                        <th>Baby Sex</th>
                                        <th>Baby Weight</th>
                                        <th>Gestational Age</th>
                                        <th>Probable Cause</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($perinatalDeaths as $death)
                                        @if (
                                            $deathType === 'all' ||
                                                $deathType === 'perinatal' ||
                                                ($deathType === 'stillbirth' && $death['death_type'] === 'Stillbirth') ||
                                                ($deathType === 'neonatal' && $death['death_type'] === 'Early Neonatal Death'))
                                            <tr>
                                                <td><strong>{{ $death['mother_name'] }}</strong></td>
                                                <td><span
                                                        class="badge bg-label-info">{{ $death['mother_din'] }}</span>
                                                </td>
                                                <td>{{ $death['mother_age'] }}</td>
                                                @if (!$selectedFacilityId)
                                                    <td><small
                                                            class="text-muted">{{ $death['facility_name'] }}</small>
                                                    </td>
                                                    <td><small class="text-muted">{{ $death['lga'] }}</small></td>
                                                @endif
                                                <td>{{ $death['death_date'] }}</td>
                                                <td>
                                                    <span
                                                        class="badge {{ $death['death_type'] === 'Stillbirth' ? 'bg-secondary' : 'bg-info' }}">
                                                        {{ $death['death_type'] }}
                                                    </span>
                                                </td>
                                                <td>{{ $death['baby_sex'] }}</td>
                                                <td>{{ $death['baby_weight'] }} kg</td>
                                                <td>{{ $death['gestational_age'] }} weeks</td>
                                                <td>
                                                    <span
                                                        class="badge bg-warning">{{ $death['probable_cause'] }}</span>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- MPDSR Information Card -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="bx bx-info-circle me-2"></i>About MPDSR in Nigeria
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">Objectives</h6>
                                <ul class="list-unstyled">
                                    <li><small><i class="bx bx-check me-1"></i>Reduce preventable maternal and
                                            perinatal
                                            deaths</small></li>
                                    <li><small><i class="bx bx-check me-1"></i>Strengthen health system
                                            accountability</small></li>
                                    <li><small><i class="bx bx-check me-1"></i>Improve quality of care through
                                            learning</small></li>
                                    <li><small><i class="bx bx-check me-1"></i>Promote a "no blame" culture for
                                            reporting</small></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success">Key Indicators</h6>
                                <ul class="list-unstyled">
                                    <li><small><i class="bx bx-data me-1"></i><strong>MMR:</strong> Maternal Mortality
                                            Ratio (per 100,000 live births)</small></li>
                                    <li><small><i class="bx bx-data me-1"></i><strong>PMR:</strong> Perinatal Mortality
                                            Rate (per 1,000 births)</small></li>
                                    <li><small><i class="bx bx-data me-1"></i><strong>SBR:</strong> Stillbirth Rate
                                            (per
                                            1,000 total births)</small></li>
                                    <li><small><i class="bx bx-data me-1"></i><strong>ENMR:</strong> Early Neonatal
                                            Mortality Rate</small></li>
                                </ul>
                            </div>
                        </div>
                        <div class="alert alert-warning mt-3" role="alert">
                            <h6 class="alert-heading mb-2">
                                <i class="bx bx-shield-quarter me-2"></i>
                                <strong>Important Notice</strong>
                            </h6>
                            <small>
                                <p class="mb-1">This dashboard is for surveillance and response purposes. All
                                    maternal and
                                    perinatal deaths require:</p>
                                <ul class="mb-2">
                                    <li>Immediate notification to appropriate authorities</li>
                                    <li>Facility-level review within 7 days</li>
                                    <li>LGA/State review as per MPDSR guidelines</li>
                                    <li>Implementation of recommended corrective actions</li>
                                </ul>
                                <p class="mb-0"><strong>Every death is preventable. Every death must be
                                        reviewed.</strong></p>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart.js Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            let trendChart = null;
            let causeChart = null;

            function initializeCharts() {
                try {
                    // Destroy existing charts
                    if (trendChart) {
                        trendChart.destroy();
                        trendChart = null;
                    }
                    if (causeChart) {
                        causeChart.destroy();
                        causeChart = null;
                    }

                    // Deaths Trend Chart
                    const trendData = @json($deathsByTimePeriod);
                    if (trendData.length > 0) {
                        const trendCtx = document.getElementById('deathsTrendChart');
                        if (trendCtx) {
                            trendChart = new Chart(trendCtx, {
                                type: 'line',
                                data: {
                                    labels: trendData.map(item => item.period),
                                    datasets: [{
                                            label: 'Maternal Deaths',
                                            data: trendData.map(item => item.maternal_deaths),
                                            borderColor: '#ea5455',
                                            backgroundColor: 'rgba(234, 84, 85, 0.1)',
                                            borderWidth: 3,
                                            fill: true,
                                            tension: 0.4,
                                        },
                                        {
                                            label: 'Perinatal Deaths',
                                            data: trendData.map(item => item.perinatal_deaths),
                                            borderColor: '#ff9f43',
                                            backgroundColor: 'rgba(255, 159, 67, 0.1)',
                                            borderWidth: 3,
                                            fill: true,
                                            tension: 0.4,
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'top',
                                        },
                                        tooltip: {
                                            mode: 'index',
                                            intersect: false,
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    }

                    // Deaths by Cause Chart
                    const causeData = @json($deathsByCause);
                    if (causeData.length > 0) {
                        const causeCtx = document.getElementById('deathsByCauseChart');
                        if (causeCtx) {
                            causeChart = new Chart(causeCtx, {
                                type: 'bar',
                                data: {
                                    labels: causeData.slice(0, 8).map(item => item.cause),
                                    datasets: [{
                                            label: 'Maternal',
                                            data: causeData.slice(0, 8).map(item => item.maternal_count),
                                            backgroundColor: 'rgba(234, 84, 85, 0.8)',
                                            borderColor: '#ea5455',
                                            borderWidth: 1,
                                        },
                                        {
                                            label: 'Perinatal',
                                            data: causeData.slice(0, 8).map(item => item.perinatal_count),
                                            backgroundColor: 'rgba(255, 159, 67, 0.8)',
                                            borderColor: '#ff9f43',
                                            borderWidth: 1,
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'top',
                                        }
                                    },
                                    scales: {
                                        x: {
                                            stacked: true,
                                        },
                                        y: {
                                            stacked: true,
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    }

                } catch (error) {
                    console.error('Chart initialization failed:', error);
                }
            }

            function scheduleMpdsrChartsInit() {
                setTimeout(() => {
                    initializeCharts();
                }, 100);
            }

            if (!window.__mpdsrChartsBound) {
                window.__mpdsrChartsBound = true;

                document.addEventListener('DOMContentLoaded', scheduleMpdsrChartsInit);
                document.addEventListener('livewire:navigated', scheduleMpdsrChartsInit);

                document.addEventListener('livewire:initialized', () => {
                    Livewire.on('refresh-charts', scheduleMpdsrChartsInit);
                });
            }

            scheduleMpdsrChartsInit();
        </script>

        <style>
            @media (max-width: 768px) {
                .card-body {
                    padding: 1rem;
                }
            }
        </style>

        @include('_partials.datatables-init')
    </div>
</div>
