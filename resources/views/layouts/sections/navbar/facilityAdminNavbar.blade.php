{{-- resources/views/layouts/sections/navbar/facilityAdminNavbar.blade.php --}}
@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;
    $containerNav = $configData['contentLayout'] === 'compact' ? 'container-xxl' : 'container-fluid';
    $navbarDetached = $navbarDetached ?? '';
@endphp

<!-- Facility Admin Navbar -->
@if (isset($navbarDetached) && $navbarDetached == 'navbar-detached')
    <nav class="layout-navbar {{ $containerNav }} navbar navbar-expand-xl {{ $navbarDetached }} align-items-center bg-navbar-theme facility-admin-navbar"
        id="layout-navbar">
        @include('layouts/sections/navbar/facilityAdminNavbar-partial')
    </nav>
@else
    <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme facility-admin-navbar"
        id="layout-navbar">
        <div class="{{ $containerNav }}">@include('layouts/sections/navbar/facilityAdminNavbar-partial')</div>
    </nav>
@endif
<!-- / Facility Admin Navbar -->
