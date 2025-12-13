---
description: Add tasks to TODO.md backlog for later
argument-hint: [task description or leave empty to scan conversation]
---

# Add to Backlog

Add tasks to the TODO.md file for later work.

## Behavior

1. **If argument provided**: Add the specific task directly to TODO.md

2. **If no argument**: Review the recent conversation and identify actionable items that were discussed but not completed. Ask the user which ones to add.

## TODO.md Format

Maintain this structure in TODO.md:

```markdown
# TODO

## Backlog
- [ ] Task description here

## In Progress
- [ ] Currently working on this

## For Testing
- [ ] Implemented but needs user verification

## Done
- [x] Completed and tested
```

**Note:** When completing tasks, move them to "For Testing" first. The user tests completed work in bulk, so keep items there until they confirm testing is done.

## Instructions

1. Read TODO.md if it exists, or prepare to create it
2. If "$1" is provided, add it directly to the Backlog section
3. If no argument, scan recent conversation for:
   - Discussed features not yet implemented
   - Bugs mentioned but not fixed
   - Ideas that were deferred
   - "We should do X later" type comments
4. Present found items to user and confirm which to add
5. Append new items to the Backlog section
6. Show the updated backlog to the user
