@php
    use Carbon\Carbon;
@endphp
@section('title', 'My Delivery Records')

<div>
    <div class="card portal-section-card">
        <div class="card-header border-0 pb-0">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="portal-section-icon">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M7 14h10l-1.2-4.2A3 3 0 0012.9 7h-1.8a3 3 0 00-2.9 2.2L7 14z" stroke="currentColor" stroke-width="1.8" />
                        <circle cx="9" cy="17.5" r="1.5" fill="currentColor" />
                        <circle cx="15" cy="17.5" r="1.5" fill="currentColor" />
                    </svg>
                </span>
                <h6 class="portal-section-title mb-0">Delivery History</h6>
            </div>
            <small class="text-muted">A clear list of recorded deliveries, outcomes, and facility context.</small>
        </div>
        <div class="table-responsive pt-0">
            <table class="table align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Delivery Date</th>
                        <th>Mode of Delivery</th>
                        <th>Baby Sex</th>
                        <th>Baby Weight</th>
                        <th>Type of Client</th>
                        <th>Facility</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deliveries as $record)
                        <tr>
                            <td>{{ $record->dodel ? Carbon::parse($record->dodel)->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                <span class="badge bg-label-primary">{{ $record->mod ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span
                                    class="badge {{ $record->baby_sex === 'Male' ? 'bg-label-primary' : 'bg-label-success' }}">
                                    {{ $record->baby_sex ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $record->weight ? $record->weight . ' kg' : 'N/A' }}</td>
                            <td>
                                <span
                                    class="badge {{ $record->toc === 'Booked' ? 'bg-label-success' : 'bg-label-warning' }}">
                                    {{ $record->toc ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $record->facility ? $record->facility->name : 'N/A' }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    wire:click="viewRecord({{ $record->id }})" data-bs-toggle="modal"
                                    data-bs-target="#deliveryViewModal">
                                    <i class="bx bx-show me-1"></i> View Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="portal-empty d-inline-block w-100">
                                    <i class="bx bx-baby-carriage bx-lg mb-2"></i>
                                    <p class="mb-0">No delivery records have been recorded yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">
                {{ $deliveries->links() }}
            </div>
        </div>
    </div>

    <!-- Delivery View Modal -->
    <!-- ... (modal code unchanged) ... -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('deliveryViewModal');

            // Listen for Livewire event to open modal
            Livewire.on('open-view-modal', () => {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            });

            // Handle modal close
            modal.addEventListener('hidden.bs.modal', function() {
                @this.call('closeModal');
            });
        });
    </script>
