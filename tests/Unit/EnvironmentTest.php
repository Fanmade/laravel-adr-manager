<?php

declare(strict_types=1);

use Fanmade\AdrManager\Support\Environment;

it('allows authoring in a configured environment', function () {
    config()->set('adr-manager.authoring.environments', ['testing']);

    expect(Environment::authoringAllowed())->toBeTrue();
});

it('denies authoring outside the configured environments', function () {
    config()->set('adr-manager.authoring.environments', ['production']);

    expect(Environment::authoringAllowed())->toBeFalse();
});

it('denies authoring when no environments are configured', function () {
    config()->set('adr-manager.authoring.environments', 'not-an-array');

    expect(Environment::authoringAllowed())->toBeFalse();
});
