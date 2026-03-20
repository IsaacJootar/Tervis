<div class="analytics-page">
    @include('livewire.analytics._template-style')
    <div>
        @section('title', 'MPDSR Death Surveillance')

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1">MPDSR Death Surveillance</h4>
                            <p class="mb-0 text-muted">Maternal and Perinatal Death Surveillance and Response</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-dark text-white" wire:click="openPrintableReview"
                                wire:loading.attr="disabled" wire:target="openPrintableReview">
                                <span wire:loading.remove wire:target="openPrintableReview">Open Printable Review Sheet</span>
                                <span wire:loading wire:target="openPrintableReview">
                                    <span class="spinner-border spinner-border-sm me-1"></span>Opening...
                                </span>
                            </button>
                            <button type="button" class="btn btn-dark text-white" wire:click="exportSurveillanceCsv"
                                wire:loading.attr="disabled" wire:target="exportSurveillanceCsv">
                                <span wire:loading.remove wire:target="exportSurveillanceCsv">Export Surveillance CSV</span>
                                <span wire:loading wire:target="exportSurveillanceCsv">
                                    <span class="spinner-border spinner-border-sm me-1"></span>Exporting...
                                </span>
                            </button>
                            <button type="button" class="btn btn-primary" wire:click="refreshData"
                                wire:loading.attr="disabled" wire:target="refreshData">
                                <span wire:loading.remove wire:target="refreshData">Refresh Data</span>
                                <span wire:loading wire:target="refreshData">
                                    <span class="spinner-border spinner-border-sm me-1"></span>Refreshing...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (count($availableFacilities) > 0)
            <div class="row mb-4">
                <div class="col-md-8">
                    <label class="form-label">Filter by Facility</label>
                    <select wire:model.live="selectedFacilityId" class="form-select">
                        <option value="">All Facilities</option>
                        @foreach ($availableFacilities as $facility)
                            <option value="{{ $facility['id'] }}">
                                {{ $facility['name'] }} - {{ $facility['lga'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    @if ($selectedFacilityId)
                        <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetToScope">
                            View All Facilities
                        </button>
                    @endif
                </div>
            </div>
        @endif

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Surveillance Filters</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">From Date</label>
                                <input wire:model.live="dateFrom" type="date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">To Date</label>
                                <input wire:model.live="dateTo" type="date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Death Type View</label>
                                <select wire:model.live="deathType" class="form-select">
                                    <option value="all">All Deaths</option>
                                    <option value="maternal">Maternal Only</option>
                                    <option value="perinatal">Perinatal Only</option>
                                    <option value="stillbirth">Stillbirths Only</option>
                                    <option value="neonatal">Neonatal Only</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="metric-card metric-card-rose h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Maternal Deaths</div>
                        <span class="metric-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="7" r="4"></circle>
                                <path d="M5 22a7 7 0 0 1 14 0"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $totalMaternalDeaths }}</div>
                    <div class="small">MMR: {{ $maternalMortalityRatio }} per 100,000 live births</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="metric-card metric-card-amber h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Perinatal Deaths</div>
                        <span class="metric-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7h18"></path>
                                <path d="M5 7v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7"></path>
                                <path d="M9 11h6"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $totalPerinatalDeaths }}</div>
                    <div class="small">PMR: {{ $perinatalMortalityRate }} per 1,000 births</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="metric-card metric-card-slate h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Stillbirths</div>
                        <span class="metric-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2v20"></path>
                                <path d="M5 8h14"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $totalStillbirths }}</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="metric-card metric-card-sky h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Neonatal</div>
                        <span class="metric-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="9" r="3"></circle>
                                <path d="M6 21a6 6 0 0 1 12 0"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $totalNeonatalDeaths }}</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="metric-card metric-card-violet h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Review Cover</div>
                        <span class="metric-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 12 2 2 4-4"></path>
                                <circle cx="12" cy="12" r="9"></circle>
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $reviewCoverageRate }}%</div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Deaths Over Time</h5>
                    </div>
                    <div class="card-body">
                        @if (count($deathsByTimePeriod) > 0)
                            <canvas id="deathsTrendChart" style="max-height: 300px;"></canvas>
                        @else
                            <div class="text-center py-5 text-muted">No trend data in selected window.</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Deaths by Probable Cause</h5>
                    </div>
                    <div class="card-body">
                        @if (count($deathsByCause) > 0)
                            <canvas id="deathsByCauseChart" style="max-height: 300px;"></canvas>
                        @else
                            <div class="text-center py-5 text-muted">No cause data in selected window.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Surveillance Issue Queue</h5>
                        <span class="badge bg-danger">{{ $criticalIssuesCount }} Critical</span>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="surveillanceIssuesTable" class="table align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Case Type</th>
                                    <th>Patient</th>
                                    <th>DIN</th>
                                    <th>Facility</th>
                                    <th>Date</th>
                                    <th>Issue</th>
                                    <th>Severity</th>
                                    <th>Recommended Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($surveillanceIssues as $item)
                                    <tr>
                                        <td>{{ $item['case_type'] }}</td>
                                        <td>{{ $item['patient_name'] }}</td>
                                        <td>{{ $item['din'] }}</td>
                                        <td>{{ $item['facility'] }}</td>
                                        <td>{{ $item['death_date'] }}</td>
                                        <td>{{ $item['issue'] }}</td>
                                        <td>
                                            <span class="badge {{ $item['severity'] === 'High' ? 'bg-danger' : 'bg-warning' }}">
                                                {{ $item['severity'] }}
                                            </span>
                                        </td>
                                        <td>{{ $item['recommended_action'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No surveillance issues detected for the selected filter.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Deaths by Facility</h5>
                    </div>
                    <div class="card-datatable table-responsive pt-0" wire:ignore>
                        <table id="deathsByFacilityTable" class="table align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Facility</th>
                                    <th>LGA</th>
                                    <th class="text-center">Maternal</th>
                                    <th class="text-center">Perinatal</th>
                                    <th class="text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($deathsByFacility as $row)
                                    <tr>
                                        <td>{{ $row['facility_name'] }}</td>
                                        <td>{{ $row['lga'] }}</td>
                                        <td class="text-center">{{ $row['maternal_deaths'] }}</td>
                                        <td class="text-center">{{ $row['perinatal_deaths'] }}</td>
                                        <td class="text-center"><strong>{{ $row['total_deaths'] }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No facility death data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if (count($filteredMaternalDeaths) > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Maternal Death Records</h5>
                        </div>
                        <div class="card-datatable table-responsive pt-0" wire:ignore>
                            <table id="maternalDeathsTable" class="table align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Patient</th>
                                        <th>DIN</th>
                                        <th>Age</th>
                                        @if (!$selectedFacilityId)
                                            <th>Facility</th>
                                            <th>LGA</th>
                                        @endif
                                        <th>Date</th>
                                        <th>Probable Cause</th>
                                        <th>Mode</th>
                                        <th>Place</th>
                                        <th>Contributing Factors</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($filteredMaternalDeaths as $death)
                                        <tr>
                                            <td>{{ $death['patient_name'] }}</td>
                                            <td>{{ $death['patient_din'] }}</td>
                                            <td>{{ $death['age'] }}</td>
                                            @if (!$selectedFacilityId)
                                                <td>{{ $death['facility_name'] }}</td>
                                                <td>{{ $death['lga'] }}</td>
                                            @endif
                                            <td>{{ $death['death_date'] }}</td>
                                            <td>{{ $death['probable_cause'] }}</td>
                                            <td>{{ $death['mode_of_delivery'] }}</td>
                                            <td>{{ $death['place_of_death'] }}</td>
                                            <td>{{ implode('; ', $death['contributing_factors']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (count($filteredPerinatalDeaths) > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Perinatal Death Records</h5>
                        </div>
                        <div class="card-datatable table-responsive pt-0" wire:ignore>
                            <table id="perinatalDeathsTable" class="table align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Mother</th>
                                        <th>Mother DIN</th>
                                        <th>Age</th>
                                        @if (!$selectedFacilityId)
                                            <th>Facility</th>
                                            <th>LGA</th>
                                        @endif
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Baby Sex</th>
                                        <th>Weight (kg)</th>
                                        <th>Gest. Age (weeks)</th>
                                        <th>Probable Cause</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($filteredPerinatalDeaths as $death)
                                        <tr>
                                            <td>{{ $death['mother_name'] }}</td>
                                            <td>{{ $death['mother_din'] }}</td>
                                            <td>{{ $death['mother_age'] }}</td>
                                            @if (!$selectedFacilityId)
                                                <td>{{ $death['facility_name'] }}</td>
                                                <td>{{ $death['lga'] }}</td>
                                            @endif
                                            <td>{{ $death['death_date'] }}</td>
                                            <td>{{ $death['death_type'] }}</td>
                                            <td>{{ $death['baby_sex'] }}</td>
                                            <td>{{ $death['baby_weight'] ?? '-' }}</td>
                                            <td>{{ $death['gestational_age'] ?? '-' }}</td>
                                            <td>{{ $death['probable_cause'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            let mpdsrTrendChart = null;
            let mpdsrCauseChart = null;

            function renderMpdsrCharts() {
                try {
                    const trendCanvas = document.getElementById('deathsTrendChart');
                    const causeCanvas = document.getElementById('deathsByCauseChart');

                    if (mpdsrTrendChart) {
                        mpdsrTrendChart.destroy();
                        mpdsrTrendChart = null;
                    }
                    if (mpdsrCauseChart) {
                        mpdsrCauseChart.destroy();
                        mpdsrCauseChart = null;
                    }

                    const trendData = @json($deathsByTimePeriod);
                    if (trendCanvas && trendData.length > 0) {
                        mpdsrTrendChart = new Chart(trendCanvas, {
                            type: 'line',
                            data: {
                                labels: trendData.map(item => item.period),
                                datasets: [{
                                        label: 'Maternal Deaths',
                                        data: trendData.map(item => item.maternal_deaths),
                                        borderColor: '#dc2626',
                                        backgroundColor: 'rgba(220,38,38,0.08)',
                                        borderWidth: 2,
                                        fill: true,
                                        tension: 0.3
                                    },
                                    {
                                        label: 'Perinatal Deaths',
                                        data: trendData.map(item => item.perinatal_deaths),
                                        borderColor: '#d97706',
                                        backgroundColor: 'rgba(217,119,6,0.08)',
                                        borderWidth: 2,
                                        fill: true,
                                        tension: 0.3
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        }
                                    }
                                }
                            }
                        });
                    }

                    const causeData = @json($deathsByCause);
                    if (causeCanvas && causeData.length > 0) {
                        const topCauses = causeData.slice(0, 8);
                        mpdsrCauseChart = new Chart(causeCanvas, {
                            type: 'bar',
                            data: {
                                labels: topCauses.map(item => item.cause),
                                datasets: [{
                                        label: 'Maternal',
                                        data: topCauses.map(item => item.maternal_count),
                                        backgroundColor: 'rgba(220,38,38,0.85)'
                                    },
                                    {
                                        label: 'Perinatal',
                                        data: topCauses.map(item => item.perinatal_count),
                                        backgroundColor: 'rgba(217,119,6,0.85)'
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        }
                                    }
                                }
                            }
                        });
                    }
                } catch (error) {
                    console.error('MPDSR chart rendering error:', error);
                }
            }

            function scheduleMpdsrCharts() {
                setTimeout(renderMpdsrCharts, 80);
            }

            if (!window.__mpdsrChartsBoundV2) {
                window.__mpdsrChartsBoundV2 = true;
                document.addEventListener('DOMContentLoaded', scheduleMpdsrCharts);
                document.addEventListener('livewire:navigated', scheduleMpdsrCharts);
                document.addEventListener('livewire:initialized', () => {
                    Livewire.on('refresh-charts', scheduleMpdsrCharts);
                });
            }
            scheduleMpdsrCharts();
        </script>

        @include('_partials.datatables-init-multi', [
            'tableIds' => ['surveillanceIssuesTable', 'deathsByFacilityTable', 'maternalDeathsTable', 'perinatalDeathsTable'],
            'orders' => [
                'surveillanceIssuesTable' => [4, 'desc'],
                'deathsByFacilityTable' => [4, 'desc'],
                'maternalDeathsTable' => [5, 'desc'],
                'perinatalDeathsTable' => [5, 'desc'],
            ],
        ])
    </div>
</div>
