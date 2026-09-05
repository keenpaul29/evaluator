<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - ColoredCow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full bg-gray-50/50 text-gray-900 antialiased">
    <div class="min-h-full flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm text-center">
            <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-lg font-semibold text-gray-900">Application Submitted</h1>
            <p class="text-sm text-gray-500 mt-2">
                Thank you for applying. Your GitHub profile is being evaluated.
            </p>

            <div class="mt-4 bg-white rounded-lg border border-gray-100 p-3">
                <p class="text-xs text-gray-400">Application ID</p>
                <p class="text-sm font-mono font-semibold text-gray-900 mt-0.5">#{{ $candidateId }}</p>
            </div>

            <p class="text-xs text-gray-400 mt-4">
                Evaluation typically takes a few minutes.
            </p>

            <a href="/" class="inline-block mt-4 text-xs text-gray-400 hover:text-gray-600">Home</a>
        </div>
    </div>
</body>
</html>
