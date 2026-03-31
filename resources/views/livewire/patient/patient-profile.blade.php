@section('title', 'My Profile')

<div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card portal-section-card">
                <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="portal-section-icon">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.8" />
                                    <path d="M5 19c1.8-3 4.2-4.5 7-4.5S17.2 16 19 19" stroke="currentColor"
                                        stroke-width="1.8" stroke-linecap="round" />
                                </svg>
                            </span>
                            <h6 class="portal-section-title mb-0">Profile Information</h6>
                        </div>
                        <small class="text-muted">Keep your patient account details accurate and easy to recognise across the system.</small>
                    </div>
                    @if (!$edit_mode)
                        <button type="button" class="btn btn-dark" wire:click="toggleEditMode">
                            <i class="bx bx-edit me-1"></i>Edit Profile
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="updateProfile">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">First Name</label>
                                @if ($edit_mode)
                                    <input wire:model="first_name" type="text" class="form-control" placeholder="Enter first name">
                                    @error('first_name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                @else
                                    <p class="form-control-static">{{ $user->first_name }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Last Name</label>
                                @if ($edit_mode)
                                    <input wire:model="last_name" type="text" class="form-control" placeholder="Enter last name">
                                    @error('last_name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                @else
                                    <p class="form-control-static">{{ $user->last_name }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email Address</label>
                                @if ($edit_mode)
                                    <input wire:model="email" type="email" class="form-control" placeholder="Enter email address">
                                    @error('email')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                @else
                                    <p class="form-control-static">{{ $user->email ?? 'Not provided' }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                @if ($edit_mode)
                                    <input wire:model="phone" type="text" class="form-control" placeholder="Enter phone number">
                                    @error('phone')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                @else
                                    <p class="form-control-static">{{ $user->phone ?? 'Not provided' }}</p>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">DIN</label>
                                <p class="form-control-static">
                                    <span class="badge bg-label-primary fs-6">{{ $user->DIN }}</span>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Username</label>
                                <p class="form-control-static">{{ $user->username }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Account Type</label>
                                <p class="form-control-static">
                                    <span class="badge bg-label-success fs-6">{{ $user->role }}</span>
                                </p>
                            </div>

                            @if ($edit_mode)
                                <div class="col-12 mt-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-dark">
                                            <i class="bx bx-check me-1"></i>Save Changes
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" wire:click="toggleEditMode">
                                            <i class="bx bx-x me-1"></i>Cancel
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card portal-section-card mb-4">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="portal-section-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="5" y="5" width="14" height="14" rx="3" stroke="currentColor"
                                    stroke-width="1.8" />
                                <path d="M8.5 10.5h7M8.5 13.5h7" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                        <h6 class="portal-section-title mb-0">Account Summary</h6>
                    </div>
                    <small class="text-muted">A quick account overview aligned with the newer Cureva portal style.</small>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bx bx-calendar-plus bx-sm text-primary me-2"></i>
                        <div>
                            <small class="text-muted d-block">Member Since</small>
                            <div class="fw-semibold">{{ $user->created_at?->format('F d, Y') }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bx bx-building bx-sm text-success me-2"></i>
                        <div>
                            <small class="text-muted d-block">Registration Facility</small>
                            <div class="fw-semibold">{{ $registration_facility_name }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bx bx-shield-quarter bx-sm text-info me-2"></i>
                        <div>
                            <small class="text-muted d-block">Portal Access</small>
                            <div class="fw-semibold">Patient Self-Service</div>
                        </div>
                    </div>
                    <a href="{{ route('account-settings') }}" class="btn btn-outline-dark w-100">
                        <i class="bx bx-cog me-1"></i>Open Account Settings
                    </a>
                </div>
            </div>

            @if ($antenatal_record)
                <div class="card portal-section-card">
                    <div class="card-header border-0 pb-0">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="portal-section-icon">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8"
                                        stroke-linecap="round" />
                                    <rect x="4" y="4" width="16" height="16" rx="4" stroke="currentColor"
                                        stroke-width="1.8" />
                                </svg>
                            </span>
                            <h6 class="portal-section-title mb-0">Medical Snapshot</h6>
                        </div>
                        <small class="text-muted">Important details from the patient’s active antenatal record.</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Blood Group</small>
                            <div class="fw-semibold">
                                <span class="badge bg-label-primary">{{ $antenatal_record->blood_group_rhesus ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Genotype</small>
                            <div class="fw-semibold">
                                <span class="badge bg-label-info">{{ $antenatal_record->genotype ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Occupation</small>
                            <div class="fw-semibold">{{ $antenatal_record->occupation ?? 'Not specified' }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Ethnic Group</small>
                            <div class="fw-semibold">{{ $antenatal_record->ethnic_group ?? 'Not specified' }}</div>
                        </div>
                        @if ($antenatal_record->consultant)
                            <div class="mb-0">
                                <small class="text-muted">Consultant</small>
                                <div class="fw-semibold">{{ $antenatal_record->consultant }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="portal-empty">
                    <i class="bx bx-file bx-md mb-2"></i>
                    <p class="mb-0">No antenatal snapshot is available for this patient yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>
