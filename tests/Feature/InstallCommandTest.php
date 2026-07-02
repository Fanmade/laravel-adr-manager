<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

afterEach(function () {
    $files = new Filesystem;
    $files->deleteDirectory(app_path('Http/Controllers/Adr'));
    $files->deleteDirectory(resource_path('js/Pages/Adr'));
    $files->deleteDirectory(resource_path('views/vendor/adr-manager'));
});

it('installs the livewire starter stack', function () {
    $this->artisan('adr:install', ['stack' => 'livewire'])->assertSuccessful();

    expect(file_exists(resource_path('views/vendor/adr-manager/livewire/adr-index.blade.php')))->toBeTrue()
        ->and(file_exists(resource_path('views/vendor/adr-manager/layouts/app.blade.php')))->toBeTrue();
});

it('installs the vue starter stack', function () {
    $this->artisan('adr:install', ['stack' => 'vue'])->assertSuccessful();

    expect(file_exists(resource_path('js/Pages/Adr/Index.vue')))->toBeTrue()
        ->and(file_exists(resource_path('js/Pages/Adr/Show.vue')))->toBeTrue()
        ->and(file_exists(app_path('Http/Controllers/Adr/AdrController.php')))->toBeTrue();
});

it('installs the react starter stack', function () {
    $this->artisan('adr:install', ['stack' => 'react'])->assertSuccessful();

    expect(file_exists(resource_path('js/Pages/Adr/Index.tsx')))->toBeTrue()
        ->and(file_exists(resource_path('js/Pages/Adr/Show.tsx')))->toBeTrue();
});

it('is case-insensitive about the stack name', function () {
    $this->artisan('adr:install', ['stack' => 'Livewire'])->assertSuccessful();

    expect(file_exists(resource_path('views/vendor/adr-manager/livewire/adr-index.blade.php')))->toBeTrue();
});

it('rejects an unknown stack with a descriptive error', function () {
    $this->artisan('adr:install', ['stack' => 'angular'])
        ->expectsOutputToContain('Unknown stack')
        ->assertExitCode(Command::INVALID);
});

it('prompts for the stack when none is provided', function () {
    $this->artisan('adr:install')
        ->expectsQuestion('Which starter stack?', 'react')
        ->assertSuccessful();

    expect(file_exists(resource_path('js/Pages/Adr/Index.tsx')))->toBeTrue();
});
