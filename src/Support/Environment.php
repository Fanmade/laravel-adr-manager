<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Support;

/**
 * Answers whether the current environment may write records to disk, based on
 * the configured `adr-manager.authoring.environments` allow-list.
 */
final class Environment
{
    public static function authoringAllowed(): bool
    {
        $configured = config('adr-manager.authoring.environments', ['local']);
        $environments = is_array($configured)
            ? array_values(array_filter($configured, 'is_string'))
            : [];

        if ($environments === []) {
            return false;
        }

        return app()->environment(...$environments);
    }
}
