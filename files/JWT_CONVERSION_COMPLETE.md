# 🔐 HAMRO LABS ERP - JWT AUTHENTICATION CONVERSION
## Complete Implementation Guide

---

## 📋 OVERVIEW

**Goal**: Convert your session-based CSRF system to stateless JWT authentication

**Benefits**:
- ✅ No CSRF token mismatch
- ✅ Super Admin impersonation works flawlessly
- ✅ Multi-tab safe
- ✅ Mobile app ready
- ✅ Scalable SaaS architecture

---

## 🚀 STEP 1: INSTALLATION

### Install JWT Package

```bash
composer require tymon/jwt-auth
```

### Publish Config

```bash
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```

### Generate Secret Key

```bash
php artisan jwt:secret
```

This adds `JWT_SECRET` to your `.env` file.

---

## ⚙️ STEP 2: CONFIGURATION

### Update `config/auth.php`

```php
<?php

return [
    'defaults' => [
        'guard' => 'api',  // Change default to api
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'jwt',  // JWT driver
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    // ... rest of config
];
```

### Update `.env`

```env
# Authentication
AUTH_GUARD=api
JWT_SECRET=your_generated_secret_here
JWT_TTL=1440  # 24 hours (in minutes)
JWT_REFRESH_TTL=20160  # 2 weeks (in minutes)

# Session (keep for backward compatibility during transition)
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

---

## 🗄️ STEP 3: DATABASE MIGRATIONS

### Create Personal Access Tokens Table (Optional - for token management)

```bash
php artisan make:migration create_personal_access_tokens_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
```

### Add Impersonation Tracking

```bash
php artisan make:migration add_impersonation_to_users_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('impersonated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('impersonation_started_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['impersonated_by']);
            $table->dropColumn(['impersonated_by', 'impersonation_started_at']);
        });
    }
};
```

Run migrations:

```bash
php artisan migrate
```

---

## 👤 STEP 4: UPDATE USER MODEL

### `app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'institute_id',
        'impersonated_by',
        'impersonation_started_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'impersonation_started_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ========================
    // JWT METHODS (REQUIRED)
    // ========================

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'institute_id' => $this->institute_id,
            'name' => $this->name,
            'email' => $this->email,
            'impersonated_by' => $this->impersonated_by,
        ];
    }

    // ========================
    // RELATIONSHIPS
    // ========================

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function impersonator()
    {
        return $this->belongsTo(User::class, 'impersonated_by');
    }

    // ========================
    // HELPER METHODS
    // ========================

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isInstituteAdmin(): bool
    {
        return $this->role === 'institute_admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isImpersonating(): bool
    {
        return !is_null($this->impersonated_by);
    }

    public function canAccessModule(string $moduleSlug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->institute_id) {
            return false;
        }

        return \DB::table('institute_modules')
            ->join('modules', 'institute_modules.module_id', '=', 'modules.id')
            ->where('institute_modules.institute_id', $this->institute_id)
            ->where('modules.slug', $moduleSlug)
            ->where('institute_modules.is_enabled', true)
            ->exists();
    }
}
```

---

## 🎮 STEP 5: AUTH CONTROLLERS

### `app/Http/Controllers/API/AuthController.php`

```bash
php artisan make:controller API/AuthController
```

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password'
            ], 401);
        }

        $user = auth('api')->user();

        // Check if user's institute is active (if not super admin)
        if (!$user->isSuperAdmin() && $user->institute) {
            if ($user->institute->status !== 'active') {
                auth('api')->logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Your institute account is not active. Please contact support.'
                ], 403);
            }
        }

        return $this->respondWithToken($token);
    }

    /**
     * Register a new user (Optional - if you allow self-registration)
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'institute_id' => 'required|exists:institutes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'staff', // Default role
            'institute_id' => $request->institute_id,
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'institute_id' => $user->institute_id,
                'institute' => $user->institute,
                'is_impersonating' => $user->isImpersonating(),
                'impersonated_by' => $user->impersonated_by,
            ]
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken($token): JsonResponse
    {
        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'institute_id' => $user->institute_id,
                'is_impersonating' => $user->isImpersonating(),
            ]
        ]);
    }
}
```

---

## 👑 STEP 6: SUPER ADMIN CONTROLLER

### `app/Http/Controllers/API/SuperAdminController.php`

