{{-- resources/views/layouts/sections/navbar/patientNavbar.blade.php --}}
@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;
    $containerNav = $configData['contentLayout'] === 'compact' ? 'container-xxl' : 'container-fluid';
    $navbarDetached = $navbarDetached ?? '';
@endphp

<!-- Patient Navbar -->
@if (isset($navbarDetached) && $navbarDetached == 'navbar-detached')
    <nav class="layout-navbar {{ $containerNav }} navbar navbar-expand-xl {{ $navbarDetached }} align-items-center bg-navbar-theme patient-navbar"
        id="layout-navbar">
        @include('layouts/sections/navbar/patientNavbar-partial')
    </nav>
@else
    <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme patient-navbar"
        id="layout-navbar">
        <div class="{{ $containerNav }}">@include('layouts/sections/navbar/patientNavbar-partial')</div>
    </nav>
@endif
<!-- / Patient Navbar -->
