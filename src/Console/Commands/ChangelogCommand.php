<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Console\Commands;

use Fanmade\AdrManager\Services\ChangelogGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

final class ChangelogCommand extends Command
{
    protected $signature = 'adr:changelog
        {--from= : Only include records on or after this date}
        {--to= : Only include records on or before this date}
        {--output= : Write the changelog to this file instead of stdout}';

    protected $description = 'Compile a Markdown changelog of architectural decisions.';

    public function handle(ChangelogGenerator $generator): int
    {
        $markdown = $generator->generate(
            $this->dateOption('from'),
            $this->dateOption('to'),
        );

        $output = $this->option('output');

        if (is_string($output) && $output !== '') {
            File::put($output, $markdown);
            $this->info("Changelog written to {$output}.");

            return self::SUCCESS;
        }

        $this->line($markdown);

        return self::SUCCESS;
    }

    private function dateOption(string $name): ?Carbon
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }
}
