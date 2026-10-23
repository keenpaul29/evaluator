@extends('layouts.app')

@section('title', 'Component Library')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Component Library</h1>
            <p class="text-sm text-gray-500 mt-0.5">Reusable UI components for the design system</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($components as $component)
            <x-card variant="elevated" class="p-5" hover>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            @if($component['name'] === 'Badge')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                            @elseif($component['name'] === 'Card')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 002.828 0l4 4a2 2 0 01-2.829 2.829h-8.485a2 2 0 01-1.414-2.829l4.899-4.899a2 2 0 012.828 0"/>
                            @elseif($component['name'] === 'Form Input')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            @elseif($component['name'] === 'Table')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            @endif
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">{{ $component['name'] }}</h3>
                        <p class="text-xs text-gray-500">{{ $component['description'] }}</p>
                    </div>
                </div>

                <div class="space-y-3 text-xs text-gray-600">
                    @if(isset($component['variants']))
                        <div>
                            <span class="font-medium text-gray-900">Variants:</span>
                            <span class="text-gray-600">{{ implode(', ', $component['variants']) }}</span>
                        </div>
                    @endif
                    @if(isset($component['types']))
                        <div>
                            <span class="font-medium text-gray-900">Types:</span>
                            <span class="text-gray-600">{{ implode(', ', $component['types']) }}</span>
                        </div>
                    @endif
                    @if(isset($component['sizes']))
                        <div>
                            <span class="font-medium text-gray-900">Sizes:</span>
                            <span class="text-gray-600">{{ implode(', ', $component['sizes']) }}</span>
                        </div>
                    @endif
                    @if(isset($component['features']))
                        <div>
                            <span class="font-medium text-gray-900">Features:</span>
                            <span class="text-gray-600">{{ implode(', ', $component['features']) }}</span>
                        </div>
                    @endif
                    @if(isset($component['paddings']))
                        <div>
                            <span class="font-medium text-gray-900">Paddings:</span>
                            <span class="text-gray-600">{{ implode(', ', $component['paddings']) }}</span>
                        </div>
                    @endif
                </div>

                <div class="mt-4 p-3 bg-gray-50 rounded-button font-mono text-xs overflow-x-auto">
                    {!! $component['example'] !!}
                </div>
            </x-card>
        @endforeach
    </div>

    <x-card variant="elevated" class="mt-6">
        <x-slot:header>Usage Guidelines</x-slot:header>
        <div class="space-y-3 text-sm text-gray-600">
            <div class="flex items-start gap-2">
                <span class="text-green-600 mt-0.5">✓</span>
                <span>Always use design tokens (CSS variables) instead of hardcoded values</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-green-600 mt-0.5">✓</span>
                <span>Prefer semantic variants (success, warning, danger) over color variants</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-green-600 mt-0.5">✓</span>
                <span>Use the `size` prop for consistent sizing across components</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-green-600 mt-0.5">✓</span>
                <span>Include `focus-visible-ring` for accessible focus states</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-red-600 mt-0.5">✗</span>
                <span>Don't override component styles with arbitrary Tailwind classes</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-red-600 mt-0.5">✗</span>
                <span>Don't nest cards more than 2 levels deep</span>
            </div>
        </div>
    </x-card>
</div>
@endsection