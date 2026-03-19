@once
    <style>
        .analytics-page .card {
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 18px;
            box-shadow: 0 10px 26px -22px rgba(15, 23, 42, 0.45);
        }

        .analytics-page .card.h-100 {
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .analytics-page .card.h-100:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -22px rgba(15, 23, 42, 0.5);
        }

        .analytics-page .card-header {
            background: #ffffff;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 18px 18px 0 0;
        }

        .analytics-page .avatar-initial {
            width: 44px !important;
            height: 44px !important;
            border-radius: 12px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.24);
        }

        .analytics-page .avatar-initial.bg-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
        }

        .analytics-page .avatar-initial.bg-success {
            background: linear-gradient(135deg, #10b981, #059669) !important;
        }

        .analytics-page .avatar-initial.bg-info {
            background: linear-gradient(135deg, #06b6d4, #0891b2) !important;
        }

        .analytics-page .avatar-initial.bg-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        }

        .analytics-page .avatar-initial.bg-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        }

        .analytics-page .avatar-initial.bg-secondary {
            background: linear-gradient(135deg, #64748b, #475569) !important;
        }

        .analytics-page .avatar-initial.bg-dark {
            background: linear-gradient(135deg, #334155, #1f2937) !important;
        }

        /* Match core metric-card style for analytics stat cards */
        .analytics-page .card.h-100:has(.avatar-initial.bg-primary) {
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .analytics-page .card.h-100:has(.avatar-initial.bg-success) {
            border-color: #a7f3d0;
            background: #ecfdf5;
        }

        .analytics-page .card.h-100:has(.avatar-initial.bg-info) {
            border-color: #bae6fd;
            background: #f0f9ff;
        }

        .analytics-page .card.h-100:has(.avatar-initial.bg-warning) {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .analytics-page .card.h-100:has(.avatar-initial.bg-danger) {
            border-color: #fecdd3;
            background: #fff1f2;
        }

        .analytics-page .card.h-100:has(.avatar-initial.bg-secondary),
        .analytics-page .card.h-100:has(.avatar-initial.bg-dark) {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .analytics-page .table thead th,
        .analytics-page .table-dark th {
            background: #111827 !important;
            color: #ffffff !important;
            border-color: #111827 !important;
        }

        .analytics-page .metric-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            padding: 14px 16px;
            min-height: 108px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 26px -22px rgba(15, 23, 42, 0.45);
        }

        .analytics-page .metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-weight: 700;
        }

        .analytics-page .metric-value {
            margin-top: 6px;
            font-size: 1.45rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .analytics-page .metric-icon {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.08);
            font-size: 18px;
        }

        .analytics-page .metric-icon svg {
            width: 18px;
            height: 18px;
        }

        .analytics-page .metric-card-slate {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
        }

        .analytics-page .metric-card-emerald {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #065f46;
        }

        .analytics-page .metric-card-sky {
            border-color: #bae6fd;
            background: #f0f9ff;
            color: #075985;
        }

        .analytics-page .metric-card-amber {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .analytics-page .metric-card-rose {
            border-color: #fecdd3;
            background: #fff1f2;
            color: #9f1239;
        }

        .analytics-page .metric-card-violet {
            border-color: #ddd6fe;
            background: #f5f3ff;
            color: #5b21b6;
        }

        .analytics-page #trendsChart,
        .analytics-page #serviceCoverageChart,
        .analytics-page #riskChart,
        .analytics-page #riskTrendsChart,
        .analytics-page #deathsTrendChart,
        .analytics-page #deathsByCauseChart {
            min-height: 240px;
        }
    </style>
@endonce
