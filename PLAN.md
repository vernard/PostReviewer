# Post Reviewer - Project Plan

## Overview
A Laravel + Vue.js application that allows agencies to create mockups of Facebook and Instagram posts, preview them accurately, and manage approval workflows across multiple brands.

---

## Tech Stack

- **Backend:** Laravel 11
- **Frontend:** Vue.js 3 with Composition API
- **Build Tool:** Vite
- **CSS:** Tailwind CSS
- **Database:** MariaDB 10.6
- **Real-time:** Laravel Reverb (WebSockets)
- **Queue:** Database (can upgrade to Redis later)
- **Storage:** S3-compatible (local disk for dev)
- **Video Processing:** FFmpeg via laravel-ffmpeg
- **Containerization:** Docker (multi-stage Dockerfile, separate dev/prod compose files)

---

## Phase 1: Foundation & Docker Setup

### 1.1 Project Setup
- [ ] Initialize Laravel 11 project
- [ ] Create multi-stage Dockerfile (base → production → development)
  - Base: serversideup/php:8.4-fpm-apache with GD, intl, ffmpeg
  - Production: Node.js, composer install --no-dev, npm build
  - Development: XDebug support
- [ ] Create docker-compose.yml (development)
  - app service (target: development, volume mount)
  - reverb service (WebSocket server)
  - db service (MariaDB 10.6 with healthcheck)
  - phpmyadmin service
  - mailhog service
- [ ] Create docker-compose.prod.yml (production)
  - app service (target: production, named volume for storage)
  - reverb service with production env vars
  - db service
  - phpmyadmin service
- [ ] Configure Vue.js 3 + Vite integration
- [ ] Set up Tailwind CSS
- [ ] Create .env.example with all required variables
- [ ] Set up Git repository with .gitignore

### 1.2 Database Schema - Core Tables
- [ ] `agencies` - id, name, slug, logo, settings (json), timestamps
- [ ] `brands` - id, agency_id, name, slug, logo, color_scheme (json), timestamps
- [ ] `users` - id, agency_id, name, email, password, role (enum: admin, manager, creator, reviewer), avatar, timestamps
- [ ] `brand_user` - pivot table for user-brand access

### 1.3 Authentication & Authorization
- [ ] Install Laravel Breeze (API + SPA mode with Sanctum)
- [ ] Agency registration flow
- [ ] User invitation system (invite by email)
- [ ] Role-based permissions using Laravel policies
- [ ] Agency-scoped middleware (users only see their agency data)

### 1.4 Agency & Brand Management
- [ ] Agency settings page (name, logo)
- [ ] Brand CRUD operations
- [ ] User management within agency (invite, remove, change role)
- [ ] Brand assignment to users

---

## Phase 2: Media Management

### 2.1 Database Schema - Media
- [ ] `media` - id, brand_id, user_id, type (image/video), original_filename, disk, path, mime_type, size, width, height, duration (for video), metadata (json), thumbnails (json), status (processing/ready/failed), timestamps

### 2.2 Upload Infrastructure
- [ ] Configure S3 disk (with local fallback for dev)
- [ ] Create MediaUpload controller
- [ ] Implement chunked upload endpoint for large files
- [ ] File validation (types, max sizes per type)

### 2.3 Image Processing
- [ ] Install Intervention Image
- [ ] Generate thumbnails on upload (small, medium, large)
- [ ] Store original + optimized versions
- [ ] Extract EXIF data if needed

### 2.4 Video Processing
- [ ] Install laravel-ffmpeg
- [ ] Add ffmpeg to Dockerfile
- [ ] Set up queue worker for video jobs
- [ ] Generate video thumbnail (multiple frames for selection)
- [ ] Extract video metadata (duration, dimensions, codec)
- [ ] Transcode to web-friendly format (mp4 h264) if needed

### 2.5 Media Library UI (Vue)
- [ ] `<MediaUploader>` component with drag-drop
- [ ] Upload progress indicator (chunked upload support)
- [ ] `<MediaLibrary>` grid view with filtering
- [ ] `<MediaPreview>` modal for details
- [ ] Bulk selection and deletion

---

## Phase 3: Post & Mockup System

### 3.1 Database Schema - Posts
- [ ] `posts` - id, brand_id, created_by (user_id), title, caption, platforms (json array), status (draft/pending_approval/changes_requested/approved/archived), scheduled_for (nullable), metadata (json), timestamps
- [ ] `post_media` - id, post_id, media_id, position (order), platform_overrides (json for per-platform crops/settings)

### 3.2 Post CRUD
- [ ] Create post (select brand, add caption, choose platforms)
- [ ] Attach media to post (from media library or direct upload)
- [ ] Edit post (only in draft/changes_requested status)
- [ ] Delete post (soft delete, archive instead)
- [ ] Duplicate post (for variations)

