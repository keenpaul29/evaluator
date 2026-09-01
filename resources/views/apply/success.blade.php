<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - ColoredCow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full bg-gray-50 text-gray-900">
    <div class="min-h-full flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md text-center">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900">Application Submitted</h1>
            <p class="text-gray-500 mt-3">
                Thank you for applying to ColoredCow. Your GitHub profile is now being evaluated across multiple dimensions including code quality, technical judgment, and cultural alignment.
            </p>

            <div class="mt-6 bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-sm text-gray-500">Application ID</p>
                <p class="text-lg font-mono font-bold text-gray-900">#{{ $candidateId }}</p>
            </div>

            <p class="text-sm text-gray-400 mt-6">
                We'll review your application and get back to you. The evaluation typically takes a few minutes.
            </p>

            <a href="/" class="inline-block mt-6 text-sm text-gray-500 hover:text-gray-900">Go to homepage</a>
        </div>
    </div>
</body>
</html>
