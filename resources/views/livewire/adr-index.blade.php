<div class="space-y-4">
    <div class="flex items-center gap-3">
        <h1 class="text-xl font-semibold">Architecture Decision Records</h1>
        @if ($canPersist)
            <a href="{{ route('adr-manager.create') }}" wire:navigate
               class="ml-auto whitespace-nowrap rounded bg-gray-900 px-3 py-2 text-sm text-white">New ADR</a>
        @endif
    </div>

    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search records…"
           class="w-full rounded border px-3 py-2">

    <div class="space-y-2">
        @forelse ($records as $adr)
            <a href="{{ route('adr-manager.show', $adr->id) }}" wire:navigate
               class="flex items-center gap-3 rounded border p-3 hover:bg-gray-100">
                <span class="text-xs text-gray-500">ADR-{{ $adr->id }}</span>
                <span class="font-medium">{{ $adr->title }}</span>
                <span class="ml-auto">@include('adr-manager::partials.status-pill', ['status' => $adr->status])</span>
            </a>
        @empty
            <p class="text-sm text-gray-500">No records found.</p>
        @endforelse
    </div>
</div>
