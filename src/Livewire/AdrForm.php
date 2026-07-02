<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Livewire;

use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Livewire\Concerns\AuthorsAdrs;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Shared form state and behaviour for the create and edit components: the
 * editable fields, an interactive supersede-link builder, validation rules and
 * a helper to assemble the working draft.
 */
abstract class AdrForm extends Component
{
    use AuthorsAdrs;

    public string $title = '';

    public string $status = 'proposed';

    public string $context = '';

    public string $decision = '';

    public string $consequences = '';

    /**
     * @var list<string>
     */
    public array $supersedes = [];

    public string $supersedeInput = '';

    public function addSupersede(): void
    {
        $id = trim($this->supersedeInput);

        if ($id !== '' && ! in_array($id, $this->supersedes, true)) {
            $this->supersedes[] = $id;
        }

        $this->supersedeInput = '';
    }

    public function removeSupersede(string $id): void
    {
        $this->supersedes = array_values(array_filter(
            $this->supersedes,
            fn (string $existing): bool => $existing !== $id,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in($this->allowedStatuses())],
            'context' => ['nullable', 'string'],
            'decision' => ['nullable', 'string'],
            'consequences' => ['nullable', 'string'],
        ];
    }

    protected function draft(string $id): AdrDto
    {
        return AdrDto::fromArray([
            'id' => $id,
            'title' => $this->title,
            'status' => $this->status,
            'context' => $this->context,
            'decision' => $this->decision,
            'consequences' => $this->consequences,
            'supersedes' => $this->supersedes,
        ]);
    }

    /**
     * @return list<string>
     */
    protected function allowedStatuses(): array
    {
        $statuses = config('adr-manager.statuses', []);

        return is_array($statuses) ? array_values(array_filter($statuses, 'is_string')) : [];
    }
}
