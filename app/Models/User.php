<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\TenantScoped;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject {
    use SoftDeletes, TenantScoped, Notifiable;

    protected $table = 'users';

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'impersonated_by' => $this->impersonated_by,
        ];
    }
    
    /**
     * Overriding password column name for Laravel Auth
     */
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }
    
    protected $fillable = [
        'tenant_id', 'role', 'email', 'password_hash', 'name', 'phone', 'status', 'two_fa_enabled', 'locked_until',
        'impersonated_by', 'impersonation_started_at'
    ];

    protected $hidden = [
        'password_hash', 'two_factor_secret'
    ];

    /**
     * Get all users (Eloquent version)
     */
    public function allUsers() {
        return self::orderBy('created_at', 'DESC')->get()->toArray();
    }
    
    /**
     * Static finder for email
     */
    public static function findByEmail($email) {
        return self::where('email', $email)->first();
    }
    
    /**
     * Create new user (Eloquent version)
     */
    public function createUser($data) {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            unset($data['password']);
        }
        
        $user = self::create($data);
        return $user->toArray();
    }
    
    /**
     * Update user (Eloquent version)
     */
    public function updateUser($id, $data) {
        $user = self::find($id);
        if (!$user) return null;

        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            unset($data['password']);
        }
        
        $user->update($data);
        return $user->toArray();
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($email, $password) {
        $user = self::findByEmail($email);
        
        if ($user && password_verify($password, $user->password_hash)) {
            return $user->toArray();
        }
        
        return null;
    }
    
    /**
     * Update last login
     */
    public function updateLastLogin($id) {
        return self::where('id', $id)->update(['last_login_at' => now()]);
    }
    
    /**
     * Record Failed Login Attempt
     */
    public function recordFailedLogin($userId, $ipAddress) {
        // This is handled by AuditLogger now in updated AuthController
        return \App\Helpers\AuditLogger::log('LOGIN_FAILURE', $userId, null, ['ip' => $ipAddress]);
    }
    /**
     * Relationship with Tenant
     */
    public function tenant() {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Relationship with Impersonator
     */
    public function impersonator() {
        return $this->belongsTo(User::class, 'impersonated_by');
    }

    // ========================
    // HELPER METHODS
    // ========================

    public function isSuperAdmin(): bool {
        return $this->role === 'superadmin';
    }

    public function isTenantAdmin(): bool {
        return $this->role === 'instituteadmin';
    }

    public function isStaff(): bool {
        return in_array($this->role, ['frontdesk', 'teacher', 'librarian']);
    }

    public function isImpersonating(): bool {
        return !is_null($this->impersonated_by);
    }

    public function canAccessModule(string $moduleSlug): bool {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->tenant_id) {
            return false;
        }

        // BUG 6 RESOLUTION: Aligning with the new system_features schema
        // Some legacy slugs differ from new feature keys
        $aliasMap = [
            'admissions' => 'inquiry',
            'exams'      => 'exam',
            'finance'    => 'accounting',
            'staff'      => 'teacher',
        ];

        $featureKey = $aliasMap[$moduleSlug] ?? $moduleSlug;

        return \DB::table('system_features')
            ->join('institute_feature_access', 'system_features.id', '=', 'institute_feature_access.feature_id')
            ->where('institute_feature_access.tenant_id', $this->tenant_id)
            ->where('system_features.feature_key', $featureKey)
            ->where('institute_feature_access.is_enabled', 1)
            ->where('system_features.status', 'active')
            ->exists();
    }
}
