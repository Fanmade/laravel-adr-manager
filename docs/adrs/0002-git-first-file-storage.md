---
id: '0002'
title: Git-first Markdown files are the source of truth
status: accepted
date: 2026-07-01
author: Ben
---

# 0002. Git-first Markdown files are the source of truth

## Context

ADRs must be reviewable in pull requests, diffable, and portable across tools.
A database-first design would couple the records to a running application and
lose the Git history that makes decisions auditable.

## Decision

Plain Markdown files with YAML front-matter are the canonical store. The
`AdrRepository` contract abstracts read/write access; `LocalMarkdownRepository`
is the default binding. All writes go through a single generator so on-disk
formatting stays stable and diffs stay minimal.

## Consequences

The filesystem is authoritative. Any relational storage is a derived cache
(see [[0003-database-as-read-cache]]). Hosts may bind an alternative repository
implementation without changing the rest of the package.
