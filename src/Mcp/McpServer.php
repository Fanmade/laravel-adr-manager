<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Mcp;

use Fanmade\AdrManager\Contracts\AdrRepository;
use Fanmade\AdrManager\Data\AdrDto;
use Fanmade\AdrManager\Services\SupersedeSynchronizer;
use Fanmade\AdrManager\Support\CommitInstructions;
use Fanmade\AdrManager\Support\Environment;
use Fanmade\AdrManager\Support\Statuses;
use stdClass;
use Throwable;

/**
 * Transport-agnostic Model Context Protocol server. It processes a single
 * decoded JSON-RPC request and returns the response array (or null for
 * notifications), exposing the ADR store as MCP tools. Reads always come from
 * the file source of truth; the single write tool (`create_adr`) only
 * persists in the configured authoring environments and otherwise returns the
 * Markdown and git commands to commit the record manually.
 */
final class McpServer
{
    private const string PROTOCOL_VERSION = '2024-11-05';

    private const string SERVER_NAME = 'laravel-adr-manager';

    private const string SERVER_VERSION = '0.1.0';

    public function __construct(
        private readonly AdrRepository $repository,
        private readonly SupersedeSynchronizer $synchronizer,
        private readonly CommitInstructions $instructions,
    ) {}

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
        } catch (Throwable) {
            // Never leak an internal message (it may expose paths); map any
            // unexpected failure to a spec-compliant internal error.
            return $isNotification ? null : $this->error($id, -32603, 'Internal error');
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
                [
                    'name' => 'search_adrs',
                    'description' => 'Search records by a case-insensitive substring of the title or section content.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => ['query' => ['type' => 'string', 'description' => 'The search term.']],
                        'required' => ['query'],
                    ],
                ],
                [
                    'name' => 'create_adr',
                    'description' => 'Create a new ADR. Persists only in the configured authoring environments; '
                        .'elsewhere it returns the Markdown and git commands to commit the record manually.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string', 'description' => 'The decision title.'],
                            'status' => ['type' => 'string', 'description' => 'One of the configured statuses (default "proposed").'],
                            'context' => ['type' => 'string'],
                            'decision' => ['type' => 'string'],
                            'consequences' => ['type' => 'string'],
                            'author' => ['type' => 'string'],
                            'supersedes' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Ids of records this decision supersedes.'],
                        ],
                        'required' => ['title'],
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
            'search_adrs' => $this->searchAdrsTool($arguments),
            'create_adr' => $this->createAdrTool($arguments),
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
     * @param  array<array-key, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function searchAdrsTool(array $arguments): array
    {
        $query = $arguments['query'] ?? null;

        if (! is_string($query) || trim($query) === '') {
            throw McpException::invalidParams('Missing argument: query.');
        }

        $needle = mb_strtolower(trim($query));

        $items = $this->repository->all()
            ->filter(fn (AdrDto $adr): bool => str_contains(
                mb_strtolower($adr->title.' '.$adr->context.' '.$adr->decision.' '.$adr->consequences),
                $needle,
            ))
            ->map(fn (AdrDto $adr): array => [
                'id' => $adr->id,
                'title' => $adr->title,
                'status' => $adr->status,
                'date' => $adr->date->toDateString(),
            ])
            ->values()
            ->all();

        return $this->textContent($this->encode($items));
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function createAdrTool(array $arguments): array
    {
        $title = $arguments['title'] ?? null;
        $title = is_string($title) ? trim($title) : '';

        if ($title === '') {
            throw McpException::invalidParams('Missing argument: title.');
        }

        $status = $arguments['status'] ?? 'proposed';

        if (! is_string($status) || ! in_array($status, Statuses::allowed(), true)) {
            throw McpException::invalidParams('Invalid status. Allowed: '.implode(', ', Statuses::allowed()).'.');
        }

        $supersedes = [];

        foreach (is_array($arguments['supersedes'] ?? null) ? $arguments['supersedes'] : [] as $target) {
            if (! is_string($target)) {
                continue;
            }

            if ($this->repository->find($target) === null) {
                throw McpException::invalidParams("Cannot supersede unknown ADR [{$target}].");
            }

            $supersedes[] = $target;
        }

        $id = str_pad((string) ($this->repository->getLatestSequence() + 1), 4, '0', STR_PAD_LEFT);

        $draft = AdrDto::fromArray([
            'id' => $id,
            'title' => $title,
            'status' => $status,
            'context' => $this->optionalString($arguments, 'context'),
            'decision' => $this->optionalString($arguments, 'decision'),
            'consequences' => $this->optionalString($arguments, 'consequences'),
            'author' => $this->optionalString($arguments, 'author'),
            'supersedes' => $supersedes,
        ]);

        if (! Environment::authoringAllowed()) {
            return $this->textContent($this->encode([
                'persisted' => false,
                'reason' => 'Authoring is disabled in this environment; commit the record manually.',
                ...$this->instructions->for($draft),
            ]));
        }

        $this->repository->save($draft);
        $this->synchronizer->apply($this->repository, null, $draft);

        return $this->textContent($this->encode([
            'persisted' => true,
            'id' => $id,
            'path' => $this->instructions->for($draft)['path'],
        ]));
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function optionalString(array $arguments, string $key): string
    {
        $value = $arguments[$key] ?? '';

        return is_string($value) ? $value : '';
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
