@php
    use Carbon\Carbon;
@endphp
@section('title', 'My Postnatal Records')

<div>
    <div class="card portal-section-card">
        <div class="card-header border-0 pb-0">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="portal-section-icon">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 20s-6.5-3.8-6.5-9A3.5 3.5 0 019 8.1c1.3 0 2.3.6 3 1.6.7-1 1.7-1.6 3-1.6a3.5 3.5 0 013.5 2.9c0 5.2-6.5 9-6.5 9z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                    </svg>
                </span>
                <h6 class="portal-section-title mb-0">Postnatal Follow-Up</h6>
            </div>
            <small class="text-muted">Your postnatal visit record, outcomes, and recovery checkpoints in one place.</small>
        </div>
        <div class="table-responsive pt-0">
            <table class="table align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Visit Date</th>
                        <th>Delivery Date</th>
                        <th>Attendance</th>
                        <th>Child Sex</th>
                        <th>Visit Outcome</th>
                        <th>Facility</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($postnatal_records as $record)
                        <tr>
                            <td>{{ $record->visit_date ? Carbon::parse($record->visit_date)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td>{{ $record->delivery_date ? Carbon::parse($record->delivery_date)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td>
                                <span class="badge bg-label-info">{{ $record->attendance ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span
                                    class="badge {{ $record->child_sex === 'Male' ? 'bg-label-primary' : 'bg-label-success' }}">
                                    {{ $record->child_sex ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span
                                    class="badge {{ $record->visit_outcome === 'Stable' ? 'bg-label-success' : 'bg-label-warning' }}">
                                    {{ $record->visit_outcome ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $record->facility ? $record->facility->name : 'N/A' }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    wire:click="viewRecord({{ $record->id }})" data-bs-toggle="modal"
                                    data-bs-target="#postnatalViewModal">
                                    <i class="bx bx-show me-1"></i> View Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="portal-empty d-inline-block w-100">
                                    <i class="bx bx-heart bx-lg mb-2"></i>
                                    <p class="mb-0">No postnatal records have been recorded yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">
                {{ $postnatal_records->links() }}
            </div>
        </div>
    </div>

    <!-- Postnatal View Modal -->
    <!-- ... (modal code unchanged) ... -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('postnatalViewModal');

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
