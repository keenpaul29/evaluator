@extends('layouts.app')

@section('title', 'Apply to ColoredCow')

@section('content')
<div class="min-h-full flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <img src="{{ asset('coloredcow-logo.webp') }}" alt="" class="h-9 w-auto mx-auto mb-4" loading="lazy">
            <h1 class="text-xl font-semibold text-gray-900">Apply to ColoredCow</h1>
            <p class="text-sm text-gray-500 mt-1">Submit your GitHub profile for evaluation</p>
        </div>

        <form method="POST" action="{{ route('apply.store') }}" class="space-y-4" x-data="applyForm()">
            @csrf

            <x-card variant="elevated" class="space-y-4">
                <x-slot:header>
                    <div class="text-center mb-4">
                        <img src="{{ asset('coloredcow-logo.webp') }}" alt="" class="h-9 w-auto mx-auto mb-4" loading="lazy">
                        <h1 class="text-xl font-semibold text-gray-900">Apply to ColoredCow</h1>
                        <p class="text-sm text-gray-500 mt-1">Submit your GitHub profile for evaluation</p>
                    </div>
                </x-slot:header>

                <div class="space-y-4">
                    <x-form-input type="text" name="name" label="Full Name" placeholder="Full name" required :value="old('name')" :error="$errors->first('name')" />
                    
                    <x-form-input type="email" name="email" label="Email" placeholder="email@example.com" required :value="old('email')" :error="$errors->first('email')" />
                    
                    <x-form-input type="text" name="github_username" label="GitHub Username" placeholder="username" required :value="old('github_username')" :error="$errors->first('github_username')" icon="github.com/" iconPosition="left" />
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-medium text-gray-700">Repositories *</h2>
                        <button type="button" @click="addRepo()" class="text-sm text-accent hover:text-blue-700 font-medium transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add
                        </button>
                    </div>
                    <p class="text-xs text-gray-400">Add 1-5 repositories for evaluation.</p>

                    <template x-for="(repo, index) in repos" :key="index">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400 font-mono w-5 text-center">{{ index + 1 }}</span>
                            <x-form-input type="url" :name="'repo_urls[' + index + ']'" label="Repository URL" placeholder="https://github.com/owner/repo" required x-model="repos[index]" :error="$errors->first('repo_urls.' + index)" />
                            <button type="button" @click="removeRepo(index)" class="p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-button transition-colors" x-show="repos.length > 1" aria-label="Remove repository">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    @error('repo_urls') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-4">
                    <x-form-input type="url" name="linkedin_url" label="LinkedIn" placeholder="https://linkedin.com/in/username" :value="old('linkedin_url')" :error="$errors->first('linkedin_url')" />
                    
                    <x-form-input type="textarea" name="notes" label="Why ColoredCow?" placeholder="Tell us why you're interested..." rows="3" :value="old('notes')" :error="$errors->first('notes')" />
                </div>

                <button type="submit" class="w-full px-4 py-3 bg-gray-900 text-white text-sm font-medium rounded-button hover:bg-gray-800 transition-all duration-150 shadow-sm focus-visible-ring">
                    Submit Application
                </button>

                <p class="text-xs text-gray-400 text-center">
                    Evaluated across code quality, judgment, and ColoredCow values.
                </p>
            </form>
        </x-card>
    </div>
</div>

@push('scripts')
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
@endpush
@endsection
