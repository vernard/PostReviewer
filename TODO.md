# To Do
- [ ] Files uploaded from home page should be purged every 24 hours. Not sure how we'll protect this from abuse. 
- [ ] Users should be limited in some way to the file size they take on the server. We can't just give them unlimited disk space. Maybe it can be set per brand? This should be planned with how we charge customers. That needs different & deeper planning.

## In Progress
- [ ] Make it easy to create multiple posts.
    - Say, on "Posts" page, they should be able to drop in multiple files.
    - Have multiple posts lined up like a gallery
    - Show a table view above for each post, where they can copy paste multiple captions
- [ ] Investigate PHP-FPM child SIGKILL - Worker killed after 13s, possible memory issue

## Backlog

### Bugs (from production logs)

### Security/Ops
- [ ] Consider fail2ban or rate limiting for WordPress scanner IPs
- [ ] Run security-check on authentication & user input controllers (AuthController, MediaController, PostController, CommentController - potential XSS in comments)
- [ ] Run performance-check on database queries (PostController eager loading, MediaController media listing)
- [ ] Review AI safety settings - audit .claude/settings.local.json permissions

### Features (Incomplete)
- [ ] Implement video processing job dispatch (MediaController:112-113)
- [ ] Implement rate limiting for API endpoints
- [ ] Set up email notifications (invitations, approval workflow)

## Done
- [x] Fix `POST /undefined` bug - Frontend is posting to undefined URL (returns 405)
- [x] Website layout is broken on mobile (home page Facebook post mockup, navigation "get started" button, among other things.)