### 3.3 Platform Configuration
- [ ] Define platform specs as config:
  ```
  - Facebook Feed: 1200x630 (landscape), 1080x1080 (square), 1080x1350 (portrait)
  - Facebook Story: 1080x1920
  - Instagram Feed: 1080x1080 (square), 1080x1350 (portrait), 1080x566 (landscape)
  - Instagram Story: 1080x1920
  - Instagram Reel: 1080x1920
  ```
- [ ] Aspect ratio validation per platform
- [ ] Auto-crop suggestions

### 3.4 Mockup Preview Components (Vue)
- [ ] `<MockupContainer>` - wrapper with platform selector
- [ ] `<FacebookFeedMockup>` - accurate FB post UI replica
  - Profile picture, name, timestamp
  - Caption with "See more" truncation
  - Media display (single, carousel, video)
  - Like/Comment/Share bar
- [ ] `<FacebookStoryMockup>` - FB story frame
- [ ] `<InstagramFeedMockup>` - IG post UI replica
  - Header (avatar, username, follow button)
  - Media (with carousel dots if multiple)
  - Action bar (like, comment, share, save)
  - Likes count, caption with truncation
- [ ] `<InstagramStoryMockup>` - IG story frame
- [ ] `<VideoPlayer>` - inline video playback in mockups
- [ ] Platform theme accuracy (fonts, colors, spacing)

### 3.5 Mockup Customization
- [ ] Custom profile name/avatar per brand (for preview)
- [ ] Simulated engagement numbers (likes, comments)
- [ ] Light/dark mode toggle for previews
- [ ] Device frame options (iPhone, Android, desktop)

---

## Phase 4: Approval Workflow

### 4.1 Database Schema - Approvals
- [ ] `approval_requests` - id, post_id, requested_by (user_id), status (pending/approved/rejected), due_date (nullable), timestamps
- [ ] `approval_responses` - id, approval_request_id, user_id, decision (approved/changes_requested), comment, timestamps
- [ ] `comments` - id, post_id, user_id, parent_id (for threads), body, attachment (nullable), timestamps

### 4.2 Approval Flow Logic
- [ ] Submit post for approval (changes status to pending_approval)
- [ ] Notify reviewers via WebSocket + email
- [ ] Reviewer can: Approve, Request Changes (with comment)
- [ ] If changes requested: post returns to creator, status updates
- [ ] If approved: post marked approved, notify creator
- [ ] Approval history log

### 4.3 Approval Settings (per brand)
- [ ] Required approvers count (1, 2, all)
- [ ] Auto-approve for certain roles
- [ ] Approval deadline reminders

### 4.4 Comments & Feedback
- [ ] Comment thread on posts
- [ ] @mentions with notifications
- [ ] Attachment support in comments (screenshots, reference images)
- [ ] Mark comments as resolved
- [ ] Real-time comment updates via Reverb

### 4.5 Approval UI (Vue)
- [ ] `<ApprovalBadge>` - status indicator
- [ ] `<ApprovalActions>` - approve/reject buttons with modal
- [ ] `<ApprovalHistory>` - timeline of decisions
- [ ] `<CommentThread>` - threaded discussion (real-time)
- [ ] `<PendingApprovals>` - dashboard widget/page

---

## Phase 5: Real-time & Notifications

### 5.1 Laravel Reverb Setup
- [ ] Install Laravel Reverb
- [ ] Configure broadcasting config
- [ ] Set up Laravel Echo on frontend
- [ ] Create broadcast events:
  - PostStatusChanged
  - NewComment
  - ApprovalDecisionMade
  - UserMentioned

### 5.2 Dashboard Views
- [ ] Agency admin dashboard (all brands overview)
- [ ] Brand dashboard (posts by status, recent activity)
- [ ] Creator dashboard (my posts, pending feedback)
- [ ] Reviewer dashboard (pending approvals, recent decisions)

### 5.3 Notification System
- [ ] Database notifications table (Laravel default)
- [ ] Notification types:
  - Post submitted for approval
  - Approval decision made
  - Comment added
  - Mentioned in comment
  - Approval deadline approaching
- [ ] Email notifications (queued)
- [ ] In-app notification center (real-time via Reverb)
- [ ] Notification preferences per user

### 5.4 Activity Feed
- [ ] Track key actions (post created, status changed, comment added)
- [ ] Activity log per post
- [ ] Brand-level activity feed

---

## Phase 6: Export & Sharing

### 6.1 Export Features
- [ ] Export mockup as PNG image (using html-to-image or similar)
- [ ] Export mockup as PDF (single or multi-platform)
- [ ] Include/exclude device frames
- [ ] Batch export (all platforms for a post)

### 6.2 Shareable Links
- [ ] Generate public preview link (no login required)
- [ ] Optional password protection
- [ ] Expiring links (24h, 7d, 30d, never)
- [ ] Track link views
- [ ] Allow external comments (optional, with email capture)

