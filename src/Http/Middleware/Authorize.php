<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the ADR control plane with the configured authorization gate,
 * returning 403 when access is denied.
 */
final class Authorize
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $gate = config('adr-manager.authorization.gate', 'viewAdrManager');

        if (Gate::denies(is_string($gate) ? $gate : 'viewAdrManager')) {
            abort(403);
        }

        return $next($request);
    }
}
