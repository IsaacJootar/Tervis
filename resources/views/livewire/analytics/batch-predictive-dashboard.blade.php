<div class="analytics-page">
    @include('livewire.analytics._template-style')
    @section('title', 'AI Predictive Analytics Dashboard')
    <div class="predictive-analytics-container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1"><i class='bx bx-trending-up me-2'></i>AI Predictive Analytics Dashboard</h4>
                            <p class="mb-0 text-muted">AI-powered predictions and insights for proactive healthcare planning.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-label-primary">{{ $user->first_name }} {{ $user->last_name }}</span>
                            <span class="badge bg-label-info">{{ $facilityCount }} Facilities</span>
                            <span class="badge bg-label-secondary">{{ \Carbon\Carbon::now('Africa/Lagos')->format('h:i A') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @php
            $activePanels =
                ($showRiskPredictions ? 1 : 0) +
                ($showServiceUtilization ? 1 : 0) +
                ($showResourceNeeds ? 1 : 0) +
                ($showOutcomes ? 1 : 0) +
                ($showSeasonalPatterns ? 1 : 0) +
                ($showInterventions ? 1 : 0);
            $scopeLabel =
                !empty($selectedFacilityId) && $facilities->firstWhere('id', $selectedFacilityId)
                    ? $facilities->firstWhere('id', $selectedFacilityId)->name
                    : 'All Facilities';
        @endphp
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="metric-card metric-card-violet h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Facilities</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4.5 19.5V6.8c0-.7.6-1.3 1.3-1.3h4.4c.7 0 1.3.6 1.3 1.3v12.7M13.5 19.5V4.8c0-.7.6-1.3 1.3-1.3h3.4c.7 0 1.3.6 1.3 1.3v14.7M8 9h.01M8 12h.01M8 15h.01M17 8h.01M17 11h.01M17 14h.01"
                                    stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $facilityCount }}</div>
                    <div class="small">In your scope</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="metric-card metric-card-sky h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Horizon</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="4.5" y="5.5" width="15" height="14" rx="2" stroke="currentColor"
                                    stroke-width="1.6" />
                                <path d="M8 3.8v3M16 3.8v3M8 11h8M8 14h5" stroke="currentColor"
                                    stroke-width="1.6" stroke-linecap="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ (int) $predictionHorizon }}</div>
                    <div class="small">Days forecast window</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="metric-card metric-card-amber h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Scope</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4.5 6.5l5-2 5 2 5-2v13l-5 2-5-2-5 2v-13z" stroke="currentColor"
                                    stroke-width="1.6" stroke-linejoin="round" />
                                <path d="M9.5 4.5v13M14.5 6.5v13" stroke="currentColor" stroke-width="1.6"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ !empty($selectedFacilityId) ? '1' : (int) $facilityCount }}</div>
                    <div class="small">{{ $scopeLabel }}</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="metric-card metric-card-emerald h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="metric-label">Active Panels</div>
                        <span class="metric-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="4.5" y="5" width="6.5" height="6.5" rx="1.3" stroke="currentColor"
                                    stroke-width="1.6" />
                                <rect x="13" y="5" width="6.5" height="6.5" rx="1.3" stroke="currentColor"
                                    stroke-width="1.6" />
                                <rect x="4.5" y="12.5" width="6.5" height="6.5" rx="1.3" stroke="currentColor"
                                    stroke-width="1.6" />
                                <rect x="13" y="12.5" width="6.5" height="6.5" rx="1.3" stroke="currentColor"
                                    stroke-width="1.6" />
                            </svg>
                        </span>
                    </div>
                    <div class="metric-value">{{ $activePanels }}</div>
                    <div class="small">Prediction sections enabled</div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="row mb-4">
            <div class="col-md-4">
                <label class="form-label">Prediction Horizon</label>
                <select wire:model.live="predictionHorizon" class="form-select">
                    <option value="7">7 Days (1 Week)</option>
                    <option value="14">14 Days (2 Weeks)</option>
                    <option value="30">30 Days (1 Month)</option>
                    <option value="60">60 Days (2 Months)</option>
                    <option value="90">90 Days (3 Months)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Select Facility (Optional)</label>
                <select wire:model="selectedFacilityId" class="form-select">
                    <option value="">All Facilities in Scope</option>
                    @foreach ($facilities as $facility)
                        <option value="{{ $facility->id }}">{{ $facility->name }} ({{ $facility->lga }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button wire:click="generatePredictions" class="btn btn-primary flex-grow-1"
                    wire:loading.attr="disabled" wire:target="generatePredictions">
                    <span wire:loading.remove wire:target="generatePredictions">
                        <i class="bx bx-trending-up me-1"></i>
                        Generate Predictions
                    </span>
                    <span wire:loading wire:target="generatePredictions">
                        <span class="spinner-border spinner-border-sm me-1"></span>
                        Analyzing...
                    </span>
                </button>
                @if (!empty($predictions))
                    <button wire:click="clearPredictions" class="btn btn-outline-secondary">
                        <i class="bx bx-x"></i>
                    </button>
                @endif
            </div>
        </div>

        @if (!empty($predictions) && !isset($predictions['error']))
            <!-- Risk Predictions -->
            @if ($showRiskPredictions && isset($predictions['risk_predictions']['risk_levels']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-error-circle me-2"></i>
                                    Risk Level Predictions ({{ $predictionHorizon }} days)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach ($predictions['risk_predictions']['risk_levels'] as $level => $data)
                                        <div class="col-md-3 mb-3">
                                            <div
                                                class="card border-{{ $level === 'critical' ? 'danger' : ($level === 'high' ? 'warning' : ($level === 'moderate' ? 'info' : 'success')) }}">
                                                <div class="card-body text-center">
                                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                                        <h6 class="text-uppercase text-muted mb-0">{{ $level }} Risk</h6>
                                                        <span class="metric-icon" aria-hidden="true">
                                                            @if ($level === 'critical')
                                                                <svg viewBox="0 0 24 24" fill="none">
                                                                    <path d="M12 4.5l8 14H4l8-14z" stroke="currentColor" stroke-width="1.7"
                                                                        stroke-linejoin="round" />
                                                                    <path d="M12 9v4M12 15.5h.01" stroke="currentColor" stroke-width="1.8"
                                                                        stroke-linecap="round" />
                                                                </svg>
                                                            @elseif ($level === 'high')
                                                                <svg viewBox="0 0 24 24" fill="none">
                                                                    <path d="M5 16l4.2-4.2 3.1 3.1L19 8.2" stroke="currentColor" stroke-width="1.8"
                                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                                    <path d="M14.5 8.2H19v4.5" stroke="currentColor" stroke-width="1.8"
                                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                                </svg>
                                                            @elseif ($level === 'moderate')
                                                                <svg viewBox="0 0 24 24" fill="none">
                                                                    <path d="M6 12h12" stroke="currentColor" stroke-width="1.8"
                                                                        stroke-linecap="round" />
                                                                </svg>
                                                            @else
                                                                <svg viewBox="0 0 24 24" fill="none">
                                                                    <circle cx="12" cy="12" r="8.5" stroke="currentColor"
                                                                        stroke-width="1.7" />
                                                                    <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor"
                                                                        stroke-width="1.8" stroke-linecap="round"
                                                                        stroke-linejoin="round" />
                                                                </svg>
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <h3 class="mb-2">
                                                        {{ $data['predicted_value'] }}
                                                        <small class="text-muted fs-6">cases</small>
                                                    </h3>
                                                    <div class="mb-2">
                                                        <span
                                                            class="badge bg-label-{{ $data['trend_direction'] === 'increasing' ? 'danger' : ($data['trend_direction'] === 'decreasing' ? 'success' : 'secondary') }}">
                                                            <i
                                                                class="bx bx-{{ $data['trend_direction'] === 'increasing' ? 'trending-up' : ($data['trend_direction'] === 'decreasing' ? 'trending-down' : 'minus') }}"></i>
                                                            {{ $data['trend_direction'] }}
                                                        </span>
                                                    </div>
                                                    <small class="text-muted">
                                                        @if ($data['change_percentage'] > 0)
                                                            +{{ $data['change_percentage'] }}%
                                                        @elseif($data['change_percentage'] < 0)
                                                            {{ $data['change_percentage'] }}%
                                                        @else
                                                            No change
                                                        @endif
                                                    </small>
                                                    <div class="mt-2">
                                                        <span class="badge bg-label-info">{{ $data['confidence'] }}
                                                            confidence</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if (!empty($predictions['risk_predictions']['recommendations']))
                                    <div class="alert alert-info mt-3">
                                        <strong><i class="bx bx-info-circle me-1"></i>Recommendations:</strong>
                                        <ul class="mb-0 mt-2">
                                            @foreach ($predictions['risk_predictions']['recommendations'] as $recommendation)
                                                <li>{{ $recommendation }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Service Utilization Predictions -->
            @if ($showServiceUtilization && isset($predictions['service_utilization']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-health me-2"></i>
                                    Service Utilization Forecast
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach ($predictions['service_utilization'] as $service => $data)
                                        <div class="col-md-4 mb-3">
                                            <div class="border rounded p-3">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <h6 class="text-uppercase text-muted mb-0">{{ ucfirst($service) }}</h6>
                                                    <span class="metric-icon" aria-hidden="true">
                                                        @if ($service === 'antenatal')
                                                            <svg viewBox="0 0 24 24" fill="none">
                                                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8"
                                                                    stroke-linecap="round" />
                                                            </svg>
                                                        @elseif ($service === 'delivery')
                                                            <svg viewBox="0 0 24 24" fill="none">
                                                                <path d="M6 13h10a3 3 0 0 0 0-6H9" stroke="currentColor" stroke-width="1.8"
                                                                    stroke-linecap="round" />
                                                                <circle cx="9" cy="18" r="1.8" stroke="currentColor" stroke-width="1.6" />
                                                                <circle cx="16" cy="18" r="1.8" stroke="currentColor" stroke-width="1.6" />
                                                            </svg>
                                                        @elseif ($service === 'postnatal')
                                                            <svg viewBox="0 0 24 24" fill="none">
                                                                <path d="M12 20s-6-3.8-8-7.2C2.2 9.7 4 6 7.7 6c1.8 0 3.1.9 4.3 2.4C13.2 6.9 14.5 6 16.3 6 20 6 21.8 9.7 20 12.8 18 16.2 12 20 12 20z"
                                                                    stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                                                            </svg>
                                                        @else
                                                            <svg viewBox="0 0 24 24" fill="none">
                                                                <rect x="4.5" y="5.5" width="15" height="14" rx="2" stroke="currentColor"
                                                                    stroke-width="1.6" />
                                                                <path d="M8 3.8v3M16 3.8v3M8.5 12.2l2.1 2.1 4.4-4.4" stroke="currentColor"
                                                                    stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-end mb-2">
                                                    <div>
                                                        <small class="text-muted d-block">Current Average</small>
                                                        <h5 class="mb-0">{{ $data['current_daily_average'] }}</h5>
                                                    </div>
                                                    <i class="bx bx-right-arrow-alt bx-sm text-muted"></i>
                                                    <div class="text-end">
                                                        <small class="text-muted d-block">Predicted Average</small>
                                                        <h5 class="mb-0 text-primary">
                                                            {{ $data['predicted_daily_average'] }}</h5>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small class="text-muted">Predicted Total
                                                            ({{ $predictionHorizon }} days)
                                                        </small>
                                                        <span
                                                            class="badge bg-label-primary">{{ $data['predicted_total'] }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">Trend</small>
                                                        <span
                                                            class="badge bg-label-{{ $data['trend_direction'] === 'increasing' ? 'success' : ($data['trend_direction'] === 'decreasing' ? 'warning' : 'secondary') }}">
                                                            {{ $data['trend_direction'] }}
                                                        </span>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                                        <small class="text-muted">Confidence</small>
                                                        <span
                                                            class="badge bg-label-info">{{ $data['confidence'] }}</span>
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

            <!-- Resource Requirements -->
            @if ($showResourceNeeds && isset($predictions['resource_requirements']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-package me-2"></i>
                                    Resource Requirements Forecast
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @if (isset($predictions['resource_requirements']['staffing']))
                                        <div class="col-md-4 mb-3">
                                            <h6 class="mb-3">Staffing Needs</h6>
                                            <ul class="list-group">
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    Additional Nurses
                                                    <span class="badge bg-primary rounded-pill">
                                                        {{ $predictions['resource_requirements']['staffing']['additional_nurses_needed'] }}
                                                    </span>
                                                </li>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    Specialist Hours
                                                    <span class="badge bg-primary rounded-pill">
                                                        {{ $predictions['resource_requirements']['staffing']['specialist_hours_needed'] }}hrs
                                                    </span>
                                                </li>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    Equipment Utilization
                                                    <span class="badge bg-info rounded-pill">
                                                        {{ round($predictions['resource_requirements']['staffing']['monitoring_equipment_utilization']) }}%
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                    @endif

                                    @if (isset($predictions['resource_requirements']['bed_capacity']))
                                        <div class="col-md-4 mb-3">
                                            <h6 class="mb-3">Bed Capacity</h6>
                                            <ul class="list-group">
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    Delivery Beds
                                                    <span class="badge bg-success rounded-pill">
                                                        {{ $predictions['resource_requirements']['bed_capacity']['delivery_beds_needed'] }}
                                                    </span>
                                                </li>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    Postnatal Beds
                                                    <span class="badge bg-success rounded-pill">
                                                        {{ $predictions['resource_requirements']['bed_capacity']['postnatal_beds_needed'] }}
                                                    </span>
                                                </li>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    Predicted Occupancy
                                                    <span class="badge bg-warning rounded-pill">
                                                        {{ round($predictions['resource_requirements']['bed_capacity']['occupancy_rate_prediction']) }}%
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                    @endif

                                    @if (isset($predictions['resource_requirements']['supplies']))
                                        <div class="col-md-4 mb-3">
                                            <h6 class="mb-3">Supply Requirements</h6>
                                            <ul class="list-group">
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    Emergency Kits
                                                    <span class="badge bg-danger rounded-pill">
                                                        {{ $predictions['resource_requirements']['supplies']['emergency_kits_needed'] }}
                                                    </span>
                                                </li>
                                                <li class="list-group-item">
                                                    <small class="text-muted d-block">Medication Stock
                                                        Multiplier</small>
                                                    <strong>{{ $predictions['resource_requirements']['supplies']['medication_stock_multiplier'] }}x</strong>
                                                </li>
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Health Outcomes Predictions -->
            @if ($showOutcomes && isset($predictions['outcome_forecasts']) && !isset($predictions['outcome_forecasts']['error']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-heart me-2"></i>
                                    Health Outcomes Forecast
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Outcome</th>
                                                <th class="text-center">Current Rate</th>
                                                <th class="text-center">Predicted Rate</th>
                                                <th class="text-center">Change</th>
                                                <th class="text-center">Risk Level</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($predictions['outcome_forecasts'] as $outcome => $data)
                                                <tr>
                                                    <td><strong>{{ ucwords(str_replace('_', ' ', $outcome)) }}</strong>
                                                    </td>
                                                    <td class="text-center">{{ $data['current_rate'] }}%</td>
                                                    <td class="text-center">
                                                        <strong
                                                            class="text-primary">{{ $data['predicted_rate'] }}%</strong>
                                                    </td>
                                                    <td class="text-center">
                                                        <span
                                                            class="badge bg-label-{{ $data['change_percentage'] > 0 ? 'warning' : ($data['change_percentage'] < 0 ? 'success' : 'secondary') }}">
                                                            {{ $data['change_percentage'] > 0 ? '+' : '' }}{{ $data['change_percentage'] }}%
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span
                                                            class="badge bg-{{ $data['risk_level'] === 'high' ? 'danger' : ($data['risk_level'] === 'improving' ? 'success' : 'secondary') }}">
                                                            {{ $data['risk_level'] }}
                                                        </span>
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

            <!-- Seasonal Patterns -->
            @if (
                $showSeasonalPatterns &&
                    isset($predictions['seasonal_patterns']) &&
                    !isset($predictions['seasonal_patterns']['error']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-calendar me-2"></i>
                                    Seasonal Patterns Analysis
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="text-center p-3 border rounded">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h6 class="text-muted mb-0">Peak Risk Month</h6>
                                                <span class="metric-icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M5 16l4.2-4.2 3.1 3.1L19 8.2" stroke="currentColor" stroke-width="1.8"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M14.5 8.2H19v4.5" stroke="currentColor" stroke-width="1.8"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </span>
                                            </div>
                                            <h4>{{ \Carbon\Carbon::create()->month($predictions['seasonal_patterns']['peak_risk_month'])->format('F') }}
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center p-3 border rounded">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h6 class="text-muted mb-0">Lowest Risk Month</h6>
                                                <span class="metric-icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M5 13.8l4.2 4.2 3.1-3.1L19 21.6" stroke="currentColor" stroke-width="1.8"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M14.5 21.6H19v-4.5" stroke="currentColor" stroke-width="1.8"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </span>
                                            </div>
                                            <h4>{{ \Carbon\Carbon::create()->month($predictions['seasonal_patterns']['lowest_risk_month'])->format('F') }}
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center p-3 border rounded">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h6 class="text-muted mb-0">Seasonal Variance</h6>
                                                <span class="metric-icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M6 17l3.5-4.5 2.8 2.8L18 8" stroke="currentColor" stroke-width="1.8"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <circle cx="6" cy="17" r="1.2" fill="currentColor" />
                                                        <circle cx="9.5" cy="12.5" r="1.2" fill="currentColor" />
                                                        <circle cx="12.3" cy="15.3" r="1.2" fill="currentColor" />
                                                        <circle cx="18" cy="8" r="1.2" fill="currentColor" />
                                                    </svg>
                                                </span>
                                            </div>
                                            <h4>{{ $predictions['seasonal_patterns']['seasonal_variance'] }}%</h4>
                                        </div>
                                    </div>
                                </div>

                                @if (!empty($predictions['seasonal_patterns']['recommendations']))
                                    <div class="alert alert-info">
                                        <strong><i class="bx bx-info-circle me-1"></i>Seasonal
                                            Recommendations:</strong>
                                        <ul class="mb-0 mt-2">
                                            @foreach ($predictions['seasonal_patterns']['recommendations'] as $recommendation)
                                                <li>{{ $recommendation }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Intervention Opportunities -->
            @if ($showInterventions && !empty($predictions['intervention_opportunities']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-warning">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-bulb me-2"></i>
                                    Intervention Opportunities
                                </h5>
                            </div>
                            <div class="card-body">
                                @foreach ($predictions['intervention_opportunities'] as $opportunity)
                                    <div
                                        class="card mb-3 border-{{ $opportunity['priority'] === 'high' ? 'danger' : 'warning' }}">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0">{{ $opportunity['title'] }}</h6>
                                                <span
                                                    class="badge bg-{{ $opportunity['priority'] === 'high' ? 'danger' : 'warning' }}">
                                                    {{ strtoupper($opportunity['priority']) }} PRIORITY
                                                </span>
                                            </div>
                                            <p class="text-muted mb-2">{{ $opportunity['description'] }}</p>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <small class="text-muted d-block">Intervention</small>
                                                    <strong>{{ $opportunity['intervention'] }}</strong>
                                                </div>
                                                <div class="col-md-4">
                                                    <small class="text-muted d-block">Expected Impact</small>
                                                    <strong
                                                        class="text-success">{{ $opportunity['expected_impact'] }}</strong>
                                                </div>
                                                <div class="col-md-4">
                                                    <small class="text-muted d-block">Timeline</small>
                                                    <strong>{{ $opportunity['timeline'] }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @elseif(!empty($predictions) && isset($predictions['error']))
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="bx bx-info-circle me-2"></i>
                        {{ $predictions['error'] }}
                    </div>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bx bx-trending-up bx-lg text-muted mb-3"></i>
                            <h6 class="text-muted mb-2">No Predictions Yet</h6>
                            <p class="text-muted mb-0">Select horizon and scope, then click "Generate Predictions".</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

