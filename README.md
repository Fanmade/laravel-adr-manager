# Laravel ADR Manager

Git-first, file-based Architectural Decision Records (ADRs) for Laravel. Records
are plain Markdown on disk — the source of truth — with an optional relational
index for fast search, a JSON control plane, and an MCP endpoint for AI agents.

## Requirements

- PHP 8.3+
- Laravel 11 or 12

## Installation

```bash
composer require fanmade/laravel-adr-manager
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=adr-manager-config
```

If you use the relational index (see [Sync](#index-and-sync)), publish and run
the migrations:

```bash
php artisan vendor:publish --tag=adr-manager-migrations
php artisan migrate
```

## Record format

Records live in `docs/adrs/` (configurable) as `{id}-{slug}.md`. Each file is
YAML front-matter followed by the standard Nygard sections:

```markdown
---
id: '0007'
title: Use PostgreSQL for persistence
status: accepted
date: 2026-01-15
author: Ben
supersedes:
  - '0002'
---

# 0007. Use PostgreSQL for persistence

## Context

We need a relational store with strong consistency.

## Decision

We will use PostgreSQL.

## Consequences

Operations must run and back up PostgreSQL.
```

Valid statuses are `proposed`, `accepted`, `deprecated` and `superseded`.

Two conventions keep parsing unambiguous:

- Quote the `id` in front-matter (`id: '0007'`) so YAML does not coerce it to a
  number and drop the zero-padding.
- `## Context`, `## Decision` and `## Consequences` are reserved section
  delimiters. Use `###` or deeper for headings inside a section's prose.

## Commands

| Command | Purpose |
| --- | --- |
| `adr:sync` | Reconcile the database index with the files on disk. |
| `adr:lint` | Validate format, statuses, links and sequence integrity. Exits non-zero on any issue. |
| `adr:changelog` | Compile a Markdown changelog. Options: `--from`, `--to`, `--output`. |
| `adr:install` | Publish a frontend starter stack (`livewire`, `vue`, `react`). |

`adr:lint` is designed for CI:

```bash
php artisan adr:lint
```

## Index and sync

The filesystem is authoritative. On staging or production, `adr:sync` projects
the files into `adr_records` and `adr_relations` for fast querying. The index is
disposable and can be rebuilt from disk at any time:

```bash
php artisan adr:sync
```

## Control plane and authorization

The package registers read-only routes (default prefix `adr`, and `api/adr` for
JSON):

- `GET /adr` — record index
- `GET /adr/{id}` — single record
- `POST /api/adr/mcp` — MCP endpoint (see below)

Access is guarded by the `viewAdrManager` gate, which is **open in the local
environment and denied everywhere else** until you define it yourself:

```php
Gate::define('viewAdrManager', fn (?Authenticatable $user) => $user?->isAdmin() ?? false);
```

Because the routes are not authenticated, the gate is evaluated for a guest, so
the closure's user parameter must be nullable.

Routing is fully configurable in `config/adr-manager.php` (prefix, domain,
middleware, or disable it entirely with `routing.enabled`).

## MCP endpoint

`POST /api/adr/mcp` speaks JSON-RPC 2.0 following the Model Context Protocol.
It supports `initialize`, `ping`, `tools/list` and `tools/call`, exposing two
read-only tools:

- `list_adrs` — the decision timeline
- `get_adr_context` — the full content of one record by `id`

```bash
curl -X POST https://your-app.test/api/adr/mcp \
  -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"get_adr_context","arguments":{"id":"0007"}}}'
```

The endpoint reads the Markdown source of truth, so responses are always current
without a prior `adr:sync`.

## Extending storage

Every read and write flows through the `AdrRepository` contract. The default
binding is `LocalMarkdownRepository`. To use different storage, bind your own
implementation in a service provider:

```php
$this->app->bind(
    \Fanmade\AdrManager\Contracts\AdrRepository::class,
    \App\Adr\MyRepository::class,
);
```

## Development

```bash
composer test        # Pest
composer test:coverage
composer stan         # PHPStan (max) + Larastan
composer lint         # Pint
```

## License

MIT.
