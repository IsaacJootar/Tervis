@php
    use Carbon\Carbon;
@endphp

@section('title', 'Pharmacy Operations')

<div>
    <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Pharmacy Operations</span></div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-capsule me-1"></i>Facility Pharmacy Operations</h4>
                <div class="text-muted small">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                <div class="text-muted small mt-1">Clear workflow: Stock In -> Adjust (if needed) -> Monitor Inventory -> Review Movement Log.</div>
            </div>
            <div class="ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#stockAdjustmentModal">
                    Manual Stock Adjustment
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Catalog Drugs</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M6 7h12M6 12h12M6 17h12M4 7h.01M4 12h.01M4 17h.01" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['total_drugs'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">In Stock</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                            <path d="M8.5 12.5l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['in_stock'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-amber h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Low Stock</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                            <path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['low_stock'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-rose h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Out of Stock</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                            <path d="M9.5 9.5l5 5M14.5 9.5l-5 5" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['out_of_stock'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="metric-card metric-card-violet h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Expired Balance</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                            <path d="M12 8v4l2.5 1.5" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value">{{ $summary['expired_with_balance'] }}</div>
            </div>
        </div>
    </div>

    <div class="alert alert-primary d-flex align-items-start gap-2 mb-4">
        <i class="bx bx-info-circle mt-1"></i>
        <div class="small">
            <strong>How to add stock:</strong> use <strong>Step 1 - Stock In</strong> below.
            Reorder level is only a warning threshold; it does not add quantity.
        </div>
    </div>

    <div class="card mb-4" id="stockInCard">
        <div class="card-header">
            <h5 class="mb-0">Step 1: Stock In (Add Quantity)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Drug <span class="text-danger">*</span></label>
                    <select class="form-select" wire:model.live="stock_drug_catalog_item_id">
                        <option value="">Select drug...</option>
                        @foreach ($catalogOptions as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->drug_name }} ({{ $item->formulation ?: 'N/A' }}, {{ $item->strength ?: 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                    @error('stock_drug_catalog_item_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Batch Number</label>
                    <input type="text" class="form-control" wire:model.live="stock_batch_number"
                        placeholder="Optional batch number">
                    @error('stock_batch_number')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Quantity Received <span class="text-danger">*</span></label>
                    <input type="number" min="0.01" step="0.01" class="form-control"
                        wire:model.live="stock_quantity_received" placeholder="0.00">
                    @error('stock_quantity_received')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Received Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" wire:model.live="stock_received_date">
                    @error('stock_received_date')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" class="form-control" wire:model.live="stock_expiry_date">
                    @error('stock_expiry_date')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Supplier Name</label>
                    <input type="text" class="form-control" wire:model.live="stock_supplier_name"
                        placeholder="Optional supplier">
                    @error('stock_supplier_name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Unit Cost (Optional)</label>
                    <input type="number" min="0" step="0.01" class="form-control"
                        wire:model.live="stock_unit_cost" placeholder="0.00">
                    @error('stock_unit_cost')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" rows="2" wire:model.live="stock_notes" placeholder="Optional notes for this stock entry"></textarea>
                    @error('stock_notes')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="button" class="btn btn-primary" wire:click="saveStockIn" wire:loading.attr="disabled"
                wire:target="saveStockIn">
                <span wire:loading.remove wire:target="saveStockIn">Save Stock In</span>
                <span wire:loading wire:target="saveStockIn"><span
                        class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Inventory Overview</h5>
        </div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="pharmacyInventoryTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Drug</th>
                        <th>Form/Strength/Route</th>
                        <th>Available Stock</th>
                        <th>Expired Balance</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inventoryRows as $row)
                        @php
                            $available = (float) ($row->available_stock ?? 0);
                            $expired = (float) ($row->expired_stock ?? 0);
                            $reorder = (int) ($row->reorder_level ?? 10);
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
                            <td class="fw-semibold">{{ $row->drug_name }}</td>
                            <td>{{ $row->formulation ?: '-' }} | {{ $row->strength ?: '-' }} | {{ $row->route ?: '-' }}</td>
                            <td>{{ number_format($available, 2) }}</td>
                            <td>{{ number_format($expired, 2) }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <input type="number" min="0" class="form-control form-control-sm"
                                        wire:model.defer="reorderLevels.{{ $row->id }}" style="max-width: 92px;">
                                    <button type="button" class="btn btn-sm btn-light text-dark border"
                                        wire:click="updateReorderLevel({{ $row->id }})">Save</button>
                                </div>
                                <small class="text-muted">Warning threshold only</small>
                            </td>
                            <td>
                                <span class="badge bg-label-{{ $statusClass }}">{{ $statusLabel }}</span>
                                @if ($available <= 0)
                                    <div><small class="text-muted">Use Step 1 to add stock</small></div>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-light text-dark border"
                                    wire:click="useDrugForStockIn({{ $row->id }})">Select + Go to Stock In</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Stock Batches</h5>
        </div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="pharmacyBatchesTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Received Date</th>
                        <th>Drug</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                        <th>Qty Received</th>
                        <th>Qty Available</th>
                        <th>Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stockBatches as $batch)
                        <tr>
                            <td data-order="{{ $batch->received_date?->format('Y-m-d') }}">{{ $batch->received_date?->format('M d, Y') ?: 'N/A' }}</td>
                            <td>{{ $batch->catalogItem?->drug_name ?: 'N/A' }}</td>
                            <td>{{ $batch->batch_number ?: 'N/A' }}</td>
                            <td data-order="{{ $batch->expiry_date?->format('Y-m-d') }}">{{ $batch->expiry_date?->format('M d, Y') ?: 'N/A' }}</td>
                            <td>{{ number_format((float) $batch->quantity_received, 2) }}</td>
                            <td>{{ number_format((float) $batch->quantity_available, 2) }}</td>
                            <td>{{ $batch->supplier_name ?: 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Stock Movement Log</h5>
        </div>
        <div class="card-datatable table-responsive pt-0" wire:ignore>
            <table id="pharmacyMovementsTable" class="table align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Time</th>
                        <th>Drug</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Balance After</th>
                        <th>Reference</th>
                        <th>Moved By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stockMovements as $movement)
                        @php
                            $qty = (float) $movement->quantity;
                            $qtyClass = $qty < 0 ? 'danger' : 'success';
                        @endphp
                        <tr>
                            <td data-order="{{ $movement->moved_at?->format('Y-m-d H:i:s') }}">{{ $movement->moved_at?->format('M d, Y h:i A') ?: 'N/A' }}</td>
                            <td>{{ $movement->catalogItem?->drug_name ?: 'N/A' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $movement->movement_type)) }}</td>
                            <td class="text-{{ $qtyClass }}">{{ number_format($qty, 2) }}</td>
                            <td>{{ number_format((float) $movement->balance_after, 2) }}</td>
                            <td>{{ $movement->reference_code ?: 'N/A' }}</td>
                            <td>{{ $movement->moved_by ?: 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="stockAdjustmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Step 2 (Optional): Manual Stock Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Drug <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="adjust_drug_catalog_item_id">
                                <option value="">Select drug...</option>
                                @foreach ($catalogOptions as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->drug_name }} ({{ $item->formulation ?: 'N/A' }}, {{ $item->strength ?: 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('adjust_drug_catalog_item_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Mode <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="adjust_mode">
                                <option value="add">Add</option>
                                <option value="deduct">Deduct</option>
                            </select>
                            @error('adjust_mode')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" min="0.01" step="0.01" class="form-control"
                                wire:model.live="adjust_quantity" placeholder="0.00">
                            @error('adjust_quantity')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" rows="3" wire:model.live="adjust_reason"
                                placeholder="Reason for this stock adjustment"></textarea>
                            @error('adjust_reason')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="applyAdjustment"
                        wire:loading.attr="disabled" wire:target="applyAdjustment">
                        <span wire:loading.remove wire:target="applyAdjustment">Apply Adjustment</span>
                        <span wire:loading wire:target="applyAdjustment"><span
                                class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
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
            font-size: 18px;
            width: 18px;
            height: 18px;
        }

        .metric-card-slate {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
        }

        .metric-card-emerald {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #065f46;
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
            Livewire.on('focus-stock-in-form', () => {
                const el = document.getElementById('stockInCard');
                if (!el) return;
                el.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });

            Livewire.on('close-stock-adjustment-modal', () => {
                const modalEl = document.getElementById('stockAdjustmentModal');
                if (!modalEl) return;
                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.hide();
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
            });
        });
    </script>

    @include('_partials.datatables-init-multi', [
        'tableIds' => ['pharmacyInventoryTable', 'pharmacyBatchesTable', 'pharmacyMovementsTable'],
        'orders' => [
            'pharmacyInventoryTable' => [0, 'asc'],
            'pharmacyBatchesTable' => [0, 'desc'],
            'pharmacyMovementsTable' => [0, 'desc'],
        ],
    ])
</div>
