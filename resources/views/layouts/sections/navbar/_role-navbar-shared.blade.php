@php
    use Illuminate\Support\Facades\Auth;
    use App\Services\Security\RolePermissionService;

    $authUser = Auth::user();
    $context = $navbarContext ?? ['quick_links' => [], 'alert_count' => 0, 'alert_items' => [], 'failed_count' => 0];
    $quickLinks = is_array($context['quick_links'] ?? null) ? $context['quick_links'] : [];
    $alertItems = is_array($context['alert_items'] ?? null) ? $context['alert_items'] : [];
    $alertCount = (int) ($context['alert_count'] ?? 0);
    $failedCount = (int) ($context['failed_count'] ?? 0);

    $shortcutTitle = $shortcutTitle ?? 'Quick Links';
    $shortcutIcon = $shortcutIcon ?? 'tabler-layout-grid-add';
    $homeUrl = $homeUrl ?? '/';
    $roleLabel = $roleLabel ?? 'User';
    $displayName = trim(((string) ($authUser?->first_name ?? '')) . ' ' . ((string) ($authUser?->last_name ?? '')));
    $displayName = $displayName !== '' ? $displayName : ((string) ($authUser?->first_name ?? 'Guest'));
    $initials = strtoupper(substr((string) ($authUser?->first_name ?? 'G'), 0, 1) . substr((string) ($authUser?->last_name ?? ''), 0, 1));

    $alertsUrl = null;
    if (RolePermissionService::canAccessMenuUrl($authUser, '/central/platform-notifications')) {
        $alertsUrl = '/central/platform-notifications';
    } elseif (RolePermissionService::canAccessMenuUrl($authUser, '/core/reminders-notifications-hub')) {
        $alertsUrl = '/core/reminders-notifications-hub';
    } elseif (RolePermissionService::canAccessMenuUrl($authUser, '/workspaces/pending-queues')) {
        $alertsUrl = '/workspaces/pending-queues';
    }
