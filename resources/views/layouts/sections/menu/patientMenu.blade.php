{{-- resources/views/layouts/sections/menu/patientMenu.blade.php --}}
@php
    use App\Services\Security\RolePermissionService;
    use Illuminate\Support\Facades\Route;
    $configData = Helper::appClasses();
    $authUser = auth()->user();

    // Load patient menu data
    $menuDataPath = resource_path('menu/patientMenu.json');
    $menuData = json_decode(file_get_contents($menuDataPath));
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu patient-menu"
    @foreach ($configData['menuAttributes'] as $attribute => $value)
  {{ $attribute }}="{{ $value }}" @endforeach>

    <!-- ! Hide app brand if navbar-full -->
    @if (!isset($navbarFull))
        <div class="app-brand demo">
            <a href="{{ url('/patient/patient-dashboard') }}" class="app-brand-link single-logo-role">
                <span class="app-brand-text demo menu-text fw-bold ms-0 d-flex flex-column lh-sm">
                    <span class="brand-name d-inline-flex align-items-center"><img src="{{ asset('assets/cureva-logo.svg') }}" alt="Cureva" class="brand-wordmark"></span>
                    @php
                        $roleLabel = auth()->user()->role ?? null;
                        $roleBadgeClass = match ($roleLabel) {
                            'Central Admin' => 'bg-label-danger',
                            'Facility Admin' => 'bg-label-warning',
                            'State Officer' => 'bg-label-success',
                            'L.G.A Officer' => 'bg-label-info',
                            'Data Officer' => 'bg-label-primary',
                            'Verification Officer' => 'bg-label-dark',
                            'Doctor', 'Medical Officer' => 'bg-label-success',
                            'Patient' => 'bg-label-secondary',
                            default => 'bg-label-secondary',
                        };
                    @endphp
                    @if ($roleLabel)
                        <small class="badge {{ $roleBadgeClass }} mt-1 align-self-start" style="font-size:0.65rem; padding:0.2rem 0.4rem;">{{ $roleLabel }}</small>
                    @endif
                </span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
                <i class="icon-base ti tabler-x d-block d-xl-none"></i>
            </a>
        </div>
    @endif

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        @foreach ($menuData->menu as $menu)
            {{-- adding active and open class if child is active --}}

            {{-- menu headers --}}
            @if (isset($menu->menuHeader))
                <li class="menu-header small">
                    <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
                </li>
            @else
                @if (!RolePermissionService::canRenderMenuNode($authUser, $menu))
                    @continue
                @endif
                {{-- active menu method --}}
                @php
                    $currentRouteName = Route::currentRouteName();
                    $currentPath = request()->path();
                    $isActive = RolePermissionService::isMenuNodeActive($menu, $currentRouteName, $currentPath);
                    $activeClass = $isActive ? (isset($menu->submenu) ? 'active open' : 'active') : null;
                @endphp

                {{-- main menu --}}
                <li class="menu-item {{ $activeClass }}">
                    <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
                        class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
                        @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
                        @isset($menu->icon)
                            <i class="{{ $menu->icon }}"></i>
                        @endisset
                        <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
                        @isset($menu->badge)
                            <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
                        @endisset
                    </a>

                    {{-- submenu --}}
                    @isset($menu->submenu)
                        @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
                    @endisset
                </li>
            @endif
        @endforeach
    </ul>

</aside>











