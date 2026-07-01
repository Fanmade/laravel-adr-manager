<?php

declare(strict_types=1);

it('mounts the control plane under the configured prefix', function () {
    $this->app['env'] = 'local';

    $this->getJson('/decisions')->assertOk();
    $this->getJson('/adr')->assertNotFound();
});