@endphp
@once
    <style>
        .navbar-shortcuts-center {
            width: min(92vw, 760px);
            border-radius: 0.85rem;
            overflow: hidden;
        }

        .navbar-shortcuts-center .dropdown-shortcuts-list {
            max-height: min(62vh, 480px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .dropdown-user-modern {
            min-width: 260px;
            border-radius: 0.85rem;
        }

        .user-identity-card {
            border: 1px solid rgba(34, 48, 62, 0.08);
            border-radius: 0.75rem;
            padding: 0.6rem 0.7rem;
            background: #fff;
        }
    </style>
    <script>
        (function() {
            if (window.__curevaShortcutsCentered) return;
            window.__curevaShortcutsCentered = true;

            const centerShortcutMenu = (dropdownRoot) => {
                const menu = dropdownRoot.querySelector('.navbar-shortcuts-center');
                const toggle = dropdownRoot.querySelector('[data-bs-toggle="dropdown"]');
                if (!menu || !toggle) return;

                const rect = toggle.getBoundingClientRect();
                const top = Math.max(56, rect.bottom + 8);
                menu.style.position = 'fixed';
                menu.style.left = '50%';
                menu.style.right = 'auto';
                menu.style.top = top + 'px';
                menu.style.transform = 'translateX(-50%)';
                menu.style.zIndex = '1085';
            };

            document.addEventListener('shown.bs.dropdown', function(event) {
                const trigger = event.target;
                if (!(trigger instanceof Element)) return;
                const dropdownRoot = trigger.closest('.dropdown-shortcuts');
                if (!dropdownRoot) return;
                centerShortcutMenu(dropdownRoot);
            });

            window.addEventListener('resize', function() {
                document.querySelectorAll('.dropdown-shortcuts.show').forEach(centerShortcutMenu);
            });
        })();
    </script>
@endonce

@if (isset($navbarFull))
    <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4 ms-0">
        <a href="{{ url($homeUrl) }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="{{ asset('assets/cureva-logo.svg') }}" alt="Cureva" height="38">
            </span>
            <small>{{ $roleLabel }}.</small>
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
    <ul class="navbar-nav flex-row align-items-center me-auto">
        <li class="nav-item dropdown-language dropdown me-3">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                <i class="icon-base ti tabler-language rounded-circle icon-md"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" data-language="en" data-text-direction="ltr">English</a></li>
                <li><a class="dropdown-item" href="#" data-language="fr" data-text-direction="ltr">French</a></li>
                <li><a class="dropdown-item" href="#" data-language="ar" data-text-direction="rtl">Arabic</a></li>
                <li><a class="dropdown-item" href="#" data-language="de" data-text-direction="ltr">German</a></li>
            </ul>
        </li>

        <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown me-3">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown"
                data-bs-auto-close="outside" aria-expanded="false">
                <i class="icon-base ti {{ $shortcutIcon }} icon-md"></i>
            </a>
            <div class="dropdown-menu py-0 navbar-shortcuts-center">
                <div class="dropdown-menu-header border-bottom">
                    <div class="dropdown-header d-flex align-items-center py-3">
                        <h5 class="text-body mb-0 me-auto">{{ $shortcutTitle }}</h5>
                        <a href="{{ url($homeUrl) }}" class="dropdown-shortcuts-add text-muted" data-bs-toggle="tooltip"
                            data-bs-placement="top" title="Home">
                            <i class="icon-base ti tabler-sm ti-home text-muted"></i>
                        </a>
                    </div>
                </div>

                <div class="dropdown-shortcuts-list scrollable-container">
                    @if (count($quickLinks) > 0)
                        @foreach (array_chunk($quickLinks, 2) as $row)
                            <div class="row row-bordered g-0">
                                @foreach ($row as $link)
                                    <div class="dropdown-shortcuts-item col">
                                        <span class="dropdown-shortcuts-icon bg-label-{{ strtolower((string) ($link['meta'] ?? 'primary')) }} rounded-circle mb-2">
                                            <i class="{{ $link['icon'] ?? 'icon-base ti tabler-link' }} fs-4"></i>
                                        </span>
                                        <a href="{{ url((string) ($link['url'] ?? '/')) }}" class="stretched-link">{{ $link['name'] ?? 'Open' }}</a>
                                        <small class="text-muted mb-0">Role-approved</small>
                                    </div>
                                @endforeach
                                @if (count($row) === 1)
                                    <div class="dropdown-shortcuts-item col"></div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="px-3 py-3 text-muted small">No shortcuts available for your current role permissions.</div>
                    @endif
                </div>
            </div>
        </li>
    </ul>

    <ul class="navbar-nav flex-row align-items-center ms-auto">
        @if ($configData['hasCustomizer'] == true)
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
        @endif

        <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                <i class="icon-base ti tabler-bell icon-md"></i>
                <span class="badge bg-danger rounded-pill badge-notifications">{{ $alertCount }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end py-0">
                <li class="dropdown-menu-header border-bottom">
                    <div class="dropdown-header d-flex align-items-center py-3">
                        <h5 class="text-body mb-0 me-auto">System Alerts</h5>
                        <span class="badge rounded-pill badge-label-primary">{{ $alertCount }} Pending</span>
                    </div>
                </li>
                <li class="dropdown-notifications-list scrollable-container" style="max-height: 320px; overflow-y: auto;">
                    @if (count($alertItems) > 0)
                        <ul class="list-group list-group-flush">
                            @foreach ($alertItems as $alert)
                                <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                    <div class="d-flex gap-2">
                                        <div class="flex-shrink-0">
                                            <div class="avatar avatar-sm">
                                                <span class="avatar-initial rounded-circle bg-label-warning"><i class="icon-base ti tabler-bell"></i></span>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column flex-grow-1 overflow-hidden">
                                            <h6 class="mb-1 text-truncate">{{ $alert['title'] ?? 'Reminder pending' }}</h6>
                                            <small class="text-body d-block text-truncate">{{ $alert['subtitle'] ?? '' }}</small>
                                            <small class="text-body-secondary text-capitalize">{{ $alert['status'] ?? 'pending' }}</small>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="px-3 py-3 text-muted small">No pending queued reminders in your scope.</div>
                    @endif
                </li>
                @if ($alertsUrl)
                    <li class="border-top">
                        <a href="{{ url($alertsUrl) }}" class="dropdown-item text-center py-2">
                            View All Alerts @if ($failedCount > 0)<span class="ms-1 text-danger">({{ $failedCount }} failed/24h)</span>@endif
                        </a>
                    </li>
                @endif
            </ul>
        </li>

        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                <i class="icon-base ti tabler-user icon-md"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end dropdown-user-modern">
                <li class="px-2 pt-2 pb-1">
                    <a class="dropdown-item user-identity-card" href="{{ route('account-settings') }}">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded-circle bg-label-primary">{{ $initials }}</span>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <h6 class="mb-0 text-truncate">{{ $displayName }}</h6>
                                <small class="text-body-secondary text-truncate d-block">{{ $authUser?->role ?? '' }}</small>
                            </div>
                        </div>
                    </a>
                </li>
                <li><div class="dropdown-divider my-1"></div></li>
                <li>
                    <a class="dropdown-item" href="{{ route('account-settings') }}">
                        <i class="icon-base ti tabler-user-cog me-3 icon-md"></i>Account Settings
                    </a>
                </li>
                <li><div class="dropdown-divider my-1"></div></li>
                <li class="px-2 pt-1 pb-2">
                    <livewire:logout-button />
                </li>
            </ul>
        </li>
    </ul>
</div>
