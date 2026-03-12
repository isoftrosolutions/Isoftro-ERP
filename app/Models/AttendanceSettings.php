<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class AttendanceSettings extends Model {
    use TenantScoped;

    protected $table = 'attendance_settings';
    protected $fillable = [
        'tenant_id', 'lock_period_hours', 'exclude_leave_from_total', 'allow_frontdesk_edit'
    ];
    
    public static function getSettings() {
        $settings = self::first();
        if (!$settings) {
            return self::create([
                'tenant_id' => $_SESSION['userData']['tenant_id'] ?? null,
                'lock_period_hours' => 24,
                'exclude_leave_from_total' => 1,
                'allow_frontdesk_edit' => 0
            ]);
        }
        return $settings;
    }

    public static function getByTenant($tenantId) {
        return self::where('tenant_id', $tenantId)->first();
    }
    
    public static function updateSettings($data) {
        $settings = self::getSettings();
        if ($settings) {
            $settings->update($data);
        }
        return $settings;
    }
}
