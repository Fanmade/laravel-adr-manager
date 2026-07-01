<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Exceptions;

use InvalidArgumentException;

final class InvalidAdrData extends InvalidArgumentException
{
    public static function missingAttribute(string $attribute): self
    {
        return new self("The ADR attribute [{$attribute}] is required and must not be empty.");
    }
}
