<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Records directory
    |--------------------------------------------------------------------------
    |
    | Directory (relative to the application base path) where ADR Markdown
    | files live. This is the Git-tracked source of truth.
    |
    */

    'path' => 'docs/adrs',

    /*
    |--------------------------------------------------------------------------
    | Filename pattern
    |--------------------------------------------------------------------------
    |
    | Pattern used when generating new record filenames. Supported tokens:
    | {id} (zero-padded sequence) and {slug} (kebab-cased title).
    |
    */

    'filename_pattern' => '{id}-{slug}.md',

    /*
    |--------------------------------------------------------------------------
    | Record format
    |--------------------------------------------------------------------------
    |
    | The template used when rendering records to disk. "nygard" produces the
    | classic Context / Decision / Consequences layout.
    |
    */

    'format' => 'nygard',

    /*
    |--------------------------------------------------------------------------
    | Allowed statuses
    |--------------------------------------------------------------------------
    */

    'statuses' => ['proposed', 'accepted', 'deprecated', 'superseded'],

    'default_status' => 'proposed',

    /*
    |--------------------------------------------------------------------------
    | Storage driver
    |--------------------------------------------------------------------------
    |
    | The container binding used for the AdrRepository contract. The default
    | driver reads and writes Markdown files on the local disk.
    |
    */

    'storage' => [
        'driver' => 'local_markdown',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    */

    'routing' => [
        'enabled' => true,
        'prefix' => 'adr',
        'domain' => null,
        'middleware' => ['web'],
        'api_middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | The gate that guards the management routes. When "open_locally" is true
    | the gate short-circuits to allow access in the local environment; every
    | other environment must define the gate explicitly.
    |
    */

    'authorization' => [
        'gate' => 'viewAdrManager',
        'open_locally' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authoring
    |--------------------------------------------------------------------------
    |
    | Environments in which the UI may write records to disk. Everywhere else
    | the UI is read-only and surfaces copy-paste git commit blocks instead of
    | editable forms, keeping deployed tiers aligned with the Git workflow.
    |
    */

    'authoring' => [
        'environments' => ['local'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database connection
    |--------------------------------------------------------------------------
    |
    | Connection used by the relational cache index (adr:sync). Null uses the
    | application's default connection.
    |
    */

    'database' => [
        'connection' => null,
    ],

];
