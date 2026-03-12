<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\TenantScoped;

class User extends Model {
    use SoftDeletes, TenantScoped;

    protected $table = 'users';
    
    protected $fillable = [
        'tenant_id', 'role', 'email', 'password_hash', 'name', 'phone', 'status', 'two_fa_enabled', 'locked_until'
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
}