---

## Phase 7: Polish & Production

### 7.1 Performance
- [ ] Eager loading optimization
- [ ] API response caching
- [ ] Image lazy loading
- [ ] Infinite scroll for media library
- [ ] Database indexing review

### 7.2 Testing
- [ ] Unit tests for models and services
- [ ] Feature tests for API endpoints
- [ ] Vue component tests (Vitest)
- [ ] E2E tests for critical flows (Playwright)

### 7.3 Security
- [ ] Rate limiting on uploads and API
- [ ] CSRF protection
- [ ] XSS prevention (sanitize user input in captions)
- [ ] Signed URLs for private media
- [ ] Audit logging for sensitive actions

### 7.4 Deployment
- [ ] Production environment setup (Coolify compatible)
- [ ] CI/CD pipeline (GitHub Actions)
- [ ] Queue worker in Docker (or separate service)
- [ ] Backup strategy (database + media)
- [ ] Monitoring and error tracking (Sentry)

---

## Database ERD

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│  agencies   │───┬───│   brands    │───────│    posts    │
└─────────────┘   │   └─────────────┘       └─────────────┘
                  │          │                     │
                  │          │                     │
            ┌─────┴─────┐    │              ┌──────┴──────┐
            │   users   │────┘              │ post_media  │
            └───────────┘                   └──────┬──────┘
                  │                                │
                  │                         ┌──────┴──────┐
                  │                         │    media    │
                  │                         └─────────────┘
                  │
            ┌─────┴──────────┐
            │                │
     ┌──────┴──────┐  ┌──────┴──────┐
     │  comments   │  │  approvals  │
     └─────────────┘  └─────────────┘
```

---

## Docker Architecture

### Development (docker-compose.yml)
```
┌─────────────────────────────────────────────────────────┐
│                    Docker Network                        │
│  ┌─────────┐  ┌─────────┐  ┌────────┐  ┌────────────┐  │
│  │   app   │  │ reverb  │  │   db   │  │ phpmyadmin │  │
│  │  :80    │  │  :8085  │  │ :3306  │  │   :8080    │  │
│  └─────────┘  └─────────┘  └────────┘  └────────────┘  │
│                                                         │
│  ┌─────────┐                                           │
│  │ mailhog │  (SMTP :1025, Web :1080)                  │
│  └─────────┘                                           │
└─────────────────────────────────────────────────────────┘
```

### Production (docker-compose.prod.yml)
```
┌─────────────────────────────────────────────────────────┐
│                    Docker Network                        │
│  ┌─────────┐  ┌─────────┐  ┌────────┐  ┌────────────┐  │
│  │   app   │  │ reverb  │  │   db   │  │ phpmyadmin │  │
│  │ (proxy) │  │ (proxy) │  │        │  │  (proxy)   │  │
│  └─────────┘  └─────────┘  └────────┘  └────────────┘  │
│       │            │                                    │
│       └────────────┴──────── Coolify/Traefik ──────────│
└─────────────────────────────────────────────────────────┘
```

---

## Recommended Build Order

1. **Phase 1** → Foundation, Docker, Auth (get it running)
2. **Phase 2** → Media uploads (core functionality)
3. **Phase 3** → Posts & Mockups (the main product value)
4. **Phase 4** → Approval workflow (key differentiator)
5. **Phase 5** → Real-time & notifications (polish)
6. **Phase 6** → Export & sharing (nice-to-have)
7. **Phase 7** → Testing, security, deployment (ongoing)

---

## Key Files to Create

```
post-mockup-approver/
├── Dockerfile
├── docker-compose.yml
├── docker-compose.prod.yml
├── .env.example
├── app/
│   ├── Models/
│   │   ├── Agency.php
│   │   ├── Brand.php
│   │   ├── Post.php
│   │   ├── Media.php
│   │   ├── ApprovalRequest.php
│   │   └── Comment.php
│   ├── Http/Controllers/
│   ├── Policies/
│   ├── Events/
│   └── Jobs/
├── resources/
│   └── js/
│       ├── app.js
│       ├── components/
│       │   ├── mockups/
│       │   │   ├── FacebookFeedMockup.vue
│       │   │   ├── InstagramFeedMockup.vue
│       │   │   └── ...
│       │   ├── media/
│       │   │   ├── MediaUploader.vue
│       │   │   └── MediaLibrary.vue
│       │   └── approval/
│       │       ├── ApprovalActions.vue
│       │       └── CommentThread.vue
│       └── pages/
└── config/
    └── platforms.php (FB/IG specs)
```

---

## Next Steps

Ready to scaffold Phase 1? I'll create:
1. Dockerfile (multi-stage)
2. docker-compose.yml (dev)
3. docker-compose.prod.yml (prod)
4. Initialize Laravel project
5. Set up Vue.js + Tailwind
6. Create core migrations

Let me know when you want to begin!
