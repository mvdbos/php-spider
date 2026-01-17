---
description: Commit current changes with proper formatting and user approval
name: commit
agent: agent
---

**CRITICAL: Only analyze actual repository changes. Ignore conversation history about what might be changed.**

## Shell Command Rules

Prefix commands with `\` to bypass aliases (e.g., `\git`, `\printf`). Use `--no-pager` and pipe to `\cat` for git output.

## Commit Message Format

```
<Title: max 72 chars, capitalized verb, imperative mood>
                                                         <- BLANK LINE
<Body: key changes and rationale, wrapped at 72 chars>
```

**CRITICAL: The blank line between title and body is MANDATORY - git uses it to parse subject/body!**

### Title Rules
- Start with capitalized verb: Add, Fix, Update, Remove, Refactor
- Max 72 characters, imperative mood, no period
- ASCII only - no emojis or Unicode
- Be specific ("Fix null pointer in user validation" not "Fix bug")
- Reference issues when applicable: "Fix #123: ..."

### Body Rules
- **Keep it brief:** Focus only on the main change, not minor or unrelated fixes
- Summarize why this change was needed (problem solved, improvement gained, refactoring goal)
- For commits with multiple unrelated changes, focus on the primary change only
- Wrap at 72 characters, ASCII only
- Use prose, not bullet lists or detailed file-by-file changes
- Avoid listing each file or function changed; emphasize the overall impact

## Workflow

### 1. Check Branch (REQUIRED FIRST STEP)

**CRITICAL: Always check the current branch FIRST and create a feature branch if on master/main.**

```bash
\git branch --show-current
```

**If on `master` or `main`, you MUST create a feature branch before staging changes:**
```bash
\git checkout -b feature/short-description  # or fix/short-description
```

Do NOT proceed to staging changes until you are on a feature branch.

### 2. Review and Stage Changes
```bash
\git status
\git --no-pager diff | cat                    # Review changes
\git add <files>                              # Stage relevant files
\git --no-pager diff --cached --stat | cat    # Verify staged
```

### 3. Draft Commit Message (MUST GET APPROVAL)

Present the message as a code block. Focus on **the main change and its rationale**, omitting secondary fixes or unrelated changes:

```
Refactor path finding to use executable location

Simplify path resolution by using os.Executable() instead of checking
multiple CWD paths. Reduces complexity and improves startup performance.
```

Note: This focuses on the primary refactoring goal, not every changed function or incidental fixes.

**STOP and ask: "Would you like me to proceed with this commit message, or would you like to modify it?"**

### 4. Create and Execute Commit (ONLY after approval)

Use exactly two `-m` flags by default: first for the title, second for the full body (single paragraph). If another paragraph is truly needed, add one extra `-m` for that paragraph—never one per line:
```bash
\git commit -m 'Title here' -m 'Body paragraph here.'
# If a distinct paragraph is clearer, add one extra -m (not one per line):
# \git commit -m 'Title here' -m 'Body paragraph here.' -m 'Second paragraph when required.'
\git --no-pager log -1 | cat                  # Verify commit
```

### 5. Push

```bash
BRANCH=$(\git branch --show-current)
\git push --dry-run 2>&1 | cat
```

- **Clean push**: Execute `\git push -u origin "$BRANCH"` automatically
- **Force push required**: **STOP and ask user** - never force push without explicit confirmation

## Prohibited Actions

- **NEVER** commit without showing draft and getting approval first
- **NEVER** include conversation references ("as discussed", "per your request")
- **NEVER** use generic messages ("Update files", "Changes", "WIP")
- **NEVER** force push or rewrite history without explicit user confirmation
