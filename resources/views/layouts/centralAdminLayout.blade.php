{{-- resources/views/layouts/centralAdminLayout.blade.php --}}
@isset($pageConfigs)
    {!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
    $configData = Helper::appClasses();

    // Override config for central admin
    $configData['layout'] = 'vertical';
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
    $isNavbar = $isNavbar ?? true;
    $isMenu = $isMenu ?? true;
    $isFlex = $isFlex ?? false;
    $isFooter = $isFooter ?? true;
    $customizerHidden = $customizerHidden ?? '';

    /* HTML Classes */
    $navbarDetached = 'navbar-detached';
    $menuFixed = isset($configData['menuFixed']) ? $configData['menuFixed'] : '';
    if (isset($navbarType)) {
        $configData['navbarType'] = $navbarType;
    }
    $navbarType = isset($configData['navbarType']) ? $configData['navbarType'] : '';
    $footerFixed = isset($configData['footerFixed']) ? $configData['footerFixed'] : '';
    $menuCollapsed = isset($configData['menuCollapsed']) ? $configData['menuCollapsed'] : '';

    /* Content classes */
    $container =
        isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact'
            ? 'container-xxl'
            : 'container-fluid';
@endphp

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/fonts/flag-icons.scss'])
@endsection
@section('page-style')
    @vite('resources/assets/vendor/scss/pages/cards-advance.scss')
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/dashboards-analytics.js')
@endsection

@section('layoutContent')
    <div class="layout-wrapper layout-content-navbar {{ $isMenu ? '' : 'layout-without-menu' }} central-admin-layout">
        <div class="layout-container">

            @if ($isMenu)
                @include('layouts/sections/menu/centralAdminMenu')
            @endif

            <!-- Layout page -->
            <div class="layout-page">

                {{-- Below commented code read by artisan command while installing jetstream. !! Do not remove if you want to use jetstream. --}}
                {{-- <x-banner /> --}}

                <!-- BEGIN: Navbar-->
                @if ($isNavbar)
                    @include('layouts/sections/navbar/centralAdminNavbar')
                @endif
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
                    @if ($isFooter)
                        @include('layouts/sections/footer/footer')
                    @endif
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
    <style>
        /* ===============================
                                                   CARDS
                                                =============================== */
        .card {
            box-shadow: 0 2px 6px rgba(67, 89, 113, 0.12);
            border: 1px solid rgba(67, 89, 113, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 89, 113, 0.16);
        }

        /* ===============================
                                                   AVATAR
                                                =============================== */
        .avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-initial {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-initial-small {
            width: 32px;
            height: 32px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ===============================
                                                   LABEL COLORS
                                                =============================== */
        .bg-label-primary {
            background-color: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .bg-label-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .bg-label-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .bg-label-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .bg-label-secondary {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }
        }
    </style>



    <!-- / Layout wrapper -->
@endsection
