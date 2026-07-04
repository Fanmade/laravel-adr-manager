---
id: '0005'
title: Track the current Laravel ecosystem, support two majors
status: accepted
date: 2026-07-04
author: Ben
---

# 0005. Track the current Laravel ecosystem, support two majors

## Context

The package initially targeted Laravel 11/12 with Livewire 3. Laravel 13 and
Livewire 4 are current; a fresh host application resolves both, so the old
constraints made the package uninstallable exactly where it is most likely to
be adopted. Every supported major multiplies the CI matrix and the manual
verification surface.

## Decision

The package supports the current Laravel major and its predecessor — today
`^12 || ^13` — and drops older majors without a deprecation cycle while the
package is pre-1.0. Companion dependencies follow the same rule: Livewire
`^3.5 || ^4.0`, Testbench `^10 || ^11`, symfony/yaml `^7 || ^8`. The local
development lock file always tracks the newest supported set, and CI tests the
oldest and newest supported pairs.

## Consequences

New Laravel releases become installation targets shortly after release, at the
cost of a recurring (roughly yearly) bump task. Host applications on
end-of-life Laravel versions must use an older package tag. The two-major
window keeps the CI matrix small enough to keep the 100% coverage and PHPStan
max gates on every leg.
