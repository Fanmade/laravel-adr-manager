<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Tests\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Stand-in for a host application that defines the authorization gate before
 * the package boots.
 */
final class PredefinedGateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('viewAdrManager', fn (?Authenticatable $user): bool => true);
    }
}
