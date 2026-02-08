@props([
    'target' => null,
    'type' => 'submit',
    'color' => 'primary',
    'icon' => 'bx-save',
    'loadingText' => 'Processing...',
])

<button type="{{ $type }}" {{ $attributes->merge(['class' => "btn btn-{$color}"]) }} wire:loading.attr="disabled"
    wire:loading.class="disabled" @if ($target) wire:target="{{ $target }}" @endif>
    <span wire:loading.remove.delay @if ($target) wire:target="{{ $target }}" @endif>
        @if ($icon)
            <i class="bx {{ $icon }} me-1"></i>
        @endif
        {{ $slot }}
    </span>
    <span wire:loading.delay @if ($target) wire:target="{{ $target }}" @endif
        style="display: none;">
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        {{ $loadingText }}
    </span>
</button>
