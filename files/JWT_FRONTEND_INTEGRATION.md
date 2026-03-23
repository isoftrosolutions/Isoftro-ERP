# 🎨 PART 2: FRONTEND INTEGRATION & TOKEN MANAGEMENT

---

## 📱 FRONTEND SETUP

Your Hamro Labs ERP uses **Blade + Alpine.js + Bootstrap**. Here's how to integrate JWT authentication.

---

## 🔧 STEP 10: TOKEN MANAGEMENT SERVICE

### Create `public/js/auth.js`

```javascript
/**
 * Hamro Labs JWT Authentication Service
 * Handles token storage, API calls, and auth state
 */

class AuthService {
    constructor() {
        this.tokenKey = 'hamro_labs_token';
        this.userKey = 'hamro_labs_user';
        this.apiBase = '/api';
    }

    /**
     * Get stored token
     */
    getToken() {
        return localStorage.getItem(this.tokenKey);
    }

    /**
     * Store token
     */
    setToken(token) {
        localStorage.setItem(this.tokenKey, token);
    }

    /**
     * Remove token
     */
    removeToken() {
        localStorage.removeItem(this.tokenKey);
        localStorage.removeItem(this.userKey);
    }

    /**
     * Get stored user data
     */
    getUser() {
        const user = localStorage.getItem(this.userKey);
        return user ? JSON.parse(user) : null;
    }

    /**
     * Store user data
     */
    setUser(user) {
        localStorage.setItem(this.userKey, JSON.stringify(user));
    }

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return !!this.getToken();
    }

    /**
     * Check if user is super admin
     */
    isSuperAdmin() {
        const user = this.getUser();
        return user && user.role === 'super_admin';
    }

    /**
     * Check if currently impersonating
     */
    isImpersonating() {
        const user = this.getUser();
        return user && user.is_impersonating;
    }

    /**
     * Make authenticated API request
     */
    async request(endpoint, options = {}) {
        const token = this.getToken();
        
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(token && { 'Authorization': `Bearer ${token}` }),
            ...options.headers
        };

        const config = {
            ...options,
            headers
        };

        try {
            const response = await fetch(`${this.apiBase}${endpoint}`, config);
            const data = await response.json();

            // Handle token expiration
            if (response.status === 401) {
                this.logout();
                window.location.href = '/login';
                return null;
            }

            return { response, data };
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    }

    /**
     * Login
     */
    async login(email, password) {
        const { response, data } = await this.request('/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });

        if (data.success) {
            this.setToken(data.access_token);
            this.setUser(data.user);
        }

        return data;
    }

    /**
     * Logout
     */
    async logout() {
        if (this.isAuthenticated()) {
            await this.request('/logout', { method: 'POST' });
        }
        this.removeToken();
        window.location.href = '/login';
    }

    /**
     * Refresh token
     */
    async refresh() {
        const { data } = await this.request('/refresh', { method: 'POST' });
        
        if (data.success) {
            this.setToken(data.access_token);
        }
        
        return data;
    }

    /**
     * Get current user from API
     */
    async me() {
        const { data } = await this.request('/me');
        
        if (data.success) {
            this.setUser(data.user);
        }
        
        return data;
    }

    /**
     * Impersonate user (Super Admin only)
     */
    async impersonate(userId) {
        const { data } = await this.request(`/super/impersonate/${userId}`, {
            method: 'POST'
        });

        if (data.success) {
            this.setToken(data.access_token);
            this.setUser(data.impersonated_user);
            
            // Show notification
            window.location.reload();
        }

        return data;
    }

    /**
     * Stop impersonation
     */
    async stopImpersonation() {
        const { data } = await this.request('/super/stop-impersonation', {
            method: 'POST'
        });

        if (data.success) {
            this.setToken(data.access_token);
            this.setUser(data.user);
            window.location.reload();
        }

        return data;
    }
}

// Global instance
window.authService = new AuthService();

// Auto-refresh token before expiry (optional but recommended)
setInterval(async () => {
    if (window.authService.isAuthenticated()) {
        try {
            await window.authService.refresh();
        } catch (error) {
            console.error('Token refresh failed:', error);
        }
    }
}, 20 * 60 * 1000); // Refresh every 20 minutes
```

---

## 🔐 STEP 11: LOGIN PAGE

