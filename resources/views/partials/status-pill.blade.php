@php
    $classes = match ($status) {
        'accepted' => 'bg-green-100 text-green-800',
        'proposed' => 'bg-yellow-100 text-yellow-800',
        'deprecated', 'superseded' => 'bg-gray-100 text-gray-600',
        default => 'bg-red-100 text-red-800',
    };
@endphp
<span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $classes }}">{{ $status }}</span>
