---
description: Pick and start working on the next task from TODO.md
argument-hint: [task number or leave empty to see list]
---

# Pick Next Task

Pick up a task from TODO.md and start working on it.

## Behavior

1. Read TODO.md
2. Show all pending tasks in Backlog (numbered)
3. **Always suggest 2-3 tasks** before asking user to pick:
   - **High priority**: Critical bugs, security issues, production problems, or features that unblock other work
   - **Quick win**: Easy tasks that can be done in under 30 minutes to clean up the backlog faster
4. If argument provided, pick that task number
5. If no argument, ask user which task to work on
6. Move the selected task to "In Progress" section
7. Begin working on the task

## Task Suggestions

When suggesting tasks, consider this priority order:
1. **Critical**: Production bugs, security vulnerabilities, data loss risks
2. **High impact**: Features that improve user experience significantly, unblock other work
3. **Operational**: Monitoring, logging, error tracking (Sentry), CI/CD improvements
4. **Quick wins**: Small tasks that take <30 min - helps clean up backlog and build momentum
5. **Nice to have**: Polish, optimizations, future-proofing

Format suggestions like:
```
**Suggested tasks:**
- **High priority**: #X - [reason why it's important]
- **Quick win**: #Y - [why it's easy/fast]
```

## Instructions

1. Read TODO.md - if it doesn't exist, tell the user to use `/backlog` first
2. List all unchecked items from the Backlog section with numbers
3. If "$1" is a number, select that task
4. If no argument or invalid, ask user to pick one
5. Move the selected task from Backlog to In Progress
6. Update TODO.md
7. Start working on the task immediately

## When Task is Complete

After finishing the task:
1. Move it from "In Progress" to "Done"
2. Mark it as checked: `- [x] Task description`
3. Ask if the user wants to pick another task
