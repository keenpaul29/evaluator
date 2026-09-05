<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply - ColoredCow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full bg-gray-50/50 text-gray-900 antialiased">
    <div class="min-h-full flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-6">
                <img src="{{ asset('coloredcow-logo.webp') }}" alt="ColoredCow" class="h-8 w-auto mx-auto mb-3">
                <h1 class="text-lg font-semibold text-gray-900">Apply to ColoredCow</h1>
                <p class="text-xs text-gray-400 mt-1">Submit your GitHub profile for evaluation</p>
            </div>

            <form method="POST" action="{{ route('apply.store') }}" class="space-y-3" x-data="applyForm()">
                @csrf

                <div class="bg-white rounded-lg border border-gray-100 p-4 space-y-3">
                    <div>
                        <label for="name" class="block text-xs font-medium text-gray-600 mb-1">Full Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="w-full px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-medium text-gray-600 mb-1">Email *</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            class="w-full px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="github_username" class="block text-xs font-medium text-gray-600 mb-1">GitHub Username *</label>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-gray-400">github.com/</span>
                            <input type="text" name="github_username" id="github_username" value="{{ old('github_username') }}" required
                                placeholder="username"
                                class="flex-1 px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                        </div>
                        @error('github_username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-100 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xs font-medium text-gray-600">Repositories *</h2>
                        <button type="button" @click="addRepo()" class="text-xs text-gray-400 hover:text-gray-600">+ Add</button>
                    </div>
                    <p class="text-xs text-gray-400">Add 1-5 repositories for evaluation.</p>

                    <template x-for="(repo, index) in repos" :key="index">
                        <div class="flex items-center gap-1.5">
                            <input type="url" :name="'repo_urls[' + index + ']'" x-model="repos[index]" placeholder="https://github.com/owner/repo" required
                                class="flex-1 px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                            <button type="button" @click="removeRepo(index)" class="text-gray-300 hover:text-red-500 text-xs" x-show="repos.length > 1">&times;</button>
                        </div>
                    </template>
                    @error('repo_urls') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="bg-white rounded-lg border border-gray-100 p-4 space-y-3">
                    <div>
                        <label for="linkedin_url" class="block text-xs font-medium text-gray-600 mb-1">LinkedIn</label>
                        <input type="url" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url') }}"
                            class="w-full px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                        @error('linkedin_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-medium text-gray-600 mb-1">Why ColoredCow?</label>
                        <textarea name="notes" id="notes" rows="2" placeholder="Tell us why you're interested..."
                            class="w-full px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="w-full px-4 py-2 bg-gray-900 text-white text-xs font-medium rounded hover:bg-gray-800 transition-colors">
                    Submit Application
                </button>

                <p class="text-xs text-gray-300 text-center">
                    Evaluated across code quality, judgment, and ColoredCow values.
                </p>
            </form>
        </div>
    </div>

    <script>
    function applyForm() {
        return {
            repos: [''],
            addRepo() {
                if (this.repos.length < 5) this.repos.push('');
            },
            removeRepo(index) {
                if (this.repos.length > 1) this.repos.splice(index, 1);
            }
        };
    }
    </script>
</body>
</html>
