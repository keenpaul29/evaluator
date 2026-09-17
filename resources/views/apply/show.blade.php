<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply - ColoredCow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full bg-gray-50/50 text-gray-900 antialiased">
    <div class="min-h-full flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <img src="{{ asset('coloredcow-logo.webp') }}" alt="ColoredCow" class="h-9 w-auto mx-auto mb-4">
                <h1 class="text-xl font-semibold text-gray-900">Apply to ColoredCow</h1>
                <p class="text-sm text-gray-500 mt-1">Submit your GitHub profile for evaluation</p>
            </div>

            <form method="POST" action="{{ route('apply.store') }}" class="space-y-4" x-data="applyForm()">
                @csrf

                <div class="bg-white rounded-card border border-gray-100 shadow-card p-5 space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">
                        @error('name') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email *</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">
                        @error('email') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="github_username" class="block text-sm font-medium text-gray-700 mb-1.5">GitHub Username *</label>
                        <div class="flex items-center">
                            <span class="text-sm text-gray-400 bg-gray-50 px-3 py-2.5 border border-r-0 border-gray-200 rounded-l-button">github.com/</span>
                            <input type="text" name="github_username" id="github_username" value="{{ old('github_username') }}" required
                                placeholder="username"
                                class="flex-1 px-3 py-2.5 border border-gray-200 rounded-r-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">
                        </div>
                        @error('github_username') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="bg-white rounded-card border border-gray-100 shadow-card p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-medium text-gray-700">Repositories *</h2>
                        <button type="button" @click="addRepo()" class="text-sm text-accent hover:text-blue-700 font-medium transition-colors">+ Add</button>
                    </div>
                    <p class="text-xs text-gray-400">Add 1-5 repositories for evaluation.</p>

                    <template x-for="(repo, index) in repos" :key="index">
                        <div class="flex items-center gap-2">
                            <input type="url" :name="'repo_urls[' + index + ']'" x-model="repos[index]" placeholder="https://github.com/owner/repo" required
                                class="flex-1 px-3 py-2.5 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">
                            <button type="button" @click="removeRepo(index)" class="p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-button transition-colors" x-show="repos.length > 1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    @error('repo_urls') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="bg-white rounded-card border border-gray-100 shadow-card p-5 space-y-4">
                    <div>
                        <label for="linkedin_url" class="block text-sm font-medium text-gray-700 mb-1.5">LinkedIn</label>
                        <input type="url" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url') }}"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">
                        @error('linkedin_url') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1.5">Why ColoredCow?</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Tell us why you're interested..."
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-button text-sm focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="w-full px-4 py-3 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-all duration-150 shadow-sm">
                    Submit Application
                </button>

                <p class="text-xs text-gray-400 text-center">
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
