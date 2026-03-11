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
     * Create default settings for a tenant
     */
    public static function createDefault() {
        return self::create([
            'tenant_id' => $_SESSION['userData']['tenant_id'] ?? null,
            'invoice_prefix' => 'INV',
            'receipt_prefix' => 'RCP',
            'next_invoice_number' => 1,
            'next_receipt_number' => 1
        ]);
    }
    
    /**
     * Increment next invoice/receipt number atomatically
     */
    public static function incrementNumber($type = 'invoice') {
        $settings = self::getSettings();
        if (!$settings) $settings = self::createDefault();

        $column = $type === 'invoice' ? 'next_invoice_number' : 'next_receipt_number';
        $settings->increment($column);
        return true;
    }
}
