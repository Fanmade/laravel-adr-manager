<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Livewire;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;

/**
 * Authoring form for an existing record. Mirrors {@see AdrCreate}, but loads
 * the current record on mount and preserves its id on save.
 */
#[Layout('adr-manager::layouts.app')]
final class AdrEdit extends AdrForm
{
    public string $id = '';

    public function mount(string $id, AdrRepository $repository): void
    {
        $adr = $repository->find($id) ?? abort(404);

        $this->id = $adr->id;
        $this->title = $adr->title;
        $this->status = $adr->status;
        $this->context = $adr->context;
        $this->decision = $adr->decision;
        $this->consequences = $adr->consequences;
        $this->supersedes = $adr->supersedes;
    }

    public function save(AdrRepository $repository): void
    {
        if (! $this->canPersist()) {
            return;
        }

        $this->validate($this->validationRules());

        $repository->save($this->draft($this->id));

        session()->flash('adr-status', "Updated ADR-{$this->id}.");

        $this->redirectRoute('adr-manager.show', ['id' => $this->id], navigate: true);
    }

    public function render(): View
    {
        $canPersist = $this->canPersist();

        return view()->make('adr-manager::livewire.adr-edit', [
            'canPersist' => $canPersist,
            'instructions' => $canPersist ? null : $this->commitInstructions($this->draft($this->id)),
        ]);
    }
}
