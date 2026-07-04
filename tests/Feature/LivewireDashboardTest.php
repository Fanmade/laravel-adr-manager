<?php

declare(strict_types=1);

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Livewire\AdrCreate;
use Fanmade\AdrManager\Livewire\AdrEdit;
use Fanmade\AdrManager\Livewire\AdrIndex;
use Fanmade\AdrManager\Livewire\AdrShow;
use Illuminate\Filesystem\Filesystem;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('adr-manager.path', 'docs/adrs');
    app()->forgetInstance(AdrRepository::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory(base_path('docs'));
});

function repo(): AdrRepository
{
    return app(AdrRepository::class);
}

// --- Index ---------------------------------------------------------------

it('lists all records', function () {
    repo()->save(record('0001', 'First decision', 'accepted'));
    repo()->save(record('0002', 'Second decision', 'proposed'));

    Livewire::test(AdrIndex::class)
        ->assertSee('First decision')
        ->assertSee('Second decision');
});

it('filters records by search term', function () {
    repo()->save(record('0001', 'Adopt PostgreSQL', 'accepted'));
    repo()->save(record('0002', 'Adopt Redis', 'accepted'));

    Livewire::test(AdrIndex::class)
        ->set('search', 'redis')
        ->assertSee('Adopt Redis')
        ->assertDontSee('Adopt PostgreSQL');
});

// --- Show ----------------------------------------------------------------

it('renders a record with its rendered markdown', function () {
    repo()->save(record('0007', 'Use PostgreSQL', 'accepted'));

    Livewire::test(AdrShow::class, ['id' => '0007'])
        ->assertSee('Use PostgreSQL')
        ->assertSee('accepted');
});

it('returns 404 for an unknown record', function () {
    Livewire::test(AdrShow::class, ['id' => '9999'])->assertStatus(404);
});

// --- Create --------------------------------------------------------------

it('creates a record and redirects when writing is allowed', function () {
    $this->app['env'] = 'local';

    Livewire::test(AdrCreate::class)
        ->set('title', 'Choose a queue driver')
        ->set('status', 'accepted')
        ->set('decision', 'Use Redis.')
        ->call('save')
        ->assertRedirect(route('adr-manager.index'));

    expect(repo()->find('0001')?->title)->toBe('Choose a queue driver');
});

it('supersedes reciprocally when creating through the dashboard', function () {
    $this->app['env'] = 'local';
    repo()->save(record('0001', 'Old decision', 'accepted'));

    Livewire::test(AdrCreate::class)
        ->set('title', 'New direction')
        ->set('supersedes', ['0001'])
        ->call('save');

    $old = repo()->find('0001');

    expect($old->status)->toBe('superseded')
        ->and($old->backlinks)->toContain('0002');
});

it('releases a supersede link removed through the edit form', function () {
    $this->app['env'] = 'local';
    repo()->save(record('0001', 'Old decision', 'accepted'));

    Livewire::test(AdrCreate::class)
        ->set('title', 'New direction')
        ->set('supersedes', ['0001'])
        ->call('save');

    Livewire::test(AdrEdit::class, ['id' => '0002'])
        ->call('removeSupersede', '0001')
        ->call('save');

    $old = repo()->find('0001');

    expect($old->status)->toBe('accepted')
        ->and($old->backlinks)->not->toContain('0002');
});

it('validates the title before creating', function () {
    $this->app['env'] = 'local';

    Livewire::test(AdrCreate::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

it('builds and clears supersede links interactively', function () {
    Livewire::test(AdrCreate::class)
        ->set('supersedeInput', '0002')
        ->call('addSupersede')
        ->assertSet('supersedes', ['0002'])
        ->set('supersedeInput', '0002')
        ->call('addSupersede') // duplicates are ignored
        ->assertSet('supersedes', ['0002'])
        ->call('removeSupersede', '0002')
        ->assertSet('supersedes', []);
});

it('shows commit instructions instead of writing outside a writable environment', function () {
    // Environment is "testing", so writes are disabled.
    Livewire::test(AdrCreate::class)
        ->set('title', 'Cannot write here')
        ->call('save')
        ->assertSee('Writing is disabled');

    expect(repo()->find('0001'))->toBeNull();
});

// --- Edit ----------------------------------------------------------------

it('loads an existing record into the edit form', function () {
    repo()->save(record('0003', 'Editable decision', 'proposed'));

    Livewire::test(AdrEdit::class, ['id' => '0003'])
        ->assertSet('title', 'Editable decision')
        ->assertSet('status', 'proposed');
});

it('updates a record when writing is allowed', function () {
    $this->app['env'] = 'local';
    repo()->save(record('0003', 'Old title', 'proposed'));

    Livewire::test(AdrEdit::class, ['id' => '0003'])
        ->set('title', 'New title')
        ->set('status', 'accepted')
        ->call('save')
        ->assertRedirect(route('adr-manager.show', '0003'));

    $updated = repo()->find('0003');
    expect($updated?->title)->toBe('New title')
        ->and($updated?->status)->toBe('accepted');
});

it('does not persist edits outside a writable environment', function () {
    repo()->save(record('0003', 'Original', 'proposed'));

    Livewire::test(AdrEdit::class, ['id' => '0003'])
        ->set('title', 'Changed')
        ->call('save');

    expect(repo()->find('0003')?->title)->toBe('Original');
});

it('aborts editing an unknown record', function () {
    Livewire::test(AdrEdit::class, ['id' => '9999'])->assertStatus(404);
});
