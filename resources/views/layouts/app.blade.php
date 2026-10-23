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
                        status: {
                            submitted: '#f59e0b',
                            analyzing: '#3b82f6',
                            evaluated: '#8b5cf6',
                            shortlisted: '#10b981',
                            rejected: '#ef4444',
                            pending: '#6b7280',
                            processing: '#3b82f6',
                            complete: '#10b981',
                            partial_failure: '#f59e0b',
                        },
                        verdict: {
                            strong_hire: '#16a34a',
                            hire: '#10b981',
                            maybe: '#f59e0b',
                            no_hire: '#f97316',
                            strong_no_hire: '#ef4444',
                            insufficient_data: '#9ca3af',
                        },
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
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                        mono: ['JetBrains Mono', 'Fira Code', 'monospace'],
                    },
                    spacing: {
                        '0': '0px',
                        '1': '4px',
                        '2': '8px',
                        '3': '12px',
                        '4': '16px',
                        '5': '20px',
                        '6': '24px',
                        '8': '32px',
                        '10': '40px',
                        '12': '48px',
                    },
                    transitionDuration: {
                        'fast': '150ms',
                        'normal': '200ms',
                        'slow': '300ms',
                    },
                    transitionTimingFunction: {
                        'ease-out': 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
                    },
                },
            },
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.x/dist/chart.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
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
            --shadow-card: 0 1px 3px 0 rgb(0 0 0 / 0.04), 0 1px 2px -1px rgb(0 0 0 / 0.04);
            --shadow-elevated: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
            --transition-fast: 150ms cubic-bezier(0.25, 0.46, 0.45, 0.94);
            --transition-normal: 200ms cubic-bezier(0.25, 0.46, 0.45, 0.94);
            --transition-slow: 300ms cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        @supports not (color: var(--surface-primary)) {
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
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .font-mono {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
        }

        .nav-link {
            position: relative;
        }

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

        .focus-visible-ring:focus-visible {
            outline: none;
            ring: 2px;
            ring-color: var(--accent);
            ring-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        .x-collapse {
            overflow: hidden;
        }

        .x-collapse-enter {
            max-height: 0;
            opacity: 0;
        }

        .x-collapse-enter-active {
            max-height: 500px;
            opacity: 1;
            transition: max-height 300ms ease-out, opacity 200ms ease-out;
        }

        .x-collapse-leave {
            max-height: 500px;
            opacity: 1;
        }

        .x-collapse-leave-active {
            max-height: 0;
            opacity: 0;
            transition: max-height 300ms ease-in, opacity 200ms ease-in;
        }

        .x-transition-enter {
            opacity: 0;
            transform: translateY(-4px);
        }

        .x-transition-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 200ms ease-out, transform 200ms ease-out;
        }

        .x-transition-leave {
            opacity: 1;
            transform: translateY(0);
        }

        .x-transition-leave-active {
            opacity: 0;
            transform: translateY(-4px);
            transition: opacity 150ms ease-in, transform 150ms ease-in;
        }

        .scrollbar-thin {
            scrollbar-width: thin;
            scrollbar-color: var(--border-subtle) transparent;
        }

        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: var(--border-subtle);
            border-radius: 3px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background-color: var(--text-tertiary);
        }

        ::selection {
            background-color: var(--accent);
            color: white;
        }

        html {
            scroll-behavior: smooth;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes slide-in-right {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        @keyframes slide-out-right {
            from { transform: translateX(0); }
            to { transform: translateX(100%); }
        }

        .animate-slide-in-right {
            animation: slide-in-right 300ms ease-out;
        }

        .animate-slide-out-right {
            animation: slide-out-right 200ms ease-in;
        }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-gray-50/50 text-gray-900 antialiased"
      x-data="{
        alpineError: null,
        handleError(error) {
            this.alpineError = error;
            console.error('Alpine error:', error);
            setTimeout(() => this.alpineError = null, 5000);
        }
    }"
      @error.window="handleError($event.detail)">

    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2" aria-label="ColoredCow Home">
                <img src="{{ asset('coloredcow-logo.webp') }}" alt="" class="h-7 w-auto" loading="lazy">
            </a>

            <nav class="flex items-center gap-1" aria-label="Main navigation">
                <a href="{{ route('dashboard') }}"
                   class="nav-link px-3 py-1.5 rounded-button text-sm font-medium transition-all duration-150 focus-visible-ring {{ request()->routeIs('dashboard') ? 'active text-gray-900' : 'text-gray-500 hover:text-gray-900' }}"
                   aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}">
                    Dashboard
                </a>
                <a href="{{ route('candidates.index') }}"
                   class="nav-link px-3 py-1.5 rounded-button text-sm font-medium transition-all duration-150 focus-visible-ring {{ request()->routeIs('candidates.*') ? 'active text-gray-900' : 'text-gray-500 hover:text-gray-900' }}"
                   aria-current="{{ request()->routeIs('candidates.*') ? 'page' : 'false' }}">
                    Candidates
                </a>
                <a href="{{ route('candidates.create') }}"
                   class="nav-link px-3 py-1.5 rounded-button text-sm font-medium transition-all duration-150 focus-visible-ring {{ request()->routeIs('candidates.create') ? 'active text-gray-900' : 'text-gray-500 hover:text-gray-900' }}"
                   aria-current="{{ request()->routeIs('candidates.create') ? 'page' : 'false' }}">
                    Add
                </a>
                <a href="{{ route('comparisons.index') }}"
                   class="nav-link px-3 py-1.5 rounded-button text-sm font-medium transition-all duration-150 focus-visible-ring {{ request()->routeIs('comparisons.*') ? 'active text-gray-900' : 'text-gray-500 hover:text-gray-900' }}"
                   aria-current="{{ request()->routeIs('comparisons.*') ? 'page' : 'false' }}">
                    Compare
                </a>
                <a href="{{ route('batches.index') }}"
                   class="nav-link px-3 py-1.5 rounded-button text-sm font-medium transition-all duration-150 focus-visible-ring {{ request()->routeIs('batches.*') ? 'active text-gray-900' : 'text-gray-500 hover:text-gray-900' }}"
                   aria-current="{{ request()->routeIs('batches.*') ? 'page' : 'false' }}">
                    Batches
                </a>
                <span class="w-px h-4 bg-gray-200 mx-1" aria-hidden="true"></span>
                <a href="{{ route('apply.show') }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="px-3 py-1.5 rounded-button text-sm font-medium text-gray-400 hover:text-gray-600 transition-colors focus-visible-ring">
                    Public Apply
                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </nav>

            <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="lg:hidden p-2 rounded-button text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors focus-visible-ring"
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                    aria-label="Toggle navigation menu"
                    x-data="{ mobileMenuOpen: false }"
                    :aria-expanded="mobileMenuOpen">
                <template x-if="!mobileMenuOpen">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </template>
                <template x-if="mobileMenuOpen">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </template>
            </button>
        </div>

        <div id="mobile-menu" class="lg:hidden border-t border-gray-100" x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y(-4px)" x-transition:enter-end="opacity-100 transform translate-y(0)" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
            <nav class="px-4 py-3 space-y-2" aria-label="Mobile navigation">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-button text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors {{ request()->routeIs('dashboard') ? 'bg-gray-100 text-gray-900' : '' }}">Dashboard</a>
                <a href="{{ route('candidates.index') }}" class="block px-3 py-2 rounded-button text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors {{ request()->routeIs('candidates.*') ? 'bg-gray-100 text-gray-900' : '' }}">Candidates</a>
                <a href="{{ route('candidates.create') }}" class="block px-3 py-2 rounded-button text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors {{ request()->routeIs('candidates.create') ? 'bg-gray-100 text-gray-900' : '' }}">Add</a>
                <a href="{{ route('comparisons.index') }}" class="block px-3 py-2 rounded-button text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors {{ request()->routeIs('comparisons.*') ? 'bg-gray-100 text-gray-900' : '' }}">Compare</a>
                <a href="{{ route('batches.index') }}" class="block px-3 py-2 rounded-button text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors {{ request()->routeIs('batches.*') ? 'bg-gray-100 text-gray-900' : '' }}">Batches</a>
                <hr class="border-gray-100 my-2">
                <a href="{{ route('apply.show') }}" target="_blank" rel="noopener noreferrer" class="block px-3 py-2 rounded-button text-sm font-medium text-gray-400 hover:text-gray-600 transition-colors">Public Apply ↗</a>
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-6" id="main-content" role="main">
        @if(session('success'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 4000)"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="mb-4 flex items-center gap-3 px-4 py-3 rounded-card bg-green-50 border-l-4 border-green-500 text-sm text-green-700"
                 role="alert"
                 aria-live="polite">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
                <button @click="show = false"
                        class="ml-auto text-green-500 hover:text-green-700 focus-visible-ring p-1 rounded"
                        aria-label="Dismiss success message">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 6000)"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="mb-4 flex items-center gap-3 px-4 py-3 rounded-card bg-red-50 border-l-4 border-red-500 text-sm text-red-700"
                 role="alert"
                 aria-live="assertive">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
                <button @click="show = false"
                        class="ml-auto text-red-500 hover:text-red-700 focus-visible-ring p-1 rounded"
                        aria-label="Dismiss error message">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if(session('info'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 4000)"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="mb-4 flex items-center gap-3 px-4 py-3 rounded-card bg-blue-50 border-l-4 border-blue-500 text-sm text-blue-700"
                 role="status"
                 aria-live="polite">
                <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('info') }}</span>
                <button @click="show = false"
                        class="ml-auto text-blue-500 hover:text-blue-700 focus-visible-ring p-1 rounded"
                        aria-label="Dismiss info message">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-gray-100 mt-8" role="contentinfo">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-400">
            <span>ColoredCow Technical Candidate Evaluator</span>
            <span>v1.0</span>
        </div>
    </footer>

    @stack('scripts')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('globalError', {
                message: null,
                show(message) {
                    this.message = message;
                    setTimeout(() => this.message = null, 5000);
                }
            });

            Alpine.data('globalErrorHandler', () => ({
                errors: [],
                add(error) {
                    this.errors.push({ message: error.message || error, timestamp: Date.now() });
                    console.error('Global Alpine error:', error);
                    if (this.errors.length > 10) this.errors.shift();
                },
                clear() {
                    this.errors = [];
                }
            }));

            Alpine.magic('toast', () => (message, type = 'info') => {
                const types = {
                    success: 'bg-green-50 border-green-500 text-green-700',
                    error: 'bg-red-50 border-red-500 text-red-700',
                    warning: 'bg-amber-50 border-amber-500 text-amber-700',
                    info: 'bg-blue-50 border-blue-500 text-blue-700',
                };
                const el = document.createElement('div');
                el.className = `fixed bottom-4 right-4 z-50 px-4 py-3 rounded-card border-l-4 shadow-lg animate-slide-in-right ${types[type] || types.info} text-sm max-w-md`;
                el.setAttribute('role', 'alert');
                el.setAttribute('aria-live', 'polite');
                el.innerHTML = `<div class="flex items-center gap-3"><span>${message}</span><button class="ml-auto text-current/70 hover:text-current focus-visible-ring p-1 rounded" aria-label="Dismiss"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>`;
                document.body.appendChild(el);
                setTimeout(() => {
                    el.classList.add('animate-slide-out-right');
                    setTimeout(() => el.remove(), 200);
                }, 4000);
                el.querySelector('button').addEventListener('click', () => {
                    el.classList.add('animate-slide-out-right');
                    setTimeout(() => el.remove(), 200);
                });
            });
        });
    </script>
</body>
</html>
