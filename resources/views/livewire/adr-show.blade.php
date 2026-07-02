<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('adr-manager.index') }}" wire:navigate class="text-sm text-gray-500 hover:underline">&larr; All records</a>
        @include('adr-manager::partials.status-pill', ['status' => $adr->status])
        <a href="{{ route('adr-manager.edit', $adr->id) }}" wire:navigate
           class="ml-auto rounded border px-3 py-1 text-sm hover:bg-gray-100">Edit</a>
    </div>

    <header>
        <p class="text-xs text-gray-500">ADR-{{ $adr->id }} &middot; {{ $adr->date->toFormattedDateString() }}@if ($adr->author) &middot; {{ $adr->author }}@endif</p>
        <h1 class="text-2xl font-semibold">{{ $adr->title }}</h1>
    </header>

    @foreach (['Context' => $adr->context, 'Decision' => $adr->decision, 'Consequences' => $adr->consequences] as $heading => $body)
        @if ($body !== '')
            <section>
                <h2 class="mb-1 text-lg font-medium">{{ $heading }}</h2>
                <div class="prose prose-sm max-w-none">{!! \Illuminate\Support\Str::markdown($body) !!}</div>
            </section>
        @endif
    @endforeach

    @if ($adr->supersedes !== [] || $adr->backlinks !== [])
        <section class="border-t pt-4 text-sm">
            @if ($adr->supersedes !== [])
                <p><span class="font-medium">Supersedes:</span>
                    @foreach ($adr->supersedes as $ref)
                        <a href="{{ route('adr-manager.show', $ref) }}" wire:navigate class="text-blue-600 hover:underline">ADR-{{ $ref }}</a>@if (! $loop->last), @endif
                    @endforeach
                </p>
            @endif
            @if ($adr->backlinks !== [])
                <p><span class="font-medium">Related:</span>
                    @foreach ($adr->backlinks as $ref)
                        <a href="{{ route('adr-manager.show', $ref) }}" wire:navigate class="text-blue-600 hover:underline">ADR-{{ $ref }}</a>@if (! $loop->last), @endif
                    @endforeach
                </p>
            @endif
        </section>
    @endif
</div>
