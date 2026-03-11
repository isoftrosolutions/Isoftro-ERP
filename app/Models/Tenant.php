namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model {
    use SoftDeletes;

    protected $table = 'tenants';
    protected $fillable = [
        'name', 'nepali_name', 'subdomain', 'brand_color', 'tagline', 'phone', 
        'address', 'province', 'plan', 'status', 'student_limit', 'sms_credits', 'trial_ends_at'
    ];
    
    /**
     * Find tenant by subdomain
     */
    public static function findBySubdomain($subdomain) {
        return self::where('subdomain', $subdomain)->first();
    }
}
