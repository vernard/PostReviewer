# To Do
- [ ] Files uploaded from home page should be purged every 24 hours. Not sure how we'll protect this from abuse.
- [ ] Users should be limited in some way to the file size they take on the server. We can't just give them unlimited disk space. Maybe it can be set per brand? This should be planned with how we charge customers. That needs different & deeper planning.
- [ ] Support Meta ad placement, but this will add more fields. We should consider how to handle this without making things complicated. Needs to be on home page too.
- [ ] Improve home page to explain how the app works, who it's for, and basically compel them to try the app instead of just telling them what it is. 
- [ ] Need make multi-user per brand work.
- [ ] It should be possible to add brand logo when creating new brand. Also, it should be easy to assign people into it.
- [ ] The whole SEO setup of the site needs a review as well. Not sure what needs to be done here. Setup robots, create sitemap, register in search console, etc.
- [ ] Blog feature? Thinking if this should be separate (like HUGO).
- [ ] Track the usages on home page mockups. Group it in some way, maybe IP address? I just need to know how many people are using it, and how many times they do. If they're registered, track it to their account. Just realized they can't access home page, but they should be able to. Along with other content pages on site. 
- [ ] Add an admin panel control where only I (Vernard, super admin) can access. This should tell me about the overall health of the app. Tell me useful stats like # of users, # of approved, etc. (tells me if I'm actually fulfilling the purpose of the app)
    * Should also allow me to login as certain users (for testing purposes).
    * Should tell me who the power users are. How many brands they have, how many posts they've made, etc. I wanna keep track the usage.
- [ ] It should be possible to add a "strict" review-only user. They shouldn't have to register, but maybe an email verification is required. Magic link sent to their email is enough? We want it easy to approve.


## In Progress

## Paused
- [ ] Make it easy to create multiple posts.
    - Say, on "Posts" page, they should be able to drop in multiple files.
    - Have multiple posts lined up like a gallery
    - Show a table view above for each post, where they can copy paste multiple captions

## Backlog

## Done
- [x] Dynamic background color for letterboxing - samples edge colors from images instead of black bars for a more natural look
- [x] Show FB & IG Story in homepage demo - Added Feed/Story format toggle and Instagram/Facebook Story mockups with 9:16 aspect ratio
- [x] Email notifications (invitations, approval workflow) - Created InvitationMail, PostSubmittedForApprovalMail, PostApprovedMail, PostChangesRequestedMail
- [x] WordPress scanner protection - BlockScannerPaths middleware blocks wp-admin, wp-login.php, etc.
- [x] Performance-check on database queries - No N+1 issues, proper eager loading and indexes exist
- [x] AI safety settings audit - Cleaned up stale permissions in .claude/settings.local.json
- [x] Security audit on AuthController, MediaController, PostController, CommentController - added rate limiting to auth routes, validated comment fields
- [x] Implement rate limiting for API endpoints (login: 5/min, register: 3/min, invitation: 5/min, public-approval: 10/min)
- [x] Implement video processing job dispatch (ProcessVideo job extracts dimensions, duration, generates thumbnails via ffmpeg)
- [x] Bug tracking setup (Sentry) for both Laravel backend and Vue frontend - just add SENTRY_LARAVEL_DSN and VITE_SENTRY_DSN to .env
- [x] Use the brand files (in ./brand/) on the website - green primary colors, Plus Jakarta Sans font, favicon, logo images, homepage copy
- [x] Investigate PHP-FPM child SIGKILL - Worker killed after 13s (was timeout, not memory - fixed with PHP_FPM_PROCESS_CONTROL_TIMEOUT)
- [x] Fix `POST /undefined` bug - Frontend is posting to undefined URL (returns 405)
- [x] Website layout is broken on mobile (home page Facebook post mockup, navigation "get started" button, among other things.)
