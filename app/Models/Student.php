<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\TenantScoped;
use App\Helpers\EncryptionHelper;
use App\Helpers\DateUtils;

class Student extends Model {
    use SoftDeletes, TenantScoped;

    protected $table = 'students';
    
    protected $fillable = [
        'tenant_id', 'user_id', 'batch_id', 'roll_no', 'full_name', 'dob_ad', 'dob_bs', 'gender', 'blood_group', 
        'phone', 'email', 'citizenship_no', 'national_id', 'father_name', 'mother_name', 'husband_name', 
        'guardian_name', 'guardian_relation', 'permanent_address', 'temporary_address', 
        'academic_qualifications', 'admission_date', 'photo_url', 'identity_doc_url', 'status', 
        'registration_mode', 'registration_status', 'id_card_status'
    ];

    /**
     * Citizenship Account - Decrypt on access
     */
    public function getCitizenshipNoAttribute($value) {
        if (empty($value)) return $value;
        return EncryptionHelper::decrypt($value);
    }

    /**
     * Citizenship Account - Encrypt on set
     */
    public function setCitizenshipNoAttribute($value) {
        if (empty($value)) {
            $this->attributes['citizenship_no'] = $value;
        } else {
            $this->attributes['citizenship_no'] = EncryptionHelper::encrypt($value);
        }
    }

    /**
     * Get students by batch
     */
    public static function getByBatch($batchId) {
        return self::where('batch_id', $batchId)->where('status', 'active')->orderBy('roll_no')->get();
    }
    
    /**
     * Get students by tenant (Global Scope handles isolation)
     */
    public static function getByTenant($status = null) {
        $query = self::orderBy('created_at', 'DESC');
        if ($status) $query->where('status', $status);
        return $query->get();
    }
    
    /**
     * Generate next roll no
     */
    public static function generateRollNo() {
        $maxId = self::max('id');
        $nextId = (int)$maxId + 1;
        $year = date('Y');
        try { $year = DateUtils::getCurrentYear(); } catch (\Throwable $e) {}

        return "STD-{$year}-" . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Search students
     */
    public static function search($term) {
        return self::where(function($q) use ($term) {
            $q->where('full_name', 'LIKE', "%{$term}%")
              ->orWhere('roll_no', 'LIKE', "%{$term}%");
        })->limit(20)->get();
    }
}
