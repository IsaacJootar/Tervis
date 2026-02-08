{{-- Workspace Card Partial --}}
{{-- Usage: @include('livewire.partials.workspace-card', ['key' => 'attendance', 'title' => 'Attendance', ...]) --}}

@php
    $status = isset($key) && is_string($key) ? ($cardStatus[$key] ?? ['enabled' => false, 'count' => 0, 'label' => 'Records']) : ['enabled' => false, 'count' => 0, 'label' => 'Records'];
    $isEnabled = $status['enabled'] ?? false;
    $count = $status['count'] ?? 0;
    $label = $status['label'] ?? 'Records';
    $requires = $status['requires'] ?? null;
    $iconMap = [
        'attendance' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M7 11h10M7 15h6"></path></svg>',
        'assessments' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M6 4h9a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6z"></path><path d="M8 8h6M8 12h6M8 16h4"></path><circle cx="18.5" cy="8.5" r="2.5"></circle></svg>',
        'anc' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M4 7h16v11H4z"></path><path d="M7 7V5h10v2"></path><path d="M12 10v6"></path><path d="M9 13h6"></path></svg>',
        'tt_vaccinations' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M6 8h10a2 2 0 0 1 2 2v6H6z"></path><path d="M16 8V6a2 2 0 0 1 2-2h2"></path><path d="M10 12v4M8 14h4"></path></svg>',
        'deliveries' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M4 12h12a4 4 0 0 1 4 4v1H4z"></path><circle cx="7" cy="18" r="2"></circle><circle cx="15" cy="18" r="2"></circle><path d="M6 12a6 6 0 0 1 12 0"></path><path d="M18 8h3"></path></svg>',
        'postnatal' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M12 20s-6-4.2-6-8.5A3.5 3.5 0 0 1 12 9a3.5 3.5 0 0 1 6 2.5C18 15.8 12 20 12 20z"></path></svg>',
        'immunizations' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M12 3l7 4v5c0 4-3 7-7 9-4-2-7-5-7-9V7l7-4z"></path><path d="M9 12h6"></path></svg>',
        'nutrition' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M7 4c2 0 3 2 3 4 0 3-2 5-5 5H4V9a5 5 0 0 1 3-5z"></path><path d="M14 4c3 0 6 4 6 7 0 4-3 7-7 7h-1v-5a9 9 0 0 0 2-9z"></path></svg>',
        'laboratory' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M9 3h6M10 3v6l-4 7a4 4 0 0 0 3.5 6h5A4 4 0 0 0 18 16l-4-7V3"></path><path d="M8 14h8"></path></svg>',
        'prescriptions' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><rect x="5" y="4" width="10" height="16" rx="2"></rect><path d="M9 8h4M9 12h6M9 16h4"></path><path d="M16 10l3 3m0-3l-3 3"></path></svg>',
        'invoices' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"></path><path d="M8 8h8M8 12h6"></path></svg>',
        'appointments' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M8 12h5"></path><circle cx="15.5" cy="12.5" r="2.5"></circle></svg>',
        'referrals' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M4 8h9"></path><path d="M13 5l3 3-3 3"></path><path d="M20 16H11"></path><path d="M11 13l-3 3 3 3"></path></svg>',
        'reminders' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M12 4a6 6 0 0 1 6 6v4l2 2H4l2-2v-4a6 6 0 0 1 6-6z"></path><path d="M9 20a3 3 0 0 0 6 0"></path></svg>',
        'family_planning' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M12 20s-6-4.2-6-8.5A3.5 3.5 0 0 1 12 9a3.5 3.5 0 0 1 6 2.5C18 15.8 12 20 12 20z"></path><path d="M12 7V4M10.5 5.5h3"></path></svg>',
        'visits' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><path d="M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6z"></path><path d="M8 8h8M8 12h8M8 16h5"></path></svg>',
        'activities' => '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><circle cx="12" cy="12" r="8"></circle><path d="M12 8v4l3 2"></path></svg>',
    ];
    $iconSvg = $iconMap[$key] ?? '<svg viewBox="0 0 24 24" class="icon-svg" aria-hidden="true"><circle cx="12" cy="12" r="7"></circle><path d="M12 8v4"></path></svg>';