### `resources/views/auth/login.blade.php`

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hamro Labs ERP</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body style="background: linear-gradient(135deg, #00B894 0%, #0F172A 100%);">
    
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div class="card" style="max-width: 420px; width: 100%;">
            
            <!-- Logo -->
            <div style="text-align: center; margin-bottom: 32px;">
                <div style="width: 64px; height: 64px; background: var(--green); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                    <i class="fas fa-graduation-cap" style="font-size: 28px; color: white;"></i>
                </div>
                <h1 style="font-size: 24px; font-weight: 800; margin-bottom: 4px;">Hamro Labs ERP</h1>
                <p style="color: var(--text-light); font-size: 13px;">Sign in to your account</p>
            </div>

            <!-- Login Form (Alpine.js) -->
            <div x-data="loginForm()">
                
                <!-- Error Alert -->
                <div x-show="error" 
                     x-transition
                     style="background: #fee2e2; color: var(--red); padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span x-text="error"></span>
                </div>

                <form @submit.prevent="login">
                    
                    <!-- Email -->
                    <div class="form-grp">
                        <label class="form-lbl">
                            <i class="fas fa-envelope" style="margin-right: 6px; color: var(--green);"></i>
                            Email Address
                        </label>
                        <input 
                            type="email" 
                            class="form-inp" 
                            x-model="email"
                            placeholder="your@email.com"
                            required
                            :disabled="loading"
                        >
                    </div>

                    <!-- Password -->
                    <div class="form-grp">
                        <label class="form-lbl">
                            <i class="fas fa-lock" style="margin-right: 6px; color: var(--green);"></i>
                            Password
                        </label>
                        <input 
                            type="password" 
                            class="form-inp" 
                            x-model="password"
                            placeholder="Enter your password"
                            required
                            :disabled="loading"
                        >
                    </div>

                    <!-- Remember Me -->
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
                            <input type="checkbox" x-model="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="#" style="color: var(--green); font-size: 13px; font-weight: 600;">Forgot Password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="btn bt" 
                        style="width: 100%;"
                        :disabled="loading"
                    >
                        <span x-show="!loading">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </span>
                        <span x-show="loading">
                            <i class="fas fa-spinner fa-spin"></i> Signing in...
                        </span>
                    </button>

                </form>

                <!-- Footer -->
                <div style="text-align: center; margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--card-border);">
                    <p style="font-size: 12px; color: var(--text-light);">
                        © 2024 Hamro Labs Pvt. Ltd. | Powered by Nepal
                    </p>
                </div>

            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="{{ asset('js/auth.js') }}"></script>
    
    <script>
        function loginForm() {
            return {
                email: '',
                password: '',
                remember: false,
                loading: false,
                error: null,

                async login() {
                    this.loading = true;
                    this.error = null;

                    try {
                        const result = await window.authService.login(this.email, this.password);
                        
                        if (result.success) {
                            // Redirect based on role
                            if (result.user.role === 'super_admin') {
                                window.location.href = '/super-admin/dashboard';
                            } else {
                                window.location.href = '/dashboard';
                            }
                        } else {
                            this.error = result.message || 'Login failed. Please try again.';
                        }
                    } catch (error) {
                        this.error = 'Network error. Please check your connection.';
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>

</body>
</html>
```

---

## 🏠 STEP 12: DASHBOARD LAYOUT (WITH JWT)

### `resources/views/layouts/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Hamro Labs ERP</title>
    
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    @stack('styles')
</head>
<body x-data="dashboardApp()" x-init="init()">

    <div class="root">
        
        <!-- Header -->
        <header class="hdr">
            <div class="hdr-left">
                <button class="sb-toggle" @click="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="hdr-logo-box">
                    <i class="fas fa-graduation-cap"></i>
                    <span style="font-weight: 800;">Hamro Labs</span>
                </div>
                <span class="hdr-title" x-text="user.institute ? user.institute.name : 'Super Admin'"></span>
            </div>

            <div class="hdr-right">
                
                <!-- Impersonation Banner -->
                <template x-if="user.is_impersonating">
                    <button @click="stopImpersonation" class="btn btn-amber btn-sm">
                        <i class="fas fa-user-secret"></i>
                        Stop Impersonating
                    </button>
                </template>

                <!-- Notifications -->
                <div class="hbtn nb">
                    <i class="fas fa-bell"></i>
                    <span class="ndot"></span>
                </div>

                <!-- User Profile -->
                <div class="hdr-user">
                    <span class="hdr-uname" x-text="user.name"></span>
                    <div class="hdr-av" x-text="user.name ? user.name.charAt(0).toUpperCase() : 'U'"></div>
                </div>

                <!-- Logout -->
                <button @click="logout" class="hbtn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </header>

        <!-- Sidebar -->
        @include('layouts.partials.sidebar')

        <!-- Main Content -->
        <main class="main">
            <div class="page">
                @yield('content')
            </div>
        </main>

    </div>

    <!-- Scripts -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="{{ asset('js/auth.js') }}"></script>
    
    <script>
        function dashboardApp() {
            return {
                user: {},
                sidebarOpen: false,

                async init() {
                    // Check authentication
                    if (!window.authService.isAuthenticated()) {
                        window.location.href = '/login';
                        return;
                    }

                    // Load user data
                    await this.loadUser();
                },

                async loadUser() {
                    try {
                        const result = await window.authService.me();
                        if (result.success) {
                            this.user = result.user;
                        }
                    } catch (error) {
                        console.error('Failed to load user:', error);
                        await window.authService.logout();
                    }
                },

                toggleSidebar() {
                    document.body.classList.toggle('sb-active');
                },

                async logout() {
                    if (confirm('Are you sure you want to logout?')) {
                        await window.authService.logout();
                    }
                },

                async stopImpersonation() {
                    if (confirm('Stop impersonating and return to Super Admin?')) {
                        await window.authService.stopImpersonation();
                    }
                }
            }
        }
    </script>

    @stack('scripts')
</body>
</html>
```

---

## 👑 STEP 13: SUPER ADMIN DASHBOARD

### `resources/views/super-admin/dashboard.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Super Admin Dashboard')

@section('content')
<div x-data="superAdminDashboard()" x-init="loadData()">

    <!-- Page Header -->
    <div class="pg-hdr">
        <div>
            <h1><i class="fas fa-crown" style="color: var(--purple);"></i> Super Admin Dashboard</h1>
            <p>Manage all institutes and system settings</p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stat-grid">
        <div class="card stat-card">
            <div class="stat-top">
                <div class="stat-icon-box ic-green">
                    <i class="fas fa-building"></i>
                </div>
            </div>
            <div class="stat-val" x-text="stats.total_institutes || 0"></div>
            <div style="font-size: 12px; color: var(--text-light); margin-top: 4px;">Total Institutes</div>
        </div>

        <div class="card stat-card">
            <div class="stat-top">
                <div class="stat-icon-box ic-blue">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-val" x-text="stats.total_users || 0"></div>
            <div style="font-size: 12px; color: var(--text-light); margin-top: 4px;">Total Users</div>
        </div>

        <div class="card stat-card">
            <div class="stat-top">
                <div class="stat-icon-box ic-purple">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
            <div class="stat-val" x-text="stats.total_students || 0"></div>
            <div style="font-size: 12px; color: var(--text-light); margin-top: 4px;">Total Students</div>
        </div>

        <div class="card stat-card">
            <div class="stat-top">
                <div class="stat-icon-box ic-teal">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-val" x-text="stats.active_institutes || 0"></div>
            <div style="font-size: 12px; color: var(--text-light); margin-top: 4px;">Active Institutes</div>
        </div>
    </div>

    <!-- Institutes List -->
    <div class="card">
        <div class="tbl-head">
            <h3 class="tbl-title">
                <i class="fas fa-building"></i> All Institutes
            </h3>
            <button class="btn bt btn-sm">
                <i class="fas fa-plus"></i> Add Institute
            </button>
        </div>

        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr style="background: var(--bg); border-bottom: 2px solid var(--card-border);">
                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 800; color: var(--text-light); text-transform: uppercase;">Institute</th>
                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 800; color: var(--text-light); text-transform: uppercase;">Admin</th>
                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 800; color: var(--text-light); text-transform: uppercase;">Users</th>
                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 800; color: var(--text-light); text-transform: uppercase;">Status</th>
                        <th style="padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 800; color: var(--text-light); text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="institute in institutes" :key="institute.id">
                        <tr style="border-bottom: 1px solid var(--card-border);">
                            <td style="padding: 16px 20px;">
                                <div class="inst-row">
                                    <div class="inst-av ic-green" x-text="institute.name.charAt(0)"></div>
                                    <div>
                                        <div class="inst-name" x-text="institute.name"></div>
                                        <div class="inst-sub" x-text="institute.domain || 'No domain'"></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 16px 20px;">
                                <template x-if="institute.users && institute.users.length > 0">
                                    <div>
                                        <div style="font-weight: 600; font-size: 13px;" x-text="institute.users[0].name"></div>
                                        <div style="font-size: 11px; color: var(--text-light);" x-text="institute.users[0].email"></div>
                                    </div>
                                </template>
                            </td>
                            <td style="padding: 16px 20px;">
                                <span class="pill pb" x-text="institute.users_count + ' users'"></span>
                            </td>
                            <td style="padding: 16px 20px;">
                                <span 
                                    class="pill"
                                    :class="institute.status === 'active' ? 'pg' : 'pr'"
                                    x-text="institute.status"
                                ></span>
                            </td>
                            <td style="padding: 16px 20px;">
                                <div style="display: flex; gap: 6px;">
                                    <template x-if="institute.users && institute.users.length > 0">
                                        <button 
                                            @click="impersonate(institute.users[0].id)"
                                            class="btn btn-purple btn-sm"
                                        >
                                            <i class="fas fa-user-secret"></i> Impersonate
                                        </button>
                                    </template>
                                    <button class="btn bs btn-sm">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
function superAdminDashboard() {
    return {
        stats: {},
        institutes: [],
        loading: false,

        async loadData() {
            this.loading = true;
            
            try {
                // Load stats
                const statsResult = await window.authService.request('/super/stats');
                if (statsResult.data.success) {
                    this.stats = statsResult.data.stats;
                }

                // Load institutes
                const institutesResult = await window.authService.request('/super/institutes');
                if (institutesResult.data.success) {
                    this.institutes = institutesResult.data.institutes;
                }
            } catch (error) {
                console.error('Failed to load data:', error);
            } finally {
                this.loading = false;
            }
        },

        async impersonate(userId) {
            if (confirm('Impersonate this user?')) {
                await window.authService.impersonate(userId);
            }
        }
    }
}
</script>
@endpush

@endsection
```

---

## 📊 STEP 14: EXAMPLE API USAGE IN PAGES

### Student List Page with JWT

```blade
@extends('layouts.app')

@section('content')
<div x-data="studentList()" x-init="loadStudents()">

    <div class="pg-hdr">
        <h1><i class="fas fa-user-graduate"></i> Students</h1>
        <button @click="addStudent" class="btn bt">
            <i class="fas fa-plus"></i> Add Student
        </button>
    </div>

    <div class="card">
        <!-- Loading State -->
        <div x-show="loading" style="padding: 40px; text-align: center;">
            <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--green);"></i>
            <p style="margin-top: 12px; color: var(--text-light);">Loading students...</p>
        </div>

        <!-- Students Table -->
        <div x-show="!loading" class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="student in students" :key="student.id">
                        <tr>
                            <td x-text="student.name"></td>
                            <td x-text="student.email"></td>
                            <td x-text="student.phone"></td>
                            <td>
                                <span class="pill pg" x-text="student.status"></span>
                            </td>
                            <td>
                                <button @click="editStudent(student)" class="btn bs btn-sm">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
function studentList() {
    return {
        students: [],
        loading: false,

        async loadStudents() {
            this.loading = true;
            
            try {
                const { data } = await window.authService.request('/students');
                
                if (data.success) {
                    this.students = data.students;
                }
            } catch (error) {
                alert('Failed to load students');
            } finally {
                this.loading = false;
            }
        },

        addStudent() {
            // Open drawer/modal
        },

        editStudent(student) {
            // Open edit drawer
        }
    }
}
</script>
@endpush

@endsection
```

---

## ✅ TESTING YOUR JWT SYSTEM

### Test with Postman/Thunder Client

**1. Login**
```http
POST http://your-domain.test/api/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 86400,
  "user": {
    "id": 1,
    "name": "Super Admin",
    "role": "super_admin"
  }
}
```

**2. Get User Info**
```http
GET http://your-domain.test/api/me
Authorization: Bearer YOUR_TOKEN_HERE
```

**3. Impersonate**
```http
POST http://your-domain.test/api/super/impersonate/5
Authorization: Bearer YOUR_SUPER_ADMIN_TOKEN
```

---

## 🎯 NEXT STEPS

✅ Backend JWT setup complete
✅ Frontend token management ready
✅ Login/logout flow working
✅ Super Admin impersonation ready

**Now implement:**
1. Actual controller logic (StudentController, etc.)
2. Module-based UI rendering
3. Error handling
4. Token refresh automation

**Want me to create:**
- ✅ Sample StudentController with JWT
- ✅ Accounting module example
- ✅ Complete middleware testing
- ✅ Migration files for modules

Just say what you need next! 🚀
