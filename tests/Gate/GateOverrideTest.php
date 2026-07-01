<?php

declare(strict_types=1);

it('leaves a gate defined before the package boots untouched', function () {
    // Environment is "testing" (not local); the pre-defined gate still grants
    // access, proving the package does not overwrite it.
    $this->getJson('/adr')->assertOk();
});
