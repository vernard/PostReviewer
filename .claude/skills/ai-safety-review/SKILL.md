---
name: ai-safety-review
description: Review permission changes and operations for AI safety risks. Use when modifying .claude/settings.local.json permissions or before allowing broad tool access. Prevents accidental exposure to dangerous operations.
allowed-tools: Read, Grep, Glob
---

# AI Safety Review

Review Claude Code permissions and operations for safety risks. Prevents giving AI agents excessive freedom that could damage repositories, production environments, or expose sensitive data.

## When to Trigger

- When modifying `.claude/settings.local.json` permissions
- When a user asks to "allow all" or "stop asking for permissions"
- Before enabling broad access patterns
- When reviewing what permissions are currently granted

## Risk Categories

### CRITICAL - Never Auto-Allow

These operations should ALWAYS require human confirmation:

| Operation | Risk | Recommendation |
|-----------|------|----------------|
| `git push --force` | Destroys commit history | Never allow `--force` patterns |
| `git reset --hard` | Loses uncommitted work | Require explicit confirmation |
| `rm -rf` | Irreversible deletion | Never allow recursive force delete |
| Pipeline/deploy triggers | Can break production | Always require manual approval |
| `curl` to arbitrary URLs | Data exfiltration risk | Restrict to specific domains |
| Database DROP/TRUNCATE | Data loss | Never auto-allow |
| Config file overwrites | Can break environments | Require review |

### HIGH RISK - Restrict Carefully

| Operation | Safe Pattern | Unsafe Pattern |
|-----------|--------------|----------------|
| git push | `Bash(git push origin:*)` | `Bash(git push:*)` (allows --force) |
| curl | `Bash(curl*example.com:*)` | `Bash(curl:*)` |
| docker | `Bash(docker compose:*)` | `Bash(docker:*)` (allows rm, rmi) |
| npm | `Bash(npm install:*)`, `Bash(npm run:*)` | `Bash(npm:*)` (allows publish) |
| artisan | `Bash(php artisan:*)` | Generally safe, but review migrate commands |

### MEDIUM RISK - Review Before Allowing

| Operation | Consideration |
|-----------|---------------|
| `git commit` | Safe but verify changes first |
| `git checkout` | Can lose uncommitted changes |
| `git rebase` | Rewrites history but recoverable via `git reflog` |
| `git cherry-pick` | Safe, recoverable via `git reflog` |
| `chown`/`chmod` | Can break permissions |
| File writes | Check what's being written |
| `php artisan migrate` | Can modify database schema |

### LOW RISK - Generally Safe

| Operation | Notes |
|-----------|-------|
| `git status`, `git log`, `git diff` | Read-only |
| `ls`, `cat`, `tree`, `find` | Read-only |
| `grep`, `awk` | Read-only |
| `docker compose up/down/logs` | Container management |
| `php artisan route:list` | Read-only |
| `php artisan config:show` | Read-only |

## Permission Pattern Guidelines

### Good Patterns

```json
{
  "permissions": {
    "allow": [
      "Bash(git status:*)",
      "Bash(git log:*)",
      "Bash(git diff:*)",
      "Bash(git add:*)",
      "Bash(git commit:*)",
      "Bash(git push origin main)",
      "Bash(git push origin develop)",
      "Bash(docker compose:*)",
      "Bash(php artisan route:*)",
      "Bash(php artisan config:*)",
      "Bash(npm install:*)",
      "Bash(npm run:*)"
    ]
  }
}
```

### Bad Patterns - Avoid These

```json
{
  "permissions": {
    "allow": [
      "Bash(git:*)",
      "Bash(curl:*)",
      "Bash(rm:*)",
      "Bash(docker:*)",
      "Bash(npm:*)",
      "Bash(*)"
    ]
  }
}
```

## Review Checklist

When reviewing permission changes:

1. **Scope check**: Is the pattern as narrow as possible?
   - Prefer specific commands over wildcards
   - Prefer specific branches/domains over "any"

2. **Reversibility check**: Can the operation be undone?
   - `git push` can be reverted (without --force)
   - `rm -rf` cannot
   - `DROP TABLE` cannot

3. **Blast radius check**: What's the worst case?
   - Local changes only? OK
   - Affects remote repo? Careful
   - Affects production? Require approval

4. **Data exposure check**: Could this leak sensitive data?
   - Unrestricted curl could send data anywhere
   - Unrestricted git could push to wrong repos

## Output Format

When reviewing permissions, report:

- **UNSAFE**: Must be changed - high risk of damage
- **WARNING**: Should reconsider - broader than needed
- **OK**: Acceptable risk level
- **RECOMMENDATION**: Suggested safer alternative
