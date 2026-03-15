<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class FeeSettings extends Model {
    use TenantScoped;

    protected $table = 'fee_settings';
    protected $fillable = [
        'tenant_id', 'invoice_prefix', 'receipt_prefix', 'next_invoice_number', 'next_receipt_number'
    ];
    
    /**
     * Get settings for a tenant
     */
    public static function getSettings() {
        return self::first();
    }

    /**
     * Get settings by tenant ID
     */
    public static function getByTenant($tenantId) {
        return self::where('tenant_id', $tenantId)->first();
    }
    
    /**
     * Create default settings for a tenant
     */
    public static function createDefault($tenantId) {
        return self::create([
            'tenant_id' => $tenantId,
            'invoice_prefix' => 'INV',
            'receipt_prefix' => 'RCP',
            'next_invoice_number' => 1,
            'next_receipt_number' => 1
        ]);
    }
    
    /**
     * Increment next invoice/receipt number atomatically
     */
    public static function incrementNumber($tenantId, $type = 'invoice') {
        $settings = self::getByTenant($tenantId);
        if (!$settings) $settings = self::createDefault($tenantId);

        $column = $type === 'invoice' ? 'next_invoice_number' : 'next_receipt_number';
        $settings->increment($column);
        return true;
    }
}
