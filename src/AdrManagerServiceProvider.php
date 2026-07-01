<?php

declare(strict_types=1);

namespace Fanmade\AdrManager;

use Illuminate\Support\ServiceProvider;

final class AdrManagerServiceProvider extends ServiceProvider
{
    private const string CONFIG_FILE = __DIR__.'/../config/adr-manager.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_FILE, 'adr-manager');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                self::CONFIG_FILE => $this->app->configPath('adr-manager.php'),
            ], 'adr-manager-config');
        }
    }
}
