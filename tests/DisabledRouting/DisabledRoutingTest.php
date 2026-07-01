<?php

declare(strict_types=1);

it('registers no control-plane routes when routing is disabled', function () {
    $this->app['env'] = 'local';

    $this->getJson('/adr')->assertNotFound();
});
