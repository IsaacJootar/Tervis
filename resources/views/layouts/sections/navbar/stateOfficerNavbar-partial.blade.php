{{-- resources/views/layouts/sections/navbar/centralAdminNavbar_partial.blade.php --}}
@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;
@endphp

@if (isset($navbarFull))
    <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4 ms-0">
        <a href="{{ url('/state-officer-dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" height="32">
            </span>
            <span class="app-brand-text demo menu-text fw-bold small">{{ config('variables.templateName') }}</span>
            <small>State Officer.</small>
        </a>
        @if (isset($menuHorizontal))
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
                <i class="icon-base ti tabler-x icon-sm d-flex align-items-center justify-content-center"></i>
            </a>
        @endif
    </div>
@endif

@if (!isset($navbarHideToggle))
    <div
        class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ? ' d-xl-none ' : '' }}">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
            <i class="icon-base ti tabler-menu-2 icon-md"></i>
        </a>
    </div>
@endif

<div class="navbar-nav-right d-flex align-items-center w-100" id="navbar-collapse">

    {{-- LEFT SIDE: Language + Shortcuts --}}
    <ul class="navbar-nav flex-row align-items-center me-auto">
        <!-- Language -->
        <li class="nav-item dropdown-language dropdown me-3">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                <i class="icon-base ti tabler-language rounded-circle icon-md"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" data-language="en" data-text-direction="ltr">English</a>
                </li>
                <li><a class="dropdown-item" href="#" data-language="fr" data-text-direction="ltr">French</a></li>
                <li><a class="dropdown-item" href="#" data-language="ar" data-text-direction="rtl">Arabic</a></li>
                <li><a class="dropdown-item" href="#" data-language="de" data-text-direction="ltr">German</a></li>
            </ul>
        </li>
        <!-- /Language -->

        <!-- Shortcuts -->
        <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown me-3">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown"
                data-bs-auto-close="outside" aria-expanded="false">
                <i class="icon-base ti tabler-clipboard-heart icon-md"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-end py-0">
                <div class="dropdown-menu-header border-bottom">
                    <div class="dropdown-header d-flex align-items-center py-3">
                        <h5 class="text-body mb-0 me-auto">Register Shortcuts</h5>
                        <a href="{{ url('/workspaces/patient-workspace') }}"
                            class="dropdown-shortcuts-add text-muted" data-bs-toggle="tooltip" data-bs-placement="top"
                            title="Patient Workspace">
                            <i class="icon-base ti tabler-sm ti-home text-muted"></i>
                        </a>
                    </div>
                </div>
                <div class="dropdown-shortcuts-list scrollable-container">
                    <div class="row row-bordered g-0">
                        <div class="dropdown-shortcuts-item col">
                            <span class="dropdown-shortcuts-icon bg-label-primary rounded-circle mb-2">
                                <i class="icon-base ti tabler-clipboard-heart fs-4"></i>
                            </span>
                            <a href="{{ url('/registers/antenatal-register') }}" class="stretched-link">Obstetric
                                Register</a>
                            <small class="text-muted mb-0">Antenatal Records</small>
                        </div>
                        <div class="dropdown-shortcuts-item col">
                            <span class="dropdown-shortcuts-icon bg-label-success rounded-circle mb-2">
                                <i class="icon-base ti tabler-baby-carriage fs-4"></i>
                            </span>
                            <a href="{{ url('/registers/general-patients-register') }}" class="stretched-link">General
                                Patients Register</a>
                            <small class="text-muted mb-0">Patient Records</small>
                        </div>
                    </div>
                    <div class="row row-bordered g-0">
                        <div class="dropdown-shortcuts-item col">
                            <span class="dropdown-shortcuts-icon bg-label-info rounded-circle mb-2">
                                <i class="icon-base ti tabler-stethoscope fs-4"></i>
                            </span>
                            <a href="{{ url('/registers/family-planning-register') }}" class="stretched-link">Family
                                Planning Register</a>
                            <small class="text-muted mb-0">Family Planning</small>
                        </div>
                        <div class="dropdown-shortcuts-item col">
                            <span class="dropdown-shortcuts-icon bg-label-warning rounded-circle mb-2">
                                <i class="icon-base ti tabler-notes fs-4"></i>
                            </span>
                            <a href="{{ url('/registers/family-planning-register') }}" class="stretched-link">Family
                                Planning Register</a>
                            <small class="text-muted mb-0">Family Planning</small>
                        </div>
                    </div>
                </div>
            </div>
        </li>
        <!-- /Shortcuts -->
    </ul>

    {{-- RIGHT SIDE: Theme + Notifications + User --}}
    <ul class="navbar-nav flex-row align-items-center ms-auto">
        @if ($configData['hasCustomizer'] == true)
            <!-- Theme Switcher -->
            <li class="nav-item dropdown me-3">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="icon-base ti tabler-sun icon-md theme-icon-active"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button type="button" class="dropdown-item active" data-bs-theme-value="light"><i
                                class="icon-base ti tabler-sun me-3"></i>Light</button></li>
                    <li><button type="button" class="dropdown-item" data-bs-theme-value="dark"><i
                                class="icon-base ti tabler-moon-stars me-3"></i>Dark</button></li>
                    <li><button type="button" class="dropdown-item" data-bs-theme-value="system"><i
                                class="icon-base ti tabler-device-desktop-analytics me-3"></i>System</button></li>
                </ul>
            </li>
            <!-- /Theme Switcher -->
        @endif

        <!-- Notifications -->
        <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                <i class="icon-base ti tabler-bell icon-md"></i>
                <span class="badge bg-danger rounded-pill badge-notifications">5</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end py-0">
                <li class="dropdown-menu-header border-bottom">
                    <div class="dropdown-header d-flex align-items-center py-3">
                        <h5 class="text-body mb-0 me-auto">System Alerts</h5>
                        <span class="badge rounded-pill badge-label-primary">8 New</span>
                    </div>
                </li>
                <li class="dropdown-notifications-list scrollable-container">
                    <!-- Notifications go here -->
                </li>
            </ul>
        </li>
        <!-- /Notifications -->

        <!-- User -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                <i class="icon-base ti tabler-user icon-md"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item mt-0"
                        href="{{ Route::has('profile.show') ? route('profile.show') : '#' }}">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-2">
                                <div class="avatar avatar-online"></div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">
                                    {{ Auth::check() ? Auth::user()->first_name : 'Guest' }}
                                </h6>
                                <small class="text-body-secondary">
                                    {{ Auth::check() ? Auth::user()->role : '' }}
                                </small>
                            </div>
                        </div>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider my-1"></div>
                </li>
                <li><a class="dropdown-item" href="#"><i class="icon-base ti tabler-user me-3 icon-md"></i>My
                        Profile</a></li>
                <li>
                    <div class="dropdown-divider my-1"></div>
                </li>
                <div class="d-grid px-2 pt-2 pb-1">
                    <livewire:logout-button />
                </div>
            </ul>
        </li>
        <!-- /User -->
    </ul>
</div>
