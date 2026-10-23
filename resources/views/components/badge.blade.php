@props([
    'label' => '',
    'variant' => 'default',
    'size' => 'sm',
    'icon' => null,
    'dot' => true,
    'href' => null,
    'class' => '',
])

@php
    $variants = [
        'default' => 'bg-gray-50 text-gray-700 ring-gray-500/20',
        'primary' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'success' => 'bg-green-50 text-green-700 ring-green-600/20',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/20',
        'info' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'verdict-strong_hire' => 'bg-green-50 text-green-700 ring-green-600/20',
        'verdict-hire' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'verdict-maybe' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'verdict-no_hire' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
        'verdict-strong_no_hire' => 'bg-red-50 text-red-700 ring-red-600/20',
        'verdict-insufficient_data' => 'bg-gray-50 text-gray-600 ring-gray-400/20',
        'status-submitted' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'status-analyzing' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'status-evaluated' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'status-shortlisted' => 'bg-green-50 text-green-700 ring-green-600/20',
        'status-rejected' => 'bg-red-50 text-red-700 ring-red-600/20',
        'status-pending' => 'bg-gray-50 text-gray-600 ring-gray-500/20',
        'status-processing' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'status-complete' => 'bg-green-50 text-green-700 ring-green-600/20',
        'status-partial_failure' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    ];

    $sizes = [
        'xs' => 'px-1.5 py-0.5 text-[10px]',
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-sm',
        'lg' => 'px-3 py-1.5 text-base',
    ];

    $iconSizes = [
        'xs' => 'w-2.5 h-2.5',
        'sm' => 'w-3.5 h-3.5',
        'md' => 'w-4 h-4',
        'lg' => 'w-5 h-5',
    ];

    $dotSizes = [
        'xs' => 'w-0.75 h-0.75',
        'sm' => 'w-1 h-1',
        'md' => 'w-1.5 h-1.5',
        'lg' => 'w-2 h-2',
    ];

    $variantClass = $variants[$variant] ?? $variants['default'];
    $sizeClass = $sizes[$size] ?? $sizes['sm'];
    $iconSizeClass = $iconSizes[$size] ?? $iconSizes['sm'];
    $dotSizeClass = $dotSizes[$size] ?? $dotSizes['sm'];
@endphp

@if($href)
    <a href="{{ $href }}"
       class="inline-flex items-center gap-1.5 {{ $sizeClass }} font-medium rounded-badge ring-1 ring-inset {{ $variantClass }} {{ $class }}"
       {{ $attributes->except(['class']) }}>
        @if($dot)
            <span class="{{ $dotSizeClass }} rounded-full bg-current"></span>
        @endif
        @if($icon)
            <span class="{{ $iconSizeClass }} shrink-0">{{ $icon }}</span>
        @endif
        {{ $label }}
    </a>
@else
    <span class="inline-flex items-center gap-1.5 {{ $sizeClass }} font-medium rounded-badge ring-1 ring-inset {{ $variantClass }} {{ $class }}"
          {{ $attributes->except(['class']) }}>
        @if($dot)
            <span class="{{ $dotSizeClass }} rounded-full bg-current"></span>
        @endif
        @if($icon)
            <span class="{{ $iconSizeClass }} shrink-0">{{ $icon }}</span>
        @endif
        {{ $label }}
    </span>
@endif