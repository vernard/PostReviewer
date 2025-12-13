# To Do

## In Progress

## Backlog
- [ ] Support Meta ad placement, but this will add more fields. We should consider how to handle this without making things complicated. Needs to be on home page too.
- [ ] Blog feature? Thinking if this should be separate (like HUGO).
- [ ] Add a payment gateway (Paddle, but may also use Xendit, PayPal)
- [ ] Make it easy to create multiple posts.
    - Say, on "Posts" page, they should be able to drop in multiple files.
    - Have multiple posts lined up like a gallery
    - Show a table view above for each post, where they can copy paste multiple captions

## For Testing
- [ ] E2E tests with Playwright - Tests for Auth, Brand, Post, Approval workflows. Run: `npm run test:e2e` (all), `npm run test:e2e:headed` (visible browser), `npm run test:e2e:ui` (Playwright UI). Some tests may need selectors adjusted as UI changes.
- [ ] Post creation UX improvements - Default platforms (FB + IG Feed), warnings moved below preview for visibility, JPEG export button
- [ ] Home page redesign - Workflow-focused hero ("Upload. Preview. Get Approved."), "Sound Familiar?" pain points, "How It Works" 3-step guide, 6-card Features grid, "Who It's For" with benefit tags, final CTA. All copy tightened to single sentences. Discord moved to CTA footer.
- [ ] Fix automated tests - Disabled rate limiting in tests, fixed blade @context escaping, fixed UserController parameter name mismatch (User $user vs $targetUser). All 82 tests pass. Run: `docker compose exec app php artisan test`
- [ ] Agency storage quotas - Agencies have storage_quota (default 1GB) and storage_used fields. Uploads blocked when over quota. Brands page shows storage indicator. Admin panel allows editing quotas per agency. Run: `docker compose exec app php artisan migrate` then `docker compose exec app php artisan storage:sync`
- [ ] Multi-user per brand - Already fully implemented: brand_user pivot table, hasBrandAccess() method, add/remove users via Brand detail page, posts/brands filtered by access. Managers see all agency brands, creators/reviewers see only assigned brands.
- [ ] Sync BatchCreate.vue with Create.vue - Added facebook_reel platform and fixed preview condition for reels
- [ ] SEO setup - Added robots.txt (blocks /api/ and /storage/), sitemap.xml, meta tags, Open Graph, Twitter Cards, and JSON-LD structured data. Submit sitemap to Google Search Console.
- [ ] Homepage usage tracking - Tracks file uploads and exports by IP/user. Shows signup prompt after 3 uses. Admin dashboard shows total/today/weekly stats, unique visitors, conversions, actions breakdown, and 7-day chart.
- [ ] Email-only review for external reviewers - Invite clients via email to approve posts without registering. "Invite Reviewers" button on pending posts, submit modal with reviewer emails, brand default reviewers saved. Run migration: `docker compose exec app php artisan migrate`
- [ ] Reels platform support - Added Facebook Reel and Instagram Reel to homepage demo and post creation with 9:16 mockups
- [ ] Add admin panel control for super admin - Dashboard with user/brand/post stats, approval rate, recent activity. Users page with power user sorting (by posts/brands). Agencies page with per-agency stats. Login-as-user feature with audit logging. Access at /admin (requires `UPDATE users SET is_super_admin = 1 WHERE email = 'your@email.com'`).
- [ ] Show FB & IG Story in homepage demo - Added Feed/Story format toggle and Instagram/Facebook Story mockups with 9:16 aspect ratio
- [ ] Email notifications (invitations, approval workflow) - Created InvitationMail, PostSubmittedForApprovalMail, PostApprovedMail, PostChangesRequestedMail
- [ ] WordPress scanner protection - BlockScannerPaths middleware blocks wp-admin, wp-login.php, etc.
- [ ] Performance-check on database queries - No N+1 issues, proper eager loading and indexes exist
- [ ] AI safety settings audit - Cleaned up stale permissions in .claude/settings.local.json
- [ ] Security audit on AuthController, MediaController, PostController, CommentController - added rate limiting to auth routes, validated comment fields
- [ ] Implement rate limiting for API endpoints (login: 5/min, register: 3/min, invitation: 5/min, public-approval: 10/min)

## Done
- [x] Brand logo on creation + user assignment - Logo upload and team member selection available in create brand modal
- [x] Dynamic background color for letterboxing - samples edge colors from images instead of black bars for a more natural look
- [x] Implement video processing job dispatch (ProcessVideo job extracts dimensions, duration, generates thumbnails via ffmpeg)
- [x] Bug tracking setup (Sentry) for both Laravel backend and Vue frontend - just add SENTRY_LARAVEL_DSN and VITE_SENTRY_DSN to .env
- [x] Use the brand files (in ./brand/) on the website - green primary colors, Plus Jakarta Sans font, favicon, logo images, homepage copy
- [x] Investigate PHP-FPM child SIGKILL - Worker killed after 13s (was timeout, not memory - fixed with PHP_FPM_PROCESS_CONTROL_TIMEOUT)
- [x] Fix `POST /undefined` bug - Frontend is posting to undefined URL (returns 405)
- [x] Website layout is broken on mobile (home page Facebook post mockup, navigation "get started" button, among other things.)
