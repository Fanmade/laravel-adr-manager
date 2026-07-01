<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Mcp;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Data\AdrDto;
use stdClass;

/**
 * Transport-agnostic Model Context Protocol server. It processes a single
 * decoded JSON-RPC request and returns the response array (or null for
 * notifications), exposing the ADR store as read-only MCP tools.
 */
final class McpServer
{
    private const string PROTOCOL_VERSION = '2024-11-05';

    private const string SERVER_NAME = 'laravel-adr-manager';

    private const string SERVER_VERSION = '0.1.0';

    public function __construct(private readonly AdrRepository $repository) {}

    /**
     * @param  array<array-key, mixed>  $request
     * @return array<string, mixed>|null Null when the request is a notification.
     */
    public function handle(array $request): ?array
    {
        $isNotification = ! array_key_exists('id', $request);
        $id = $this->extractId($request);
        $method = $request['method'] ?? null;

        if (($request['jsonrpc'] ?? null) !== '2.0' || ! is_string($method)) {
            return $isNotification ? null : $this->error($id, -32600, 'Invalid Request');
        }

        try {
            $params = is_array($request['params'] ?? null) ? $request['params'] : [];

            $result = $this->dispatch($method, $params);
        } catch (McpException $e) {
            return $isNotification ? null : $this->error($id, $e->rpcCode, $e->getMessage());
        }

        return $isNotification ? null : $this->success($id, $result);
    }

    /**
     * @param  array<array-key, mixed>  $params
     */
    private function dispatch(string $method, array $params): mixed
    {
        return match (true) {
            $method === 'initialize' => $this->initialize(),
            $method === 'ping' => new stdClass,
            $method === 'tools/list' => $this->toolsList(),
            $method === 'tools/call' => $this->toolsCall($params),
            str_starts_with($method, 'notifications/') => new stdClass,
            default => throw McpException::methodNotFound($method),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function initialize(): array
    {
        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => ['tools' => new stdClass],
            'serverInfo' => ['name' => self::SERVER_NAME, 'version' => self::SERVER_VERSION],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toolsList(): array
    {
        return [
            'tools' => [
                [
                    'name' => 'list_adrs',
                    'description' => 'List all architectural decision records as a timeline.',
                    'inputSchema' => ['type' => 'object', 'properties' => new stdClass],
                ],
                [
                    'name' => 'get_adr_context',
                    'description' => 'Fetch the full content of a single ADR by its id.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => ['id' => ['type' => 'string', 'description' => 'The ADR id, e.g. "0007".']],
                        'required' => ['id'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<array-key, mixed>  $params
     * @return array<string, mixed>
     */
    private function toolsCall(array $params): array
    {
        $name = $params['name'] ?? null;

        if (! is_string($name)) {
            throw McpException::invalidParams('Missing tool name.');
        }

        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        return match ($name) {
            'list_adrs' => $this->listAdrsTool(),
            'get_adr_context' => $this->getAdrContextTool($arguments),
            default => throw McpException::invalidParams("Unknown tool: {$name}"),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function listAdrsTool(): array
    {
        $items = $this->repository->all()->map(fn (AdrDto $adr): array => [
            'id' => $adr->id,
            'title' => $adr->title,
            'status' => $adr->status,
            'date' => $adr->date->toDateString(),
        ])->all();

        return $this->textContent($this->encode($items));
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function getAdrContextTool(array $arguments): array
    {
        $id = $arguments['id'] ?? null;

        if (! is_string($id)) {
            throw McpException::invalidParams('Missing argument: id.');
        }

        $adr = $this->repository->find($id);

        if ($adr === null) {
            return [
                'content' => [['type' => 'text', 'text' => "No ADR found with id [{$id}]."]],
                'isError' => true,
            ];
        }

        return $this->textContent($this->encode($adr->toArray()));
    }

    /**
     * @return array<string, mixed>
     */
    private function textContent(string $text): array
    {
        return ['content' => [['type' => 'text', 'text' => $text]]];
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<array-key, mixed>  $request
     */
    private function extractId(array $request): int|string|null
    {
        $id = $request['id'] ?? null;

        return is_int($id) || is_string($id) ? $id : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function success(int|string|null $id, mixed $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    /**
     * @return array<string, mixed>
     */
    private function error(int|string|null $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
