<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Livewire;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Renders a single record with its Markdown sections converted to HTML and its
 * supersede / backlink references shown as links.
 */
#[Layout('adr-manager::layouts.app')]
final class AdrShow extends Component
{
    public string $id = '';

    public function mount(string $id): void
    {
        $this->id = $id;
    }

    public function render(AdrRepository $repository): View
    {
        $adr = $repository->find($this->id);

        abort_if($adr === null, 404);

        return view()->make('adr-manager::livewire.adr-show', [
            'adr' => $adr,
        ]);
    }
}
