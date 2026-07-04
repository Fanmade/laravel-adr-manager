<?php

declare(strict_types=1);

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Livewire\AdrGraph;
use Fanmade\AdrManager\Models\AdrRecord;
use Fanmade\AdrManager\Services\RelationGraph;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('adr-manager.path', 'docs/adrs');
    app()->forgetInstance(AdrRepository::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory(base_path('docs'));
});

it('builds nodes in sequence order and supersede edges from the index', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0002', 'Second', 'accepted'));
    $repo->save(record('0001', 'First', 'accepted'));
    $repo->supersede('0001', '0002');
    $this->artisan('adr:sync')->assertSuccessful();

    $graph = app(RelationGraph::class)->build();

    expect(array_column($graph['nodes'], 'id'))->toBe(['0001', '0002'])
        ->and($graph['edges'])->toBe([['from' => '0002', 'to' => '0001']]);
});

it('excludes backlink rows so each relation appears once', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0001', 'First', 'accepted'));
    $repo->save(record('0002', 'Second', 'accepted'));
    $repo->supersede('0001', '0002');
    $this->artisan('adr:sync')->assertSuccessful();

    expect(app(RelationGraph::class)->build()['edges'])->toHaveCount(1);
});

it('returns an empty graph when the index is empty', function () {
    expect(app(RelationGraph::class)->build())->toBe(['nodes' => [], 'edges' => []]);
});

it('renders the graph with linked records', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0001', 'First decision', 'accepted'));
    $repo->save(record('0002', 'Second decision', 'accepted'));
    $repo->supersede('0001', '0002');
    $this->artisan('adr:sync')->assertSuccessful();

    Livewire::test(AdrGraph::class)
        ->assertSee('First decision')
        ->assertSee('Second decision')
        ->assertSee(route('adr-manager.show', '0001'), false);
});

it('shows a hint instead of a graph when the index is empty', function () {
    Livewire::test(AdrGraph::class)->assertSee('adr:sync');
});

it('skips edges whose endpoints are missing from the index', function () {
    $repo = app(AdrRepository::class);
    $repo->save(record('0001', 'First', 'accepted'));
    $repo->save(record('0002', 'Second', 'accepted'));
    $repo->supersede('0001', '0002');
    $this->artisan('adr:sync')->assertSuccessful();

    AdrRecord::query()->whereKey('0001')->delete();

    Livewire::test(AdrGraph::class)->assertSee('Second');
});

it('serves the graph page in the local environment', function () {
    $this->app['env'] = 'local';

    $this->get('/adr/graph')->assertOk()->assertSee('Relation graph');
});
