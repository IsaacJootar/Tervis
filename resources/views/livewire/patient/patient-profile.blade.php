@php
    use Carbon\Carbon;
@endphp
@section('title', 'My Profile')

<div>
    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 32px;">
                            <i class='bx bx-user me-2'></i>
                            My Profile
                        </h4>
                        <div class="hero-info mb-2">
                            <p class="hero-subtitle">{{ Carbon::today()->format('l, F j, Y') }}</p>
                            <div class="hero-stats">
                                <span class="hero-stat">
                                    <i class="bx bx-id-card"></i>
                                    DIN: {{ $user->DIN }}
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-calendar"></i>
                                    Joined: {{ Carbon::parse($user->created_at)->format('M Y') }}
                                </span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-3 text-white mb-1">
                            <span>
                                <i class="bx bx-building me-1"></i>
                                <strong>Registration Facility:</strong> {{ $registration_facility_name }}
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

    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Profile Information</h5>
                    @if (!$edit_mode)
                        <button type="button" class="btn btn-primary" wire:click="toggleEditMode">
                            <i class="bx bx-edit me-1"></i>Edit Profile
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="updateProfile">
                        <div class="row g-3">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">First Name</label>
                                @if ($edit_mode)
                                    <input wire:model="first_name" type="text" class="form-control"
                                        placeholder="Enter first name">
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
                                    <input wire:model="last_name" type="text" class="form-control"
                                        placeholder="Enter last name">
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
                                    <input wire:model="email" type="email" class="form-control"
                                        placeholder="Enter email address">
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
                                    <input wire:model="phone" type="text" class="form-control"
                                        placeholder="Enter phone number">
                                    @error('phone')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                @else
                                    <p class="form-control-static">{{ $user->phone ?? 'Not provided' }}</p>
                                @endif
                            </div>

                            <!-- Read-only Information -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">DIN (Patient ID)</label>
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

                            <!-- Password Change Section (only in edit mode) -->
                            @if ($edit_mode)
                                <div class="col-12">
                                    <hr class="my-4">
                                    <h6 class="text-muted mb-3">Change Password (Optional)</h6>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Current Password</label>
                                    <input readonly wire:model="current_password" type="password" class="form-control"
                                        placeholder="Enter current password">
                                    @error('current_password')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">New Password</label>
                                    <input readonly wire:model="new_password" type="password" class="form-control"
                                        placeholder="Enter new password">
                                    @error('new_password')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Confirm New Password</label>
                                    <input readonly wire:model="new_password_confirmation" type="password"
                                        class="form-control" placeholder="Confirm new password">
                                </div>
                            @endif

                            <!-- Action Buttons -->
                            @if ($edit_mode)
                                <div class="col-12 mt-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-check me-1"></i>Save Changes
                                        </button>
                                        <button type="button" class="btn btn-secondary" wire:click="toggleEditMode">
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

        <!-- Additional Information -->
        <div class="col-md-4">
            <!-- Account Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bx bx-calendar-plus bx-sm text-primary me-2"></i>
                        <div>
                            <small class="text-muted">Member Since</small>
                            <div class="fw-semibold">{{ Carbon::parse($user->created_at)->format('F d, Y') }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bx bx-building bx-sm text-success me-2"></i>
                        <div>
                            <small class="text-muted">Registration Facility</small>
                            <div class="fw-semibold">{{ $registration_facility_name }}</div>
                        </div>
                    </div>
                    @if ($antenatal_record)
                        <div class="d-flex align-items-center">
                            <i class="bx bx-map bx-sm text-info me-2"></i>
                            <div>
                                <small class="text-muted">Address</small>
                                <div class="fw-semibold">{{ $antenatal_record->address ?? 'Not provided' }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Medical Information -->
            @if ($antenatal_record)
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Medical Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Age</small>
                            <div class="fw-semibold">{{ $antenatal_record->age }} years</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Blood Group</small>
                            <div class="fw-semibold">
                                <span
                                    class="badge bg-label-primary">{{ $antenatal_record->blood_group_rhesus ?? 'N/A' }}</span>
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
                            <div class="mb-3">
                                <small class="text-muted">Consultant</small>
                                <div class="fw-semibold">{{ $antenatal_record->consultant }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
