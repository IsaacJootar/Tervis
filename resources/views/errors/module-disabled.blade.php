<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Module Unavailable' }}</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 560px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 10px 28px -20px rgba(15, 23, 42, 0.45);
            padding: 24px;
        }
        .badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #1e3a8a;
            background: #dbeafe;
            padding: 6px 10px;
            border-radius: 999px;
            margin-bottom: 12px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 24px;
            line-height: 1.2;
        }
        p {
            margin: 0 0 12px;
            color: #475569;
            line-height: 1.5;
        }
        .meta {
            margin-top: 10px;
            padding: 12px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 14px;
            color: #334155;
        }
        .meta strong {
            color: #0f172a;
        }
        .actions {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            border: none;
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-primary {
            background: #111827;
            color: #ffffff;
        }
        .btn-secondary {
            background: #e2e8f0;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Access Restricted</span>
        <h1>{{ $title ?? 'Module Unavailable' }}</h1>
        <p>{{ $message ?? 'This module is currently disabled for your facility.' }}</p>
        <div class="meta">
            <div><strong>Module:</strong> {{ $moduleLabel ?? 'N/A' }}</div>
            <div><strong>Facility:</strong> {{ $facilityName ?? 'N/A' }}</div>
        </div>
        <div class="actions">
            <button
                type="button"
                class="btn btn-primary"
                onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href='{{ $fallbackUrl ?? url('/') }}'; }"
            >
                Go Back
            </button>
            <a href="{{ $fallbackUrl ?? url('/') }}" class="btn btn-secondary">Open Dashboard</a>
        </div>
    </div>
</body>
</html>

