# To Do
- [ ] Files uploaded from home page should be purged every 24 hours. Not sure how we'll protect this from abuse.
- [ ] Users should be limited in some way to the file size they take on the server. We can't just give them unlimited disk space. Maybe it can be set per brand? This should be planned with how we charge customers. That needs different & deeper planning.
- [ ] Show FB & IG Story in homepage sample.
- [ ] Support Meta ad placement, but this will add more fields. We should consider how to handle this without making things complicated. Needs to be on home page too.
- [ ] Improve home page to explain how the app works, who it's for, and basically compel them to try the app instead of just telling them what it is. 
- [ ] Need make multi-user per brand work.
- [ ] It should be possible to add brand logo when creating new brand. Also, it should be easy to assign people into it.
- [ ] The whole SEO setup of the site needs a review as well. Not sure what needs to be done here. Setup robots, create sitemap, register in search console, etc.
- [ ] Blog feature? Thinking if this should be separate (like HUGO).
- [ ] When an image doesn't fit the aspect ratio, it shows a black bar. But in reality, Meta is using some sort of magic to identify what color is best for that. Maybe check the color at the edges thn average that?

## In Progress
- [ ] Make it easy to create multiple posts.
    - Say, on "Posts" page, they should be able to drop in multiple files.
    - Have multiple posts lined up like a gallery
    - Show a table view above for each post, where they can copy paste multiple captions

## Backlog

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
- [x] Bug tracking setup (Sentry) for both Laravel backend and Vue frontend - just add SENTRY_LARAVEL_DSN and VITE_SENTRY_DSN to .env
- [x] Use the brand files (in ./brand/) on the website - green primary colors, Plus Jakarta Sans font, favicon, logo images, homepage copy
- [x] Investigate PHP-FPM child SIGKILL - Worker killed after 13s (was timeout, not memory - fixed with PHP_FPM_PROCESS_CONTROL_TIMEOUT)
- [x] Fix `POST /undefined` bug - Frontend is posting to undefined URL (returns 405)
- [x] Website layout is broken on mobile (home page Facebook post mockup, navigation "get started" button, among other things.)
