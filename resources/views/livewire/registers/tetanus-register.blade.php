@php
    use Carbon\Carbon;
@endphp
@section('title', 'Tetanus Register')
<div>
    <!-- Hero Card Header -->
    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 28px;">
                            <i class='bx bx-shield-plus me-2'></i>
                            Tetanus Register
                        </h4>



                        <div class="mt-3">
                            <button class="btn btn-light btn-lg rounded-pill shadow-sm d-inline-flex align-items-center"
                                style="border: 1px solid #ddd; padding: 10px 20px;" data-bs-toggle="modal"
                                data-bs-target="#dinVerificationModal" type="button"
                                title="Record New Tetanus Vaccination">
                                <i class="bx bx-plus me-2" style="font-size: 15px;"></i>
                                + Record New Tetanus Vaccination
                            </button>
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

    <!-- DIN Verification Modal -->
    <div wire:ignore.self class="modal fade" id="dinVerificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content" style="height: 600px;">
                <div class="modal-body" style="padding: 2.5rem;">
                    <div class="text-center mb-5">
                        <h3 class="mb-3"><i class="menu-icon icon-base ti tabler-checklist me-1 text-success"
                                style="font-size: 1.2rem;"></i>
                            Verify Patient</h3>
                        <p class="text-muted mb-2">Enter the Patient's 6-digit DIN to proceed with verification.</p>

                    </div>
                    <form onSubmit="return false">
                        @csrf
                        <div class="mb-4 form-control-validation">
                            <label class="form-label fw-bold">Enter 6-Digit DIN <span
                                    class="text-danger">*</span></label>
                            <div
                                class="auth-input-wrapper d-flex align-items-center justify-content-between numeral-mask-wrapper mb-3">
                                <input type="tel"
                                    class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                    maxlength="1" autofocus />
                                <input type="tel"
                                    class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                    maxlength="1" />
                                <input type="tel"
                                    class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                    maxlength="1" />
                                <input type="tel"
                                    class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                    maxlength="1" />
                                <input type="tel"
                                    class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                    maxlength="1" />
                                <input type="tel"
                                    class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                    maxlength="1" />
                            </div>

                            <div id="din-error" class="text-danger mt-2" style="display: none;">Please enter all 6
                                digits.</div>
                        </div>
                        <div class="text-center mb-4">
                            <button wire:click="verifyPatient" type="button" class="btn btn-primary" id="verify-btn"
                                disabled>
                                <i class="bx bx-check me-1"></i>Verify
                            </button>
                            <button wire:click="exit" type="button" class="btn btn-label-secondary"
                                data-bs-dismiss="modal" aria-label="Close">
                                <i class="bx bx-x me-1"></i>Cancel
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End DIN Verification Modal -->

    <!-- Tetanus Modal -->


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const inputs = document.querySelectorAll('.numeral-mask');
            const hiddenInput = document.querySelector('input[name="din"]');
            const verifyBtn = document.querySelector('#verify-btn');
            const errorDiv = document.querySelector('#din-error');

            // Store original button text
            const originalRecordText = '<i class="bx bx-plus me-1"></i>Register Tetanus Vaccination';
            const originalUpdateText = '<i class="bx bx-check me-1"></i>Update Register';

            // Function to validate inputs and toggle button state
            const updateFormState = () => {
                const dinValue = Array.from(inputs)
                    .map(inp => inp.value.match(/^[0-9]$/) ? inp.value : '')
                    .join('');

                // Update hidden input
                if (hiddenInput) {
                    hiddenInput.value = dinValue;
                    // Trigger Livewire update
                    hiddenInput.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                }

                // Enable/disable button based on input length
                if (verifyBtn) {
                    if (dinValue.length === 6) {
                        verifyBtn.removeAttribute('disabled');
                        if (errorDiv) errorDiv.style.display = 'none';
                    } else {
                        verifyBtn.setAttribute('disabled', 'true');
                        if (errorDiv) errorDiv.style.display = 'none';
                    }
                }
            };

            // Handle verify button click
            if (verifyBtn) {
                verifyBtn.addEventListener('click', () => {
                    // Show loading state
                    verifyBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';
                    verifyBtn.setAttribute('disabled', 'true');
                });
            }

            // Handle form submission for record/update buttons
            const form = document.querySelector('#tetanusModal form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    const recordBtn = document.querySelector('#record-btn');
                    const updateBtn = document.querySelector('#update-btn');

                    if (recordBtn) {
                        recordBtn.innerHTML =
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Recording...';
                        recordBtn.setAttribute('disabled', 'true');
                    }
                    if (updateBtn) {
                        updateBtn.innerHTML =
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
                        updateBtn.setAttribute('disabled', 'true');
                    }
                });
            }

            // Listen for Livewire response to reset buttons
            document.addEventListener('livewire:finished', () => {
                if (verifyBtn) {
                    verifyBtn.innerHTML = '<i class="bx bx-check me-1"></i>Verify';
                    updateFormState(); // Re-enable if DIN is complete
                }

                // Reset record/update buttons
                const recordBtn = document.querySelector('#record-btn');
                const updateBtn = document.querySelector('#update-btn');

                if (recordBtn) {
                    recordBtn.innerHTML = originalRecordText;
                    recordBtn.removeAttribute('disabled');
                }
                if (updateBtn) {
                    updateBtn.innerHTML = originalUpdateText;
                    updateBtn.removeAttribute('disabled');
                }
            });

            inputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    // Only allow numbers
                    if (!input.value.match(/^[0-9]$/)) {
                        input.value = '';
                    }

                    // Move to next input on valid digit
                    if (input.value.match(/^[0-9]$/) && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }

                    updateFormState();
                });

                // Handle backspace
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !input.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
            });

            // Initial state
            updateFormState();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dinModal = document.getElementById('dinVerificationModal');
            const tetanusModal = document.getElementById('tetanusModal');

            // Listen for Livewire event to close modals
            Livewire.on('close-modals', () => {
                const dinModalInstance = bootstrap.Modal.getInstance(dinModal);
                const tetanusModalInstance = bootstrap.Modal.getInstance(tetanusModal);
                if (dinModalInstance) {
                    dinModalInstance.hide();
                }
                if (tetanusModalInstance) {
                    tetanusModalInstance.hide();
                }
            });

            // Handle modal close events (backdrop click or ESC key)
            dinModal.addEventListener('hidden.bs.modal', function(event) {
                // Prevent triggering if modal is closing due to another modal opening
                if (!tetanusModal.classList.contains('show')) {
                    @this.call('exit');
                }
            });
            tetanusModal.addEventListener('hidden.bs.modal', function(event) {
                // Prevent triggering if modal is closing due to another modal opening
                if (!dinModal.classList.contains('show')) {
                    @this.call('exit');
                }
            });
        });
    </script>

    @livewireScripts
    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                console.log('Livewire initialized');
                Livewire.on('open-main-modal', () => {
                    console.log('open-main-modal event received');
                    const dinModal = document.getElementById('dinVerificationModal');
                    const mainModal = document.getElementById('tetanusModal');
                    const bootstrapDinModal = bootstrap.Modal.getInstance(dinModal) || new bootstrap.Modal(
                        dinModal);
                    bootstrapDinModal.hide();
                    console.log('DIN modal hidden');
                    const bootstrapMainModal = new bootstrap.Modal(mainModal);
                    bootstrapMainModal.show();
                    console.log('Main modal shown');
                });

                Livewire.on('close-modals', () => {
                    console.log('close-modals event received');
                    const dinModal = document.getElementById('dinVerificationModal');
                    const mainModal = document.getElementById('tetanusModal');
                    const bootstrapDinModal = bootstrap.Modal.getInstance(dinModal);
                    const bootstrapMainModal = bootstrap.Modal.getInstance(mainModal);
                    if (bootstrapDinModal) {
                        bootstrapDinModal.hide();
                        console.log('DIN modal closed');
                    }
                    if (bootstrapMainModal) {
                        bootstrapMainModal.hide();
                        console.log('Main modal closed');
                    }
                });
            });

            document.addEventListener('submit', (event) => {
                if (event.target.closest('#tetanusModal')) {
                    console.log('Form submission triggered in tetanusModal');
                }
            });
        </script>
    @endpush

    @include('_partials.datatables-init')
</div>
