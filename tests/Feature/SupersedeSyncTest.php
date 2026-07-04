<?php

declare(strict_types=1);

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Services\SupersedeSynchronizer;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    config()->set('adr-manager.path', 'docs/adrs');
    app()->forgetInstance(AdrRepository::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory(base_path('docs'));
});

function sync(): SupersedeSynchronizer
{
    return app(SupersedeSynchronizer::class);
}

it('links added supersedes reciprocally', function () {
    repo()->save(record('0001', 'Old way', 'accepted'));
    $new = record('0002', 'New way', 'accepted')->with(supersedes: ['0001']);
    repo()->save($new);

    sync()->apply(repo(), null, $new);

    $old = repo()->find('0001');

    expect($old->status)->toBe('superseded')
        ->and($old->backlinks)->toContain('0002')
        ->and(repo()->find('0002')->supersedes)->toContain('0001');
});

it('does nothing when the supersedes list is unchanged', function () {
    repo()->save(record('0001', 'Old way', 'accepted'));
    $before = record('0002', 'New way', 'accepted')->with(supersedes: ['0001']);
    repo()->save($before);

    sync()->apply(repo(), $before, $before->with(title: 'New way, renamed'));

    expect(repo()->find('0001')->status)->toBe('accepted');
});

it('reverts the target when its last superseder lets go', function () {
    repo()->save(record('0001', 'Old way', 'accepted'));
    $before = record('0002', 'New way', 'accepted')->with(supersedes: ['0001']);
    repo()->save($before);
    sync()->apply(repo(), null, $before);

    $after = $before->with(supersedes: []);
    repo()->save($after);
    sync()->apply(repo(), $before, $after);

    $old = repo()->find('0001');

    expect($old->status)->toBe('accepted')
        ->and($old->backlinks)->not->toContain('0002');
});

it('keeps the target superseded while another record still supersedes it', function () {
    repo()->save(record('0001', 'Old way', 'accepted'));
    $second = record('0002', 'Second way', 'accepted')->with(supersedes: ['0001']);
    repo()->save($second);
    sync()->apply(repo(), null, $second);

    $third = record('0003', 'Third way', 'accepted')->with(supersedes: ['0001']);
    repo()->save($third);
    sync()->apply(repo(), null, $third);

    $released = $third->with(supersedes: []);
    repo()->save($released);
    sync()->apply(repo(), $third, $released);

    $old = repo()->find('0001');

    expect($old->status)->toBe('superseded')
        ->and($old->backlinks)->toContain('0002')
        ->and($old->backlinks)->not->toContain('0003');
});

it('leaves a non-superseded status untouched when releasing', function () {
    repo()->save(record('0001', 'Old way', 'deprecated')->with(backlinks: ['0002']));
    $before = record('0002', 'New way', 'accepted')->with(supersedes: ['0001']);
    repo()->save($before);

    $after = $before->with(supersedes: []);
    repo()->save($after);
    sync()->apply(repo(), $before, $after);

    $old = repo()->find('0001');

    expect($old->status)->toBe('deprecated')
        ->and($old->backlinks)->not->toContain('0002');
});

it('ignores releasing a target that no longer exists', function () {
    $before = record('0002', 'New way', 'accepted')->with(supersedes: ['9999']);
    repo()->save($before);

    $after = $before->with(supersedes: []);
    repo()->save($after);

    sync()->apply(repo(), $before, $after);

    expect(repo()->find('0002')->supersedes)->toBeEmpty();
});
