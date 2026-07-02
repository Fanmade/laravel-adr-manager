<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('adr-manager.show', $id) }}" wire:navigate class="text-sm text-gray-500 hover:underline">&larr; Back</a>
        <h1 class="text-xl font-semibold">Edit ADR-{{ $id }}</h1>
    </div>

    @include('adr-manager::partials.form-fields')

    @if ($canPersist)
        <button type="button" wire:click="save" class="rounded bg-gray-900 px-4 py-2 text-white">Save changes</button>
    @elseif ($instructions)
        @include('adr-manager::partials.commit-instructions')
    @endif
</div>
