<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium">Title</label>
        <input type="text" wire:model="title" class="w-full rounded border px-3 py-2">
        @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium">Status</label>
        <select wire:model="status" class="w-full rounded border px-3 py-2">
            @foreach ((array) config('adr-manager.statuses', []) as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @foreach (['context' => 'Context', 'decision' => 'Decision', 'consequences' => 'Consequences'] as $field => $label)
        <div>
            <label class="block text-sm font-medium">{{ $label }}</label>
            <textarea wire:model="{{ $field }}" rows="4" class="w-full rounded border px-3 py-2 font-mono text-sm"></textarea>
        </div>
    @endforeach

    <div>
        <label class="block text-sm font-medium">Supersedes</label>
        <div class="flex gap-2">
            <input type="text" wire:model="supersedeInput" placeholder="e.g. 0002" class="flex-1 rounded border px-3 py-2">
            <button type="button" wire:click="addSupersede" class="rounded border px-3">Add</button>
        </div>
        <ul class="mt-2 flex flex-wrap gap-2">
            @foreach ($supersedes as $ref)
                <li class="flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-sm">
                    ADR-{{ $ref }}
                    <button type="button" wire:click="removeSupersede('{{ $ref }}')" class="text-gray-500">&times;</button>
                </li>
            @endforeach
        </ul>
    </div>
</div>
