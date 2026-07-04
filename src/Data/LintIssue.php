<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Data;

/**
 * A single problem found by the linter, tied to the file (or directory) that
 * caused it.
 */
final class LintIssue
{
    public const string FORMAT = 'format';

    public const string STATUS = 'status';

    public const string LINK = 'link';

    public const string RELATION = 'relation';

    public const string SEQUENCE = 'sequence';

    public const string DUPLICATE = 'duplicate';

    public function __construct(
        public readonly string $file,
        public readonly string $category,
        public readonly string $message,
    ) {}
}
