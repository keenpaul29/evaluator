@props([
    'candidate' => null,
])

<a href="{{ route('candidates.show', $candidate) }}"
   class="font-medium text-gray-900 hover:text-accent transition-colors">
    {{ $candidate->name }}
</a>
<div class="text-xs text-gray-400">{{ $candidate->github_username }}</div>