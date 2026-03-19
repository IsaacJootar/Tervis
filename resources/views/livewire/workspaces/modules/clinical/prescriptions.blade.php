<div>
    @if (!$hasAccess)
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body text-center py-5">
                        <div class="mb-4"><i class="bx bx-error-circle text-danger" style="font-size: 5rem;"></i></div>
                        <h3 class="text-danger mb-3">Access Denied</h3>
                        <p class="text-muted mb-4">{{ $accessError }}</p>
                        <a href="{{ route('workspace-dashboard', ['patientId' => $patientId]) }}" class="btn btn-primary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Workspace
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Prescriptions & Drugs</span></div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                    style="width:64px;height:64px;font-weight:700;">
                    {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class='bx bx-capsule me-1'></i>Drug Dispensing Workspace</h4>
                    <div class="text-muted small">{{ \Carbon\Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                        <span class="badge bg-label-secondary">Patient: {{ $first_name }} {{ $last_name }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button wire:click="goToInvoices" type="button" class="btn btn-outline-primary"
                        wire:loading.attr="disabled" wire:target="goToInvoices">
                        <span wire:loading.remove wire:target="goToInvoices"><i class="bx bx-receipt me-1"></i>Open
                            Invoices</span>
                        <span wire:loading wire:target="goToInvoices"><span
                                class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                    </button>
                    <button wire:click="backToDashboard" type="button" class="btn btn-primary" wire:loading.attr="disabled"
                        wire:target="backToDashboard">
                        <span wire:loading.remove wire:target="backToDashboard"><i
                                class="bx bx-arrow-back me-1"></i>Back to Workspace</span>
                        <span wire:loading wire:target="backToDashboard"><span
                                class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                    </button>
                    @if ($showAiAssistant)
                        <button type="button" class="btn btn-outline-secondary" wire:click="hideAiAssistant">
                            Hide AI Assistant
                        </button>
                    @else
                        <button type="button" class="btn btn-outline-dark" wire:click="useAiAssistant" wire:loading.attr="disabled" wire:target="useAiAssistant">
                            <span wire:loading.remove wire:target="useAiAssistant"><i class="bx bx-bot me-1"></i>Use AI Assistant</span>
                            <span wire:loading wire:target="useAiAssistant"><span class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header text-white" style="background-color:#2c3e50;">
                <h5 class="mb-0 text-white">Pending Prescriptions from Doctor Assessment</h5>
            </div>
            <div class="card-body p-0">
                <div class="card-datatable table-responsive pt-0">
                    <table id="pendingPrescriptionsTable" class="table align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 48px;">Do</th>
                                <th>Date</th>
                                <th>Drug</th>
                                <th>Dose/Freq/Duration/Route</th>
                                <th>Qty Prescribed</th>
                                <th>Instructions</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingPrescriptions as $item)
                                <tr wire:key="pending-rx-{{ $item->id }}">
                                    <td><input class="form-check-input" type="checkbox"
                                            wire:model.live="selected_prescription_map.{{ $item->id }}"></td>
                                    <td>{{ $item->prescribed_date?->format('M d, Y') ?: 'N/A' }}</td>
                                    <td class="fw-semibold">{{ $item->drug_name }}</td>
                                    <td>{{ $item->dosage ?: '-' }} | {{ $item->frequency ?: '-' }} |
                                        {{ $item->duration ?: '-' }} | {{ $item->route ?: '-' }}</td>
                                    <td>{{ $item->quantity_prescribed ?? '-' }}</td>
                                    <td>{{ $item->instructions ?: '-' }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            wire:click="cancelPending({{ $item->id }})" wire:loading.attr="disabled"
                                            wire:target="cancelPending({{ $item->id }})">Cancel</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">Page {{ $pendingPrescriptions->currentPage() }} of {{ $pendingPrescriptions->lastPage() }} | Total {{ $pendingPrescriptions->total() }}</small>
                    <div>{{ $pendingPrescriptions->links() }}</div>
                </div>
            </div>
            <div class="card-footer small text-muted">
                Select the pending prescriptions being fulfilled, then checkout cart once.
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 mb-4">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><span class="badge bg-label-primary text-uppercase"><i class='bx bx-wallet me-1'></i>Billing Summary</span></h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-uppercase text-muted fw-semibold">Total Billed</div>
                        <div class="fw-bold fs-5">{{ number_format((float)($billingSummary->total_billed ?? 0), 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-uppercase text-muted fw-semibold">Total Paid</div>
                        <div class="fw-bold fs-5 text-success">{{ number_format((float)($billingSummary->total_paid ?? 0), 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-uppercase text-muted fw-semibold">Outstanding</div>
                        <div class="fw-bold fs-5 text-danger">{{ number_format((float)($billingSummary->total_outstanding ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0"><span class="badge bg-label-info text-uppercase"><i class='bx bx-plus-circle me-1'></i>Add Drug to Cart</span></h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Search Drug Catalog</label>
                            <input type="text" class="form-control" wire:model.live.debounce.300ms="drug_search"
                                placeholder="Type drug name, strength, route...">
                            @if ($selected_catalog_id)
                                <div class="small mt-2">
                                    <span class="text-success fw-semibold">Selected:</span>
                                    <span class="text-dark">{{ $selected_catalog_name }}</span>
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-2" wire:click="clearCatalogSelection">Change</button>
                                </div>
                            @endif
                            <div class="list-group mt-2" style="max-height: 190px; overflow-y: auto;">
                                @forelse ($catalogSearchResults as $item)
                                    <button type="button"
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ (int) $selected_catalog_id === (int) $item->id ? 'active' : '' }}"
                                        wire:click="selectCatalogItem({{ $item->id }})">
                                        <span>
                                            {{ $item->drug_name }}
                                            <small class="{{ (int) $selected_catalog_id === (int) $item->id ? 'text-white' : 'text-muted' }}">
                                                ({{ $item->formulation ?: 'N/A' }}, {{ $item->strength ?: 'N/A' }}, {{ $item->route ?: 'N/A' }})
                                            </small>
                                        </span>
                                        @if ((int) $selected_catalog_id === (int) $item->id)
                                            <i class="bx bx-check"></i>
                                        @endif
                                    </button>
                                @empty
                                    <div class="list-group-item text-muted small">No active drugs found for this search.</div>
                                @endforelse
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Quantity</label>
                                <input type="number" class="form-control" min="0.1" step="0.1"
                                    wire:model="entry_quantity">
                            </div>
                            <div class="col-md-6 d-grid align-items-end">
                                <label class="form-label opacity-0 text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Add</label>
                                <button type="button" class="btn btn-primary" wire:click="addToCart"
                                    wire:loading.attr="disabled" wire:target="addToCart">
                                    <span wire:loading.remove wire:target="addToCart">Add to Cart</span>
                                    <span wire:loading wire:target="addToCart"><span
                                            class="spinner-border spinner-border-sm me-1"></span>Adding...</span>
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="small text-muted mb-2">Batch code: <span
                                class="fw-semibold text-dark">{{ $dispense_code ?: 'Auto-generated' }}</span></div>
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Dispense Date</label>
                                <input type="date" class="form-control" wire:model="dispensed_date">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Dispense Notes</label>
                                <input type="text" class="form-control" wire:model="dispense_notes"
                                    placeholder="Optional notes">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Charge Amount <span class="text-danger">*</span></label>
                                <input type="number" min="0" step="0.01" class="form-control" wire:model="charge_amount"
                                    placeholder="Enter bill amount for this dispensing">
                                <div class="form-text">Required. This is the bill to post to invoice.</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" wire:click="clearCart"
                            wire:loading.attr="disabled" wire:target="clearCart">Clear Cart</button>
                        <button type="button" class="btn btn-primary" wire:click="checkoutDispensing"
                            wire:loading.attr="disabled" wire:target="checkoutDispensing">
                            <span wire:loading.remove wire:target="checkoutDispensing">Submit Checkout</span>
                            <span wire:loading wire:target="checkoutDispensing"><span
                                    class="spinner-border spinner-border-sm me-1"></span>Submitting...</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><span class="badge bg-label-warning text-uppercase"><i class='bx bx-cart me-1'></i>Cart Items</span></h6>
                        <small class="text-muted">{{ count($cart) }} line(s)</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="card-datatable table-responsive pt-0">
                            <table id="cartItemsTable" class="table align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Drug</th>
                                        <th style="width: 180px;">Quantity</th>
                                        <th style="width: 120px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cart as $line)
                                        <tr wire:key="rx-cart-{{ $line['cart_item_id'] }}">
                                            <td class="fw-semibold">{{ $line['drug_name'] }}</td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm" min="0.1"
                                                    step="0.1" value="{{ $line['quantity'] }}"
                                                    wire:change="updateCartQuantity('{{ $line['cart_item_id'] }}', $event.target.value)">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    wire:click="removeFromCart('{{ $line['cart_item_id'] }}')">Remove</button>
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

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><span class="badge bg-label-success text-uppercase"><i class='bx bx-history me-1'></i>Dispensing Batches</span></h6>
                <div class="d-flex gap-2">
                    <input type="date" class="form-control form-control-sm" wire:model.live="history_from_date">
                    <input type="date" class="form-control form-control-sm" wire:model.live="history_to_date">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="card-datatable table-responsive pt-0">
                    <table id="dispenseBatchesTable" class="table align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Batch Code</th>
                                <th>Lines</th>
                                <th>Total Qty</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dispenseBatches as $batch)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($batch->dispensed_date)->format('M d, Y') }}</td>
                                    <td class="fw-semibold">{{ $batch->dispense_code }}</td>
                                    <td>{{ $batch->lines_count }}</td>
                                    <td>{{ $batch->total_quantity }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-light text-dark border"
                                            wire:click="openReceipt('{{ $batch->dispense_code }}')">View Receipt</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">Page {{ $dispenseBatches->currentPage() }} of {{ $dispenseBatches->lastPage() }} | Total {{ $dispenseBatches->total() }}</small>
                    <div>{{ $dispenseBatches->links() }}</div>
                </div>
            </div>
        </div>

        <div wire:ignore.self class="modal fade" id="drugReceiptModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color:#2c3e50;">
                        <h5 class="modal-title text-white">Dispense Receipt: {{ $receipt_code ?: '-' }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="drug-receipt-printable">
                        <div class="mb-2 small text-muted">Date: {{ $receipt_date ?: '-' }}</div>
                        <div class="card-datatable table-responsive pt-0">
                            <table class="table align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Drug</th>
                                        <th>Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($receipt_lines as $line)
                                        <tr>
                                            <td>{{ $line['drug_name'] ?? '-' }}</td>
                                            <td>{{ $line['quantity'] ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-4">No receipt lines found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-primary" wire:click="printReceipt">
                            <i class="bx bx-printer me-1"></i>Print
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    @endif

    @include('livewire.partials.ai-assistant-panel', [
        'show' => $showAiAssistant,
        'summary' => $aiAssistantSummary,
        'riskLevel' => $aiAssistantRiskLevel,
        'generatedAt' => $aiAssistantGeneratedAt,
        'items' => $aiAssistantItems,
        'refreshAction' => 'refreshAiAssistant',
        'hideAction' => 'hideAiAssistant',
        'title' => 'AI Assistant',
    ])

    <script>
        document.addEventListener('livewire:initialized', function() {
            const modalEl = document.getElementById('drugReceiptModal');
            if (!modalEl) return;

            let modalInstance = null;
            const getModal = () => {
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(modalEl);
                }
                return modalInstance;
            };

            const cleanupModalArtifacts = () => {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
            };

            Livewire.on('open-drug-receipt-modal', () => {
                getModal().show();
            });

            Livewire.on('close-drug-receipt-modal', () => {
                if (modalInstance) {
                    modalInstance.hide();
                }
            });

            modalEl.addEventListener('hidden.bs.modal', function() {
                @this.call('closeReceipt', true);
                cleanupModalArtifacts();
            });
        });
    </script>

</div>
