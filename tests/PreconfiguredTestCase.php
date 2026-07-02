<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Tests;

use Fanmade\AdrManager\AdrManagerServiceProvider;
use Fanmade\AdrManager\Tests\Support\PredefinedGateServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;

/**
 * Boots a host-defined authorization gate before the package provider so the
 * package's default gate is left untouched.
 */
class PreconfiguredTestCase extends TestCase
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            PredefinedGateServiceProvider::class,
            AdrManagerServiceProvider::class,
        ];
    }
}
