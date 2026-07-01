---
id: '0004'
title: The MCP server reads the file source of truth
status: accepted
date: 2026-07-01
author: Ben
---

# 0004. The MCP server reads the file source of truth

## Context

The MCP endpoint exposes ADRs to external AI agents. It could read from the
relational index (fast, but a truncated projection) or from the Markdown files
(full fidelity, always current). AI agents need the complete Context, Decision
and Consequences to reason about past decisions.

## Decision

The MCP tools read through the `AdrRepository` (the files), not the database
index. `list_adrs` returns the timeline; `get_adr_context` returns the full
record. The database index remains dedicated to dashboard search and graph
traversal (see [[0003-database-as-read-cache]]).

## Consequences

MCP responses are always accurate without a prior `adr:sync`. The endpoint
performs a filesystem read per call, which is acceptable for its low-traffic,
read-only role. If MCP traffic grows, a cached read path can be added behind
the same contract.
