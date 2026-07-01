<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Tests;

use Illuminate\Foundation\Application;

/**
 * Boots the package with a non-default routing prefix so tests can prove the
 * control plane mounts wherever configuration points it.
 */
class RoutingPrefixTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('adr-manager.routing.prefix', 'decisions');
    }
}
