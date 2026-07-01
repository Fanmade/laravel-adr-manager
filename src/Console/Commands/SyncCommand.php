<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Console\Commands;

use Fanmade\AdrManager\Services\AdrIndexer;
use Illuminate\Console\Command;

final class SyncCommand extends Command
{
    protected $signature = 'adr:sync';

    protected $description = 'Synchronize the database index with the ADR files on disk.';

    public function handle(AdrIndexer $indexer): int
    {
        $count = $indexer->sync();

        $this->info("Synchronized {$count} ADR(s) into the index.");

        return self::SUCCESS;
    }
}
