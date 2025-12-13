---
name: security-check
description: Check new or modified code for security vulnerabilities. Use automatically after creating files, committing code, or working on features that handle user input, forms, file uploads, or authentication.
allowed-tools: Read, Grep, Glob, Bash
---

# Security Check

Automatically review new or modified code for security issues in Laravel applications.

## When to Trigger
- After creating new files
- After committing code
- When working on forms, file uploads, authentication, or user input
- When adding new routes or controllers

## Checks to Perform

### 1. CSRF Protection
For any code handling forms:
```php
// Forms should use @csrf directive
<form method="POST">
    @csrf
    ...
</form>

// API routes should use sanctum or passport middleware
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});
```

### 2. Input Validation
- **Always validate on server-side**:
  ```php
  // GOOD - Use Form Requests or validate()
  $validated = $request->validate([
      'email' => 'required|email',
      'name' => 'required|string|max:255',
  ]);

  // BAD - Using raw input
  $email = $request->input('email');
  ```

### 3. SQL Injection Prevention
- Use Eloquent or Query Builder, not raw SQL
- If raw SQL needed, use parameter binding:
  ```php
  // GOOD
  DB::select('SELECT * FROM users WHERE id = ?', [$id]);
  User::where('email', $email)->first();

  // BAD
  DB::select("SELECT * FROM users WHERE id = $id");
  ```

### 4. XSS Prevention
- Escape all user-generated content before output:
  ```blade
  {{-- GOOD - Escaped by default --}}
  {{ $user->name }}

  {{-- BAD - Unescaped output --}}
  {!! $user->bio !!}
  ```
- Only use `{!! !!}` when absolutely necessary and content is sanitized

### 5. File Upload Security
For file upload functionality:
```php
// Validate file type and size
$request->validate([
    'file' => 'required|file|mimes:jpg,png,pdf|max:10240',
]);

// Store with randomized name
$path = $request->file('file')->store('uploads', 'private');

// Never trust original filename for storage
```

- Whitelist allowed extensions (not blacklist)
- Validate MIME types server-side
- Store outside public directory when possible
- Use Laravel's storage system

### 6. Authentication & Authorization
```php
// Check authentication
if (!auth()->check()) {
    abort(401);
}

// Use policies for authorization
$this->authorize('update', $post);

// Or Gates
if (Gate::denies('edit-post', $post)) {
    abort(403);
}

// Middleware
Route::middleware(['auth', 'verified'])->group(function () {
    // Protected routes
});
```

### 7. Mass Assignment Protection
```php
// GOOD - Explicitly define fillable
class User extends Model {
    protected $fillable = ['name', 'email'];
}

// Or use guarded
protected $guarded = ['id', 'is_admin'];

// BAD - No protection
User::create($request->all());
```

### 8. Sensitive Data Exposure
- Never log sensitive data:
  ```php
  // BAD
  Log::info('User login', ['password' => $password]);

  // GOOD
  Log::info('User login', ['user_id' => $user->id]);
  ```
- Use `hidden` attribute for sensitive model fields:
  ```php
  protected $hidden = ['password', 'remember_token', 'api_key'];
  ```

### 9. Rate Limiting
For sensitive actions (login, password reset, API endpoints):
```php
// In RouteServiceProvider or route file
Route::middleware(['throttle:6,1'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Custom rate limiter
RateLimiter::for('uploads', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
});
```

### 10. Environment & Config Security
- Never commit `.env` file
- Use `config()` helper, not `env()` in code (except config files)
- Check `.gitignore` includes sensitive files:
  ```
  .env
  .env.backup
  storage/*.key
  ```

## Output Format
Report findings as:
- **CRITICAL**: Must fix before deployment
- **WARNING**: Should fix, potential vulnerability
- **INFO**: Best practice recommendation
