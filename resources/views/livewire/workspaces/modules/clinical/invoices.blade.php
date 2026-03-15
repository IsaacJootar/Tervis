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
        <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Invoices & Payments</span></div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                    style="width:64px;height:64px;font-weight:700;">
                    {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class='bx bx-receipt me-1'></i>Patient Billing Workspace</h4>
                    <div class="text-muted small">{{ \Carbon\Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                        <span class="badge bg-label-secondary">Patient: {{ $first_name }} {{ $last_name }}</span>
                    </div>
                </div>
                <button wire:click="backToDashboard" type="button" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="backToDashboard">
                    <span wire:loading.remove wire:target="backToDashboard"><i
                            class="bx bx-arrow-back me-1"></i>Back to Workspace</span>
                    <span wire:loading wire:target="backToDashboard"><span
                            class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                </button>
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

        @if (!$billingAvailable)
            <div class="alert alert-warning">
                Billing tables are not available yet. Run migrations to enable invoices and payments.
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <span class="badge bg-label-primary text-uppercase">
                        <i class='bx bx-wallet me-1'></i>Billing Summary
                    </span>
                </h6>
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
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <span class="badge bg-label-info text-uppercase">
                                <i class='bx bx-file me-1'></i>Invoices
                            </span>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="card-datatable table-responsive pt-0">
                            <table id="invoiceRecordsTable" class="table align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Invoice Code</th>
                                        <th>Lines</th>
                                        <th>Total</th>
                                        <th>Paid</th>
                                        <th>Outstanding</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoices as $invoice)
                                        <tr>
                                            <td>{{ $invoice->invoice_date?->format('M d, Y') }}</td>
                                            <td class="fw-semibold">{{ $invoice->invoice_code }}</td>
                                            <td>{{ $invoice->lines_count }}</td>
                                            <td>{{ number_format((float)$invoice->total_amount, 2) }}</td>
                                            <td class="text-success">{{ number_format((float)$invoice->amount_paid, 2) }}</td>
                                            <td class="text-danger fw-semibold">{{ number_format((float)$invoice->outstanding_amount, 2) }}</td>
                                            <td>
                                                <span class="badge bg-label-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'partially_paid' ? 'warning' : 'secondary') }}">
                                                    {{ str_replace('_', ' ', ucfirst($invoice->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-light text-dark border"
                                                    wire:click="selectInvoice({{ $invoice->id }})">View</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="px-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted">Page {{ $invoices->currentPage() }} of {{ $invoices->lastPage() }} | Total {{ $invoices->total() }}</small>
                            <div>
                                @if (method_exists($invoices, 'links'))
                                    {{ $invoices->links() }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <span class="badge bg-label-success text-uppercase">
                                <i class='bx bx-money me-1'></i>Record Payment
                            </span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Invoice</label>
                            <select class="form-select" wire:model="selected_invoice_id">
                                @if (($payableInvoices ?? collect())->isEmpty())
                                    <option value="">No outstanding invoices</option>
                                @else
                                    <option value="">Select outstanding invoice...</option>
                                    @foreach ($payableInvoices as $invoice)
                                        <option value="{{ $invoice->id }}">
                                            {{ $invoice->invoice_code }} | Out: {{ number_format((float)$invoice->outstanding_amount, 2) }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @if (($payableInvoices ?? collect())->isEmpty())
                                <div class="form-text text-muted">All invoices are fully paid.</div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Payment Date</label>
                            <input type="date" class="form-control" wire:model="payment_date">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Amount</label>
                            <input type="number" min="0.01" step="0.01" class="form-control" wire:model="payment_amount"
                                placeholder="Enter amount paid">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Method</label>
                            <select class="form-select" wire:model="payment_method">
                                <option value="Cash">Cash</option>
                                <option value="Transfer">Transfer</option>
                                <option value="POS">POS</option>
                                <option value="Insurance">Insurance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Notes</label>
                            <input type="text" class="form-control" wire:model="payment_notes" placeholder="Optional notes">
                        </div>
                        <button type="button" class="btn btn-primary w-100" wire:click="recordPayment"
                            wire:loading.attr="disabled" wire:target="recordPayment"
                            @disabled(($payableInvoices ?? collect())->isEmpty())>
                            <span wire:loading.remove wire:target="recordPayment">Submit Payment</span>
                            <span wire:loading wire:target="recordPayment"><span
                                    class="spinner-border spinner-border-sm me-1"></span>Processing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if ($selectedInvoice)
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <span class="badge bg-label-warning text-uppercase">
                            <i class='bx bx-detail me-1'></i>Invoice Details
                        </span>
                        <span class="ms-2 small text-muted">{{ $selectedInvoice->invoice_code }}</span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="card-datatable table-responsive pt-0">
                        <table id="invoiceLinesTable" class="table align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Module</th>
                                    <th>Description</th>
                                    <th>Ref Code</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selectedInvoice->lines as $line)
                                    <tr>
                                        <td>{{ ucfirst($line->module ?: '-') }}</td>
                                        <td>{{ $line->description }}</td>
                                        <td>{{ $line->reference_code ?: '-' }}</td>
                                        <td>{{ number_format((float)$line->quantity, 2) }}</td>
                                        <td>{{ number_format((float)$line->unit_price, 2) }}</td>
                                        <td class="fw-semibold">{{ number_format((float)$line->line_amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <span class="badge bg-label-dark text-uppercase">
                        <i class='bx bx-history me-1'></i>Payment History
                    </span>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="card-datatable table-responsive pt-0">
                    <table id="invoicePaymentHistoryTable" class="table align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Payment Code</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Received By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date?->format('M d, Y') }}</td>
                                    <td class="fw-semibold">{{ $payment->payment_code }}</td>
                                    <td class="text-success fw-semibold">{{ number_format((float)$payment->amount_received, 2) }}</td>
                                    <td>{{ $payment->payment_method ?: 'N/A' }}</td>
                                    <td>{{ $payment->received_by ?: 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">Page {{ $payments->currentPage() }} of {{ $payments->lastPage() }} | Total {{ $payments->total() }}</small>
                    <div>
                        @if (method_exists($payments, 'links'))
                            {{ $payments->links() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
