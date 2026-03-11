<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\TenantScoped;

class FeeItem extends Model {
    use SoftDeletes, TenantScoped;

    protected $table = 'fee_items';
    protected $fillable = [
        'tenant_id', 'course_id', 'name', 'type', 'amount', 'installments', 'late_fine_per_day', 'is_active'
    ];
    
    /**
     * Get active fee items for a course
     */
    public static function getByCourse($courseId) {
        return self::where('course_id', $courseId)
            ->where('is_active', 1)
            ->get();
    }
}
