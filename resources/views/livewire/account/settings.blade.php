@php
    use Carbon\Carbon;
@endphp

@section('title', 'Account Settings')

<div class="account-settings-page">
    <div class="card hero-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="mb-1 d-flex align-items-center gap-2">
                        <span class="hero-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.8" />
                                <path d="M5 19c1.8-3 4.2-4.5 7-4.5S17.2 16 19 19" stroke="currentColor"
                                    stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </span>
                        Account Settings
                    </h5>
                    <div class="small text-muted">Manage your profile and password securely.</div>
                    <div class="small text-muted">{{ Carbon::now('Africa/Lagos')->format('l, F j, Y, h:i A') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="metric-card metric-card-sky h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Role</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="4" y="6" width="16" height="12" rx="2.2" stroke="currentColor"
                                stroke-width="1.8" />
                            <circle cx="9" cy="11" r="1.5" stroke="currentColor" stroke-width="1.8" />
                            <path d="M12.5 10h4M12.5 13h4" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value metric-text">{{ $role ?: 'N/A' }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card metric-card-emerald h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Username</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.8" />
                            <path d="M6 18c1.5-2.5 3.6-3.8 6-3.8s4.5 1.3 6 3.8" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value metric-text">{{ $username ?: 'N/A' }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card metric-card-slate h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="metric-label">Email</div>
                    <span class="metric-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="4" y="7" width="16" height="10" rx="2" stroke="currentColor"
                                stroke-width="1.8" />
                            <path d="M5 8l7 5 7-5" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <div class="metric-value metric-text metric-email">{{ $email ?: 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card settings-card">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="section-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="4" y="6" width="16" height="12" rx="2.2" stroke="currentColor"
                                    stroke-width="1.8" />
                                <circle cx="9" cy="11" r="1.5" stroke="currentColor" stroke-width="1.8" />
                                <path d="M12.5 10h4M12.5 13h4" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                        <h6 class="mb-0">Profile Information</h6>
                    </div>
                    <small class="text-muted">Update your basic account details.</small>
                </div>
                <div class="card-body">
                    <form wire:submit="updateProfile">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model.defer="first_name" placeholder="Enter first name">
                                @error('first_name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model.defer="last_name" placeholder="Enter last name">
                                @error('last_name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" wire:model.defer="email" placeholder="Enter email">
                                @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" wire:model.defer="phone" placeholder="Enter phone">
                                @error('phone') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="{{ $username }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="{{ $role }}" readonly>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-dark" wire:loading.attr="disabled" wire:target="updateProfile">
                                    <span wire:loading.remove wire:target="updateProfile">
                                        <span class="btn-inline-icon me-1" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M5 7.5A2.5 2.5 0 017.5 5h8.2a2.5 2.5 0 011.8.75l1.75 1.75c.47.47.75 1.1.75 1.77v7.23A2.5 2.5 0 0117.5 19h-10A2.5 2.5 0 015 16.5v-9z" stroke="currentColor" stroke-width="1.8" />
                                                <path d="M9 5v4h6V5M9 19v-4h6v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                            </svg>
                                        </span>Save Profile
                                    </span>
                                    <span wire:loading wire:target="updateProfile">
                                        <span class="spinner-border spinner-border-sm me-1"></span>Saving...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card settings-card">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="section-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor"
                                    stroke-width="1.8" />
                                <path d="M8 11V8a4 4 0 118 0v3" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" />
                            </svg>
                        </span>
                        <h6 class="mb-0">Change Password</h6>
                    </div>
                    <small class="text-muted">Use a strong password you do not reuse elsewhere.</small>
                </div>
                <div class="card-body">
                    <form wire:submit="updatePassword">
                        <div class="mb-3">
                            <label class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" wire:model.defer="current_password" placeholder="Enter current password">
                            @error('current_password') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" wire:model.defer="new_password" placeholder="Minimum 8 characters">
                            @error('new_password') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" wire:model.defer="new_password_confirmation" placeholder="Confirm new password">
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-dark" wire:loading.attr="disabled" wire:target="updatePassword">
                                <span wire:loading.remove wire:target="updatePassword">
                                    <span class="btn-inline-icon me-1" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <circle cx="8.5" cy="12" r="3" stroke="currentColor" stroke-width="1.8" />
                                            <path d="M11.5 12H20M17 12v2m-3-2v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                        </svg>
                                    </span>Update Password
                                </span>
                                <span wire:loading wire:target="updatePassword">
                                    <span class="spinner-border spinner-border-sm me-1"></span>Updating...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .account-settings-page .hero-card {
            border: 1px solid rgba(148, 163, 184, 0.24);
            border-radius: 18px;
            box-shadow: 0 12px 28px -24px rgba(15, 23, 42, 0.5);
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        }

        .account-settings-page .hero-card h5 {
            color: #0f172a;
            font-weight: 700;
        }

        .account-settings-page .hero-card h5 i {
            color: #2563eb;
        }

        .account-settings-page .hero-icon {
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
        }

        .account-settings-page .hero-icon svg {
            width: 22px;
            height: 22px;
        }

        .account-settings-page .metric-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            padding: 14px 16px;
            min-height: 108px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 26px -22px rgba(15, 23, 42, 0.45);
        }

        .account-settings-page .metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-weight: 700;
        }

        .account-settings-page .metric-value {
            margin-top: 8px;
            line-height: 1.2;
        }

        .account-settings-page .metric-text {
            font-size: 1.05rem;
            font-weight: 700;
            word-break: break-word;
        }

        .account-settings-page .metric-email {
            font-size: 0.95rem;
        }

        .account-settings-page .metric-icon {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.1);
        }

        .account-settings-page .metric-icon svg {
            width: 18px;
            height: 18px;
        }

        .account-settings-page .metric-card-slate {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
        }

        .account-settings-page .metric-card-sky {
            border-color: #bae6fd;
            background: #f0f9ff;
            color: #0c4a6e;
        }

        .account-settings-page .metric-card-emerald {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #065f46;
        }

        .account-settings-page .settings-card {
            border: 1px solid rgba(148, 163, 184, 0.24);
            border-radius: 18px;
            box-shadow: 0 12px 30px -24px rgba(15, 23, 42, 0.42);
        }

        .account-settings-page .section-icon {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        .account-settings-page .section-icon svg {
            width: 16px;
            height: 16px;
        }

        .account-settings-page .btn-inline-icon {
            width: 14px;
            height: 14px;
            display: inline-flex;
            vertical-align: -2px;
        }

        .account-settings-page .btn-inline-icon svg {
            width: 14px;
            height: 14px;
        }
    </style>
</div>
