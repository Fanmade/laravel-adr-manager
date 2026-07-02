<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Livewire;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;

/**
 * Authoring form for a new record. In a writable environment it persists the
 * file and redirects to the index; elsewhere it shows the Markdown and git
 * commands to commit the record by hand.
 */
#[Layout('adr-manager::layouts.app')]
final class AdrCreate extends AdrForm
{
    public function save(AdrRepository $repository): void
    {
        if (! $this->canPersist()) {
            return;
        }

        $this->validate($this->validationRules());

        $id = $this->nextId($repository);
        $repository->save($this->draft($id));

        session()->flash('adr-status', "Created ADR-{$id}.");

        $this->redirectRoute('adr-manager.index', navigate: true);
    }

    public function render(AdrRepository $repository): View
    {
        $canPersist = $this->canPersist();

        return view()->make('adr-manager::livewire.adr-create', [
            'canPersist' => $canPersist,
            'instructions' => $canPersist || $this->title === ''
                ? null
                : $this->commitInstructions($this->draft($this->nextId($repository))),
        ]);
    }

    private function nextId(AdrRepository $repository): string
    {
        return str_pad((string) ($repository->getLatestSequence() + 1), 4, '0', STR_PAD_LEFT);
    }
}
