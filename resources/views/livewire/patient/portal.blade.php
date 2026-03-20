<div>
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="mb-1">{{ $heading }}</h4>
            <p class="text-muted mb-0">{{ $description }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h6 class="mb-1"><i class="bx bx-lock-alt me-1"></i>Account Security</h6>
                <p class="text-muted mb-0">Update your password and profile details from account settings.</p>
            </div>
            <a href="{{ route('account-settings') }}" class="btn btn-dark">
                <i class="bx bx-user-cog me-1"></i>Open Account Settings
            </a>
        </div>
    </div>
</div>
