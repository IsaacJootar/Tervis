{{-- resources/views/layouts/centralAdminLayout.blade.php --}}
@isset($pageConfigs)
    {!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
    $configData = Helper::appClasses();

    // Override config for central admin
    $configData['layout'] = 'access-auth';
    $configData['theme'] = 'light';
    $configData['navbarType'] = 'fixed';
    $configData['menuFixed'] = 'menu-fixed';
    $configData['skinName'] = 'default';
@endphp

@extends('layouts/commonMaster')

@php
    /* Display elements */
    $contentNavbar = $contentNavbar ?? true;
    $containerNav = $containerNav ?? 'container-xxl';
    $isNavbar = false;
    $isMenu = false;
    $isFlex = $isFlex ?? false;
    $isFooter = $isFooter ?? true;
    $customizerHidden = $customizerHidden ?? '';

    /* Content classes */
    $container =
        isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact'
            ? 'container-xxl'
            : 'container-fluid';
@endphp
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/pages-auth.js'])
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection
@section('layoutContent')
    <div class="layout-wrapper layout-content-navbar {{ $isMenu ? '' : 'layout-without-menu' }} central-admin-layout">
        <div class="layout-container">



            <!-- Layout page -->
            <div class="layout-page">

                {{-- Below commented code read by artisan command while installing jetstream. !! Do not remove if you want to use jetstream. --}}
                {{-- <x-banner /> --}}

                <!-- BEGIN: Navbar-->

                <!-- END: Navbar-->

                <!-- Content wrapper -->
                <div class="content-wrapper">

                    <!-- Content -->
                    @if ($isFlex)
                        <div class="{{ $container }} d-flex align-items-stretch flex-grow-1 p-0">
                            {{ $slot }}
                        </div>
                    @else
                        <div class="{{ $container }} flex-grow-1 container-p-y">
                            {{ $slot }}
                        </div>
                    @endif
                    <!-- / Content -->

                    <!-- Footer -->

                    <!-- / Footer -->
                    <div class="content-backdrop fade"></div>
                </div>
                <!--/ Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        @if ($isMenu)
            <!-- Overlay -->
            <div class="layout-overlay layout-menu-toggle"></div>
        @endif
        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->
@endsection
