<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Support;

/**
 * Reads the configured status vocabulary (`adr-manager.statuses`).
 */
final class Statuses
{
    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        $statuses = config('adr-manager.statuses', []);

        return is_array($statuses) ? array_values(array_filter($statuses, 'is_string')) : [];
    }
}
