<?php

declare(strict_types=1);

use Fanmade\AdrManager\Http\Middleware\Authorize;
use Fanmade\AdrManager\Livewire\AdrCreate;
use Fanmade\AdrManager\Livewire\AdrEdit;
use Fanmade\AdrManager\Livewire\AdrGraph;
use Fanmade\AdrManager\Livewire\AdrIndex;
use Fanmade\AdrManager\Livewire\AdrShow;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

// The web dashboard is built with Livewire; only register it when Livewire is
// installed. The JSON control plane (routes/api.php) is always available.
if (! class_exists(Livewire::class)) {
    return;
}

Route::group([
    'prefix' => config('adr-manager.routing.prefix', 'adr'),
    'domain' => config('adr-manager.routing.domain'),
    'middleware' => [...(array) config('adr-manager.routing.middleware', ['web']), Authorize::class],
], function (): void {
    Route::get('/', AdrIndex::class)->name('adr-manager.index');
    Route::get('/create', AdrCreate::class)->name('adr-manager.create');
    Route::get('/graph', AdrGraph::class)->name('adr-manager.graph');
    Route::get('/{id}', AdrShow::class)->name('adr-manager.show');
    Route::get('/{id}/edit', AdrEdit::class)->name('adr-manager.edit');
});
