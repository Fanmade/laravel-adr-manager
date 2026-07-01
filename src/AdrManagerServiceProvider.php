<?php

declare(strict_types=1);

namespace Fanmade\AdrManager;

use Fanmade\AdrManager\Console\Commands\LintCommand;
use Fanmade\AdrManager\Console\Commands\SyncCommand;
use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Repositories\LocalMarkdownRepository;
use Fanmade\AdrManager\Services\AdrLinter;
use Fanmade\AdrManager\Services\MarkdownGenerator;
use Fanmade\AdrManager\Services\MarkdownParser;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

final class AdrManagerServiceProvider extends ServiceProvider
{
    private const string CONFIG_FILE = __DIR__.'/../config/adr-manager.php';

    private const string MIGRATIONS_DIR = __DIR__.'/../database/migrations';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_FILE, 'adr-manager');

        $this->app->singleton(AdrRepository::class, fn (Application $app): LocalMarkdownRepository => new LocalMarkdownRepository(
            $app->make(Filesystem::class),
            $app->make(MarkdownParser::class),
            $app->make(MarkdownGenerator::class),
            $this->recordsDirectory($app),
            $this->configString($app, 'adr-manager.filename_pattern', '{id}-{slug}.md'),
        ));

        $this->app->bind(AdrLinter::class, fn (Application $app): AdrLinter => new AdrLinter(
            $app->make(Filesystem::class),
            $app->make(MarkdownParser::class),
            $this->recordsDirectory($app),
            $this->configStringList($app, 'adr-manager.statuses'),
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(self::MIGRATIONS_DIR);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                self::CONFIG_FILE => $this->app->configPath('adr-manager.php'),
            ], 'adr-manager-config');

            $this->publishes([
                self::MIGRATIONS_DIR => $this->app->databasePath('migrations'),
            ], 'adr-manager-migrations');

            $this->commands([
                SyncCommand::class,
                LintCommand::class,
            ]);
        }
    }

    private function recordsDirectory(Application $app): string
    {
        $path = $this->configString($app, 'adr-manager.path', 'docs/adrs');

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return $app->basePath($path);
    }

    private function configString(Application $app, string $key, string $default): string
    {
        $value = $app->make(Config::class)->get($key, $default);

        return is_string($value) ? $value : $default;
    }

    /**
     * @return list<string>
     */
    private function configStringList(Application $app, string $key): array
    {
        $value = $app->make(Config::class)->get($key, []);

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_string'));
    }
}
