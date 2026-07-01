<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Tests;

use Illuminate\Foundation\Application;

class DisabledRoutingTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('adr-manager.routing.enabled', false);
    }
}
