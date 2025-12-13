---
description: Commit changes with review and smart message generation
---

!git status
!git diff --stat
!git diff

Review all unstaged changes to prepare a commit:

1. **Review each change**:
   - Look at each hunk/change in the diff, not just file names
   - A file can contain unrelated changes mixed in
   - Check for:
     - Debug code (var_dump, console.log, die(), dd())
     - Commented-out code that should be removed
     - Formatting-only changes mixed with functional changes
     - Unrelated refactoring mixed with feature work
   - Warn about files that shouldn't be committed (.env, credentials, etc.)

2. **Group related changes**:
   - If changes span multiple unrelated features, suggest splitting into multiple commits
   - Each commit should be atomic and focused on one thing

3. **Stage and commit**:
   - Stage all related changes with `git add`
   - Generate a concise commit message that:
     - Starts with a verb (Add, Fix, Update, Remove, Refactor)
     - Focuses on the "why" not just the "what"
     - Is 1-2 sentences max
   - Include the Claude Code attribution footer

4. **Repeat if needed**:
   - If there are remaining unrelated changes, offer to commit them separately
