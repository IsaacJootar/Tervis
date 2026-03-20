@php
    use App\Services\Security\RolePermissionService;
    use Illuminate\Support\Facades\Route;
    $authUser = auth()->user();
@endphp

<ul class="menu-sub">
    @if (isset($menu))
        @foreach ($menu as $submenu)
            @if (!RolePermissionService::canRenderMenuNode($authUser, $submenu))
                @continue
            @endif
            {{-- active menu method --}}
            @php
                $active = $configData['layout'] === 'vertical' ? 'active open' : 'active';
                $currentRouteName = Route::currentRouteName();
                $currentPath = request()->path();
                $isActive = RolePermissionService::isMenuNodeActive($submenu, $currentRouteName, $currentPath);
                $activeClass = $isActive ? (isset($submenu->submenu) ? $active : 'active') : null;
            @endphp

            <li class="menu-item {{ $activeClass }}">
                <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}"
                    class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
                    @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
                    @if (isset($submenu->icon))
                        <i class="{{ $submenu->icon }}"></i>
                    @endif
                    <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
                    @isset($submenu->badge)
                        <div class="badge bg-{{ $submenu->badge[0] }} rounded-pill ms-auto">{{ $submenu->badge[1] }}</div>
                    @endisset
                </a>

                {{-- submenu --}}
                @if (isset($submenu->submenu))
                    @include('layouts.sections.menu.submenu', ['menu' => $submenu->submenu])
                @endif
            </li>
        @endforeach
    @endif
</ul>

