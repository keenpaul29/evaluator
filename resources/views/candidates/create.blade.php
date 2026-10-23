@extends('layouts.app')

@section('title', 'Add Candidate')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="mb-6">
        <h1 class="text-lg font-semibold text-gray-900">Add Candidate</h1>
        <p class="text-xs text-gray-400 mt-0.5">Add a candidate for technical evaluation</p>
    </div>

    <form method="POST" action="{{ route('candidates.store') }}" class="space-y-4" x-data="candidateForm()">
        @csrf

        <x-card variant="default" class="space-y-4">
            <x-slot:header>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <h2 class="text-xs font-medium text-gray-900 uppercase tracking-wide">Personal</h2>
                </div>
            </x-slot:header>

            <div class="space-y-3">
                <x-form-input type="text" name="name" label="Full Name" placeholder="Full name" required :value="old('name')" :error="$errors->first('name')" />
                
                <div class="grid grid-cols-2 gap-3">
                    <x-form-input type="email" name="email" label="Email" placeholder="email@example.com" :value="old('email')" :error="$errors->first('email')" />
                    <x-form-input type="text" name="phone" label="Phone" placeholder="+1 (555) 000-0000" :value="old('phone')" :error="$errors->first('phone')" />
                </div>
            </div>
        </x-card>

        <x-card variant="default" class="space-y-4">
            <x-slot:header>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                    </div>
                    <h2 class="text-xs font-medium text-gray-900 uppercase tracking-wide">GitHub</h2>
                </div>
            </x-slot:header>

            <x-form-input type="text" name="github_username" label="Username" placeholder="username" required :value="old('github_username')" :error="$errors->first('github_username')" icon="github.com/" iconPosition="left" />
        </x-card>

        <x-card variant="default" class="space-y-4">
            <x-slot:header>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-purple-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-8 4"/></svg>
                        </div>
                        <h2 class="text-xs font-medium text-gray-900 uppercase tracking-wide">Repositories</h2>
                    </div>
                    <button type="button" @click="addRepo()" class="text-xs text-gray-500 hover:text-gray-900 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add
                    </button>
                </div>
            </x-slot:header>

            <p class="text-xs text-gray-400">Optional — all public repos will be fetched if empty.</p>

            <template x-for="(repo, index) in repos" :key="index">
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-gray-400 font-mono w-5 text-center">{{ index + 1 }}</span>
                    <x-form-input type="url" :name="'repo_urls[' + index + ']'" label="Repository URL" placeholder="https://github.com/owner/repo" x-model="repos[index]" :error="$errors->first('repo_urls.' + index)" />
                    <button type="button" @click="removeRepo(index)" class="text-gray-300 hover:text-red-500 text-lg leading-none p-1 focus-visible-ring rounded" aria-label="Remove repository">&times;</button>
                </div>
            </template>
            @error('repo_urls') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            @error('repo_urls.*') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
        </x-card>

        <x-card variant="default" class="space-y-4">
            <x-slot:header>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l4-4a4 4 0 005.656-5.656l-4-4a4 4 0 00-5.656-5.656l-4 4a4 4 0 105.656 5.656l4-4a4 4 0 00-5.656 0z"/></svg>
                    </div>
                    <h2 class="text-xs font-medium text-gray-900 uppercase tracking-wide">Links</h2>
                </div>
            </x-slot:header>

            <div class="grid grid-cols-2 gap-3">
                <x-form-input type="url" name="linkedin_url" label="LinkedIn" placeholder="https://linkedin.com/in/username" :value="old('linkedin_url')" :error="$errors->first('linkedin_url')" />
                <x-form-input type="url" name="portfolio_url" label="Portfolio" placeholder="https://portfolio.example.com" :value="old('portfolio_url')" :error="$errors->first('portfolio_url')" />
            </div>

            <x-form-input type="textarea" name="notes" label="Notes" placeholder="Any additional notes..." rows="2" :value="old('notes')" :error="$errors->first('notes')" />
        </x-card>

        <div class="flex items-center gap-2">
            <button type="submit" class="flex-1 px-4 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-colors focus-visible-ring">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Add & Evaluate
            </button>
            <a href="{{ route('candidates.index') }}" class="px-4 py-2.5 text-gray-500 text-sm font-medium hover:text-gray-900 focus-visible-ring">
                Cancel
            </a>
        </div>
    </form>
</div>
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
