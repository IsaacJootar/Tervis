<div>
    <div>
        @php
            use Carbon\Carbon;
        @endphp
        @section('title', 'MPDSR Report Dashboard')

        <!-- Hero Card Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="hero-card">
                    <div class="hero-content">
                        <div class="hero-text">
                            <h4 class="hero-title" style="color: white; font-size: 28px;">
                                <i class='bx bx-health me-2'></i>
                                Maternal and Perinatal Death Surveillance and Response (MPDSR)
                            </h4>
                            <span>
                                <i class="bx bx-time me-1"></i>
                                <strong>Time:</strong>
                                {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                            </span>
                            <div class="hero-stats">
                                <span class="hero-stat">
                                    <i class="bx bx-female"></i>
                                    {{ $totalMaternalDeaths }} Maternal Deaths
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-baby-carriage"></i>
                                    {{ $totalPerinatalDeaths }} Perinatal Deaths
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-heart-circle"></i>
                                    {{ $totalStillbirths }} Stillbirths
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-child"></i>
                                    {{ $totalNeonatalDeaths }} Neonatal Deaths
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-building"></i>
                                    @if ($selectedFacilityId)
                                        Single Facility
                                    @else
                                        {{ $scopeInfo['scope_type'] === 'state' ? 'State-wide' : ($scopeInfo['scope_type'] === 'lga' ? 'LGA-wide' : 'Single Facility') }}
                                        ({{ count($scopeInfo['facility_ids']) }}
                                        {{ count($scopeInfo['facility_ids']) == 1 ? 'facility' : 'facilities' }})
                                    @endif
                                </span>

                            </div>

                        </div>
                        <div class="hero-decoration">
                            <div class="floating-shape shape-1"></div>
                            <div class="floating-shape shape-2"></div>
                            <div class="floating-shape shape-3"></div>
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
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-danger">
                                    <i class="bx bx-female bx-sm text-white"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $totalMaternalDeaths }}</h5>
                                <small class="text-muted">Maternal Deaths</small>
                                <div class="mt-1">
                                    <small class="text-danger fw-bold">MMR: {{ $maternalMortalityRatio }}</small>
                                    <small class="text-muted d-block">per 100,000 live births</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-warning">
                                    <i class="bx bx-baby-carriage bx-sm text-white"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $totalPerinatalDeaths }}</h5>
                                <small class="text-muted">Total Perinatal Deaths</small>
                                <div class="mt-1">
                                    <small class="text-warning fw-bold">PMR: {{ $perinatalMortalityRate }}</small>
                                    <small class="text-muted d-block">per 1,000 births</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-secondary">
                                    <i class="bx bx-heart-circle bx-sm text-white"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $totalStillbirths }}</h5>
                                <small class="text-muted">Stillbirths</small>
                                <div class="mt-1">
                                    <small
                                        class="text-secondary">{{ $totalPerinatalDeaths > 0 ? round(($totalStillbirths / $totalPerinatalDeaths) * 100, 1) : 0 }}%
                                        of perinatal</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-info">
                                    <i class="bx bx-child bx-sm text-white"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $totalNeonatalDeaths }}</h5>
                                <small class="text-muted">Early Neonatal Deaths</small>
                                <div class="mt-1">
                                    <small
                                        class="text-info">{{ $totalPerinatalDeaths > 0 ? round(($totalNeonatalDeaths / $totalPerinatalDeaths) * 100, 1) : 0 }}%
                                        of perinatal</small>
                                </div>
                            </div>
                        </div>
                    </div>
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
                        <canvas id="deathsTrendChart" style="max-height: 300px;"></canvas>
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
                        <canvas id="deathsByCauseChart" style="max-height: 300px;"></canvas>
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

            document.addEventListener('DOMContentLoaded', function() {
                initializeCharts();
            });

            document.addEventListener('livewire:initialized', () => {
                Livewire.on('refresh-charts', () => {
                    setTimeout(() => {
                        initializeCharts();
                    }, 100);
                });
            });

            // Reinitialize charts when Livewire updates
            document.addEventListener('livewire:update', () => {
                setTimeout(() => {
                    initializeCharts();
                }, 100);
            });
        </script>

        <style>
            .hero-card {
                background: linear-gradient(135deg, #ea5455 0%, #ff6b6b 100%);
                border-radius: 20px;
                overflow: hidden;
                position: relative;
                min-height: 220px;
            }

            .hero-content {
                position: relative;
                z-index: 2;
                padding: 2rem;
            }

            .hero-decoration {
                position: absolute;
                top: 0;
                right: 0;
                width: 100%;
                height: 100%;
                overflow: hidden;
                z-index: 1;
            }

            .floating-shape {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.1);
                animation: float 6s ease-in-out infinite;
            }

            .floating-shape.shape-1 {
                width: 80px;
                height: 80px;
                top: 20%;
                right: 10%;
                animation-delay: 0s;
            }

            .floating-shape.shape-2 {
                width: 60px;
                height: 60px;
                top: 60%;
                right: 20%;
                animation-delay: 2s;
            }

            .floating-shape.shape-3 {
                width: 40px;
                height: 40px;
                top: 40%;
                right: 5%;
                animation-delay: 4s;
            }

            @keyframes float {

                0%,
                100% {
                    transform: translateY(0px) rotate(0deg);
                }

                50% {
                    transform: translateY(-20px) rotate(180deg);
                }
            }

            .hero-stats {
                display: flex;
                flex-wrap: wrap;
                gap: 2rem;
                margin-top: 1rem;
            }

            .hero-stat {
                display: flex;
                align-items: center;
                color: rgba(255, 255, 255, 0.95);
                font-weight: 500;
                font-size: 14px;
            }

            .hero-stat i {
                margin-right: 0.5rem;
                font-size: 18px;
            }

            .card {
                box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
                border: 1px solid rgba(67, 89, 113, 0.1);
                transition: all 0.3s ease;
            }

            .card:hover {
                transform: translateY </div> (-2px);
                box-shadow: 0 4px 12px 0 rgba(67, 89, 113, 0.16);
            }

            .avatar {
                position: relative;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
            }

            .avatar-initial {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
            }

            @media (max-width: 768px) {
                .hero-stats {
                    gap: 1rem;
                }

                .hero-stat {
                    font-size: 12px;
                }

                .card-body {
                    padding: 1rem;
                }
            }
        </style>

        @include('_partials.datatables-init')
    </div>
