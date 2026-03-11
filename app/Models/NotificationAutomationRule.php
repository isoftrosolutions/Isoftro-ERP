<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class NotificationAutomationRule extends Model {
    use TenantScoped;

    protected $table = 'notification_automation_rules';
    protected $fillable = [
        'tenant_id', 'name', 'trigger_type', 'conditions', 'message_template', 'is_active'
    ];
    
    protected $casts = [
        'conditions' => 'json'
    ];
    
    public static function getActive($triggerType = null) {
        $query = self::where('is_active', 1);
        if ($triggerType) {
            $query->where('trigger_type', $triggerType);
        }
        return $query->get();
    }
}
