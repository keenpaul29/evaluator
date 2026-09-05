@extends('layouts.app')

@section('title', 'Add Candidate')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="mb-5">
        <h1 class="text-lg font-semibold text-gray-900">Add Candidate</h1>
        <p class="text-xs text-gray-400 mt-0.5">Add a candidate for technical evaluation</p>
    </div>

    <form method="POST" action="{{ route('candidates.store') }}" class="space-y-4" x-data="candidateForm()">
        @csrf

        <div class="bg-white rounded-lg border border-gray-100 p-4 space-y-3">
            <h2 class="text-xs font-medium text-gray-900 uppercase tracking-wide">Personal</h2>

            <div>
                <label for="name" class="block text-xs font-medium text-gray-600 mb-1">Full Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="email" class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                        class="w-full px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                        class="w-full px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                    @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-100 p-4 space-y-3">
            <h2 class="text-xs font-medium text-gray-900 uppercase tracking-wide">GitHub</h2>

            <div>
                <label for="github_username" class="block text-xs font-medium text-gray-600 mb-1">Username *</label>
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
                <h2 class="text-xs font-medium text-gray-900 uppercase tracking-wide">Repositories</h2>
                <button type="button" @click="addRepo()" class="text-xs text-gray-500 hover:text-gray-900">+ Add</button>
            </div>
            <p class="text-xs text-gray-400">Optional — all public repos will be fetched if empty.</p>

            <template x-for="(repo, index) in repos" :key="index">
                <div class="flex items-center gap-1.5">
                    <input type="url" :name="'repo_urls[' + index + ']'" x-model="repos[index]" placeholder="https://github.com/owner/repo"
                        class="flex-1 px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                    <button type="button" @click="removeRepo(index)" class="text-gray-300 hover:text-red-500 text-xs">&times;</button>
                </div>
            </template>
            @error('repo_urls') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            @error('repo_urls.*') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
        </div>

        <div class="bg-white rounded-lg border border-gray-100 p-4 space-y-3">
            <h2 class="text-xs font-medium text-gray-900 uppercase tracking-wide">Links</h2>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="linkedin_url" class="block text-xs font-medium text-gray-600 mb-1">LinkedIn</label>
                    <input type="url" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url') }}"
                        class="w-full px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                    @error('linkedin_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="portfolio_url" class="block text-xs font-medium text-gray-600 mb-1">Portfolio</label>
                    <input type="url" name="portfolio_url" id="portfolio_url" value="{{ old('portfolio_url') }}"
                        class="w-full px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">
                    @error('portfolio_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="notes" class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                <textarea name="notes" id="notes" rows="2"
                    class="w-full px-2.5 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-gray-900 focus:border-gray-900">{{ old('notes') }}</textarea>
                @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-1.5 bg-gray-900 text-white text-xs font-medium rounded hover:bg-gray-800 transition-colors">
                Add & Evaluate
            </button>
            <a href="{{ route('candidates.index') }}" class="px-3 py-1.5 text-gray-500 text-xs font-medium hover:text-gray-900">
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
