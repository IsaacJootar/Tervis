<div>
    @if (!$hasAccess)
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body text-center py-5">
                        <div class="mb-4"><i class="bx bx-error-circle text-danger" style="font-size: 5rem;"></i></div>
                        <h3 class="text-danger mb-3">Access Denied</h3>
                        <p class="text-muted mb-4">{{ $accessError }}</p>
                        <a href="{{ $patientId ? route('workspace-dashboard', ['patientId' => $patientId]) : route('patient-workspace') }}" class="btn btn-primary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Workspace
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-3"><span class="badge bg-label-primary text-uppercase">Drug Catalog Management</span></div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                    style="width:64px;height:64px;font-weight:700;">
                    @if ($isFacilityCatalog)
                        {{ strtoupper(substr($facility_name ?? 'F', 0, 1)) }}{{ strtoupper(substr($officer_name ?? 'C', 0, 1)) }}
                    @else
                        {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                    @endif
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><i class='bx bx-capsule me-1'></i>Drug Catalog Management</h4>
                    <div class="text-muted small">{{ \Carbon\Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @if ($isFacilityCatalog)
                            <span class="badge bg-label-primary">Facility: {{ $facility_name }}</span>
                            <span class="badge bg-label-secondary">Officer: {{ $officer_name }}</span>
                        @else
                            <span class="badge bg-label-primary">DIN: {{ $patient_din }}</span>
                            <span class="badge bg-label-secondary">Patient Context: {{ $first_name }} {{ $last_name }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @if (!$isFacilityCatalog)
                        <button wire:click="goToDispensing" type="button" class="btn btn-outline-primary"
                            wire:loading.attr="disabled" wire:target="goToDispensing">
                            <span wire:loading.remove wire:target="goToDispensing"><i class="bx bx-cart me-1"></i>Go to
                                Dispensing</span>
                            <span wire:loading wire:target="goToDispensing"><span
                                    class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                        </button>
                        <button wire:click="backToDashboard" type="button" class="btn btn-primary" wire:loading.attr="disabled"
                            wire:target="backToDashboard">
                            <span wire:loading.remove wire:target="backToDashboard"><i
                                    class="bx bx-arrow-back me-1"></i>Back to Workspace</span>
                            <span wire:loading wire:target="backToDashboard"><span
                                    class="spinner-border spinner-border-sm me-1"></span>Opening...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header" style="background-color:#ffedd5;color:#9a3412;border-bottom:1px solid #fdba74;">
                <h6 class="mb-0"><i class='bx bx-plus-circle me-1'></i>{{ $catalog_id ? 'Update Drug Item' : 'Add Drug Item' }}
                </h6>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Drug Name</label>
                        <input type="text" class="form-control" wire:model="catalog_drug_name"
                            placeholder="e.g. Amoxicillin">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Formulation</label>
                        <input type="text" class="form-control" wire:model="catalog_formulation" placeholder="Tablet">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Strength</label>
                        <input type="text" class="form-control" wire:model="catalog_strength" placeholder="500mg">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Route</label>
                        <input type="text" class="form-control" wire:model="catalog_route" placeholder="Oral">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Status</label>
                        <select class="form-select" wire:model="catalog_is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="button" class="btn btn-primary" wire:click="saveCatalogItem"
                            wire:loading.attr="disabled" wire:target="saveCatalogItem">
                            <span wire:loading.remove
                                wire:target="saveCatalogItem">{{ $catalog_id ? 'Update Item' : 'Save Item' }}</span>
                            <span wire:loading wire:target="saveCatalogItem"><span
                                    class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                        </button>
                    </div>
                    <div class="col-md-10">
                        <label class="form-label text-uppercase fw-semibold" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Notes</label>
                        <input type="text" class="form-control" wire:model="catalog_notes"
                            placeholder="Optional notes for this drug item">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="button" class="btn btn-outline-secondary" wire:click="resetCatalogForm">Clear</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background-color:#ffedd5;color:#9a3412;border-bottom:1px solid #fdba74;">
                <h6 class="mb-0"><i class='bx bx-list-ul me-1'></i>Catalog Records</h6>
                <small class="text-muted">{{ count($catalogItems) }} total item(s)</small>
            </div>
            <div class="card-body p-0">
                <div class="card-datatable table-responsive pt-0">
                    <table id="drugCatalogRecordsTable" class="table">
                        <thead class="table-light">
                            <tr>
                                <th>Drug</th>
                                <th>Form / Strength</th>
                                <th>Route</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($catalogItems as $item)
                                <tr wire:key="drug-catalog-row-{{ $item->id }}">
                                    <td class="fw-semibold">{{ $item->drug_name }}</td>
                                    <td>{{ $item->formulation ?: '-' }} / {{ $item->strength ?: '-' }}</td>
                                    <td>{{ $item->route ?: '-' }}</td>
                                    <td>
                                        <span class="badge bg-label-{{ $item->is_active ? 'success' : 'secondary' }}">
                                            {{ $item->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $item->notes ?: '-' }}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-light text-dark border"
                                                wire:click="editCatalogItem({{ $item->id }})">Edit</button>
                                            <button type="button" class="btn btn-sm btn-light text-dark border"
                                                wire:click="toggleCatalogStatus({{ $item->id }})">
                                                {{ $item->is_active ? 'Disable' : 'Enable' }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-light text-dark border"
                                                wire:click="deleteCatalogItem({{ $item->id }})">Remove</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>

@include('_partials.datatables-init-multi', [
    'tableIds' => ['drugCatalogRecordsTable'],
    'orders' => [
        'drugCatalogRecordsTable' => [0, 'asc'],
    ],
])
