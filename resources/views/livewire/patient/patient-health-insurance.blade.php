@section('title', 'My Health Insurance')

<div>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card portal-section-card h-100">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="portal-section-icon">
                            <i class="bx bx-shield-quarter"></i>
                        </span>
                        <h6 class="portal-section-title mb-0">Coverage Summary</h6>
                    </div>
                    <small class="text-muted">Your current NHIS enrollment details as stored in Cureva.</small>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Status</small>
                        <div class="fw-semibold">{{ $insurance_status }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Provider</small>
                        <div class="fw-semibold">{{ $insurance_provider }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">NHIS Number</small>
                        <div class="fw-semibold">{{ $insurance_number }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Plan Type</small>
                        <div class="fw-semibold">{{ $insurance_plan }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Expiry Date</small>
                        <div class="fw-semibold">{{ $insurance_expiry }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Principal Name</small>
                        <div class="fw-semibold">{{ $insurance_principal_name }}</div>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Principal Number</small>
                        <div class="fw-semibold">{{ $insurance_principal_number }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card portal-section-card h-100">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="portal-section-icon">
                            <i class="bx bx-history"></i>
                        </span>
                        <h6 class="portal-section-title mb-0">Insurance Activity History</h6>
                    </div>
                    <small class="text-muted">A read-only history of insurance activations, updates, and removals.</small>
                </div>
                <div class="table-responsive pt-0">
                    <table class="table align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Facility</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($insurance_history as $item)
                                <tr>
                                    <td>{{ $item->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                    <td>{{ str((string) $item->action)->replace(['_', '-'], ' ')->title()->value() }}</td>
                                    <td>{{ $item->facility?->name ?? 'N/A' }}</td>
                                    <td>{{ $item->description ?: 'Insurance activity recorded.' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="portal-empty d-inline-block w-100">
                                            <i class="bx bx-shield-quarter bx-lg mb-2"></i>
                                            <p class="mb-0">No health insurance activity has been recorded yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($insurance_history->hasPages())
                    <div class="card-body pt-3">
                        {{ $insurance_history->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
