---
id: '0006'
title: Supersede relations are engine-managed and reciprocal
status: accepted
date: 2026-07-04
author: Ben
---

# 0006. Supersede relations are engine-managed and reciprocal

## Context

A record's `supersedes` list and the target's `status`/`backlinks` describe one
relationship stored in two files. When authoring surfaces write only their own
side (as the dashboard originally did), the files silently disagree: the old
record keeps its `accepted` status and shows no successor. The linter could not
detect this, and the original status of a superseded record is not stored
anywhere, so releasing a link has no exact state to restore.

## Decision

All authoring surfaces (CLI, Livewire dashboard, published starter
controllers) route supersede changes through the engine: adding "A supersedes
B" also marks B `superseded` and backlinks it to A. Removing the link releases
B — the backlink is dropped and, when no other record still supersedes B, its
status reverts to `accepted` by convention, since the prior status is not
recorded. `adr:lint` gains a `relation` rule that fails on any one-sided
supersede, so hand-edited files surface in CI.

## Consequences

Dashboard, CLI and starter-kit writes produce identical file changes; the
`SupersedeSynchronizer` service is the single place encoding the semantics.
Reverting to `accepted` may misstate a record that was, e.g., `deprecated`
before being superseded — accepted as a documented convention rather than
storing status history. Existing repositories with one-sided links will start
failing `adr:lint` and must be repaired once.
