@php
    $panelTitle = $title ?? 'AI Assistant';
    $summaryText = $summary ?? null;
    $assistantItems = $items ?? [];
    $generatedAtText = $generatedAt ?? null;
    $refreshActionName = $refreshAction ?? 'refreshAiAssistant';
    $hideActionName = $hideAction ?? 'hideAiAssistant';
    $riskLevel = $riskLevel ?? 'low';
@endphp

@if ($show ?? false)
    <aside class="ai-assistant-panel" aria-label="AI Assistant Panel">
        <div class="ai-assistant-head">
            <div>
                <h6 class="mb-1">{{ $panelTitle }}</h6>
                <div class="d-flex align-items-center gap-2">
                    @php
                        $riskClass = match ($riskLevel) {
                            'high' => 'danger',
                            'medium' => 'warning',
                            default => 'success',
                        };
                    @endphp
                    <span class="badge bg-label-{{ $riskClass }}">Risk: {{ ucfirst($riskLevel) }}</span>
                    @if ($generatedAtText)
                        <small class="text-muted">Updated {{ $generatedAtText }}</small>
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-outline-dark" wire:click="{{ $refreshActionName }}"
                    wire:loading.attr="disabled" wire:target="{{ $refreshActionName }}">
                    <span wire:loading.remove wire:target="{{ $refreshActionName }}">Refresh</span>
                    <span wire:loading wire:target="{{ $refreshActionName }}"><span
                            class="spinner-border spinner-border-sm me-1"></span>Running...</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="{{ $hideActionName }}">
                    Hide AI Assistant
                </button>
            </div>
        </div>

        <p class="ai-assistant-note">
            Advisory only: suggestions never auto-save, auto-complete, or override user actions.
        </p>

        @if ($summaryText)
            <div class="ai-assistant-summary">{{ $summaryText }}</div>
        @endif

        <div class="ai-assistant-items">
            @forelse ($assistantItems as $item)
                @php
                    $severity = $item['severity'] ?? 'ok';
                    $itemClass = match ($severity) {
                        'action' => 'ai-item-action',
                        'watch' => 'ai-item-watch',
                        default => 'ai-item-ok',
                    };
                @endphp
                <article class="ai-assistant-item {{ $itemClass }}">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <h6 class="mb-0">{{ $item['title'] ?? 'Suggestion' }}</h6>
                        @if (!empty($item['confidence']))
                            <span class="badge bg-white text-dark border">Confidence: {{ ucfirst($item['confidence']) }}</span>
                        @endif
                    </div>
                    <p class="mb-0 mt-1">{{ $item['message'] ?? '' }}</p>
                </article>
            @empty
                <p class="small text-muted mb-0">No AI Assistant suggestions available yet.</p>
            @endforelse
        </div>
    </aside>
@endif

@once
    <style>
        .ai-assistant-panel {
            position: fixed;
            right: 1rem;
            top: 5.5rem;
            z-index: 1090;
            width: min(430px, calc(100vw - 2rem));
            max-height: calc(100vh - 7rem);
            overflow: auto;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.16);
            padding: 0.85rem;
        }

        .ai-assistant-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.65rem;
            margin-bottom: 0.65rem;
        }

        .ai-assistant-note {
            margin-bottom: 0.65rem;
            padding: 0.5rem 0.6rem;
            border: 1px solid #c7d2fe;
            background: #eef2ff;
            border-radius: 8px;
            font-size: 0.78rem;
            color: #3730a3;
        }

        .ai-assistant-summary {
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            border-radius: 8px;
            padding: 0.55rem 0.6rem;
            margin-bottom: 0.65rem;
            font-size: 0.8rem;
            color: #334155;
        }

        .ai-assistant-items {
            display: grid;
            gap: 0.55rem;
        }

        .ai-assistant-item {
            border: 1px solid #dbeafe;
            border-radius: 8px;
            padding: 0.6rem;
            font-size: 0.82rem;
        }

        .ai-item-action {
            border-color: #fecaca;
            background: #fef2f2;
            color: #7f1d1d;
        }

        .ai-item-watch {
            border-color: #fde68a;
            background: #fffbeb;
            color: #78350f;
        }

        .ai-item-ok {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #14532d;
        }

        @media (max-width: 991.98px) {
            .ai-assistant-panel {
                top: auto;
                bottom: 0.5rem;
                right: 0.5rem;
                left: 0.5rem;
                width: auto;
                max-height: 72vh;
            }
        }
    </style>
@endonce

