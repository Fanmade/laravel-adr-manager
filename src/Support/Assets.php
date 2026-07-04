<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Support;

/**
 * Serves the pre-compiled dashboard stylesheet so the Livewire dashboard is
 * styled without requiring Tailwind (or any asset build) in the host app.
 *
 * Regenerate after changing utility classes in resources/views:
 *   npx @tailwindcss/cli -i <(echo '@import "tailwindcss" source("resources/views");') \
 *     -o resources/dist/adr-manager.css --minify
 */
final class Assets
{
    public static function css(?string $path = null): string
    {
        $path ??= dirname(__DIR__, 2).'/resources/dist/adr-manager.css';
        $css = is_file($path) ? file_get_contents($path) : false;

        return $css === false ? '' : $css;
    }
}
