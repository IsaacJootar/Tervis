<!DOCTYPE html>
@php
    use Illuminate\Support\Str;
    use App\Helpers\Helpers;

    $menuFixed =
        $configData['layout'] === 'vertical'
            ? $menuFixed ?? ''
            : ($configData['layout'] === 'front'
                ? ''
                : $configData['headerType']);
    $navbarType =
        $configData['layout'] === 'vertical'
            ? $configData['navbarType']
            : ($configData['layout'] === 'front'
                ? 'layout-navbar-fixed'
                : '');
    $isFront = ($isFront ?? '') == true ? 'Front' : '';
    $contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';

    // Get skin name from configData - only applies to admin layouts
    $isAdminLayout = !Str::contains($configData['layout'] ?? '', 'front');
    $skinName = $isAdminLayout ? $configData['skinName'] ?? 'default' : 'default';

    // Get semiDark value from configData - only applies to admin layouts
    $semiDarkEnabled = $isAdminLayout && filter_var($configData['semiDark'] ?? false, FILTER_VALIDATE_BOOLEAN);

    // Generate primary color CSS if color is set
    $primaryColorCSS = '';
    if (isset($configData['color']) && $configData['color']) {
        $primaryColorCSS = Helpers::generatePrimaryColorCSS($configData['color']);
    }

@endphp

<html lang="{{ session()->get('locale') ?? app()->getLocale() }}"
    class="{{ $navbarType ?? '' }} {{ $contentLayout ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}"
    dir="{{ $configData['textDirection'] }}" data-skin="{{ $skinName }}"
    data-assets-path="{{ asset('/assets') . '/' }}" data-base-url="{{ url('/') }}" data-framework="laravel"
    data-template="{{ $configData['layout'] }}-menu-template" data-bs-theme="{{ $configData['theme'] }}"
    @if ($isAdminLayout && $semiDarkEnabled) data-semidark-menu="true" @endif>

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>
        @yield('title') | {{ config('variables.templateName') ? config('variables.templateName') : 'TemplateName' }}
        - {{ config('variables.templateSuffix') ? config('variables.templateSuffix') : 'TemplateSuffix' }}
    </title>
    <meta name="description"
        content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
    <meta name="keywords"
        content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}" />
    <meta property="og:title" content="{{ config('variables.ogTitle') ? config('variables.ogTitle') : '' }}" />
    <meta property="og:type" content="{{ config('variables.ogType') ? config('variables.ogType') : '' }}" />
    <meta property="og:url" content="{{ config('variables.productPage') ? config('variables.productPage') : '' }}" />
    <meta property="og:image" content="{{ config('variables.ogImage') ? config('variables.ogImage') : '' }}" />
    <meta property="og:description"
        content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
    <meta property="og:site_name"
        content="{{ config('variables.creatorName') ? config('variables.creatorName') : '' }}" />
    <meta name="robots" content="noindex, nofollow" />
    <!-- laravel CRUD token -->
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Canonical SEO -->
    <link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}" />
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

    <!-- Include Styles -->
    <!-- $isFront is used to append the front layout styles only on the front layout otherwise the variable will be blank -->
    @include('layouts/sections/styles' . $isFront)

    @if (
        $primaryColorCSS &&
            (config('custom.custom.primaryColor') ||
                isset($_COOKIE['admin-primaryColor']) ||
                isset($_COOKIE['front-primaryColor'])))
        <!-- Primary Color Style -->
        <style id="primary-color-style">
            {!! $primaryColorCSS !!}
        </style>
    @endif

    <!-- Include Scripts for customizer, helper, analytics, config -->
    <!-- $isFront is used to append the front layout scriptsIncludes only on the front layout otherwise the variable will be blank -->
    @include('layouts/sections/scriptsIncludes' . $isFront)


    <!-- Essential DataTables CSS (includes buttons, pagination, responsive) -->
    <link
        href="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.2.2/b-3.2.2/b-html5-3.2.2/b-print-3.2.2/r-3.0.4/datatables.min.css"
        rel="stylesheet" integrity="sha384-Lyca+jsk9Q+XLYmuTBriITsVJpOxGXNqWAWFFT5SdYRiDsUSGoaekwOTIO9kgfem"
        crossorigin="anonymous">
    <!-- FILES FOR DATA TABLES-->
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <!-- PDFMake for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"
        integrity="sha384-VFQrHzqBh5qiJIU0uGU5CIW3+OWpdGGJM9LBnGbuIH2mkICcFZ7lPd/AAtI7SNf7" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"
        integrity="sha384-/RlQG9uf0M2vcTw3CX7fbqgbj/h8wKxw7C3zu9/GxcBPRKOEcESxaxufwRXqzq6n" crossorigin="anonymous">
    </script>

    <!-- Essential DataTables JS (includes all buttons, pagination, responsive) -->


    <script
        src="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.2.2/b-3.2.2/b-html5-3.2.2/b-print-3.2.2/r-3.0.4/datatables.min.js"
        integrity="sha384-/wsDbsz8pRfwq3zQ5D36rGcm7HGUCCg0WxzK0y3yxeRsF7+PKBoPEorAVw441sbW" crossorigin="anonymous">
    </script>
</head>

<body>
    <!-- Layout Content -->
    @yield('layoutContent')
    <!--/ Layout Content -->

    {{-- remove while creating package --}}
    {{-- remove while creating package end --}}

    <!-- Include Scripts -->
    <!-- $isFront is used to append the front layout scripts only on the front layout otherwise the variable will be blank -->
    @include('layouts/sections/scripts' . $isFront)

</body>

</html>
