@php
    use Carbon\Carbon;
@endphp

@section('title', 'Laboratory Operations')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Laboratory Operations</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-test-tube me-1"></i>Facility Laboratory Operations</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Workflow: Queue Intake -> Batch Processing -> QC -> Reagents -> Equipment Logs.</div>
            </div>
            <div class="ms-auto d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignSampleBatchModal">
                    Assign Sample to Batch
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reagentAdjustmentModal">
                    Reagent Adjustment
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Pending Orders</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M7 5h10M7 9h10M7 13h6M5 5h.01M5 9h.01M5 13h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['pending_orders'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-info h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Samples Received</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M10 4h4M12 4v6l4 6a3 3 0 01-2.6 4.5H10.6A3 3 0 018 16l4-6V4z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['samples_received'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-amber h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Processing</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 4v3M12 17v3M5 12H2M22 12h-3M6.3 6.3L4.2 4.2M19.8 19.8l-2.1-2.1M17.7 6.3l2.1-2.1M4.2 19.8l2.1-2.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['samples_processing'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Ready For Result</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M8 12.5l2.5 2.5L16 9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['ready_for_result'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-rose h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">QC Fail (30d)</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" />
                            <path d="M9.5 9.5l5 5M14.5 9.5l-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['qc_failed_last_30_days'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-warning h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Low Reagents</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['low_reagents'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-dark h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Out of Stock</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M8 8l8 8M16 8l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['out_of_stock_reagents'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card metric-card-violet h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Equipment Due (7d)</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" />
                            <path d="M12 8v4l2.5 1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['equipment_due_soon'] }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Step 1: Pending Order Queue (Sample Intake Source)</h5>
                </div>
                <div class="card-datatable table-responsive pt-0">
                    <table id="labPendingOrdersTable" class="table align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Requested At</th>
                                <th>Patient</th>
                                <th>Test</th>
                                <th>Specimen</th>
                                <th>Priority</th>
                                <th>Requested By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingOrders as $order)
                                @php
                                    $priorityClass = $order->priority === 'STAT' ? 'danger' : ($order->priority === 'Urgent' ? 'warning' : 'primary');
                                @endphp
                                <tr>
                                    <td data-order="{{ $order->requested_at?->format('Y-m-d H:i:s') }}">{{ $order->requested_at?->format('M d, Y h:i A') ?: 'N/A' }}</td>
                                    <td>
                                        {{ trim(($order->patient->first_name ?? '') . ' ' . ($order->patient->last_name ?? '')) ?: 'N/A' }}
                                        <br><small class="text-muted">DIN: {{ $order->patient->din ?? 'N/A' }}</small>
                                    </td>
                                    <td class="fw-semibold">{{ $order->test_name }}</td>
                                    <td>{{ $order->specimen ?: 'N/A' }}</td>
                                    <td><span class="badge bg-label-{{ $priorityClass }}">{{ $order->priority ?: 'Routine' }}</span></td>
                                    <td>{{ $order->requested_by ?: 'N/A' }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-light text-dark border"
                                            wire:click="preloadSampleFromOrder({{ $order->id }})"
                                            wire:loading.attr="disabled" wire:target="preloadSampleFromOrder({{ $order->id }})">
                                            Use For Intake
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No pending lab orders in this facility.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100" id="sampleIntakeCard">
                <div class="card-header">
                    <h5 class="mb-0">Step 1B: Sample Intake Form</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Source Order</label>
                            <input type="text" class="form-control"
                                value="{{ $sample_lab_test_order_id ? ('Pending Order #' . $sample_lab_test_order_id) : 'Manual / Not linked' }}"
                                readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Accession No.</label>
                            <input type="text" class="form-control" wire:model.live="sample_accession_no"
                                placeholder="Auto-generated if empty">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sample Status <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="sample_status">
                                @foreach ($sampleStatusOptions as $option)
                                    <option value="{{ $option }}">{{ ucwords(str_replace('_', ' ', $option)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Test Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model.live="sample_test_name" placeholder="e.g. Full Blood Count">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Specimen Type</label>
                            <input type="text" class="form-control" wire:model.live="sample_specimen_type" placeholder="e.g. Blood, Urine, Stool">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Collected At</label>
                            <input type="datetime-local" class="form-control" wire:model.live="sample_collected_at">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Received At <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" wire:model.live="sample_received_at">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Processing Batch</label>
                            <select class="form-select" wire:model.live="sample_processing_batch_id">
                                <option value="">Select batch (optional)</option>
                                @foreach ($batches->whereIn('status', ['scheduled', 'running']) as $batchOption)
                                    <option value="{{ $batchOption->id }}">{{ $batchOption->batch_code }} ({{ ucfirst($batchOption->status) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" rows="2" wire:model.live="sample_remarks" placeholder="Collection/handling notes"></textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearSampleIntakeForm"
                        wire:loading.attr="disabled" wire:target="clearSampleIntakeForm">
                        Clear
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="saveSampleIntake"
                        wire:loading.attr="disabled" wire:target="saveSampleIntake">
                        <span wire:loading.remove wire:target="saveSampleIntake">Save Intake</span>
                        <span wire:loading wire:target="saveSampleIntake"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Step 2: Processing Batch Creation</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Batch Code</label>
                    <input type="text" class="form-control" wire:model.live="batch_code" placeholder="Auto-generated if empty">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Test Profile</label>
                    <input type="text" class="form-control" wire:model.live="batch_test_profile" placeholder="e.g. Chemistry Panel">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Analyzer</label>
                    <input type="text" class="form-control" wire:model.live="batch_analyzer_name" placeholder="e.g. Sysmex XN-550">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Run Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" wire:model.live="batch_run_date">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" wire:model.live="batch_status">
                        @foreach ($batchStatusOptions as $option)
                            <option value="{{ $option }}">{{ ucwords($option) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notes</label>
                    <input type="text" class="form-control" wire:model.live="batch_notes" placeholder="Optional notes">
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" wire:click="clearBatchForm"
                wire:loading.attr="disabled" wire:target="clearBatchForm">Clear</button>
            <button type="button" class="btn btn-primary" wire:click="saveProcessingBatch"
                wire:loading.attr="disabled" wire:target="saveProcessingBatch">
                <span wire:loading.remove wire:target="saveProcessingBatch">Save Batch</span>
                <span wire:loading wire:target="saveProcessingBatch"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Step 2B: Processing Batches</h5>
        </div>
        <div class="card-datatable table-responsive pt-0">
            <table id="labBatchesTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Run Date</th>
                        <th>Batch Code</th>
                        <th>Profile / Analyzer</th>
                        <th>Status</th>
                        <th>Samples</th>
                        <th>Created By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        @php
                            $statusClass = match ($batch->status) {
                                'completed' => 'success',
                                'running' => 'warning',
                                'cancelled' => 'danger',
                                default => 'primary',
                            };
                        @endphp
                        <tr>
                            <td data-order="{{ $batch->run_date?->format('Y-m-d') }}">{{ $batch->run_date?->format('M d, Y') ?: 'N/A' }}</td>
                            <td class="fw-semibold">{{ $batch->batch_code }}</td>
                            <td>{{ $batch->test_profile ?: 'N/A' }}<br><small class="text-muted">{{ $batch->analyzer_name ?: 'N/A' }}</small></td>
                            <td><span class="badge bg-label-{{ $statusClass }}">{{ ucwords($batch->status) }}</span></td>
                            <td>{{ $batch->sample_count }}</td>
                            <td>{{ $batch->created_by ?: 'N/A' }}</td>
                            <td>
                                @if ($batch->status !== 'completed')
                                    <button type="button" class="btn btn-sm btn-light text-dark border"
                                        wire:click="markBatchCompleted({{ $batch->id }})"
                                        wire:loading.attr="disabled" wire:target="markBatchCompleted({{ $batch->id }})">
                                        Mark Completed
                                    </button>
                                @else
                                    <span class="text-muted small">Done</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No laboratory processing batches yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Step 3: Samples Tracking</h5>
        </div>
        <div class="card-datatable table-responsive pt-0">
            <table id="labSamplesTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Received At</th>
                        <th>Accession</th>
                        <th>Patient</th>
                        <th>Test / Specimen</th>
                        <th>Sample Status</th>
                        <th>Batch</th>
                        <th>Order Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($samples as $sample)
                        @php
                            $sampleStatusClass = match ($sample->sample_status) {
                                'reported' => 'success',
                                'ready_for_result' => 'info',
                                'processing' => 'warning',
                                'rejected' => 'danger',
                                default => 'primary',
                            };
                            $orderStatus = $sample->order?->status ?? 'N/A';
                            $orderClass = match ($orderStatus) {
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                'pending' => 'warning',
                                default => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td data-order="{{ $sample->received_at?->format('Y-m-d H:i:s') }}">{{ $sample->received_at?->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td class="fw-semibold">{{ $sample->accession_no }}</td>
                            <td>
                                {{ trim(($sample->patient->first_name ?? '') . ' ' . ($sample->patient->last_name ?? '')) ?: 'N/A' }}
                                <br><small class="text-muted">DIN: {{ $sample->patient->din ?? 'N/A' }}</small>
                            </td>
                            <td>{{ $sample->test_name }}<br><small class="text-muted">{{ $sample->specimen_type ?: 'N/A' }}</small></td>
                            <td><span class="badge bg-label-{{ $sampleStatusClass }}">{{ ucwords(str_replace('_', ' ', $sample->sample_status)) }}</span></td>
                            <td>{{ $sample->processingBatch?->batch_code ?: 'N/A' }}</td>
                            <td><span class="badge bg-label-{{ $orderClass }}">{{ ucwords($orderStatus) }}</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-light text-dark border"
                                    wire:click="openAssignBatchModal({{ $sample->id }})"
                                    wire:loading.attr="disabled" wire:target="openAssignBatchModal({{ $sample->id }})">
                                    Assign Batch
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No samples tracked yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Step 4: Quality Control (QC)</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">QC Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" wire:model.live="qc_date">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">QC Type <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="qc_type">
                                @foreach ($qcTypeOptions as $option)
                                    <option value="{{ $option }}">{{ ucwords($option) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Test Profile <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model.live="qc_test_profile" placeholder="e.g. Hematology Controls">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Control Level</label>
                            <input type="text" class="form-control" wire:model.live="qc_control_level" placeholder="e.g. Level 1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="qc_status">
                                @foreach ($qcStatusOptions as $option)
                                    <option value="{{ $option }}">{{ strtoupper($option) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected Range</label>
                            <input type="text" class="form-control" wire:model.live="qc_expected_range" placeholder="e.g. 4.0-5.5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Observed Value</label>
                            <input type="text" class="form-control" wire:model.live="qc_observed_value" placeholder="e.g. 5.1">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reviewed By</label>
                            <input type="text" class="form-control" wire:model.live="qc_reviewed_by">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" rows="2" wire:model.live="qc_remarks" placeholder="Any deviations/corrective actions"></textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearQcForm"
                        wire:loading.attr="disabled" wire:target="clearQcForm">Clear</button>
                    <button type="button" class="btn btn-primary" wire:click="saveQcLog"
                        wire:loading.attr="disabled" wire:target="saveQcLog">
                        <span wire:loading.remove wire:target="saveQcLog">Save QC Log</span>
                        <span wire:loading wire:target="saveQcLog"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">QC Log History</h5>
                </div>
                <div class="card-datatable table-responsive pt-0">
                    <table id="labQcLogsTable" class="table align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Profile</th>
                                <th>Expected</th>
                                <th>Observed</th>
                                <th>Status</th>
                                <th>Reviewed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($qcLogs as $log)
                                @php
                                    $statusClass = $log->status === 'pass' ? 'success' : ($log->status === 'fail' ? 'danger' : 'warning');
                                @endphp
                                <tr>
                                    <td data-order="{{ $log->qc_date?->format('Y-m-d') }}">{{ $log->qc_date?->format('M d, Y') ?: 'N/A' }}</td>
                                    <td>{{ strtoupper($log->qc_type) }}</td>
                                    <td>{{ $log->test_profile }}</td>
                                    <td>{{ $log->expected_range ?: 'N/A' }}</td>
                                    <td>{{ $log->observed_value ?: 'N/A' }}</td>
                                    <td><span class="badge bg-label-{{ $statusClass }}">{{ strtoupper($log->status) }}</span></td>
                                    <td>{{ $log->reviewed_by ?: 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No QC logs available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Step 5: Reagent Stock In</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Reagent Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" wire:model.live="reagent_name" placeholder="e.g. HBsAg Kit">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Lot Number</label>
                    <input type="text" class="form-control" wire:model.live="reagent_lot_number" placeholder="Optional lot no.">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Manufacturer</label>
                    <input type="text" class="form-control" wire:model.live="reagent_manufacturer" placeholder="Optional manufacturer">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantity In <span class="text-danger">*</span></label>
                    <input type="number" min="0.01" step="0.01" class="form-control" wire:model.live="reagent_quantity_received" placeholder="0.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unit <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" wire:model.live="reagent_unit" placeholder="e.g. kits, mL, packs">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Reorder Level</label>
                    <input type="number" min="0" step="0.01" class="form-control" wire:model.live="reagent_reorder_level" placeholder="0.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" class="form-control" wire:model.live="reagent_expiry_date">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" rows="2" wire:model.live="reagent_notes" placeholder="Optional stock notes"></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" wire:click="clearReagentStockInForm"
                wire:loading.attr="disabled" wire:target="clearReagentStockInForm">Clear</button>
            <button type="button" class="btn btn-primary" wire:click="saveReagentStockIn"
                wire:loading.attr="disabled" wire:target="saveReagentStockIn">
                <span wire:loading.remove wire:target="saveReagentStockIn">Save Stock In</span>
                <span wire:loading wire:target="saveReagentStockIn"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Reagent Inventory Overview</h5>
        </div>
        <div class="card-datatable table-responsive pt-0">
            <table id="labReagentsTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Reagent</th>
                        <th>Lot / Expiry</th>
                        <th>Unit</th>
                        <th>Available</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reagents as $reagent)
                        @php
                            $available = (float) $reagent->quantity_available;
                            $reorder = (float) $reagent->reorder_level;
                            $statusClass = 'success';
                            $statusLabel = 'Healthy';
                            if ($available <= 0) {
                                $statusClass = 'danger';
                                $statusLabel = 'Out of Stock';
                            } elseif ($available <= $reorder) {
                                $statusClass = 'warning';
                                $statusLabel = 'Low Stock';
                            }
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $reagent->reagent_name }}<br><small class="text-muted">{{ $reagent->manufacturer ?: 'N/A' }}</small></td>
                            <td>{{ $reagent->lot_number ?: 'N/A' }}<br><small class="text-muted">{{ $reagent->expiry_date?->format('M d, Y') ?: 'N/A' }}</small></td>
                            <td>{{ $reagent->unit }}</td>
                            <td>{{ number_format($available, 2) }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <input type="number" min="0" step="0.01" class="form-control form-control-sm"
                                        wire:model.defer="reorderLevels.{{ $reagent->id }}" style="max-width: 110px;">
                                    <button type="button" class="btn btn-sm btn-light text-dark border"
                                        wire:click="updateReagentReorderLevel({{ $reagent->id }})">Save</button>
                                </div>
                            </td>
                            <td><span class="badge bg-label-{{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-light text-dark border"
                                    wire:click="openReagentAdjustmentModal({{ $reagent->id }})"
                                    wire:loading.attr="disabled" wire:target="openReagentAdjustmentModal({{ $reagent->id }})">
                                    Adjust Qty
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No reagent stock records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Reagent Movement Log</h5>
        </div>
        <div class="card-datatable table-responsive pt-0">
            <table id="labReagentMovementsTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Time</th>
                        <th>Reagent</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Balance After</th>
                        <th>Reference</th>
                        <th>Moved By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reagentMovements as $move)
                        @php($qtyClass = (float) $move->quantity < 0 ? 'danger' : 'success')
                        <tr>
                            <td data-order="{{ $move->moved_at?->format('Y-m-d H:i:s') }}">{{ $move->moved_at?->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td>{{ $move->stock?->reagent_name ?: 'N/A' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $move->movement_type)) }}</td>
                            <td class="text-{{ $qtyClass }}">{{ number_format((float) $move->quantity, 2) }}</td>
                            <td>{{ number_format((float) $move->balance_after, 2) }}</td>
                            <td>{{ $move->reference_code ?: 'N/A' }}</td>
                            <td>{{ $move->moved_by ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No reagent movement logs yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Step 6: Equipment Calibration / Maintenance Log</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Equipment Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" wire:model.live="equipment_name" placeholder="e.g. Centrifuge">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Equipment Code</label>
                    <input type="text" class="form-control" wire:model.live="equipment_code" placeholder="e.g. EQ-CHM-004">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Log Type <span class="text-danger">*</span></label>
                    <select class="form-select" wire:model.live="equipment_log_type">
                        @foreach ($equipmentLogTypeOptions as $option)
                            <option value="{{ $option }}">{{ ucwords($option) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Performed Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" wire:model.live="equipment_performed_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Next Due Date</label>
                    <input type="date" class="form-control" wire:model.live="equipment_next_due_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Result <span class="text-danger">*</span></label>
                    <select class="form-select" wire:model.live="equipment_result_status">
                        @foreach ($equipmentResultOptions as $option)
                            <option value="{{ $option }}">{{ strtoupper($option) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Performed By</label>
                    <input type="text" class="form-control" wire:model.live="equipment_performed_by">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" rows="2" wire:model.live="equipment_notes" placeholder="Calibration details or follow-up actions"></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" wire:click="clearEquipmentForm"
                wire:loading.attr="disabled" wire:target="clearEquipmentForm">Clear</button>
            <button type="button" class="btn btn-primary" wire:click="saveEquipmentLog"
                wire:loading.attr="disabled" wire:target="saveEquipmentLog">
                <span wire:loading.remove wire:target="saveEquipmentLog">Save Equipment Log</span>
                <span wire:loading wire:target="saveEquipmentLog"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Equipment Log History</h5>
        </div>
        <div class="card-datatable table-responsive pt-0">
            <table id="labEquipmentLogsTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Performed Date</th>
                        <th>Equipment</th>
                        <th>Type</th>
                        <th>Result</th>
                        <th>Next Due</th>
                        <th>Performed By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($equipmentLogs as $log)
                        @php
                            $resultClass = $log->result_status === 'pass' ? 'success' : ($log->result_status === 'fail' ? 'danger' : 'warning');
                            $isOverdue = $log->next_due_date && $log->next_due_date->isPast();
                        @endphp
                        <tr>
                            <td data-order="{{ $log->performed_date?->format('Y-m-d') }}">{{ $log->performed_date?->format('M d, Y') ?: 'N/A' }}</td>
                            <td class="fw-semibold">{{ $log->equipment_name }}<br><small class="text-muted">{{ $log->equipment_code ?: 'N/A' }}</small></td>
                            <td>{{ ucwords($log->log_type) }}</td>
                            <td><span class="badge bg-label-{{ $resultClass }}">{{ strtoupper($log->result_status) }}</span></td>
                            <td>
                                @if ($log->next_due_date)
                                    <span class="{{ $isOverdue ? 'text-danger fw-semibold' : '' }}">{{ $log->next_due_date->format('M d, Y') }}</span>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $log->performed_by ?: 'N/A' }}</td>
                            <td>{{ $log->notes ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No equipment logs available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="assignSampleBatchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Sample to Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sample ID</label>
                            <input type="text" class="form-control" value="{{ $assign_sample_id ?: 'Not selected' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batch <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="assign_batch_id">
                                <option value="">Select batch...</option>
                                @foreach ($batches->whereIn('status', ['scheduled', 'running']) as $batchOption)
                                    <option value="{{ $batchOption->id }}">{{ $batchOption->batch_code }} ({{ ucfirst($batchOption->status) }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="assignSelectedSampleToBatch"
                        wire:loading.attr="disabled" wire:target="assignSelectedSampleToBatch">
                        <span wire:loading.remove wire:target="assignSelectedSampleToBatch">Assign</span>
                        <span wire:loading wire:target="assignSelectedSampleToBatch"><span class="spinner-border spinner-border-sm me-1"></span>Assigning...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="reagentAdjustmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manual Reagent Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Reagent Stock</label>
                            <select class="form-select" wire:model.live="adjust_reagent_stock_id">
                                <option value="">Select reagent...</option>
                                @foreach ($reagents as $reagent)
                                    <option value="{{ $reagent->id }}">
                                        {{ $reagent->reagent_name }} (Avail: {{ number_format((float) $reagent->quantity_available, 2) }} {{ $reagent->unit }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mode</label>
                            <select class="form-select" wire:model.live="adjust_mode">
                                <option value="add">Add</option>
                                <option value="deduct">Deduct</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantity</label>
                            <input type="number" min="0.01" step="0.01" class="form-control" wire:model.live="adjust_quantity" placeholder="0.00">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" rows="3" wire:model.live="adjust_notes" placeholder="Reason for manual adjustment"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="applyReagentAdjustment"
                        wire:loading.attr="disabled" wire:target="applyReagentAdjustment">
                        <span wire:loading.remove wire:target="applyReagentAdjustment">Apply Adjustment</span>
                        <span wire:loading wire:target="applyReagentAdjustment"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                    </button>
                </div>
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

        .metric-card-info {
            border-color: #bae6fd;
            background: #f0f9ff;
            color: #0c4a6e;
        }

        .metric-card-amber {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
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

        .metric-card-warning {
            border-color: #fed7aa;
            background: #fff7ed;
            color: #9a3412;
        }

        .metric-card-dark {
            border-color: #cbd5e1;
            background: #f1f5f9;
            color: #334155;
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
    </style>

    <script>
        document.addEventListener('livewire:initialized', function() {
            const sampleBatchModalEl = document.getElementById('assignSampleBatchModal');
            const reagentModalEl = document.getElementById('reagentAdjustmentModal');

            const cleanupModalArtifacts = () => {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
            };

            Livewire.on('focus-lab-sample-intake', () => {
                const el = document.getElementById('sampleIntakeCard');
                if (!el) return;
                el.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });

            Livewire.on('open-lab-sample-batch-modal', () => {
                if (!sampleBatchModalEl) return;
                const modal = bootstrap.Modal.getInstance(sampleBatchModalEl) || new bootstrap.Modal(sampleBatchModalEl);
                modal.show();
            });

            Livewire.on('close-lab-sample-batch-modal', () => {
                if (!sampleBatchModalEl) return;
                const modal = bootstrap.Modal.getInstance(sampleBatchModalEl) || new bootstrap.Modal(sampleBatchModalEl);
                modal.hide();
                cleanupModalArtifacts();
            });

            Livewire.on('open-lab-reagent-adjustment-modal', () => {
                if (!reagentModalEl) return;
                const modal = bootstrap.Modal.getInstance(reagentModalEl) || new bootstrap.Modal(reagentModalEl);
                modal.show();
            });

            Livewire.on('close-lab-reagent-adjustment-modal', () => {
                if (!reagentModalEl) return;
                const modal = bootstrap.Modal.getInstance(reagentModalEl) || new bootstrap.Modal(reagentModalEl);
                modal.hide();
                cleanupModalArtifacts();
            });
        });
    </script>

    @include('_partials.datatables-init-multi', [
        'tableIds' => [
            'labPendingOrdersTable',
            'labBatchesTable',
            'labSamplesTable',
            'labQcLogsTable',
            'labReagentsTable',
            'labReagentMovementsTable',
            'labEquipmentLogsTable',
        ],
        'orders' => [
            'labPendingOrdersTable' => [0, 'desc'],
            'labBatchesTable' => [0, 'desc'],
            'labSamplesTable' => [0, 'desc'],
            'labQcLogsTable' => [0, 'desc'],
            'labReagentsTable' => [0, 'asc'],
            'labReagentMovementsTable' => [0, 'desc'],
            'labEquipmentLogsTable' => [0, 'desc'],
        ],
    ])
</div>
