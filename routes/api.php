<?php

declare(strict_types=1);

use Fanmade\AdrManager\Http\Controllers\AdrController;
use Fanmade\AdrManager\Http\Middleware\Authorize;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api/'.config('adr-manager.routing.prefix', 'adr'),
    'domain' => config('adr-manager.routing.domain'),
    'middleware' => [...(array) config('adr-manager.routing.api_middleware', ['api']), Authorize::class],
], function (): void {
    Route::get('/', [AdrController::class, 'index'])->name('adr-manager.api.index');
    Route::get('/{id}', [AdrController::class, 'show'])->name('adr-manager.api.show');
});
