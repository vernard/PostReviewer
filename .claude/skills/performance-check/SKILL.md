---
name: performance-check
description: Analyze code for performance issues including memory usage, database optimization, caching opportunities, and response time. Use automatically when reviewing new features, database queries, or code that processes large data sets.
allowed-tools: Read, Grep, Glob, Bash
---

# Performance Check

Automatically review code for performance issues and optimization opportunities in Laravel applications.

## When to Trigger
- After implementing new features
- When adding database queries or models
- When processing collections or large data sets
- When adding API calls or external service integrations
- During code review

## Checks to Perform

### 1. N+1 Query Problem
```php
// BAD - N+1 queries
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name; // Query per post
}

// GOOD - Eager loading
$posts = Post::with('author')->get();
foreach ($posts as $post) {
    echo $post->author->name; // No additional queries
}

// GOOD - Nested eager loading
$posts = Post::with(['author', 'comments.user'])->get();
```

### 2. Query Optimization
```php
// BAD - Loading unnecessary columns
$users = User::all();

// GOOD - Select only needed columns
$users = User::select(['id', 'name', 'email'])->get();

// BAD - Multiple queries for counts
$activeCount = User::where('active', true)->count();
$inactiveCount = User::where('active', false)->count();

// GOOD - Single query with grouping
$counts = User::selectRaw('active, COUNT(*) as count')
    ->groupBy('active')
    ->pluck('count', 'active');
```

### 3. Chunking Large Data Sets
```php
// BAD - Loading everything into memory
$users = User::all();
foreach ($users as $user) {
    // Process user
}

// GOOD - Process in chunks
User::chunk(1000, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});

// GOOD - For queued jobs, use cursor for memory efficiency
foreach (User::cursor() as $user) {
    // Process user (one at a time, low memory)
}
```

### 4. Queue Long-Running Tasks
Consider moving to queue if:
- Operation takes > 5 seconds
- Operation doesn't need immediate response
- Operation involves external APIs
- User can be notified later

```php
// Instead of synchronous processing
ProcessMockupApproval::dispatch($mockup);

// Then notify user via email/notification when done
```

### 5. Caching
```php
// Cache expensive queries
$brands = Cache::remember('brands.all', 3600, function () {
    return Brand::with('settings')->get();
});

// Cache with tags for easy invalidation
$posts = Cache::tags(['posts', 'brand.'.$brandId])
    ->remember("posts.brand.{$brandId}", 3600, function () use ($brandId) {
        return Post::where('brand_id', $brandId)->get();
    });

// Invalidate related caches
Cache::tags(['posts'])->flush();
```

### 6. Database Indexes
Check if queries need indexes:
```php
// If you filter/sort by these fields often, add indexes
Schema::table('posts', function (Blueprint $table) {
    $table->index('brand_id');
    $table->index('status');
    $table->index(['brand_id', 'status']); // Composite for combined queries
    $table->index('created_at');
});
```

Signs you need an index:
- Fields in `where()` clauses
- Fields in `orderBy()` clauses
- Foreign key fields
- Fields used in `JOIN` conditions

### 7. Lazy Loading Collections
```php
// BAD - Loads all into memory then filters
$activeUsers = User::all()->filter(fn($u) => $u->isActive());

// GOOD - Filter at database level
$activeUsers = User::where('active', true)->get();

// BAD - Multiple iterations
$users = User::all();
$names = $users->pluck('name');
$emails = $users->pluck('email');

// GOOD - Single iteration
$users = User::select(['name', 'email'])->get();
```

### 8. API & External Service Calls
```php
// Add timeouts to prevent hanging
Http::timeout(10)->get('https://api.example.com/data');

// Cache API responses when appropriate
$data = Cache::remember('external.api.data', 300, function () {
    return Http::get('https://api.example.com/data')->json();
});

// Use queues for non-critical external calls
SendWebhookNotification::dispatch($payload)->onQueue('webhooks');
```

### 9. File & Image Processing
```php
// Process images asynchronously
ProcessUploadedImage::dispatch($imagePath);

// Use streaming for large files
return response()->streamDownload(function () use ($path) {
    echo Storage::get($path);
}, 'filename.pdf');
```

### 10. Config & Route Caching (Production)
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache
```

## Output Format
Report findings as:
- **HIGH IMPACT**: Significant performance improvement possible
- **MEDIUM IMPACT**: Noticeable improvement
- **LOW IMPACT**: Minor optimization, nice to have

Include estimated impact and implementation complexity.
