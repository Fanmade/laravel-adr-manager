<?php

declare(strict_types=1);

use Fanmade\AdrManager\Tests\TestCase;

require_once __DIR__.'/Helpers.php';

pest()->extend(TestCase::class)->in('Feature', 'Unit');
