<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Mcp;

use RuntimeException;

/**
 * A JSON-RPC level failure carrying a spec-defined error code.
 */
final class McpException extends RuntimeException
{
    public function __construct(public readonly int $rpcCode, string $message)
    {
        parent::__construct($message);
    }

    public static function methodNotFound(string $method): self
    {
        return new self(-32601, "Method not found: {$method}");
    }

    public static function invalidParams(string $message): self
    {
        return new self(-32602, $message);
    }
}
