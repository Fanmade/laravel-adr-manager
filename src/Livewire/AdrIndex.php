<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Livewire;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Support\Environment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Lists every record, optionally filtered by a free-text search over the id,
 * title and status. Reads exclusively through the AdrRepository contract.
 */
#[Layout('adr-manager::layouts.app')]
final class AdrIndex extends Component
{
    public string $search = '';

    public function render(AdrRepository $repository): View
    {
        return view()->make('adr-manager::livewire.adr-index', [
            'records' => $this->records($repository),
            'canPersist' => Environment::authoringAllowed(),
        ]);
    }

    /**
     * @return Collection<int, AdrDto>
     */
    private function records(AdrRepository $repository): Collection
    {
        $term = trim(mb_strtolower($this->search));

        if ($term === '') {
            return $repository->all();
        }

        return $repository->all()
            ->filter(fn (AdrDto $adr): bool => str_contains(mb_strtolower($adr->id.' '.$adr->title.' '.$adr->status), $term))
            ->values();
    }
}
