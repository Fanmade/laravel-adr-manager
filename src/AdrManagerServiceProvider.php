<?php

declare(strict_types=1);

namespace Fanmade\AdrManager;

use Fanmade\AdrManager\Console\Commands\ChangelogCommand;
use Fanmade\AdrManager\Console\Commands\InstallCommand;
use Fanmade\AdrManager\Console\Commands\LintCommand;
use Fanmade\AdrManager\Console\Commands\SyncCommand;
use Fanmade\AdrManager\Console\StackInstaller;
use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Livewire\AdrCreate;
use Fanmade\AdrManager\Livewire\AdrEdit;
use Fanmade\AdrManager\Livewire\AdrIndex;
use Fanmade\AdrManager\Livewire\AdrShow;
use Fanmade\AdrManager\Repositories\LocalMarkdownRepository;
use Fanmade\AdrManager\Services\AdrLinter;
use Fanmade\AdrManager\Services\MarkdownGenerator;
use Fanmade\AdrManager\Services\MarkdownParser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class AdrManagerServiceProvider extends ServiceProvider
{
    private const string CONFIG_FILE = __DIR__.'/../config/adr-manager.php';

    private const string MIGRATIONS_DIR = __DIR__.'/../database/migrations';

    private const string ROUTES_DIR = __DIR__.'/../routes';

    private const string VIEWS_DIR = __DIR__.'/../resources/views';

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

        $this->app->bind(StackInstaller::class, fn (Application $app): StackInstaller => new StackInstaller(
            $app->make(Filesystem::class),
            $this->stackManifest($app),
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(self::MIGRATIONS_DIR);
        $this->loadViewsFrom(self::VIEWS_DIR, 'adr-manager');
        $this->registerAuthorization();
        $this->registerRoutes();
        $this->registerLivewireComponents();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                self::CONFIG_FILE => $this->app->configPath('adr-manager.php'),
            ], 'adr-manager-config');

            $this->publishes([
                self::MIGRATIONS_DIR => $this->app->databasePath('migrations'),
            ], 'adr-manager-migrations');

            $this->publishes([
                self::VIEWS_DIR => $this->app->resourcePath('views/vendor/adr-manager'),
            ], 'adr-manager-views');

            $this->commands([
                SyncCommand::class,
                LintCommand::class,
                ChangelogCommand::class,
                InstallCommand::class,
            ]);
        }
    }

    /**
     * Define the default authorization gate: open in the local environment
     * (when configured), denied everywhere else until the host application
     * binds its own definition.
     */
    private function registerAuthorization(): void
    {
        $gate = $this->configString($this->app, 'adr-manager.authorization.gate', 'viewAdrManager');

        if (Gate::has($gate)) {
            return;
        }

        Gate::define($gate, function (?Authenticatable $user): bool {
            $openLocally = (bool) config('adr-manager.authorization.open_locally', true);

            return $openLocally && $this->app->environment('local');
        });
    }

    private function registerRoutes(): void
    {
        if ((bool) config('adr-manager.routing.enabled', true) === false) {
            return;
        }

        $this->loadRoutesFrom(self::ROUTES_DIR.'/web.php');
        $this->loadRoutesFrom(self::ROUTES_DIR.'/api.php');
    }

    /**
     * Register the Livewire dashboard components when Livewire is installed.
     * The package works headlessly without it.
     */
    private function registerLivewireComponents(): void
    {
        if (class_exists(Livewire::class)) {
            Livewire::component('adr-index', AdrIndex::class);
            Livewire::component('adr-show', AdrShow::class);
            Livewire::component('adr-create', AdrCreate::class);
            Livewire::component('adr-edit', AdrEdit::class);
        }
    }

    /**
     * @return array<string, list<array{0: string, 1: string}>>
     */
    private function stackManifest(Application $app): array
    {
        $stubs = __DIR__.'/../resources/stubs';
        $views = self::VIEWS_DIR;

        return [
            // Livewire ships as working package components; publishing exposes
            // the Blade views so a host application can restyle the dashboard.
            'livewire' => [
                [$views.'/layouts/app.blade.php', $app->resourcePath('views/vendor/adr-manager/layouts/app.blade.php')],
                [$views.'/partials/status-pill.blade.php', $app->resourcePath('views/vendor/adr-manager/partials/status-pill.blade.php')],
                [$views.'/partials/form-fields.blade.php', $app->resourcePath('views/vendor/adr-manager/partials/form-fields.blade.php')],
                [$views.'/partials/commit-instructions.blade.php', $app->resourcePath('views/vendor/adr-manager/partials/commit-instructions.blade.php')],
                [$views.'/livewire/adr-index.blade.php', $app->resourcePath('views/vendor/adr-manager/livewire/adr-index.blade.php')],
                [$views.'/livewire/adr-show.blade.php', $app->resourcePath('views/vendor/adr-manager/livewire/adr-show.blade.php')],
                [$views.'/livewire/adr-create.blade.php', $app->resourcePath('views/vendor/adr-manager/livewire/adr-create.blade.php')],
                [$views.'/livewire/adr-edit.blade.php', $app->resourcePath('views/vendor/adr-manager/livewire/adr-edit.blade.php')],
            ],
            'vue' => [
                [$stubs.'/vue/AdrController.php.stub', $app->basePath('app/Http/Controllers/Adr/AdrController.php')],
                [$stubs.'/vue/Index.vue.stub', $app->resourcePath('js/Pages/Adr/Index.vue')],
                [$stubs.'/vue/Show.vue.stub', $app->resourcePath('js/Pages/Adr/Show.vue')],
                [$stubs.'/vue/Create.vue.stub', $app->resourcePath('js/Pages/Adr/Create.vue')],
                [$stubs.'/vue/Edit.vue.stub', $app->resourcePath('js/Pages/Adr/Edit.vue')],
                [$stubs.'/vue/Form.vue.stub', $app->resourcePath('js/Pages/Adr/Form.vue')],
                [$stubs.'/vue/CommitInstructions.vue.stub', $app->resourcePath('js/Pages/Adr/CommitInstructions.vue')],
            ],
            'react' => [
                [$stubs.'/react/AdrController.php.stub', $app->basePath('app/Http/Controllers/Adr/AdrController.php')],
                [$stubs.'/react/Index.tsx.stub', $app->resourcePath('js/Pages/Adr/Index.tsx')],
                [$stubs.'/react/Show.tsx.stub', $app->resourcePath('js/Pages/Adr/Show.tsx')],
                [$stubs.'/react/Create.tsx.stub', $app->resourcePath('js/Pages/Adr/Create.tsx')],
                [$stubs.'/react/Edit.tsx.stub', $app->resourcePath('js/Pages/Adr/Edit.tsx')],
                [$stubs.'/react/Form.tsx.stub', $app->resourcePath('js/Pages/Adr/Form.tsx')],
                [$stubs.'/react/CommitInstructions.tsx.stub', $app->resourcePath('js/Pages/Adr/CommitInstructions.tsx')],
            ],
        ];
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
