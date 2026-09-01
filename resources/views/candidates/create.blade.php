@extends('layouts.app')

@section('title', 'Add Candidate')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Add Candidate</h1>
        <p class="text-sm text-gray-500 mt-1">Add a candidate for technical evaluation</p>
    </div>

    <form method="POST" action="{{ route('candidates.store') }}" class="space-y-6" x-data="candidateForm()">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Personal Information</h2>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                    @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">GitHub Profile</h2>

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
                <h2 class="text-lg font-semibold text-gray-900">Repository URLs</h2>
                <button type="button" @click="addRepo()" class="text-sm text-gray-600 hover:text-gray-900">+ Add Repo</button>
            </div>
            <p class="text-xs text-gray-400">Optional — if empty, all public repos from the GitHub username will be fetched.</p>

            <template x-for="(repo, index) in repos" :key="index">
                <div class="flex items-center gap-2">
                    <input type="url" :name="'repo_urls[' + index + ']' x-model="repos[index]" placeholder="https://github.com/owner/repo"
                        class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                    <button type="button" @click="removeRepo(index)" class="text-gray-400 hover:text-red-500 text-sm">✕</button>
                </div>
            </template>
            @error('repo_urls') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            @error('repo_urls.*') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Additional Links</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="linkedin_url" class="block text-sm font-medium text-gray-700 mb-1">LinkedIn URL</label>
                    <input type="url" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url') }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                    @error('linkedin_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="portfolio_url" class="block text-sm font-medium text-gray-700 mb-1">Portfolio URL</label>
                    <input type="url" name="portfolio_url" id="portfolio_url" value="{{ old('portfolio_url') }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                    @error('portfolio_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" id="notes" rows="3"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-gray-900 focus:border-transparent">{{ old('notes') }}</textarea>
                @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                Add & Evaluate
            </button>
            <a href="{{ route('candidates.index') }}" class="px-4 py-2.5 text-gray-600 text-sm font-medium hover:text-gray-900">
                Cancel
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function candidateForm() {
    return {
        repos: [''],
        addRepo() {
            this.repos.push('');
        },
        removeRepo(index) {
            this.repos.splice(index, 1);
        }
    };
}
</script>
@endpush
@endsection
