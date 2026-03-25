<div>
    <!-- Content -->
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic py-0"
            style="align-items:flex-start; min-height:100vh; padding-top:0;">
            <div class="authentication-inner py-0" style="margin-top:-.9rem;">
                <!-- Login -->
                <div class="card">
                    <div class="card-body p-3">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-2">
                            <a href="{{ url('/') }}" class="app-brand-link">
                                <span class="app-brand-logo demo">@include('_partials.macros')</span>
                            </a>
                        </div>
                        <!-- /Logo -->

                        <form wire:submit.prevent="login">
                            @csrf

                            @error('message')
                                <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
                            @enderror

                            <div class="mb-4">
                                <label for="username" class="form-label">Username<strong style="color: red">*</strong></label>
                                <input wire:model.defer='username' type="text" class="form-control" id="username"
                                    placeholder="Enter your username" autofocus>
                            </div>

                            <div class="mb-4 form-password-toggle form-control-validation">
                                <label class="form-label" for="password">Password <strong class="text-danger">*</strong></label>
                                <div class="input-group input-group-merge">
                                    <input wire:model.defer="password" id="password" type="password" class="form-control"
                                        name="password" placeholder="••••••••••••" aria-describedby="password" />
                                    <span class="input-group-text cursor-pointer" id="togglePassword">
                                        <i class="icon-base ti tabler-eye-off" id="toggleIcon"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="my-4">
                                <div class="d-flex justify-content-between">
                                    <div class="form-check mb-0 ms-2">
                                        <input type="checkbox" class="form-check-input" id="remember-me" wire:model="remember" />
                                        <label class="form-check-label" for="remember-me"> Remember Me </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <x-app-loader target="login" type="submit" icon="bx-log-in" loadingText="Logging in..."
                                    class="d-grid w-100">
                                    Login In
                                </x-app-loader>
                            </div>
                        </form>

                        <div class="divider my-3">
                            <div class="divider-text" style="font-size:.72rem; letter-spacing:.04em;">follow us</div>
                        </div>

                        <div class="d-flex justify-content-center">
                            <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-facebook me-1_5">
                                <i class="icon-base ti tabler-brand-facebook-filled icon-20px"></i>
                            </a>
                            <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-twitter me-1_5">
                                <i class="icon-base ti tabler-brand-twitter-filled icon-20px"></i>
                            </a>
                            <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-github me-1_5">
                                <i class="icon-base ti tabler-brand-github-filled icon-20px"></i>
                            </a>
                            <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-google-plus">
                                <i class="icon-base ti tabler-brand-google-filled icon-20px"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- /Login -->
            </div>
        </div>
    </div>
</div>
