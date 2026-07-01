---
id: '0003'
title: The database is a rebuildable read cache
status: accepted
date: 2026-07-01
author: Ben
supersedes: []
backlinks: []
---

# 0003. The database is a rebuildable read cache

## Context

Filesystem scans are fine for authoring but too slow for search, graph
traversal and read-only dashboards on staging or production. We need fast
queries without making the database authoritative.

## Decision

`adr:sync` projects the files into `adr_records` and `adr_relations` in one
transaction: upsert current records, prune removed ones, rebuild relations.
The index is disposable and can be regenerated from disk at any time.

## Consequences

The database can be dropped and rebuilt with `adr:sync`. Reads may use
Eloquent; writes never target the database directly. The index may lag disk
until the next sync, which is acceptable for its read-only role.
