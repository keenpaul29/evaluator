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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full bg-gray-50 text-gray-900">
    <div class="min-h-full flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-lg">
            <div class="text-center mb-8">
                <div class="w-12 h-12 rounded-xl bg-gray-900 flex items-center justify-center mx-auto mb-4">
                    <span class="text-white text-lg font-bold">CC</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Apply to ColoredCow</h1>
                <p class="text-sm text-gray-500 mt-2">Submit your GitHub profile for technical evaluation</p>
            </div>

            <form method="POST" action="{{ route('apply.store') }}" class="space-y-5" x-data="applyForm()">
                @csrf

                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="github_username" class="block text-sm font-medium text-gray-700 mb-1">GitHub Username *</label>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-400">github.com/</span>
                            <input type="text" name="github_username" id="github_username" value="{{ old('github_username') }}" required
                                placeholder="username"
                                class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                        </div>
                        @error('github_username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-900">Featured Repositories *</h2>
                        <button type="button" @click="addRepo()" class="text-sm text-gray-600 hover:text-gray-900">+ Add</button>
                    </div>
                    <p class="text-xs text-gray-400">Add 1-5 repositories you'd like us to evaluate.</p>

                    <template x-for="(repo, index) in repos" :key="index">
                        <div class="flex items-center gap-2">
                            <input type="url" :name="'repo_urls[' + index + ']' x-model="repos[index]" placeholder="https://github.com/owner/repo" required
                                class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                            <button type="button" @click="removeRepo(index)" class="text-gray-400 hover:text-red-500 text-sm" x-show="repos.length > 1">✕</button>
                        </div>
                    </template>
                    @error('repo_urls') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <div>
                        <label for="linkedin_url" class="block text-sm font-medium text-gray-700 mb-1">LinkedIn URL</label>
                        <input type="url" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url') }}"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                        @error('linkedin_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Why ColoredCow?</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Tell us why you're interested..."
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="w-full px-6 py-3 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    Submit Application
                </button>

                <p class="text-xs text-gray-400 text-center">
                    Your GitHub profile will be evaluated across code quality, technical judgment, communication, and alignment with ColoredCow values.
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
