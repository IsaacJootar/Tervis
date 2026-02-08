<div>
    @php use Carbon\Carbon; @endphp
    @section('title', 'Disable Officer Accounts')
    <div x-data="{ modal_flag: @entangle('modal_flag').live }">

        <!-- Hero Card Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="hero-card">
                    <div class="hero-content">
                        <div class="hero-text">
                            <h4 class="hero-title" style="color: white; font-size: 28px;">
                                <i class='bx bx-lock-alt me-2'></i>
                                Manage Data Officer Accounts
                            </h4>

                            <div class="hero-stats">
                                <span class="hero-stat">
                                    <i class="bx bx-group"></i>
                                    {{ count($dataOfficers) }} Total Data Officers
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-check-circle"></i>
                                    {{ $dataOfficers->where('account_status', 'active')->count() }} Active
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-x-circle"></i>
                                    {{ $dataOfficers->where('account_status', 'disabled')->count() }} Disabled
                                </span>
                                {{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}
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

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-datatable table-responsive pt-0" wire:ignore>
                <table id="dataTable" class="table">
                    <thead class="table-dark">
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Role</th>
                            <th>Account Status</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dataOfficers as $officer)
                            <tr wire:key="{{ $officer->id }}">
                                <td>{{ $officer->first_name }}</td>
                                <td>{{ $officer->last_name }}</td>
                                <td>
                                    <span class="badge bg-label-primary">
                                        Data Officer
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="badge
                                    @if ($officer->account_status === 'active') bg-label-success
                                    @elseif($officer->account_status === 'disabled') bg-label-danger
                                    @else bg-label-secondary @endif">
                                        {{ ucfirst($officer->account_status ?? 'active') }}
                                    </span>
                                </td>
                                <td>{{ $officer->created_at ? Carbon::parse($officer->created_at)->format('M d, Y') : 'N/A' }}
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="icon-base ti tabler-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal"
                                                data-bs-target="#accountModal"
                                                wire:click="openModal({{ $officer->id }})">
                                                <i class="icon-base ti tabler-settings me-1"></i> Manage Account
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Account Status Update Modal -->
        <div wire:ignore.self class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-simple modal-add-new-cc">
                <div class="modal-content">
                    <div class="modal-body">
                        <button wire:click="exit" type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                        <div class="text-center mb-4">
                            <h4 class="mb-2" id="accountModalLabel">Manage Account Status</h4>
                            <p class="text-muted"><span class="badge bg-info">Account Management</span></p>
                        </div>
                        <form onsubmit="return false">
                            @csrf
                            <!-- Officer Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><span class="badge text-bg-primary">Officer
                                            Information</span></h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" value="{{ $first_name }}"
                                                readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" value="{{ $last_name }}"
                                                readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control" value="Data Officer" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Status Update -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><span class="badge text-bg-secondary">Account Status
                                            Management</span></h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Current Status</label>
                                            <div class="d-flex align-items-center">
                                                <span
                                                    class="badge me-2
                                                @if ($current_account_status == 'active') bg-label-success
                                                @elseif($current_account_status == 'disabled') bg-label-danger
                                                @else bg-label-secondary @endif">
                                                    {{ ucfirst($current_account_status ?? 'active') }}
                                                </span>
                                                <input type="text" class="form-control"
                                                    value="{{ ucfirst($current_account_status ?? 'active') }}"
                                                    readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">New Status <span
                                                    class="text-danger">*</span></label>
                                            <select wire:model.live="new_account_status" class="form-select">
                                                <option value="">--Select Status--</option>
                                                @foreach ($available_statuses as $status)
                                                    <option value="{{ $status }}">{{ ucfirst($status) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('new_account_status')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Status Information -->
                                    <div class="alert alert-info mt-2" role="alert">
                                        <h6 class="alert-heading mb-2">
                                            <i class="bx bx-info-circle me-2"></i>Account Status Guide
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small><strong>Active:</strong> User can login and access all
                                                    features</small>
                                            </div>
                                            <div class="col-md-6">
                                                <small><strong>Disabled:</strong> User cannot login into the
                                                    system</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-12 text-center">
                                @if ($modal_flag)
                                    <x-app-loader /> <button wire:click="updateAccountStatus" type="button"
                                        class="btn btn-primary" @if (!$new_account_status || $new_account_status === $current_account_status) disabled @endif>
                                        <i class="bx bx-check me-1"></i>Update Account Status
                                    </button>
                                    <button wire:click="exit" type="button" class="btn btn-label-secondary"
                                        data-bs-dismiss="modal" aria-label="Close">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Account Status Update Modal -->

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const accountModal = document.getElementById('accountModal');

            // Listen for Livewire event to close modal
            Livewire.on('close-account-modal', () => {
                const modal = bootstrap.Modal.getInstance(accountModal);
                if (modal) {
                    modal.hide();
                }
            });

            // Handle modal close events (both backdrop click and ESC key)
            accountModal.addEventListener('hidden.bs.modal', function() {
                @this.call('exit');
            });
        });
    </script>

    @include('_partials.datatables-init')
</div>
