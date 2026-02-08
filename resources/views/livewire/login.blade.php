<div>
    <x-input-error-messages />
    <!-- Content -->
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <!-- Login -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-6">
                            <a href="{{ url('/') }}" class="app-brand-link">
                                <span class="app-brand-logo demo">@include('_partials.macros')</span>
                            </a>
                        </div>
                        <!-- /Logo -->
                        <h4 class="mb-1">Welcome to {{ config('variables.templateName') }}! 🙋 </h4>
                        <p class="mb-6">Please sign-in to your account to begin your maternal process</p>

                        <form wire:submit.prevent="login">
                            @csrf

                            <div class="mb-6">
                                <label for="username" class="form-label">Username<strong
                                        style="color: red">*</strong></label>
                                <input wire:model='username' type="text" class="form-control" id="email"
                                    placeholder="Enter your username" autofocus>
                            </div>

                            <div class="mb-6 form-password-toggle form-control-validation">
                                <label class="form-label" for="password">Password <strong
                                        class="text-danger">*</strong></label>
                                <div class="input-group input-group-merge">
                                    <input wire:model="password" id="password" type="password" class="form-control"
                                        name="password" placeholder="••••••••••••" aria-describedby="password" />
                                    <span class="input-group-text cursor-pointer" id="togglePassword">
                                        <i class="icon-base ti tabler-eye-off" id="toggleIcon"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="my-8">
                                <div class="d-flex justify-content-between">
                                    <div class="form-check mb-0 ms-2">
                                        <input type="checkbox" class="form-check-input" id="remember-me" />
                                        <label class="form-check-label" for="remember-me"> Remember Me </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <!-- USING LOADING BUTTON COMPONENT -->
                                <x-app-loader target="login" type="submit" icon="bx-log-in" loadingText="Logging in..."
                                    class="d-grid w-100">
                                    Login In
                                </x-app-loader>
                            </div>
                        </form> <!-- FIXED: Added closing form tag -->

                        <div class="text-body">
                            &#169;
                            @php
                                print Date('Y');
                            @endphp, Made with ❤️ by
                            <a href="{{ !empty(config('variables.creatorUrl')) ? config('variables.creatorUrl') : '' }}"
                                target="_blank"
                                class="footer-link">{{ !empty(config('variables.creatorName')) ? config('variables.creatorName') : '' }}</a>
                        </div>

                        <div class="divider my-6">
                            <div class="divider-text">or</div>
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
