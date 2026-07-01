<?php

declare(strict_types=1);

use Fanmade\AdrManager\Tests\DisabledRoutingTestCase;
use Fanmade\AdrManager\Tests\PreconfiguredTestCase;
use Fanmade\AdrManager\Tests\RoutingPrefixTestCase;
use Fanmade\AdrManager\Tests\TestCase;

require_once __DIR__.'/Helpers.php';

pest()->extend(TestCase::class)->in('Feature', 'Unit');
pest()->extend(RoutingPrefixTestCase::class)->in('Routing');
pest()->extend(PreconfiguredTestCase::class)->in('Gate');
pest()->extend(DisabledRoutingTestCase::class)->in('DisabledRouting');
