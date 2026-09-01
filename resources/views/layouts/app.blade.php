<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ColoredCow Evaluator')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.x/dist/chart.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-gray-50 text-gray-900">

    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gray-900 flex items-center justify-center">
                    <span class="text-white text-sm font-bold">CC</span>
                </div>
                <span class="text-base font-semibold tracking-tight">ColoredCow Evaluator</span>
            </a>

            <nav class="flex items-center gap-1">
                <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' }}">
                    Dashboard
                </a>
                <a href="{{ route('candidates.index') }}" class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('candidates.*') ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' }}">
                    Candidates
                </a>
                <a href="{{ route('candidates.create') }}" class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('candidates.create') ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' }}">
                    + Add Candidate
                </a>
                <a href="{{ route('apply.show') }}" target="_blank" class="px-3 py-1.5 rounded-md text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100">
                    Apply (Public)
                </a>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8">
        @if(session('success'))
            <div class="mb-6 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if(session('info'))
            <div class="mb-6 px-4 py-3 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 text-sm">
                {{ session('info') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
