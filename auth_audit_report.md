# 🔐 Hamro Labs ERP — Complete Authentication Audit Report

> **Audited on:** 2026-03-25  
> **Stack:** Custom PHP (Apache) + tymon/jwt-auth (partially integrated)  
> **Severity scale:** 🔴 Critical · 🟠 High · 🟡 Medium · 🟢 Low

---

## 🗺️ Architecture Reality vs. Intention

The system was **intended** to migrate from session-based auth to JWT, but the migration is **incomplete and contradictory**. Two separate JWT implementations exist simultaneously, creating a broken hybrid.

```
INTENDED FLOW:
  Login → tymon/jwt-auth → Bearer token in cookie → JwtAuthMiddleware → auth('api')->user()

ACTUAL FLOW (broken hybrid):
  Login A → app/Http/Controllers/AuthController.php (custom PDO + custom jwtEncode)
         → sets $_SESSION + cookie('token') with custom JWT
  Login B → app/Http/Controllers/API/AuthController.php (tymon/jwt-auth)
         → sets cookie 'token' with tymon JWT

  Protected Page → reads $_COOKIE['token']
                 → isLoggedIn() decodes it as base64 (NO signature check)
                 → RoleMiddleware reads $_SESSION['userData'] (not JWT)
                 → JwtAuthMiddleware reads via auth('api') (tymon)
                 → CONFLICT: cookie was set by custom encoder, not tymon
```

---

## 🚨 Problems Found (12 Critical Issues)

---

### BUG 1 🔴 — Dual JWT Implementation (Root Cause of All Login Failures)

**File:** [app/Http/Controllers/AuthController.php](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php) vs [app/Http/Controllers/API/AuthController.php](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/API/AuthController.php)

