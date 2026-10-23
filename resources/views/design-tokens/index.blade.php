@extends('layouts.app')

@section('title', 'Design Tokens Reference')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Design Tokens Reference</h1>
            <p class="text-sm text-gray-500 mt-0.5">Complete reference of design tokens used in the system</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('components.index') }}"
               class="px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-button hover:bg-gray-200 transition-colors focus-visible-ring">
                View Components
            </a>
            <button onclick="copyJsonTokens()"
                    class="px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-colors focus-visible-ring">
                Copy JSON
            </button>
        </div>
    </div>

    <x-card variant="default" class="p-4">
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-button flex items-center gap-3">
            <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="text-sm font-medium text-green-800">Style Dictionary Format</p>
                <p class="text-xs text-green-700">Tokens follow the <a href="https://github.com/amzn/style-dictionary" target="_blank" rel="noopener noreferrer" class="underline hover:text-green-600">Style Dictionary</a> specification for cross-platform compatibility</p>
            </div>
        </div>

        @foreach($tokens as $category => $groups)
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-3 capitalize">{{ $category }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($groups as $group => $tokens)
                        <div class="bg-gray-50 rounded-card p-4 ring-1 ring-gray-200">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $group }}</h3>
                            <div class="space-y-2">
                                @foreach($tokens as $name => $data)
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-mono text-gray-600">{{ $category }}.{{ $group }}.{{ $name }}</span>
                                            @if($data['type'] === 'color')
                                                <span class="w-5 h-5 rounded border" style="background-color: {{ $data['value'] }};" title="{{ $data['value'] }}"></span>
                                            @endif
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $data['description'] }}</span>
                                        <code class="text-xs text-gray-400 bg-white px-2 py-1 rounded font-mono break-all">{{ $data['value'] }}</code>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </x-card>

    <x-card variant="default">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">CSS Custom Properties</h2>
        <p class="text-sm text-gray-500 mb-4">These CSS variables are available globally in <code class="font-mono text-xs bg-gray-50 px-1.5 py-0.5 rounded">app.blade.php</code></p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-900">Color Tokens</h3>
                <div class="space-y-2">
                    @foreach([
                        '--surface-primary' => '#ffffff',
                        '--surface-secondary' => '#f9fafb',
                        '--surface-elevated' => '#ffffff',
                        '--border-default' => '#f3f4f6',
                        '--border-subtle' => '#e5e7eb',
                        '--text-primary' => '#111827',
                        '--text-secondary' => '#6b7280',
                        '--text-tertiary' => '#9ca3af',
                        '--accent' => '#2563eb',
                    ] as $name => $value)
                        <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-button">
                            <span class="w-6 h-6 rounded border" style="background-color: {{ $value }}"></span>
                            <code class="text-xs font-mono text-gray-700">{{ $name }}</code>
                            <code class="text-xs text-gray-500 font-mono">{{ $value }}</code>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-900">Spacing & Radius Tokens</h3>
                <div class="space-y-2">
                    @foreach([
                        '--radius-card' => '12px',
                        '--radius-badge' => '6px',
                        '--radius-button' => '8px',
                        'spacing-1' => '4px',
                        'spacing-2' => '8px',
                        'spacing-3' => '12px',
                        'spacing-4' => '16px',
                        'spacing-5' => '20px',
                        'spacing-6' => '24px',
                        'spacing-8' => '32px',
                        'spacing-10' => '40px',
                        'spacing-12' => '48px',
                    ] as $name => $value)
                        <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-button">
                            <code class="text-xs font-mono text-gray-700">{{ $name }}</code>
                            <code class="text-xs text-gray-500 font-mono">{{ $value }}</code>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-900">Shadow & Transition Tokens</h3>
                <div class="space-y-2">
                    @foreach([
                        '--shadow-card' => '0 1px 3px 0 rgb(0 0 0 / 0.04), 0 1px 2px -1px rgb(0 0 0 / 0.04)',
                        '--shadow-elevated' => '0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05)',
                        '--transition-fast' => '150ms',
                        '--transition-normal' => '200ms',
                        '--transition-slow' => '300ms',
                    ] as $name => $value)
                        <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-button">
                            <code class="text-xs font-mono text-gray-700">{{ $name }}</code>
                            <code class="text-xs text-gray-500 font-mono break-all">{{ $value }}</code>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-card>

    <x-card variant="default">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">JSON Export</h2>
        <p class="text-sm text-gray-500 mb-4">Complete token set in Style Dictionary format. Click "Copy JSON" to copy to clipboard.</p>
        <div class="bg-gray-900 rounded-button p-4 overflow-x-auto max-h-96">
            <pre id="json-tokens" class="text-xs text-green-300 font-mono overflow-auto">{{ $jsonTokens }}</pre>
        </div>
    </x-card>

    <x-card variant="default">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Tailwind Config Mapping</h2>
        <p class="text-sm text-gray-500 mb-4">How CSS custom properties map to Tailwind config in <code class="font-mono text-xs bg-gray-50 px-1.5 py-0.5 rounded">app.blade.php</code></p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left px-4 py-2 font-medium text-gray-500 uppercase tracking-wider">CSS Variable</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-500 uppercase tracking-wider">Tailwind Key</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-500 uppercase tracking-wider">Usage Example</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">--accent</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">colors.accent</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-600">bg-accent, text-accent, border-accent</code></td>
                    </tr>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">--surface-primary</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">colors.surface.primary</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-600">bg-surface-primary</code></td>
                    </tr>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">--surface-secondary</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">colors.surface.secondary</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-600">bg-surface-secondary</code></td>
                    </tr>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">--surface-elevated</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">colors.surface.elevated</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-600">bg-surface-elevated</code></td>
                    </tr>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">--border-default</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">colors.border.default</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-600">border-border-default</code></td>
                    </tr>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">--border-subtle</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">colors.border.subtle</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-600">border-border-subtle</code></td>
                    </tr>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">--text-primary</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">colors.text.primary</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-600">text-text-primary</code></td>
                    </tr>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">--radius-card</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">borderRadius.card</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-600">rounded-card</code></td>
                    </tr>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">--radius-badge</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">borderRadius.badge</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-600">rounded-badge</code></td>
                    </tr>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">--radius-button</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">borderRadius.button</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-600">rounded-button</code></td>
                    </tr>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">--shadow-card</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">boxShadow.card</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-600">shadow-card</code></td>
                    </tr>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">--shadow-elevated</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-700">boxShadow.elevated</code></td>
                        <td class="px-4 py-2"><code class="text-xs font-mono text-gray-600">shadow-elevated</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection