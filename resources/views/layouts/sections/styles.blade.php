<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
    href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
    rel="stylesheet" />

<!-- Fonts Icons -->
@vite(['resources/assets/vendor/fonts/iconify/iconify.css'])

<!-- BEGIN: Vendor CSS-->
@vite(['resources/assets/vendor/libs/node-waves/node-waves.scss'])

@if ($configData['hasCustomizer'])
    @vite(['resources/assets/vendor/libs/pickr/pickr-themes.scss'])
@endif

<!-- Core CSS -->
@vite(['resources/assets/vendor/scss/core.scss', 'resources/assets/css/demo.css', 'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss'])

<!-- Vendor Styles -->
@vite(['resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss', 'resources/assets/vendor/libs/typeahead-js/typeahead.scss'])
@yield('vendor-style')

<!-- Page Styles -->
@yield('page-style')

<!-- app CSS -->
@vite(['resources/css/app.css'])
<!-- END: app CSS-->

<!-- Hero Card Styles -->
<style>
    :root {
        --primary: #4361ee;
        --primary-light: rgba(67, 97, 238, 0.1);
        --success: #2ecc71;
        --success-light: rgba(46, 204, 113, 0.1);
        --info: #00b4d8;
        --info-light: rgba(0, 255, 216, 0.1);
        --danger: #e63946;
        --danger-light: rgba(230, 57, 70, 0.1);
        --warning: #ff9f1c;
        --warning-light: rgba(255, 159, 28, 0.1);
        --purple: #7209b7;
        --purple-light: rgba(114, 9, 183, 0.1);
        --teal: #1abc9c;
        --teal-light: rgba(26, 188, 156, 0.1);
        --indigo: #3a0ca3;
        --indigo-light: rgba(58, 12, 163, 0.1);
        --orange: #f77f00;
        --orange-light: rgba(247, 127, 0, 0.1);
        --cyan: #00bbf9;
        --cyan-light: rgba(0, 187, 249, 0.1);
        --pink: #ff70a6;
        --pink-light: rgba(255, 112, 166, 0.1);
        --red: #d00000;
        --red-light: rgba(208, 0, 0, 0.1);
        --softgreen: #a7f3d0;
        --softgreen-light: rgba(167, 243, 208, 0.1);
        --lavender: #e9d5ff;
        --lavender-light: rgba(233, 213, 255, 0.1);
        --mutedgreen: #4b5563;
        --mutedgreen-light: rgba(75, 85, 99, 0.1);
        --black: #1f2937;
        --black-light: rgba(31, 41, 55, 0.1);
        --laundry: #4b5e7d;
        --laundry-light: rgba(75, 94, 125, 0.1);
        --others: #6d28d9;
        --others-light: rgba(109, 40, 217, 0.1);
        --body-bg: #f5f5f9;
        --card-bg: #ffffff;
        --card-border: #e9ecef;
        --body-color: #697a8d;
        --heading-color: #566a7f;
        --primary-gradient: linear-gradient(135deg, #5a4bff 0%, #7b6eff 100%);
        --success-gradient: linear-gradient(135deg, #2ecc71 0%, #4ade80 100%);
        --softgreen-gradient: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%);
        --lavender-gradient: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%);
        --teal-gradient: linear-gradient(135deg, #20c997 0%, #34d399 100%);
        --orange-gradient: linear-gradient(135deg, #f77f00 0%, #fb923c 100%);
        --cyan-gradient: linear-gradient(135deg, #00bbf9 0%, #22d3ee 100%);
        --mutedgreen-gradient: linear-gradient(135deg, #4b5563 0%, #6b7280 100%);
        --black-gradient: linear-gradient(135deg, #1f2937 0%, #374151 100%);
        --laundry-gradient: linear-gradient(135deg, #4b5e7d 0%, #6b7280 100%);
        --others-gradient: linear-gradient(135deg, #6d28d9 0%, #8b5cf6 100%);
    }

    .hero-card {
        background: var(--primary-gradient);
        border: none;
        border-radius: 0.625rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: all 0.2s ease-in-out;
        position: relative;
        overflow: hidden;
    }

    .hero-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .hero-content {
        padding: 1.5rem;
        position: relative;
        z-index: 1;
    }

    .hero-text {
        color: #fff;
    }

    .hero-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #fff;
    }

    .hero-subtitle {
        font-size: 1rem;
        opacity: 0.75;
        margin-bottom: 1rem;
        color: #fff;
    }

    .hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .hero-stat {
        display: flex;
        align-items: center;
        font-size: 0.9rem;
        font-weight: 500;
        color: #fff;
        opacity: 0.9;
    }

    .hero-stat i {
        margin-right: 0.5rem;
        font-size: 1.2rem;
    }

    .hero-decoration {
        position: absolute;
        top: 0;
        right: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    .floating-shape {
        position: absolute;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .shape-1 {
        width: 100px;
        height: 100px;
        top: -20px;
        right: -20px;
        animation: float 6s ease-in-out infinite;
    }

    .shape-2 {
        width: 60px;
        height: 60px;
        top: 50%;
        right: 20px;
        animation: float 8s ease-in-out infinite;
    }

    .shape-3 {
        width: 80px;
        height: 80px;
        bottom: -10px;
        right: 50px;
        animation: float 7s ease-in-out infinite;
    }

    @keyframes float {
        0% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-20px);
        }

        100% {
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 1.25rem;
        }

        .hero-content {
            padding: 1rem;
        }

        .hero-stats {
            flex-direction: column;
            gap: 0.75rem;
        }

        .hero-stat {
            font-size: 0.85rem;
        }

        .hero-stat i {
            font-size: 1rem;
        }

        .shape-1 {
            width: 80px;
            height: 80px;
        }

        .shape-2 {
            width: 50px;
            height: 50px;
        }

        .shape-3 {
            width: 60px;
            height: 60px;
        }
    }

    /* DataTables Button Styling - Vuexy Style */
    /* DataTables Button Styling - Vuexy Style */
    .dt-buttons .btn {
        background-color: #696cff !important;
        border-color: #696cff !important;
        color: white !important;
        margin-right: 0.5rem;
        border-radius: 0.375rem !important;
        font-weight: 500 !important;
        padding: 0.4375rem 1.25rem !important;
    }

    .dt-buttons .btn:hover {
        background-color: #5f61e6 !important;
        border-color: #5f61e6 !important;
        color: white !important;
    }

    .dt-buttons .btn:focus,
    .dt-buttons .btn:active {
        background-color: #5f61e6 !important;
        border-color: #5f61e6 !important;
        color: white !important;
        box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25) !important;
    }

    /* Hide individual buttons and create dropdown */
    .dt-buttons {
        position: relative;
    }

    .dt-buttons .btn:not(:first-child) {
        display: none !important;
    }

    .dt-buttons .btn:first-child::after {
        margin-left: 0.5rem;
    }

    .dt-buttons:hover .btn:not(:first-child) {
        display: inline-block !important;
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1000;
        background: white !important;
        color: #696cff !important;
        border: 1px solid #dee2e6 !important;
        margin-top: 0.25rem;
        min-width: 120px;
    }

    .dt-buttons:hover .btn:not(:first-child):hover {
        background-color: #f8f9fa !important;
        color: #696cff !important;
    }
</style>
<!-- DataTables CSS -->

<!-- app CSS -->
@vite(['resources/css/app.css'])
<!-- END: app CSS-->
