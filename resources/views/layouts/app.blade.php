<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ColoredCow Technical Candidate Evaluator')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.x/dist/chart.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-gray-50/50 text-gray-900 antialiased">

    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-12 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <img src="{{ asset('coloredcow-logo.webp') }}" alt="ColoredCow" class="h-6 w-auto">
            </a>

            <nav class="flex items-center gap-0.5">
                <a href="{{ route('dashboard') }}" class="px-2.5 py-1 rounded text-xs font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900' }}">
                    Dashboard
                </a>
                <a href="{{ route('candidates.index') }}" class="px-2.5 py-1 rounded text-xs font-medium transition-colors {{ request()->routeIs('candidates.*') ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900' }}">
                    Candidates
                </a>
                <a href="{{ route('candidates.create') }}" class="px-2.5 py-1 rounded text-xs font-medium transition-colors {{ request()->routeIs('candidates.create') ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900' }}">
                    Add
                </a>
                <span class="w-px h-3 bg-gray-200 mx-1"></span>
                <a href="{{ route('apply.show') }}" target="_blank" class="px-2.5 py-1 rounded text-xs font-medium text-gray-400 hover:text-gray-600">
                    Public Apply
                </a>
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6">
        @if(session('success'))
            <div class="mb-4 px-3 py-2 rounded-md bg-green-50 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 px-3 py-2 rounded-md bg-red-50 text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if(session('info'))
            <div class="mb-4 px-3 py-2 rounded-md bg-blue-50 text-blue-700 text-sm">
                {{ session('info') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
