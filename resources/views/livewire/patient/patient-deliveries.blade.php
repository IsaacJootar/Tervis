@php
    use Carbon\Carbon;
@endphp
@section('title', 'My Delivery Records')

<div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="mb-1 d-flex align-items-center gap-2">
                        <i class='bx bx-baby-carriage text-primary'></i>
                        My Delivery Records
                    </h5>
                    <div class="small text-muted">{{ Carbon::today()->format('l, F j, Y') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-label-dark"><i class="bx bx-folder me-1"></i>{{ $deliveries->count() }}
                            Total</span>
                        <span class="badge bg-label-success"><i class="bx bx-calendar me-1"></i>Latest:
                            {{ $deliveries->first() ? Carbon::parse($deliveries->first()->dodel)->format('M d, Y') : 'N/A' }}</span>
                        <span class="badge bg-label-primary"><i class="bx bx-id-card me-1"></i>{{ $user->DIN }}</span>
                    </div>
                </div>
                <div>
                    <a href="{{ route('patient-dashboard') }}" class="btn btn-outline-dark">
                        <i class="bx bx-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable with Records -->
    <!-- DataTable with Records -->
    <div class="card">
        <div class="card-datatable table-responsive pt-0">
            <table id="dataTable" class="table">
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
                                <i class="bx bx-baby-carriage bx-lg text-muted mb-2"></i>
                                <p class="text-muted">No delivery records found</p>
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
