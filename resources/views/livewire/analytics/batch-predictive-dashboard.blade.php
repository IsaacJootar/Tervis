<div>
    <div class="predictive-analytics-container">
        <!-- Hero Card Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="hero-card">
                    <div class="hero-content">
                        <div class="hero-text">
                            <h4 class="hero-title" style="color: white; font-size: 30px;">
                                <i class='bx bx-trending-up me-2'></i>
                                Predictive Analytics Dashboard
                            </h4>

                            <div class="d-flex flex-wrap gap-3 text-white mb-1">
                                <span>
                                    <i class="bx bx-user-circle me-1"></i>
                                    <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>
                                </span>
                                <span>
                                    <i class="bx bx-building-house me-1"></i>
                                    <strong>Facilities:</strong> {{ $facilityCount }}
                                </span>
                                <span>
                                    <i class="bx bx-time me-1"></i>
                                    {{ \Carbon\Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
                                </span>
                            </div>

                            <p class="text-white-50 mb-0">
                                AI-powered predictions and insights for proactive healthcare planning
                            </p>
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
                            <div class="card-header bg-label-primary">
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
                                                    <h6 class="text-uppercase text-muted">{{ $level }} Risk</h6>
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
                            <div class="card-header bg-label-success">
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
                                                <h6 class="text-uppercase text-muted mb-3">{{ ucfirst($service) }}</h6>
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
                            <div class="card-header bg-label-warning">
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
                            <div class="card-header bg-label-info">
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
                            <div class="card-header bg-label-secondary">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-calendar me-2"></i>
                                    Seasonal Patterns Analysis
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="text-center p-3 border rounded">
                                            <h6 class="text-muted">Peak Risk Month</h6>
                                            <h4>{{ \Carbon\Carbon::create()->month($predictions['seasonal_patterns']['peak_risk_month'])->format('F') }}
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center p-3 border rounded">
                                            <h6 class="text-muted">Lowest Risk Month</h6>
                                            <h4>{{ \Carbon\Carbon::create()->month($predictions['seasonal_patterns']['lowest_risk_month'])->format('F') }}
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center p-3 border rounded">
                                            <h6 class="text-muted">Seasonal Variance</h6>
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
                            <div class="card-header bg-label-warning">
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
                    <div class="alert alert-info text-center">
                        <i class="bx bx-info-circle me-2"></i>
                        Select a prediction horizon and click "Generate Predictions" to see AI-powered forecasts
                    </div>
                </div>
            </div>
        @endif

        <style>
            .hero-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 20px;
                overflow: hidden;
                position: relative;
                min-height: 180px;
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

            .card {
                box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
                transition: all 0.3s ease;
            }

            .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px 0 rgba(67, 89, 113, 0.16);
            }
        </style>
    </div>
</div>
