<?php

declare(strict_types=1);

use Fanmade\AdrManager\Contracts\AdrRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Gate;

afterEach(function () {
    (new Filesystem)->deleteDirectory(base_path('docs'));
});

it('denies control-plane access outside the local environment', function () {
    $this->get('/adr')->assertForbidden();
});

it('serves the dashboard in the local environment', function () {
    $this->app['env'] = 'local';

    $this->get('/adr')
        ->assertOk()
        ->assertSee('Architecture Decision Records');
});

it('inlines the bundled stylesheet so the dashboard works without a host asset build', function () {
    $this->app['env'] = 'local';

    $this->get('/adr')
        ->assertOk()
        ->assertSee('max-w-3xl', false);
});

it('exposes a json api under the api prefix', function () {
    $this->app['env'] = 'local';

    $this->getJson('/api/adr')->assertOk()->assertExactJson([]);
});

it('returns a single record as json from the api', function () {
    $this->app['env'] = 'local';
    config()->set('adr-manager.path', 'docs/adrs');
    app()->forgetInstance(AdrRepository::class);
    app(AdrRepository::class)->save(record('0001', 'First decision', 'accepted'));

    $this->getJson('/api/adr/0001')
        ->assertOk()
        ->assertJsonPath('id', '0001')
        ->assertJsonPath('title', 'First decision');
});

it('returns 404 for an unknown record', function () {
    $this->app['env'] = 'local';

    $this->getJson('/api/adr/9999')->assertNotFound();
});

it('honours a custom override of the authorization gate', function () {
    Gate::define('viewAdrManager', fn (?Authenticatable $user) => true);

    // Environment is "testing" (not local), yet the override grants access.
    $this->get('/adr')->assertOk();
});
