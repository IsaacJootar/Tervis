@php
    use Carbon\Carbon;
@endphp
@section('title', 'My Delivery Records')

<div>
    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 32px;">
                            <i class='bx bx-baby-carriage me-2'></i>
                            My Delivery Records
                        </h4>
                        <div class="hero-info mb-2">
                            <p class="hero-subtitle">{{ Carbon::today()->format('l, F j, Y') }}</p>
                            <div class="hero-stats">
                                <span class="hero-stat">
                                    <i class="bx bx-folder"></i>
                                    {{ $deliveries->count() }} Total Deliveries
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-calendar"></i>
                                    Latest:
                                    {{ $deliveries->first() ? Carbon::parse($deliveries->first()->dodel)->format('M d, Y') : 'N/A' }}
                                </span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-3 text-white mb-1">

                            <span>
                                <i class="bx bx-id-card me-1"></i>
                                <strong>DIN:</strong> {{ $user->DIN }}
                            </span>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('patient-dashboard') }}"
                                class="btn btn-light btn-lg rounded-pill shadow-sm d-inline-flex align-items-center"
                                style="border: 1px solid #ddd; padding: 12px 24px;">
                                <i class="bx bx-arrow-left me-2" style="font-size: 20px;"></i>
                                Back to Dashboard
                            </a>
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
