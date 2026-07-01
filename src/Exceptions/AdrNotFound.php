<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Exceptions;

use RuntimeException;

final class AdrNotFound extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self("No ADR found with id [{$id}].");
    }
}
