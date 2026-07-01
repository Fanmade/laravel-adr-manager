---
id: '0001'
title: Record architecture decisions
status: accepted
date: 2026-07-01
author: Ben
---

# 0001. Record architecture decisions

## Context

This package needs a durable, reviewable record of the significant technical
choices behind it. The choices outlive any single conversation and need to be
discoverable by future contributors.

## Decision

We use Architectural Decision Records (ADRs) in the Nygard format, stored as
Markdown under `docs/adrs/`. The package dogfoods itself: these files are valid
input for its own parser, linter and sync command.

## Consequences

Every non-obvious architectural choice is captured as a numbered ADR. The
records double as fixtures and living documentation for package users.
