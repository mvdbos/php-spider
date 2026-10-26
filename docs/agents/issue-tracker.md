# Issue tracker: GitHub

Specs and tickets live in GitHub Issues for `mvdbos/php-spider`.
Use the `gh` CLI with `--repo mvdbos/php-spider` for issue operations.

## Operations

- Create: `gh issue create --repo mvdbos/php-spider --title "..." --body-file <file>`
- Read: `gh issue view <number> --repo mvdbos/php-spider --comments`
- List: `gh issue list --repo mvdbos/php-spider --state open`
- Comment: `gh issue comment <number> --repo mvdbos/php-spider --body-file <file>`
- Label: `gh issue edit <number> --repo mvdbos/php-spider --add-label "..."`
- Remove label: `gh issue edit <number> --repo mvdbos/php-spider --remove-label "..."`
- Close: `gh issue close <number> --repo mvdbos/php-spider --comment "..."`

Read issue bodies, comments, and labels before acting.
Use the state mapping in `docs/agents/triage-labels.md`.

## Pull requests as a triage surface

PRs as a request surface: no.

## Publication

When a skill says to publish a spec or ticket, create a GitHub issue.
When it says to fetch a ticket, read the issue and its comments.

## Ticket dependencies

Use native GitHub issue dependencies, not just parent/sub-issue links.

Obtain the blocker's database ID:
`gh api repos/mvdbos/php-spider/issues/<blocker-number> --jq .id`

Add the blocking edge:
`gh api --method POST repos/mvdbos/php-spider/issues/<child-number>/dependencies/blocked_by -F issue_id=<blocker-database-id>`

If native dependencies are unavailable, put `Blocked by: #<number>` at
the top of the child issue body. A ticket is unblocked when all blockers
are closed. Verify published links and blocking edges.
