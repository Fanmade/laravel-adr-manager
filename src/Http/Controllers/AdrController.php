<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Http\Controllers;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Data\AdrDto;
use Illuminate\Http\JsonResponse;

/**
 * Read-only control-plane endpoints. The default responses are JSON; a
 * published starter kit may replace these with a rendered UI.
 */
final class AdrController
{
    public function index(AdrRepository $repository): JsonResponse
    {
        return new JsonResponse(
            $repository->all()->map(fn (AdrDto $adr): array => $adr->toArray())->all(),
        );
    }

    public function show(AdrRepository $repository, string $id): JsonResponse
    {
        $adr = $repository->find($id);

        abort_if($adr === null, 404);

        return new JsonResponse($adr->toArray());
    }
}
