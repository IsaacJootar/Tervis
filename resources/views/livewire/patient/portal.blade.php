<div class="patient-portal-page">
    <div class="card hero-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-start gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="hero-icon">
                            @switch($section)
                                @case('profile')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.8" />
                                        <path d="M5 19c1.8-3 4.2-4.5 7-4.5S17.2 16 19 19" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" />
                                    </svg>
                                @break

                                @case('antenatal')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" />
                                        <rect x="4" y="4" width="16" height="16" rx="4" stroke="currentColor"
                                            stroke-width="1.8" />
                                    </svg>
                                @break

                                @case('deliveries')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M7 14h10l-1.2-4.2A3 3 0 0012.9 7h-1.8a3 3 0 00-2.9 2.2L7 14z"
                                            stroke="currentColor" stroke-width="1.8" />
                                        <circle cx="9" cy="17.5" r="1.5" fill="currentColor" />
                                        <circle cx="15" cy="17.5" r="1.5" fill="currentColor" />
                                    </svg>
                                @break

                                @case('postnatal')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M12 20s-6.5-3.8-6.5-9A3.5 3.5 0 019 8.1c1.3 0 2.3.6 3 1.6.7-1 1.7-1.6 3-1.6a3.5 3.5 0 013.5 2.9c0 5.2-6.5 9-6.5 9z"
                                            stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                    </svg>
                                @break

                                @case('tetanus')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M12 4l6 2.5v4.8c0 4.2-2.6 7.8-6 8.7-3.4-.9-6-4.5-6-8.7V6.5L12 4z"
                                            stroke="currentColor" stroke-width="1.8" />
                                        <path d="M9.5 12.2l1.7 1.7 3.3-3.6" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                @break

                                @case('attendance')
                                @case('appointments')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <rect x="4" y="6" width="16" height="14" rx="2.2" stroke="currentColor"
                                            stroke-width="1.8" />
                                        <path d="M8 4v4M16 4v4M4 10h16" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" />
                                    </svg>
                                @break

                                @case('activities')
                                @case('visits')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M5 14h3l2-5 4 10 2-5h3" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                @break

                                @case('assessments')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.8" />
                                        <path d="M6 18h12" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" />
                                        <path d="M9 14.5h6" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" />
                                    </svg>
                                @break

                                @case('reminders')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M12 5a4 4 0 00-4 4v2.6L6.7 14a1 1 0 00.8 1.6h9a1 1 0 00.8-1.6L16 11.6V9a4 4 0 00-4-4z"
                                            stroke="currentColor" stroke-width="1.8" />
                                        <path d="M10 18a2 2 0 004 0" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" />
                                    </svg>
                                @break

                                @case('laboratory')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M10 4v6l-3.5 5.8A2.5 2.5 0 008.6 20h6.8a2.5 2.5 0 002.1-4.2L14 10V4"
                                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                @break

                                @case('prescriptions')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M8 8.5a3.5 3.5 0 117 0c0 1.2-.6 2.2-1.5 2.9L9.8 15a2.2 2.2 0 01-3.1-3.1l3.6-3.7A3.5 3.5 0 0113 7"
                                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                    </svg>
                                @break

                                @case('invoices')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M8 4h8l3 3v13l-2-1-2 1-2-1-2 1-2-1-2 1V4z" stroke="currentColor"
                                            stroke-width="1.8" stroke-linejoin="round" />
                                        <path d="M9 10h6M9 13h6" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" />
                                    </svg>
                                @break

                                @case('referrals')
                                @case('family-planning')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M7 7h10M7 17h10M14 4l3 3-3 3M10 14l-3 3 3 3" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                @break

                                @case('health-insurance')
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M12 4l6 2.5v4.8c0 4.2-2.6 7.8-6 8.7-3.4-.9-6-4.5-6-8.7V6.5L12 4z"
                                            stroke="currentColor" stroke-width="1.8" />
                                        <path d="M8.8 12.2h6.4M12 9v6.4" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" />
                                    </svg>
                                @break

                                @default
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <rect x="4" y="5" width="16" height="14" rx="3" stroke="currentColor"
                                            stroke-width="1.8" />
                                        <path d="M8 12h3M13.5 12H16M12 8.5v7" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" />
                                    </svg>
                            @endswitch
                        </span>
                        <h5 class="mb-0">{{ $heading }}</h5>
                    </div>
                    <div class="small text-muted">{{ $description }}</div>
                    <div class="small text-muted">{{ $currentDateTime }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="badge bg-label-primary"><i class="bx bx-id-card me-1"></i>DIN:
                            {{ $user->DIN ?? 'N/A' }}</span>
                        <span class="badge bg-label-info"><i class="bx bx-building me-1"></i>{{ $registration_facility_name }}</span>
                        <span class="badge bg-label-success"><i class="bx bx-user me-1"></i>{{ $user->first_name }}
                            {{ $user->last_name }}</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('account-settings') }}" class="btn btn-dark">
                        <i class="bx bx-cog me-1"></i>Account Settings
                    </a>
                    @if ($section !== 'dashboard')
                        <a href="{{ route('patient-dashboard') }}" class="btn btn-outline-dark">
                            <i class="bx bx-home-alt me-1"></i>Back to Dashboard
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="portal-nav mb-4">
        @foreach ($portalSections as $sectionLink)
            <a href="{{ route($sectionLink['route']) }}"
                class="portal-nav-link {{ $section === $sectionLink['key'] ? 'active' : '' }}">
                <i class="bx {{ $sectionLink['icon'] }}"></i>
                <span>{{ $sectionLink['label'] }}</span>
            </a>
        @endforeach
    </div>

    @if (!empty($sectionMetrics))
        <div class="row g-4 mb-4">
            @foreach ($sectionMetrics as $metric)
                <div class="col-md-4 col-xl-{{ count($sectionMetrics) > 3 ? '2' : '4' }}">
                    <div class="metric-card metric-card-{{ $metric['tone'] }} h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="metric-label">{{ $metric['label'] }}</div>
                            <span class="metric-icon">
                                @switch($metric['icon'])
                                    @case('antenatal')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8"
                                                stroke-linecap="round" />
                                            <rect x="4" y="4" width="16" height="16" rx="4" stroke="currentColor"
                                                stroke-width="1.8" />
                                        </svg>
                                    @break

                                    @case('delivery')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M7 14h10l-1.2-4.2A3 3 0 0012.9 7h-1.8a3 3 0 00-2.9 2.2L7 14z"
                                                stroke="currentColor" stroke-width="1.8" />
                                            <circle cx="9" cy="17.5" r="1.5" fill="currentColor" />
                                            <circle cx="15" cy="17.5" r="1.5" fill="currentColor" />
                                        </svg>
                                    @break

                                    @case('postnatal')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M12 20s-6.5-3.8-6.5-9A3.5 3.5 0 019 8.1c1.3 0 2.3.6 3 1.6.7-1 1.7-1.6 3-1.6a3.5 3.5 0 013.5 2.9c0 5.2-6.5 9-6.5 9z"
                                                stroke="currentColor" stroke-width="1.8"
                                                stroke-linejoin="round" />
                                        </svg>
                                    @break

                                    @case('shield')
                                    @case('security')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M12 4l6 2.5v4.8c0 4.2-2.6 7.8-6 8.7-3.4-.9-6-4.5-6-8.7V6.5L12 4z"
                                                stroke="currentColor" stroke-width="1.8" />
                                            <path d="M9.5 12.2l1.7 1.7 3.3-3.6" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                    @break

                                    @case('calendar')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <rect x="4" y="6" width="16" height="14" rx="2.2"
                                                stroke="currentColor" stroke-width="1.8" />
                                            <path d="M8 4v4M16 4v4M4 10h16" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" />
                                        </svg>
                                    @break

                                    @case('id-card')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <rect x="4" y="6" width="16" height="12" rx="2.2"
                                                stroke="currentColor" stroke-width="1.8" />
                                            <circle cx="9" cy="11" r="1.5" stroke="currentColor"
                                                stroke-width="1.8" />
                                            <path d="M12.5 10h4M12.5 13h4" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" />
                                        </svg>
                                    @break

                                    @case('user')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="8" r="3.5" stroke="currentColor"
                                                stroke-width="1.8" />
                                            <path d="M5 19c1.8-3 4.2-4.5 7-4.5S17.2 16 19 19"
                                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                        </svg>
                                    @break

                                    @case('pregnancy')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M9 5.5A3.5 3.5 0 1114.2 8v3.2a4.2 4.2 0 003 4"
                                                stroke="currentColor" stroke-width="1.8"
                                                stroke-linecap="round" />
                                            <path d="M8 18.5c1.2.9 2.6 1.5 4 1.5 2.6 0 4.8-1.6 5.8-4"
                                                stroke="currentColor" stroke-width="1.8"
                                                stroke-linecap="round" />
                                        </svg>
                                    @break

                                    @case('visit')
                                    @case('appointment')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <rect x="4" y="6" width="16" height="14" rx="2.2"
                                                stroke="currentColor" stroke-width="1.8" />
                                            <path d="M8 4v4M16 4v4M4 10h16" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" />
                                        </svg>
                                    @break

                                    @case('facility')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M6 20V7l6-3 6 3v13" stroke="currentColor"
                                                stroke-width="1.8" stroke-linejoin="round" />
                                            <path d="M10 12h4M10 16h4" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" />
                                        </svg>
                                    @break

                                    @case('activity')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M5 14h3l2-5 4 10 2-5h3" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                    @break

                                    @case('assessment')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="8" r="3.5" stroke="currentColor"
                                                stroke-width="1.8" />
                                            <path d="M6 18h12" stroke="currentColor" stroke-width="1.8"
                                                stroke-linecap="round" />
                                        </svg>
                                    @break

                                    @case('reminder')
                                    @case('alert')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M12 5a4 4 0 00-4 4v2.6L6.7 14a1 1 0 00.8 1.6h9a1 1 0 00.8-1.6L16 11.6V9a4 4 0 00-4-4z"
                                                stroke="currentColor" stroke-width="1.8" />
                                            <path d="M10 18a2 2 0 004 0" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" />
                                        </svg>
                                    @break

                                    @case('lab')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M10 4v6l-3.5 5.8A2.5 2.5 0 008.6 20h6.8a2.5 2.5 0 002.1-4.2L14 10V4"
                                                stroke="currentColor" stroke-width="1.8"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    @break

                                    @case('prescription')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M8 8.5a3.5 3.5 0 117 0c0 1.2-.6 2.2-1.5 2.9L9.8 15a2.2 2.2 0 01-3.1-3.1l3.6-3.7A3.5 3.5 0 0113 7"
                                                stroke="currentColor" stroke-width="1.8"
                                                stroke-linecap="round" />
                                        </svg>
                                    @break

                                    @case('invoice')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M8 4h8l3 3v13l-2-1-2 1-2-1-2 1-2-1-2 1V4z"
                                                stroke="currentColor" stroke-width="1.8"
                                                stroke-linejoin="round" />
                                            <path d="M9 10h6M9 13h6" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" />
                                        </svg>
                                    @break

                                    @case('referral')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M7 7h10M7 17h10M14 4l3 3-3 3M10 14l-3 3 3 3"
                                                stroke="currentColor" stroke-width="1.8"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    @break

                                    @case('insurance')
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M12 4l6 2.5v4.8c0 4.2-2.6 7.8-6 8.7-3.4-.9-6-4.5-6-8.7V6.5L12 4z"
                                                stroke="currentColor" stroke-width="1.8" />
                                            <path d="M8.8 12.2h6.4M12 9v6.4" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" />
                                        </svg>
                                    @break

                                    @default
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <rect x="5" y="5" width="14" height="14" rx="3"
                                                stroke="currentColor" stroke-width="1.8" />
                                            <path d="M8.5 10.5h7M8.5 13.5h7" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" />
                                        </svg>
                                @endswitch
                            </span>
                        </div>
                        <div class="metric-value metric-text">{{ $metric['value'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @include($contentView)

    <style>
        .patient-portal-page .hero-card {
            border: 1px solid rgba(148, 163, 184, 0.24);
            border-radius: 18px;
            box-shadow: 0 12px 28px -24px rgba(15, 23, 42, 0.5);
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        }

        .patient-portal-page .hero-card h5 {
            color: #0f172a;
            font-weight: 700;
        }

        .patient-portal-page .hero-icon {
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
        }

        .patient-portal-page .hero-icon svg {
            width: 22px;
            height: 22px;
        }

        .patient-portal-page .portal-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 4px;
        }

        .patient-portal-page .portal-nav-link {
            border: 1px solid rgba(148, 163, 184, 0.22);
            background: #fff;
            color: #334155;
            border-radius: 999px;
            padding: 10px 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            box-shadow: 0 10px 26px -22px rgba(15, 23, 42, 0.35);
            transition: all 0.18s ease;
        }

        .patient-portal-page .portal-nav-link:hover {
            border-color: #bfdbfe;
            color: #1d4ed8;
            transform: translateY(-1px);
        }

        .patient-portal-page .portal-nav-link.active {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .patient-portal-page .metric-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            padding: 14px 16px;
            min-height: 108px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 26px -22px rgba(15, 23, 42, 0.45);
        }

        .patient-portal-page .metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-weight: 700;
        }

        .patient-portal-page .metric-value {
            margin-top: 8px;
            line-height: 1.25;
        }

        .patient-portal-page .metric-text {
            font-size: 1.02rem;
            font-weight: 700;
            word-break: break-word;
        }

        .patient-portal-page .metric-icon {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.08);
        }

        .patient-portal-page .metric-icon svg {
            width: 18px;
            height: 18px;
        }

        .patient-portal-page .metric-card-slate {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
        }

        .patient-portal-page .metric-card-sky {
            border-color: #bae6fd;
            background: #f0f9ff;
            color: #0c4a6e;
        }

        .patient-portal-page .metric-card-emerald {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #065f46;
        }

        .patient-portal-page .metric-card-amber {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .patient-portal-page .metric-card-rose {
            border-color: #fecdd3;
            background: #fff1f2;
            color: #9f1239;
        }

        .patient-portal-page .metric-card-mint {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .patient-portal-page .portal-section-card {
            border: 1px solid rgba(148, 163, 184, 0.24);
            border-radius: 18px;
            box-shadow: 0 12px 30px -24px rgba(15, 23, 42, 0.42);
        }

        .patient-portal-page .portal-section-icon {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        .patient-portal-page .portal-section-icon svg {
            width: 16px;
            height: 16px;
        }

        .patient-portal-page .portal-section-title {
            color: #0f172a;
            font-weight: 700;
        }

        .patient-portal-page .portal-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            padding: 32px 18px;
            text-align: center;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            color: #64748b;
        }

        .patient-portal-page .portal-action-list .btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
        }
    </style>
</div>
