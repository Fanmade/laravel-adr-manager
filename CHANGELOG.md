# Changelog

All notable changes to `fanmade/laravel-adr-manager` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- MCP tools `search_adrs` (case-insensitive substring search over titles and
  sections, reading the file source of truth) and `create_adr` — the first
  write tool. It persists only in the configured authoring environments and
  otherwise returns the Markdown plus git commands for a manual commit.
- Relation graph view at `GET /adr/graph`: server-rendered SVG of the
  supersede graph (git-log style), reading the `adr:sync`ed index per
  ADR 0003, with linked nodes and a hint when the index is empty.

## [0.2.0] - 2026-07-04

### Added

- `adr:make` command: terminal-first record authoring with `--status`,
  `--author` and repeatable `--supersedes` options. Assigns the next id,
  honors the authoring environment gate and prints the git commands to commit
  the new record.
- `adr:lint` now fails on one-sided supersede relations (`relation` rule):
  every "A supersedes B" must be reciprocal. Repositories with legacy
  one-sided links need a one-time repair.

### Changed

- Supersede relations are engine-managed everywhere (see ADR 0006): the
  Livewire dashboard and the published Vue/React starter controllers now
  route supersede changes through the new `SupersedeSynchronizer`. Removing a
  record's last superseder reverts it to `accepted` by convention.
- `supersede()` is now part of the `AdrRepository` contract. Custom
  repository implementations must add the method.

## [0.1.0] - 2026-07-04

### Added

- Git-first ADR storage: Markdown files with YAML front-matter under
  `docs/adrs/` (configurable) as the source of truth, parsed into immutable
  DTOs behind the `AdrRepository` contract.
- Byte-stable Markdown generator with round-trip parsing, plus an automated
  supersede/backlink engine.
- Rebuildable relational read cache (`adr_records`, `adr_relations`) with
  `adr:sync`, integrity linting via `adr:lint`, and an architectural changelog
  compiler via `adr:changelog`.
- Web dashboard (Livewire 3.5+/4, optional): index with search, record view,
  create/edit forms. Writing is restricted to configured environments; other
  environments get the exact Markdown and git commands to commit by hand.
  Ships a pre-compiled inline stylesheet — no asset build required.
- JSON control plane under `api/adr` and an MCP endpoint (`POST /api/adr/mcp`)
  exposing `list_adrs` and `get_adr_context`, all guarded by the
  `viewAdrManager` gate (open locally, denied elsewhere until defined).
- `adr:install` scaffolder with three starter stacks: Livewire (publishable
  views) and Inertia Vue / React (full Index/Show/Create/Edit component sets
  published as application code).
- Supports PHP 8.3+, Laravel 12 and 13, Livewire 3.5+ and 4.

[Unreleased]: https://github.com/fanmade/laravel-adr-manager/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/fanmade/laravel-adr-manager/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/fanmade/laravel-adr-manager/releases/tag/v0.1.0
