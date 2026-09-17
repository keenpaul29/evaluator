<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - ColoredCow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    borderRadius: {
                        card: '12px',
                        badge: '6px',
                        button: '8px',
                    },
                    boxShadow: {
                        card: '0 1px 3px 0 rgb(0 0 0 / 0.04), 0 1px 2px -1px rgb(0 0 0 / 0.04)',
                    },
                },
            },
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @keyframes checkmark {
            0% { transform: scale(0) rotate(-45deg); opacity: 0; }
            50% { transform: scale(1.2) rotate(-45deg); }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        .checkmark-animate { animation: checkmark 0.5s ease-out forwards; }
        @keyframes confetti {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(-20px) rotate(180deg); opacity: 0; }
        }
    </style>
</head>
<body class="h-full bg-gray-50/50 text-gray-900 antialiased">
    <div class="min-h-full flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm text-center" x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)">
            <div class="relative inline-block mb-6">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-lg shadow-green-500/25" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="scale-0 opacity-0" x-transition:enter-end="scale-100 opacity-100">
                    <svg class="w-8 h-8 text-white checkmark-animate" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-green-400 checkmark-animate" style="animation-delay: 0.2s"></div>
                <div class="absolute -bottom-1 -left-1 w-3 h-3 rounded-full bg-green-300 checkmark-animate" style="animation-delay: 0.3s"></div>
                <div class="absolute top-0 -left-2 w-2 h-2 rounded-full bg-green-200 checkmark-animate" style="animation-delay: 0.4s"></div>
            </div>

            <h1 class="text-xl font-semibold text-gray-900" x-show="show" x-transition:enter="transition ease-out duration-300 delay-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">Application Submitted</h1>
            
            <p class="text-sm text-gray-500 mt-3 leading-relaxed" x-show="show" x-transition:enter="transition ease-out duration-300 delay-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                Thank you for applying. Your GitHub profile is being evaluated.
            </p>

            <div class="mt-6 bg-white rounded-card border border-gray-100 shadow-card p-4" x-show="show" x-transition:enter="transition ease-out duration-300 delay-400" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                <p class="text-xs text-gray-400 font-medium">Application ID</p>
                <p class="text-lg font-mono font-semibold text-gray-900 mt-1">#{{ $candidateId }}</p>
            </div>

            <p class="text-sm text-gray-400 mt-6" x-show="show" x-transition:enter="transition ease-out duration-300 delay-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                Evaluation typically takes a few minutes. We'll notify you by email.
            </p>

            <a href="/" class="mt-6 inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors" x-show="show" x-transition:enter="transition ease-out duration-300 delay-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>
