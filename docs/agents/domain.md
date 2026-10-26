# Domain documentation

## Layout

This is a single-context project. Use root `CONTEXT.md` for resolved
domain terminology and `docs/adr/` for significant settled decisions.
Create these lazily, not as setup scaffolding.

## Read on demand

Read relevant project instructions and current decisions wherever they
live. Preserve existing documentation and formats, including
`docs/architecture.md`, `docs/lifecycle.md`, `docs/extending.md`, and
the filter documentation.

Reuse established findings; read further when facts are missing or stale.
If CONTEXT.md or ADRs do not exist, proceed using existing documentation
without requesting their creation.

## Vocabulary and decisions

Use the project's established terminology. A new module name does not
require a glossary entry. Use domain-modeling when concepts or significant
documentation decisions need resolution.

Surface conflicts with documented decisions, citing their location and
the concrete reason for reconsideration. Do not silently override them
or create a parallel decision store.
