<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class PaymentReceipt extends Model {
    use TenantScoped;

    protected $table = 'payment_receipts';
    protected $fillable = ['tenant_id', 'payment_id', 'pdf_path'];
    
    public static function findByPayment($paymentId) {
        return self::where('payment_id', $paymentId)->first();
    }
}
