<?php

declare(strict_types=1);

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Repositories\LocalMarkdownRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

it('binds the repository contract to the local markdown implementation by default', function () {
    expect(app(AdrRepository::class))->toBeInstanceOf(LocalMarkdownRepository::class);
});

it('anchors a relative records directory to the application base path', function () {
    config()->set('adr-manager.path', 'docs/decisions');

    // Rebuild the singleton so it picks up the new configuration.
    app()->forgetInstance(AdrRepository::class);
    $repo = app(AdrRepository::class);

    $repo->save(AdrDto::fromArray([
        'id' => '0001',
        'title' => 'Anchored',
        'status' => 'proposed',
        'date' => '2026-01-01',
    ]));

    expect(is_dir(base_path('docs/decisions')))->toBeTrue();

    (new Filesystem)->deleteDirectory(base_path('docs'));
});

it('uses an absolute records path verbatim', function () {
    $absolute = sys_get_temp_dir().'/adr-abs-'.uniqid();
    config()->set('adr-manager.path', $absolute);
    app()->forgetInstance(AdrRepository::class);

    app(AdrRepository::class)->save(AdrDto::fromArray([
        'id' => '0001',
        'title' => 'Absolute',
        'status' => 'proposed',
        'date' => '2026-01-01',
    ]));

    expect(is_dir($absolute))->toBeTrue();

    (new Filesystem)->deleteDirectory($absolute);
});

it('lets the host application swap the repository implementation wholesale', function () {
    $fake = new class implements AdrRepository
    {
        public function all(): Collection
        {
            return collect();
        }

        public function find(string $id): ?AdrDto
        {
            return null;
        }

        public function save(AdrDto $adr): void {}

        public function getLatestSequence(): int
        {
            return 42;
        }

        public function supersede(string $targetId, string $newId): void {}
    };

    app()->instance(AdrRepository::class, $fake);

    expect(app(AdrRepository::class))->toBe($fake)
        ->and(app(AdrRepository::class)->getLatestSequence())->toBe(42);
});