@endphp

<div class="col-md-4 col-lg-3 mb-4">
    <div class="card h-100 shadow-sm workspace-card accent-{{ $color }} {{ !$isEnabled ? 'card-disabled' : '' }}"
        @if ($isEnabled) wire:click="navigateToWorkspace('{{ $key }}')" style="cursor: pointer;"
    @else
    style="opacity: 0.6; cursor: not-allowed;" @endif>
        <div class="card-body">
            <div class="card-topline"></div>

            <div class="d-flex align-items-start mb-3">
                <span class="badge bg-label-{{ $color }} icon-badge me-3">
                    {!! $iconSvg !!}
                </span>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-1 {{ !$isEnabled ? 'text-muted' : '' }}">{{ $title }}</h6>
                        @if (!$isEnabled)
                            <span class="badge bg-label-secondary">
                                <i class="bx bx-lock-alt"></i>
                            </span>
                        @endif
                    </div>
                    <p class="card-text text-muted small mb-0">{{ $description }}</p>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-auto">
                <div class="metric-chip">
                    @if ($isEnabled)
                        <span class="metric-value">{{ $count }}</span>
                        <span class="metric-label">{{ $label }}</span>
                    @else
                        <i class="bx bx-info-circle me-1"></i>
                        <span class="metric-label">{{ $requires ?? 'Not Available' }}</span>
                    @endif
                </div>
                @if ($isEnabled)
                    <div class="cta-pill">Open</div>
                @endif
            </div>
        </div>

        @if ($isEnabled)
            <div class="card-hover-overlay"></div>
        @endif
    </div>
</div>
@once
    <style>
        .workspace-card {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .workspace-card .card-body {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            min-height: 175px;
            gap: 0.25rem;
        }

        .workspace-card:not(.card-disabled):hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
            border-color: rgba(59, 130, 246, 0.22);
            background: #f8fafc;
        }

        .workspace-card:not(.card-disabled):hover .card-hover-overlay {
            opacity: 1;
        }

        .card-hover-overlay {
            position: absolute;
            inset: 0;
            background: radial-gradient(90% 90% at 100% 0%, rgba(37, 99, 235, 0.06), rgba(37, 99, 235, 0));
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .card-topline {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent, #2563eb);
            opacity: 0.9;
        }


        .icon-badge {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .icon-svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
            stroke-width: 1.7;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .workspace-card:not(.card-disabled):hover .icon-badge {
            filter: saturate(1.1);
        }

        .workspace-card:not(.card-disabled):hover .cta-pill {
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.18);
        }

        .workspace-card:not(.card-disabled):hover .card-topline {
            opacity: 1;
        }

        .metric-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            font-size: 0.78rem;
            color: #0f172a;
            white-space: nowrap;
        }

        .metric-value {
            font-weight: 700;
            color: var(--accent, #2563eb);
        }

        .metric-label {
            color: #334155;
        }

        .cta-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            background: var(--accent, #2563eb);
            color: white;
            font-size: 0.78rem;
            font-weight: 600;
            box-shadow: none;
        }

        .workspace-card .card-text {
            line-height: 1.35;
            margin-top: 2px;
        }

        .accent-primary { --accent: #2563eb; }
        .accent-info { --accent: #0ea5e9; }
        .accent-success { --accent: #16a34a; }
        .accent-warning { --accent: #f59e0b; }
        .accent-danger { --accent: #ef4444; }
        .accent-secondary { --accent: #64748b; }
        .accent-dark { --accent: #0f172a; }
        .accent-pink { --accent: #ec4899; }

        .card-disabled {
            background: linear-gradient(180deg, #f3f5f7, #f9fafb);
            border-color: #e5e7eb;
        }

        .card-disabled .icon-badge {
            background: #eef2f7 !important;
            color: #94a3b8 !important;
        }
    </style>
@endonce
