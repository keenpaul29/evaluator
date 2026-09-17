<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ColoredCow Technical Candidate Evaluator')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        surface: {
                            primary: '#ffffff',
                            secondary: '#f9fafb',
                            elevated: '#ffffff',
                        },
                        border: {
                            default: '#f3f4f6',
                            subtle: '#e5e7eb',
                        },
                        text: {
                            primary: '#111827',
                            secondary: '#6b7280',
                            tertiary: '#9ca3af',
                        },
                        accent: '#2563eb',
                    },
                    borderRadius: {
                        card: '12px',
                        badge: '6px',
                        button: '8px',
                    },
                    boxShadow: {
                        card: '0 1px 3px 0 rgb(0 0 0 / 0.04), 0 1px 2px -1px rgb(0 0 0 / 0.04)',
                        elevated: '0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05)',
                    },
                },
            },
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.x/dist/chart.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --surface-primary: #ffffff;
            --surface-secondary: #f9fafb;
            --surface-elevated: #ffffff;
            --border-default: #f3f4f6;
            --border-subtle: #e5e7eb;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --text-tertiary: #9ca3af;
            --accent: #2563eb;
            --radius-card: 12px;
            --radius-badge: 6px;
            --radius-button: 8px;
        }
        body { font-family: 'Inter', sans-serif; }
        .nav-link { position: relative; }
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 16px;
            height: 2px;
            background: var(--accent);
            border-radius: 1px;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-gray-50/50 text-gray-900 antialiased">

    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <img src="{{ asset('coloredcow-logo.webp') }}" alt="ColoredCow" class="h-7 w-auto">
            </a>

            <nav class="flex items-center gap-1">
                <a href="{{ route('dashboard') }}" class="nav-link px-3 py-1.5 rounded-button text-sm font-medium transition-all duration-150 {{ request()->routeIs('dashboard') ? 'active text-gray-900' : 'text-gray-500 hover:text-gray-900' }}">
                    Dashboard
                </a>
                <a href="{{ route('candidates.index') }}" class="nav-link px-3 py-1.5 rounded-button text-sm font-medium transition-all duration-150 {{ request()->routeIs('candidates.*') ? 'active text-gray-900' : 'text-gray-500 hover:text-gray-900' }}">
                    Candidates
                </a>
                <a href="{{ route('candidates.create') }}" class="nav-link px-3 py-1.5 rounded-button text-sm font-medium transition-all duration-150 {{ request()->routeIs('candidates.create') ? 'active text-gray-900' : 'text-gray-500 hover:text-gray-900' }}">
                    Add
                </a>
                <a href="{{ route('comparisons.index') }}" class="nav-link px-3 py-1.5 rounded-button text-sm font-medium transition-all duration-150 {{ request()->routeIs('comparisons.*') ? 'active text-gray-900' : 'text-gray-500 hover:text-gray-900' }}">
                    Compare
                </a>
                <a href="{{ route('batches.index') }}" class="nav-link px-3 py-1.5 rounded-button text-sm font-medium transition-all duration-150 {{ request()->routeIs('batches.*') ? 'active text-gray-900' : 'text-gray-500 hover:text-gray-900' }}">
                    Batches
                </a>
                <span class="w-px h-4 bg-gray-200 mx-1"></span>
                <a href="{{ route('apply.show') }}" target="_blank" class="px-3 py-1.5 rounded-button text-sm font-medium text-gray-400 hover:text-gray-600 transition-colors">
                    Public Apply ↗
                </a>
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6">
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="mb-4 flex items-center gap-3 px-4 py-3 rounded-card bg-green-50 border-l-4 border-green-500 text-sm text-green-700">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
                <button @click="show = false" class="ml-auto text-green-500 hover:text-green-700">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="mb-4 flex items-center gap-3 px-4 py-3 rounded-card bg-red-50 border-l-4 border-red-500 text-sm text-red-700">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
                <button @click="show = false" class="ml-auto text-red-500 hover:text-red-700">&times;</button>
            </div>
        @endif

        @if(session('info'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="mb-4 flex items-center gap-3 px-4 py-3 rounded-card bg-blue-50 border-l-4 border-blue-500 text-sm text-blue-700">
                <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('info') }}</span>
                <button @click="show = false" class="ml-auto text-blue-500 hover:text-blue-700">&times;</button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-gray-100 mt-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between text-xs text-gray-400">
            <span>ColoredCow Technical Candidate Evaluator</span>
            <span>v1.0</span>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
