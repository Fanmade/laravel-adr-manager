<div class="space-y-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('adr-manager.index') }}" wire:navigate class="text-sm text-gray-500 hover:underline">&larr; All records</a>
        <h1 class="text-xl font-semibold">Relation graph</h1>
    </div>

    @if ($nodes === [])
        <p class="text-sm text-gray-500">
            The relation index is empty. Run <code class="rounded bg-gray-100 px-1">php artisan adr:sync</code> to build it.
        </p>
    @else
        <svg viewBox="0 0 640 {{ $height }}" width="640" height="{{ $height }}" role="img" aria-label="ADR supersede graph">
            @foreach ($edges as $edge)
                <path d="M {{ $spineX }} {{ $edge['fromY'] }} C {{ $spineX - $edge['depth'] }} {{ $edge['fromY'] }}, {{ $spineX - $edge['depth'] }} {{ $edge['toY'] }}, {{ $spineX }} {{ $edge['toY'] }}"
                      fill="none" stroke="#9ca3af" stroke-width="1.5"/>
            @endforeach

            @foreach ($nodes as $node)
                <circle cx="{{ $spineX }}" cy="{{ $node['y'] }}" r="6"
                        fill="{{ match ($node['status']) {
                            'accepted' => '#16a34a',
                            'proposed' => '#ca8a04',
                            'deprecated', 'superseded' => '#9ca3af',
                            default => '#dc2626',
                        } }}"/>
                <a href="{{ route('adr-manager.show', $node['id']) }}">
                    <text x="{{ $spineX + 24 }}" y="{{ $node['y'] + 4 }}" class="text-sm" fill="#111827" font-size="14">
                        ADR-{{ $node['id'] }} — {{ $node['title'] }}
                    </text>
                    <text x="600" y="{{ $node['y'] + 4 }}" text-anchor="end" fill="#6b7280" font-size="11">{{ $node['status'] }}</text>
                </a>
            @endforeach
        </svg>
    @endif
</div>
