@php
    use Carbon\Carbon;
@endphp
@section('title', 'My Postnatal Records')

<div>
    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 32px;">
                            <i class='bx bx-heart me-2'></i>
                            My Postnatal Records
                        </h4>
                        <div class="hero-info mb-2">
                            <p class="hero-subtitle">{{ Carbon::today()->format('l, F j, Y') }}</p>
                            <div class="hero-stats">
                                <span class="hero-stat">
                                    <i class="bx bx-folder"></i>
                                    {{ $postnatal_records->count() }} Total Visits
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-calendar"></i>
                                    Latest:
                                    {{ $postnatal_records->first() ? Carbon::parse($postnatal_records->first()->visit_date)->format('M d, Y') : 'N/A' }}
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
    <div class="card">
        <div class="card-datatable table-responsive pt-0">
            <table id="dataTable" class="table">
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
                                <i class="bx bx-heart bx-lg text-muted mb-2"></i>
                                <p class="text-muted">No postnatal records found</p>
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
