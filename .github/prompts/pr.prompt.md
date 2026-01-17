---
description: Create a pull request on GitHub using GitHub CLI (gh)
name: pr
agent: agent
---

# Create Pull Request

You are tasked with creating a pull request on GitHub for the current branch.

## Critical Instructions

**DO NOT rely on conversation history or context.** Only analyze what has actually changed in the repository. Inspect the actual commits and file changes to generate an accurate PR description.

## Your Task

1. **Verify prerequisites:**
   - Check GitHub CLI is installed: `gh --version`
   - Check if authenticated: `gh auth status`
   - If not authenticated, instruct user to run: `gh auth login` (HTTPS; `gh auth login -w` for browser flow)

2. **Analyze current state:**
   - Run `git branch --show-current` to get source branch name
   - Ask user for target branch (default: `master` if not specified)
   - Run `git fetch origin` to ensure branches are up to date
   - Get commits ahead of target: `git log origin/<target-branch>..HEAD --oneline`
   - Get detailed commits: `git log origin/<target-branch>..HEAD --format="%h %s%n%b"`

3. **Check for issue link (optional):**
   - Ask if there is a GitHub issue to close (e.g., `123` or `owner/repo#123`)
   - If provided, include `Closes #123` (or repo-qualified) in the PR description

4. **Generate PR title and description:**
    - **Title**: concise (max 80 characters), imperative voice
    - If single commit: reuse commit subject when appropriate
    - If multiple commits: summarize the main theme
    - **Description**:
       - 2-3 sentences: what changed and why it matters
       - Use prose, not bullets
       - Mention `Closes #123` if an issue number was provided

5. **Request approval:**
   - Display the proposed PR title and description
   - **STOP and ask: "Proceed with this PR, or modify the title/description?"**
   - Only proceed after explicit approval

6. **Create the pull request:**
    - Write the description to a temporary file to avoid shell escaping issues
    - Use `gh pr create` with file or inline description
    - Ensure long descriptions are passed via file
    ```bash
    # Option 1: Using a file (recommended for multi-line)
    cat > /tmp/pr_description.txt <<'DESC'
    This PR refactors the release detection logic from bash to Python, improving testability and maintainability. It also splits the pipeline into separate Build and Release stages for clearer separation of concerns and better workflow control.
   
    Closes #123
    DESC

    gh pr create \
       --title "<PR title>" \
       --body-file /tmp/pr_description.txt \
       --base <target-branch> \
       --head <source-branch> \
       [--draft]

    # Option 2: Simple inline
    gh pr create \
       --title "<PR title>" \
       --body "<PR description>" \
       --base <target-branch> \
       --head <source-branch> \
       [--draft]
    ```

7. **Handle draft PRs:**
   - Ask user if this should be a draft PR
   - Use `--draft` flag if confirmed

## PR Title Best Practices

- **Use imperative mood:** "Add feature" not "Added feature"
- **Be specific but concise:** Max 80 characters
- **Summarize the overall change:** Not just first commit message
- **Examples:**
  - Single feature: "Add Python release detection script"
  - Multiple related changes: "Refactor pipeline with Python detection and stage separation"
  - Bug fixes: "Fix Kroki rendering and preview publication issues"

## PR Description Best Practices

- **Keep it brief:** 2-3 sentences max, focus on primary changes only
- **Lead with impact:** what changed and why it matters
- **Skip secondary details:** omit minor fixes or refactors unless core
- **Use prose:** short paragraph, not a list
- **For complex PRs:** focus on the main theme; mention secondary changes briefly
- **Format example:**
   ```markdown
   This PR adds full automation to the release preparation workflow. The prepare-release script now handles git operations and GitHub PR creation automatically. Supporting improvements fix URL parsing and error handling. Closes #123.
   ```

## What NOT to Include

- ❌ Individual commit hashes or a "## Commits" section
- ❌ Internal debugging notes or TODO comments
- ❌ References to private conversations
- ❌ Overly technical jargon without context
- ❌ Commit hashes in the title
- ❌ "WIP" or "Draft" in title (use --draft flag instead)
- ❌ Long multi-line descriptions as inline shell arguments (use file-based approach)

## Output Format

Provide:
1. A summary of commits to be included in the PR
2. The PR title and description that will be used
3. Confirmation that PR was created with link

Example output:
```
### Commits analyzed (2 commits):
Extracted key changes and rationale from the following commits

### PR Details:
**Title:** Refactor pipeline with Python detection and stage separation

**Description:**
This PR refactors the release detection logic from bash to Python for better testability and maintainability, splitting the pipeline into separate Build and Release stages. Supporting improvements fix URL parsing and error handling. Closes #123.

**Target branch:** master
**Draft:** No
**Issue:** 123

Would you like me to proceed with this PR, or would you like to modify the title/description?
```

## Error Handling

If PR creation fails:
- Check if PR already exists for this branch (`gh pr list --head <branch>`)
- Verify GitHub CLI authentication (`gh auth status`)
- Ensure branch has been pushed to remote
- Confirm repo and remote origin are correct and you have permissions

If issue linking fails:
- Verify the issue number exists and is in the same repo (or use repo-qualified `owner/repo#123`)
- User can edit the PR body manually after creation if needed

## GitHub CLI Reference

Key commands used:
```bash
# Check login status
gh auth status

# Create PR
gh pr create \
   --head <branch> \
   --base <target> \
   --title "Title" \
   --body "Description" \
   [--draft]

# List existing PRs for branch
gh pr list --head <branch>
```