```bash
php artisan make:controller API/SuperAdminController
```

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('super_admin'); // We'll create this middleware
    }

    /**
     * List all institutes
     */
    public function institutes(): JsonResponse
    {
        $institutes = Institute::with(['users' => function($query) {
            $query->where('role', 'institute_admin');
        }])
        ->withCount('users')
        ->get();

        return response()->json([
            'success' => true,
            'institutes' => $institutes
        ]);
    }

    /**
     * Impersonate an institute admin
     */
    public function impersonate(Request $request, $userId): JsonResponse
    {
        $superAdmin = auth('api')->user();
        
        // Find target user
        $targetUser = User::findOrFail($userId);

        // Security: Only allow impersonating institute admins or staff
        if ($targetUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot impersonate another super admin'
            ], 403);
        }

        // Update target user to mark impersonation
        $targetUser->update([
            'impersonated_by' => $superAdmin->id,
            'impersonation_started_at' => now()
        ]);

        // Generate token for target user
        $token = auth('api')->login($targetUser);

        return response()->json([
            'success' => true,
            'message' => "Now impersonating {$targetUser->name}",
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'impersonated_user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'role' => $targetUser->role,
                'institute_id' => $targetUser->institute_id,
                'institute' => $targetUser->institute,
            ],
            'original_admin' => [
                'id' => $superAdmin->id,
                'name' => $superAdmin->name,
            ]
        ]);
    }

    /**
     * Stop impersonation and return to super admin
     */
    public function stopImpersonation(): JsonResponse
    {
        $currentUser = auth('api')->user();

        if (!$currentUser->isImpersonating()) {
            return response()->json([
                'success' => false,
                'message' => 'Not currently impersonating anyone'
            ], 400);
        }

        $superAdmin = User::findOrFail($currentUser->impersonated_by);

        // Clear impersonation
        $currentUser->update([
            'impersonated_by' => null,
            'impersonation_started_at' => null
        ]);

        // Logout current session
        auth('api')->logout();

        // Login as super admin
        $token = auth('api')->login($superAdmin);

        return response()->json([
            'success' => true,
            'message' => 'Impersonation stopped. Returned to super admin account.',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $superAdmin->id,
                'name' => $superAdmin->name,
                'email' => $superAdmin->email,
                'role' => $superAdmin->role,
            ]
        ]);
    }

    /**
     * Manage institute modules
     */
    public function assignModules(Request $request, $instituteId): JsonResponse
    {
        $request->validate([
            'module_ids' => 'required|array',
            'module_ids.*' => 'exists:modules,id'
        ]);

        $institute = Institute::findOrFail($instituteId);

        // Clear existing modules
        DB::table('institute_modules')
            ->where('institute_id', $instituteId)
            ->delete();

        // Assign new modules
        foreach ($request->module_ids as $moduleId) {
            DB::table('institute_modules')->insert([
                'institute_id' => $instituteId,
                'module_id' => $moduleId,
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Modules assigned successfully'
        ]);
    }

    /**
     * Get dashboard stats
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_institutes' => Institute::count(),
            'active_institutes' => Institute::where('status', 'active')->count(),
            'total_users' => User::count(),
            'total_students' => DB::table('students')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}
```

---

## 🛡️ STEP 7: MIDDLEWARE

### Create Super Admin Middleware

```bash
php artisan make:middleware SuperAdminMiddleware
```

### `app/Http/Middleware/SuperAdminMiddleware.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        if (!auth('api')->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Super Admin access required.'
            ], 403);
        }

        return $next($request);
    }
}
```

### Create Module Access Middleware

```bash
php artisan make:middleware CheckModuleAccess
```

### `app/Http/Middleware/CheckModuleAccess.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Super admins have access to everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check module access
        if (!$user->canAccessModule($moduleSlug)) {
            return response()->json([
                'success' => false,
                'message' => "Module '{$moduleSlug}' is not enabled for your institute."
            ], 403);
        }

        return $next($request);
    }
}
```

### Register Middleware in `app/Http/Kernel.php`

```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // ...

    /**
     * The application's route middleware.
     */
    protected $middlewareAliases = [
        // ... existing middleware
        'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
        'module' => \App\Http\Middleware\CheckModuleAccess::class,
    ];
}
```

---

## 🌐 STEP 8: API ROUTES

### `routes/api.php`

```php
<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SuperAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| 
| These routes are automatically prefixed with /api
| CSRF is automatically disabled for api routes
|
*/

// ========================
// PUBLIC ROUTES (No Auth)
// ========================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // Optional

// ========================
// AUTHENTICATED ROUTES
// ========================
Route::middleware('auth:api')->group(function () {
    
    // Auth endpoints
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);

    // ========================
    // SUPER ADMIN ROUTES
    // ========================
    Route::prefix('super')->middleware('super_admin')->group(function () {
        Route::get('/institutes', [SuperAdminController::class, 'institutes']);
        Route::get('/stats', [SuperAdminController::class, 'stats']);
        Route::post('/institutes/{id}/modules', [SuperAdminController::class, 'assignModules']);
        Route::post('/impersonate/{userId}', [SuperAdminController::class, 'impersonate']);
        Route::post('/stop-impersonation', [SuperAdminController::class, 'stopImpersonation']);
    });

    // ========================
    // INSTITUTE ROUTES (Module-based access)
    // ========================
    
    // Student Management Module
    Route::middleware('module:student_management')->prefix('students')->group(function () {
        Route::get('/', [\App\Http\Controllers\API\StudentController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\API\StudentController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\API\StudentController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\API\StudentController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\API\StudentController::class, 'destroy']);
    });

    // Attendance Module
    Route::middleware('module:attendance')->prefix('attendance')->group(function () {
        Route::get('/', [\App\Http\Controllers\API\AttendanceController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\API\AttendanceController::class, 'store']);
    });

    // Accounting Module
    Route::middleware('module:accounting')->prefix('accounting')->group(function () {
        Route::get('/transactions', [\App\Http\Controllers\API\AccountingController::class, 'index']);
        Route::post('/transactions', [\App\Http\Controllers\API\AccountingController::class, 'store']);
        Route::get('/summary', [\App\Http\Controllers\API\AccountingController::class, 'summary']);
    });

    // Add more module routes as needed...
});
```

---

## 🚫 STEP 9: DISABLE CSRF FOR API

### `app/Http/Middleware/VerifyCsrfToken.php`

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     */
    protected $except = [
        'api/*',  // Exclude all API routes
    ];
}
```

---

## READY TO IMPLEMENT ✅

Part 2 will cover:
- Frontend integration (Blade + Alpine.js)
- Token management
- Example controllers
- Testing guide

Continue to Part 2?
