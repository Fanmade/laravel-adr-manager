<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Http\Controllers;

use Fanmade\AdrManager\Mcp\McpServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JsonException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * HTTP transport for the MCP server: decodes the JSON-RPC body (single or
 * batch), delegates to {@see McpServer}, and encodes the response. Notifications
 * receive an empty 202 per the MCP HTTP transport.
 */
final class McpController
{
    public function __invoke(Request $request, McpServer $server): SymfonyResponse
    {
        try {
            /** @var mixed $decoded */
            $decoded = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse($this->parseError());
        }

        if (! is_array($decoded)) {
            return new JsonResponse($this->parseError());
        }

        if (array_is_list($decoded) && $decoded !== []) {
            return $this->handleBatch($server, $decoded);
        }

        $response = $server->handle($decoded);

        return $response === null ? new Response('', 202) : new JsonResponse($response);
    }

    /**
     * @param  list<mixed>  $requests
     */
    private function handleBatch(McpServer $server, array $requests): SymfonyResponse
    {
        $responses = [];

        foreach ($requests as $request) {
            if (is_array($request)) {
                $response = $server->handle($request);

                if ($response !== null) {
                    $responses[] = $response;
                }
            }
        }

        return $responses === [] ? new Response('', 202) : new JsonResponse($responses);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseError(): array
    {
        return ['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32700, 'message' => 'Parse error']];
    }
}
