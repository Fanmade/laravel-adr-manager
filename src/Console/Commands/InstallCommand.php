<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Console\Commands;

use Fanmade\AdrManager\Console\StackInstaller;
use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'adr:install {stack? : The starter stack to publish (livewire, vue, react)}';

    protected $description = 'Publish an editable frontend starter stack.';

    public function handle(StackInstaller $installer): int
    {
        $stack = $this->resolveStack($installer);

        if (! $installer->supports($stack)) {
            $this->error("Unknown stack [{$stack}]. Available: ".implode(', ', $installer->stacks()).'.');

            return self::INVALID;
        }

        foreach ($installer->install($stack) as $destination) {
            $this->line("<info>Published:</info> {$destination}");
        }

        $this->info("Installed the {$stack} starter stack.");

        return self::SUCCESS;
    }

    private function resolveStack(StackInstaller $installer): string
    {
        $stack = $this->argument('stack');

        if (! is_string($stack) || $stack === '') {
            $stack = $this->choice('Which starter stack?', $installer->stacks());
        }

        return strtolower(is_string($stack) ? $stack : '');
    }
}