**Problem:** Two completely different [AuthController](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php#15-621) classes exist:
- `App\Http\Controllers\AuthController` — Custom PHP with hand-rolled [jwtEncode()](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php#410-421) using raw `base64_encode` (NOT URL-safe base64)
- `App\Http\Controllers\API\AuthController` — Proper Laravel controller using [auth('api')->attempt()](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/RoleMiddleware.php#243-258) via tymon/jwt-auth

**When the web login form POSTs**, it hits the custom controller because [routes/web.php](file:///c:/Apache24/htdocs/erp/routes/web.php) likely routes there. The resulting `cookie('token')` is a **non-standard JWT** created by the custom encoder.

**When [JwtAuthMiddleware.php](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/JwtAuthMiddleware.php) runs**, it calls [auth('api')->setToken($token)->user()](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/RoleMiddleware.php#243-258) which uses **tymon's decoder** — which **cannot verify** the custom-encoded token because the base64 encoding format differs and signature is wrong.

**Result:** Login succeeds → cookie set → next request → middleware fails → 401.

---

### BUG 2 🔴 — Unsafe JWT Signature Verification (Security Vulnerability)

**File:** [app/Http/Controllers/AuthController.php](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php) lines 413–420, [config/config.php](file:///c:/Apache24/htdocs/erp/config/config.php) lines 344–351

**Current broken code (config.php):**
```php
// isLoggedIn() — NO SIGNATURE VERIFICATION AT ALL
$payloadStr = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
$payload = json_decode($payloadStr, true);
if ($payload && isset($payload['exp']) && $payload['exp'] > time() && isset($payload['user_id'])) {
    return true;  // ← TOKEN NEVER VERIFIED! Anyone can forge a JWT!
}
```

**Current broken encoder (AuthController.php):**
```php
private function jwtEncode($payload) {
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payloadEncoded = base64_encode(json_encode($payload));  // ← NOT URL-safe!
    $signature = base64_encode(hash_hmac('sha256', "$header.$payloadEncoded", $this->jwtSecret, true));
    return "$header.$payloadEncoded.$signature";  // ← Signature uses wrong base64 encoding
}
```

Standard JWT requires `base64url` (replaces `+` with `-`, `/` with `_`, strips `=`). The custom encoder uses plain `base64_encode`. The decoder in [isLoggedIn()](file:///c:/Apache24/htdocs/erp/config/config.php#328-359) normalizes the received token (line 346: `str_replace(['-', '_'], ['+', '/']...)`), but the encoder never produced URL-safe base64 in the first place. The signature comparison will **always fail** in [jwtDecode()](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php#422-441) line 435.

---

### BUG 3 🔴 — [IdentifyTenant](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/IdentifyTenant.php#13-164) Called Inside [JwtAuthMiddleware](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/JwtAuthMiddleware.php#11-90) (Infinite Loop Risk)

**File:** [app/Http/Middleware/JwtAuthMiddleware.php](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/JwtAuthMiddleware.php) lines 43–45

```php
$identifyTenant = new \App\Http\Middleware\IdentifyTenant();
$resolvedTenant = $identifyTenant->handle(); // ← calls auth('api')->check() INSIDE auth middleware
```

`IdentifyTenant::handle()` (line 52–64) calls [auth('api')->check()](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/RoleMiddleware.php#243-258). This is being called **while still inside** [JwtAuthMiddleware](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/JwtAuthMiddleware.php#11-90) which itself is the `auth:api` middleware handler. On some Laravel/PHP versions this causes a circular call or double-processing of the JWT, leading to `TokenInvalidException` on the second decode.

---

### BUG 4 🔴 — [RoleMiddleware](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/RoleMiddleware.php#9-278) Reads `$_SESSION`, Not JWT

**File:** [app/Http/Middleware/RoleMiddleware.php](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/RoleMiddleware.php) line 144

```php
private static function getCurrentUser() {
    return $_SESSION['userData'] ?? null;  // ← Depends on session, not JWT
}
```

If `$_SESSION['userData']` is not populated (e.g., stateless API request without web context, session expired, or session not started), **every role check silently fails** and the user gets a 403 even with a perfectly valid JWT.

---

### BUG 5 🔴 — Feature Gating Entirely Session-Dependent

**File:** [config/config.php](file:///c:/Apache24/htdocs/erp/config/config.php) lines 488–497 (hasFeature), lines 422–450 (loadFeatures)

```php
// hasFeature() — reads from $_SESSION
$enabled = $_SESSION['enabled_features'] ?? [];
return in_array($searchKey, $enabled);
```

```php
// loadFeatures() — writes to $_SESSION
$_SESSION['enabled_features'] = $features ?: [];
$_SESSION['loaded_tenant_id'] = $tenantId;
```

For stateless JWT API requests (no session), `$_SESSION['enabled_features']` is always empty → [hasFeature()](file:///c:/Apache24/htdocs/erp/config/config.php#457-499) always returns `false` → ALL module-gated routes return 403.

[JwtAuthMiddleware](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/JwtAuthMiddleware.php#11-90) calls [loadFeatures()](file:///c:/Apache24/htdocs/erp/config/config.php#423-451) only when the session variable `loaded_tenant_id` mismatches — but on a stateless request, **there is no session**, so it re-loads every single request (DB hit every API call).

---

### BUG 6 🟠 — Two Different [canAccessModule](file:///c:/Apache24/htdocs/erp/app/Models/User.php#163-179) Implementations (Table Mismatch)

**File 1:** [app/Models/User.php](file:///c:/Apache24/htdocs/erp/app/Models/User.php) line 172 — queries `institute_modules` + `modules` tables  
**File 2:** [config/config.php](file:///c:/Apache24/htdocs/erp/config/config.php) line 431 — queries `system_features` + `institute_feature_access` tables

[CheckModuleAccess.php](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/CheckModuleAccess.php) uses `$user->canAccessModule()` → queries `institute_modules/modules`.  
Legacy [hasFeature()](file:///c:/Apache24/htdocs/erp/config/config.php#457-499) queries `system_features/institute_feature_access`.

If one table has a module enabled but the other doesn't (or the tables don't exist), access is inconsistent between the Laravel API layer and the legacy PHP pages.

---

### BUG 7 🟠 — [auth()->attempt()](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/RoleMiddleware.php#243-258) Cannot Verify Password (Wrong Column Name)

**File:** [app/Models/User.php](file:///c:/Apache24/htdocs/erp/app/Models/User.php) lines 44–48

```php
public function getAuthPasswordName() {
    return 'password_hash';  // ← Correct method for Laravel 10+
}
```

This is **correct** for Laravel 10+. However, Laravel's [auth()->attempt()](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/RoleMiddleware.php#243-258) calls `Hash::check($password, $user->getAuthPassword())`. The [getAuthPassword()](file:///c:/Apache24/htdocs/erp/app/Models/User.php#42-49) method returns `$this->password_hash`. This only works if `password_hash` column stores bcrypt hashes.

**The custom [AuthController](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php#15-621) uses `password_hash()` with `PASSWORD_ARGON2ID`** (auth.php line 212) while **User model uses `PASSWORD_BCRYPT` cost 12** (User.php line 78). If users were created via the old auth.php path, `laravel auth()->attempt()` will fail because `Hash::check()` only handles bcrypt/argon by default — but the custom controller stored argon2id. Mixed hash algorithms cause intermittent auth failures.

---

### BUG 8 🟠 — Token Cookie SameSite: `Strict` Breaks Subdomain Navigation

**File:** [app/Http/Controllers/AuthController.php](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php) line 171

```php
setcookie('token', $accessToken, [
    'samesite' => 'Strict'   // ← Cookies NOT sent on cross-origin navigations
]);
```

**API AuthController** (API/AuthController.php line 177) uses `'Lax'`. These are inconsistent. `Strict` means the cookie is **not sent when navigating from an external link or redirect**. If your loading screen redirects cross-origin (even same-domain subdomains), the token cookie may not be sent with the first request.

---

### BUG 9 🟡 — [verifyCSRFToken()](file:///c:/Apache24/htdocs/erp/config/config.php#291-296) Always Returns `true`

**File:** [config/config.php](file:///c:/Apache24/htdocs/erp/config/config.php) lines 290–296

```php
function verifyCSRFToken($token) {
    // JWT is its own security, CSRF is disabled globally in this migration
    return true;  // ← Security: CSRF validation completely disabled
}
```

While JWT on a cookie with `SameSite=Lax` provides CSRF protection for modern browsers, completely bypassing CSRF verification for ALL routes (including session-based routes on the legacy path) is dangerous. Any session-based form submission is now CSRF-unprotected.

---

### BUG 10 🟡 — [checkRememberMe()](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/auth.php#172-209) Defined Twice (Double Execution)

**File:** [config/config.php](file:///c:/Apache24/htdocs/erp/config/config.php) line 731 and [app/Http/Middleware/auth.php](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/auth.php) line 415

Both files call [checkRememberMe()](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/auth.php#172-209) at file-load time. Since [auth.php](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/auth.php) requires [config.php](file:///c:/Apache24/htdocs/erp/config/config.php) first (line 7), [config.php](file:///c:/Apache24/htdocs/erp/config/config.php) executes its [checkRememberMe()](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/auth.php#172-209) call. Then [auth.php](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/auth.php) calls it again. This means **two DB queries per request** to check the remember_me token.

---

### BUG 11 🟡 — [sanitizeUser()](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php#586-596) Includes Wrong Field ([name](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/auth.php#277-289) instead of `full_name`)

**File:** [app/Http/Controllers/AuthController.php](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php) lines 586–595

```php
private function sanitizeUser($user) {
    return [
        'id'        => $user['id'],
        'name'      => $user['name'],   // ← DB column is 'full_name', not 'name'
        ...
    ];
}
```

The DB schema uses `full_name` (referenced in auth.php line 41 and config.php line 695), but PDO fetch returns column names as-is. `$user['name']` will be `null`, causing empty user names in the frontend.

---

### BUG 12 🟢 — JWT_TTL in .env Not Read by Custom AuthController

**File:** [.env](file:///c:/Apache24/htdocs/erp/.env) line 42: `JWT_TTL=1440`  
**File:** [app/Http/Controllers/AuthController.php](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php) line 19: `$accessTokenExpiry = 28800;` (hardcoded)

The tymon/jwt-auth package reads `JWT_TTL` from .env. The custom AuthController ignores it entirely. Tokens created by the custom controller expire in 8 hours; tokens created by the API controller expire in 24 hours (1440 minutes). A user might get a 8h token or 24h token depending on which login path was hit.

---

## 🏗️ Architecture Diagram (Current vs. Correct)

```
❌ CURRENT (BROKEN) ARCHITECTURE:
─────────────────────────────────────────────────────────────
Browser
  │
  ├─ POST /auth/login (web form)
  │      └─ app/Http/Controllers/AuthController.php
  │             └─ Custom jwtEncode() [non-standard base64]
  │             └─ Sets $_SESSION + cookie('token') [custom JWT]
  │
  ├─ POST /api/login (fetch/axios)
  │      └─ app/Http/Controllers/API/AuthController.php
  │             └─ auth('api')->attempt() [tymon JWT]
  │             └─ Sets cookie('token') [standard JWT]
  │
  ├─ ANY protected page request
  │      ├─ JwtAuthMiddleware [uses tymon] ← fails on custom tokens
  │      ├─ RoleMiddleware [uses $_SESSION] ← fails on stateless requests  
  │      └─ hasFeature() [uses $_SESSION] ← fails on stateless requests
  │
  └─ Result: INCONSISTENT. Login from web → protected API fails.

✅ CORRECT TARGET ARCHITECTURE:
─────────────────────────────────────────────────────────────
Browser
  │
  ├─ POST /api/login (ALL login paths)
  │      └─ app/Http/Controllers/API/AuthController.php ONLY
  │             └─ auth('api')->attempt() [tymon JWT]
  │             └─ Returns { access_token, refresh_token }
  │             └─ Sets HttpOnly cookie('token', SameSite=Lax)
  │
  ├─ ANY protected page/API request
  │      ├─ JwtAuthMiddleware [tymon only]
  │      │      └─ Extracts from Bearer header OR cookie
  │      │      └─ Validates via auth('api')->setToken()->user()
  │      │      └─ Injects $request->auth_user for downstream
  │      │
  │      ├─ Tenant validation [from JWT claim, not subdomain-first]
  │      │
  │      └─ hasFeature() [queries DB, not $_SESSION]
  │
  └─ Result: CONSISTENT. One login path. One token format. One decoder.
```

---

## 🔧 Exact Code Fixes

---

### FIX 1 — Delete the Custom AuthController, Use API One Exclusively

Delete or rename [app/Http/Controllers/AuthController.php](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php) to `AuthController.php.LEGACY`.

Make sure [routes/web.php](file:///c:/Apache24/htdocs/erp/routes/web.php) login route points to the API controller:
```php
// routes/web.php — change login route to use API controller
Route::post('/auth/login', [\App\Http\Controllers\API\AuthController::class, 'login']);
```

---

### FIX 2 — Fix [isLoggedIn()](file:///c:/Apache24/htdocs/erp/config/config.php#328-359) to Properly Verify JWT Signature

**File:** [config/config.php](file:///c:/Apache24/htdocs/erp/config/config.php) — replace the entire [isLoggedIn()](file:///c:/Apache24/htdocs/erp/config/config.php#328-359) function:

```php
if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool {
        $token = null;

        // Priority 1: Authorization header (API requests)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $m)) {
                $token = $m[1];
            }
        }

        // Priority 2: Cookie (web requests)
        if (!$token && isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];
        }

        if (!$token) return false;

        return (bool) verifyJwtToken($token);
    }
}

/**
 * Verify and decode a JWT token with FULL signature validation.
 * This replaces the insecure base64-only decode.
 */
if (!function_exists('verifyJwtToken')) {
    function verifyJwtToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$headerB64, $payloadB64, $sigB64] = $parts;

        $secret = defined('JWT_SECRET') ? JWT_SECRET : null;
        if (!$secret || $secret === 'PLEASE_SET_JWT_SECRET_IN_ENV') {
            error_log('[AUTH] JWT_SECRET is not set!');
            return null;
        }

        // Reconstruct expected signature using URL-safe base64
        $expectedSig = rtrim(strtr(base64_encode(
            hash_hmac('sha256', "$headerB64.$payloadB64", $secret, true)
        ), '+/', '-_'), '=');

        // Use hash_equals to prevent timing attacks
        if (!hash_equals($expectedSig, $sigB64)) {
            return null; // Invalid signature
        }

        $payload = json_decode(
            base64_decode(str_pad(strtr($payloadB64, '-_', '+/'), strlen($payloadB64) % 4 === 0 ? 0 : 4 - strlen($payloadB64) % 4, '=')),
            true
        );

        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null; // Expired or malformed
        }

        return $payload;
    }
}
```

---

### FIX 3 — Fix [getCurrentUser()](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/RoleMiddleware.php#140-146) to Use Verified JWT

```php
if (!function_exists('getCurrentUser')) {
    function getCurrentUser(): ?array {
        $token = null;

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $m)) {
                $token = $m[1];
            }
        }
        if (!$token && isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];
        }

        if (!$token) return null;

        $payload = verifyJwtToken($token); // Uses secure verification from FIX 2
        if (!$payload) return null;

        return [
            'id'        => $payload['sub'] ?? $payload['user_id'] ?? null,
            'tenant_id' => $payload['tenant_id'] ?? null,
            'role'      => $payload['role'] ?? null,
            'name'      => $payload['name'] ?? null,
            'email'     => $payload['email'] ?? null,
            'is_jwt'    => true,
        ];
    }
}
```

---

### FIX 4 — Fix [JwtAuthMiddleware](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/JwtAuthMiddleware.php#11-90) (Remove IdentifyTenant Call Inside Auth Middleware)

**File:** [app/Http/Middleware/JwtAuthMiddleware.php](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/JwtAuthMiddleware.php) — full replacement:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Extract from Bearer header OR cookie (DO NOT call IdentifyTenant here)
            $token = $request->bearerToken();
            if (!$token && $request->hasCookie('token')) {
                $token = $request->cookie('token');
                // Inject into Authorization header so tymon can find it
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token not found. Please login.'
                ], 401);
            }

            // Let tymon parse and validate the token
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found.'], 401);
            }

            // Tenant isolation check (derive from JWT payload, not session)
            $payload = JWTAuth::parseToken()->getPayload();
            $tokenTenantId = $payload->get('tenant_id');

            // Only enforce tenant check for non-super-admins
            if (!$user->isSuperAdmin()) {
                if (empty($tokenTenantId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token missing tenant context.'
                    ], 403);
                }
                // Bind tenant_id to request for downstream controllers
                $request->merge(['_tenant_id' => $tokenTenantId]);
            }

            // Bind user to request for downstream use
            $request->merge(['_auth_user' => $user]);

        } catch (TokenExpiredException $e) {
            return response()->json(['success' => false, 'message' => 'Token has expired.'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['success' => false, 'message' => 'Token is invalid.'], 401);
        } catch (JWTException $e) {
            return response()->json(['success' => false, 'message' => 'Token error: ' . $e->getMessage()], 401);
        }

        return $next($request);
    }
}
```

---

### FIX 5 — Fix [hasFeature()](file:///c:/Apache24/htdocs/erp/config/config.php#457-499) to NOT Rely on `$_SESSION` for Core Logic

**File:** [config/config.php](file:///c:/Apache24/htdocs/erp/config/config.php) — replace [hasFeature()](file:///c:/Apache24/htdocs/erp/config/config.php#457-499):

```php
if (!function_exists('hasFeature')) {
    function hasFeature(string $featureKey): bool {
        $featureKey = strtolower(trim($featureKey));

        $user = getCurrentUser();
        $role = $user['role'] ?? '';

        // Superadmin bypasses all feature checks
        if (in_array($role, ['superadmin', 'super-admin'])) {
            return true;
        }

        // Core features always enabled
        if (in_array($featureKey, ['dashboard', 'system', 'student', 'academic'])) {
            return true;
        }

        $tenantId = $user['tenant_id'] ?? null;
        if (empty($tenantId)) {
            return false;
        }

        // Cache key per tenant per request (avoid N+1 DB hits)
        static $featureCache = [];
        if (isset($featureCache[$tenantId])) {
            return in_array($featureKey, $featureCache[$tenantId]);
        }

        try {
            $db = getDBConnection();
            $stmt = $db->prepare("
                SELECT f.feature_key
                FROM system_features f
                JOIN institute_feature_access ifa ON f.id = ifa.feature_id
                WHERE ifa.tenant_id = :tenant_id
                AND ifa.is_enabled = 1
                AND f.status = 'active'
            ");
            $stmt->execute(['tenant_id' => $tenantId]);
            $featureCache[$tenantId] = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        } catch (\PDOException $e) {
            error_log('[FEATURE-GATE] DB error: ' . $e->getMessage());
            $featureCache[$tenantId] = [];
        }

        return in_array($featureKey, $featureCache[$tenantId]);
    }
}
```

> **Key change:** Uses a `static` in-memory cache per request instead of `$_SESSION`. No session dependency. No stale data across requests. Single DB query per tenant per PHP process lifetime.

---

### FIX 6 — Fix [RoleMiddleware](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/RoleMiddleware.php#9-278) to Read from JWT, Not Session

**File:** [app/Http/Middleware/RoleMiddleware.php](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/RoleMiddleware.php) — replace [getCurrentUser()](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/RoleMiddleware.php#140-146):

```php
private static function getCurrentUser(): ?array {
    // Priority 1: Already authenticated via JwtAuthMiddleware (Laravel/Eloquent)
    try {
        if (function_exists('auth') && auth('api')->check()) {
            $u = auth('api')->user();
            return [
                'id'        => $u->id,
                'role'      => $u->role,
                'tenant_id' => $u->tenant_id,
                'name'      => $u->name,
                'email'     => $u->email,
            ];
        }
    } catch (\Exception $e) {}

    // Priority 2: Legacy PHP pages using verifyJwtToken()
    if (function_exists('getCurrentUser')) {
        // Call the global getCurrentUser from config.php
        return \getCurrentUser();
    }

    return null;
}
```

---

### FIX 7 — Fix Custom JWT Encoder to Use Proper URL-Safe Base64

If you want to keep the custom encoder for any reason, fix it:

```php
private function jwtEncode(array $payload): string
{
    $encode = fn($data) => rtrim(strtr(base64_encode(is_string($data) ? $data : json_encode($data)), '+/', '-_'), '=');

    $header    = $encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $body      = $encode($payload);
    $signature = $encode(hash_hmac('sha256', "$header.$body", $this->jwtSecret, true));

    return "$header.$body.$signature";
}

public function jwtDecode(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    $decode = fn($b64) => base64_decode(str_pad(strtr($b64, '-_', '+/'), strlen($b64) % 4 === 0 ? 0 : 4 - (strlen($b64) % 4), '='));

    $expectedSig = rtrim(strtr(base64_encode(hash_hmac('sha256', "$parts[0].$parts[1]", $this->jwtSecret, true)), '+/', '-_'), '=');

    if (!hash_equals($expectedSig, $parts[2])) return null;  // hash_equals prevents timing attacks

    $payload = json_decode($decode($parts[1]), true);
    if (!$payload || ($payload['exp'] ?? 0) < time()) return null;

    return $payload;
}
```

---

### FIX 8 — Fix [sanitizeUser()](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php#586-596) to Handle `full_name`

**File:** [app/Http/Controllers/AuthController.php](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/AuthController.php):

```php
private function sanitizeUser(array $user): array
{
    return [
        'id'        => $user['id'],
        'name'      => $user['full_name'] ?? $user['name'] ?? $user['email'],
        'email'     => $user['email'],
        'role'      => $user['role'],
        'tenant_id' => $user['tenant_id'] ?? null,
    ];
}
```

---

### FIX 9 — Unify Password Hashing Algorithm

All new and updated users must use bcrypt (which Laravel's `Hash` facade and tymon expect).
Remove the `PASSWORD_ARGON2ID` usage from [auth.php](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/auth.php):

```php
// app/Http/Middleware/auth.php — FIX hashPassword()
function hashPassword(string $password): string {
    // Use bcrypt (cost 12) for full Laravel Hash::check() compatibility
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}
```

For existing argon2id hashes, add a migration wrapper: on first successful login with a valid password, rehash to bcrypt and store.

---

### FIX 10 — Fix .env: Add `JWT_ALGO`, Align TTL

```dotenv
# .env — Correct JWT config
JWT_SECRET=K7lBfdoHhGjI09XZPVb6CZV18dRE4hQj1WgKuQLFpQcQjcmPsRVdexy6AKOjK1h5
JWT_TTL=480           # 8 hours in MINUTES (tymon uses minutes)
JWT_REFRESH_TTL=43200 # 30 days in minutes
JWT_ALGO=HS256
JWT_BLACKLIST_ENABLED=true
JWT_BLACKLIST_GRACE_PERIOD=30
```

Then in [config/auth.php](file:///c:/Apache24/htdocs/erp/config/auth.php):
```php
'guards' => [
    'api' => [
        'driver'   => 'jwt',
        'provider' => 'users',
    ],
],
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\User::class,
    ],
],
```

---

### FIX 11 — Consolidate Cookie SameSite Policy

In [app/Http/Controllers/API/AuthController.php](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/API/AuthController.php), [respondWithToken()](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/API/AuthController.php#121-180) line 177, change `'Lax'` to match the web login (use `'Lax'` everywhere — `'Strict'` breaks cookie in redirects from loading screen):

```php
// In respondWithToken(), ensure consistent SameSite=Lax across ALL token-setting paths
)->cookie(
    'token',
    $token,
    $ttl / 60,
    '/',
    $cookieDomain,
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    true,   // HttpOnly
    false,  // Raw
    'Lax'   // SameSite — NOT Strict (Strict breaks loading screen redirects)
);
```

---

## 🧪 Edge Case Testing

### Test: Expired Token
```bash
# Manually set a short TTL in .env (JWT_TTL=1), login, wait 1 min, try protected route
curl -H "Authorization: Bearer <expired_token>" http://localhost/erp/api/me
# Expected: {"success":false,"message":"Token has expired."}
```

### Test: Missing Token
```bash
curl http://localhost/erp/api/me
# Expected: {"success":false,"message":"Authorization token not found. Please login."}
```

### Test: Invalid Tenant Cross-Access
```bash
# Login as Tenant A user, get their token
# Then call Tenant B's API endpoint
curl -H "Authorization: Bearer <tenant_a_token>" http://localhost/erp/api/students
# Expected: 403 if tenant_id in token doesn't match
```

### Test: Role Mismatch
```bash
# Login as student, try accessing admin route
curl -H "Authorization: Bearer <student_token>" http://localhost/erp/api/super/tenants
# Expected: {"success":false,"message":"Unauthorized. Super Admin access required."}
```

### Debug: Verify Token Contents (PHP)
Create a one-time debug route or script:
```php
// temp_debug.php (delete after use)
require_once 'config/config.php';
$token = $_COOKIE['token'] ?? $_GET['token'] ?? '';
$payload = verifyJwtToken($token);
header('Content-Type: application/json');
echo json_encode(['payload' => $payload, 'valid' => $payload !== null]);
```

---

## 📋 Production Readiness Checklist

| # | Check | Status | Fix |
|---|-------|--------|-----|
| 1 | Single login path (API controller only) | ❌ | Delete custom AuthController |
| 2 | JWT signature verified on every request | ❌ | Apply FIX 2 |
| 3 | No raw `base64_encode` in JWT encode/decode | ❌ | Apply FIX 7 |
| 4 | [hasFeature()](file:///c:/Apache24/htdocs/erp/config/config.php#457-499) independent of `$_SESSION` | ❌ | Apply FIX 5 |
| 5 | [RoleMiddleware](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/RoleMiddleware.php#9-278) reads JWT not session | ❌ | Apply FIX 6 |
| 6 | [IdentifyTenant](file:///c:/Apache24/htdocs/erp/app/Http/Middleware/IdentifyTenant.php#13-164) not called inside auth middleware | ❌ | Apply FIX 4 |
| 7 | Consistent bcrypt hashing | ❌ | Apply FIX 9 |
| 8 | Consistent SameSite=Lax cookie | ❌ | Apply FIX 11 |
| 9 | JWT_TTL aligned between .env and code | ❌ | Apply FIX 10 |
| 10 | JWT_BLACKLIST_ENABLED=true | ❌ | Apply FIX 10 |
| 11 | `hash_equals()` for signature comparison | ❌ | Apply FIX 2 |
| 12 | `full_name` used in sanitizeUser | ❌ | Apply FIX 8 |
| 13 | Rate limiting on login | ✅ | Already in checkRateLimit() |
| 14 | Account lockout | ✅ | Already in isAccountLocked() |
| 15 | Refresh token rotation | ✅ | Already in refresh() |
| 16 | Secure cookie HttpOnly | ✅ | Already set |
| 17 | Two-factor auth for admins | ✅ | Already in login() |
| 18 | Audit logging | ✅ | Already in logAuthEvent() |

---

## 📈 Best Practices Summary

### JWT vs Session
- **Use JWT** for all API routes, mobile clients, and multi-tenant isolation
- **Avoid Sessions** for auth state — sessions cause the exact conflicts seen here
- **Keep Sessions** only for short-lived non-auth state (flash messages, loading token)

### Secure Token Storage
- **HttpOnly cookie**: Best for web apps (immune to XSS)
- **Never `localStorage`**: Vulnerable to XSS
- **`SameSite=Lax`**: Allows GET cross-site navigations (loading screen redirects) but blocks POST CSRF

### Refresh Token Strategy (Already Mostly Correct)
- ✅ Rotation on use (invalidate old, issue new)
- ✅ Stored as hash in DB
- ❌ Should also enforce single-use via `jti` blacklist — use `JWT_BLACKLIST_ENABLED=true`

### Rate Limiting Login
- ✅ IP-based rate limiting exists
- 🔧 Move to Laravel's `RateLimiter` facade for atomic counting across workers:
```php
// In API/AuthController.php login():
use Illuminate\Support\Facades\RateLimiter;
$key = 'login:' . $request->ip();
if (RateLimiter::tooManyAttempts($key, 5)) {
    return response()->json(['message' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.'], 429);
}
RateLimiter::hit($key, 60 * 15); // 15 minute decay
```
