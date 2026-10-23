@props([
    'variant' => 'default',
    'padding' => 'md',
    'hover' => false,
    'header' => null,
    'footer' => null,
    'class' => '',
])

@php
    $variants = [
        'default' => 'bg-white border border-gray-100 shadow-card',
        'elevated' => 'bg-white border border-gray-100 shadow-elevated',
        'bordered' => 'bg-white border border-gray-200 shadow-none',
        'ghost' => 'bg-transparent border-none shadow-none',
        'surface' => 'bg-gray-50 border border-gray-100 shadow-card',
    ];

    $paddings = [
        'none' => '',
        'xs' => 'p-2',
        'sm' => 'p-3',
        'md' => 'p-4 sm:p-5',
        'lg' => 'p-6',
        'xl' => 'p-8',
    ];

    $hoverClass = $hover ? 'hover:bg-gray-50/50 transition-colors cursor-pointer' : '';

    $variantClass = $variants[$variant] ?? $variants['default'];
    $paddingClass = $paddings[$padding] ?? $paddings['md'];
@endphp

<div class="rounded-card {{ $variantClass }} {{ $paddingClass }} {{ $hoverClass }} {{ $class }}"
     {{ $attributes->except(['class']) }}>
    @if($header)
        <div class="mb-4 pb-3 border-b border-gray-100 flex items-center justify-between">
            <div>
                @if(is_string($header))
                    <h3 class="text-sm font-semibold text-gray-900">{{ $header }}</h3>
                @else
                    {{ $header }}
                @endif
            </div>
        </div>
    @endif

    {{ $slot }}

    @if($footer)
        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
            @if(is_string($footer))
                <span class="text-xs text-gray-400">{{ $footer }}</span>
            @else
                {{ $footer }}
            @endif
        </div>
    @endif
</div>