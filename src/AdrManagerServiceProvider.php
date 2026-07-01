<?php

declare(strict_types=1);

namespace Fanmade\AdrManager;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Repositories\LocalMarkdownRepository;
use Fanmade\AdrManager\Services\MarkdownGenerator;
use Fanmade\AdrManager\Services\MarkdownParser;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

final class AdrManagerServiceProvider extends ServiceProvider
{
    private const string CONFIG_FILE = __DIR__.'/../config/adr-manager.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_FILE, 'adr-manager');

        $this->app->singleton(AdrRepository::class, function (Application $app): LocalMarkdownRepository {
            $config = $app->make(Config::class);

            $path = $config->get('adr-manager.path', 'docs/adrs');
            $pattern = $config->get('adr-manager.filename_pattern', '{id}-{slug}.md');

            return new LocalMarkdownRepository(
                $app->make(Filesystem::class),
                $app->make(MarkdownParser::class),
                $app->make(MarkdownGenerator::class),
                $this->resolveDirectory($app, is_string($path) ? $path : 'docs/adrs'),
                is_string($pattern) ? $pattern : '{id}-{slug}.md',
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                self::CONFIG_FILE => $this->app->configPath('adr-manager.php'),
            ], 'adr-manager-config');
        }
    }

    /**
     * Resolve a configured path to an absolute directory. Absolute paths are
     * used verbatim; relative paths are anchored to the application base path.
     */
    private function resolveDirectory(Application $app, string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return $app->basePath($path);
    }
}
