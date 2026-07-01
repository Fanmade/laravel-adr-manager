<?php

declare(strict_types=1);

it('boots inside a Laravel application and exposes default configuration', function () {
    expect(config('adr-manager.path'))->toBe('docs/adrs')
        ->and(config('adr-manager.format'))->toBe('nygard')
        ->and(config('adr-manager.filename_pattern'))->toBe('{id}-{slug}.md');
});

it('lets the host application override package defaults', function () {
    config()->set('adr-manager.path', 'docs/decisions');

    expect(config('adr-manager.path'))->toBe('docs/decisions');
});

it('publishes the configuration file to the host application', function () {
    $target = config_path('adr-manager.php');

    if (file_exists($target)) {
        unlink($target);
    }

    $this->artisan('vendor:publish', ['--tag' => 'adr-manager-config'])
        ->assertSuccessful();

    expect(file_exists($target))->toBeTrue();
});
