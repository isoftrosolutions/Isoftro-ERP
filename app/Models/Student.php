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
    
    /**
     * Eager load user profile by default
     */
    protected $with = ['user'];

    protected $fillable = [
        'tenant_id', 'user_id', 'roll_no', 'dob_bs', 'dob_ad', 'gender', 'blood_group', 
        'citizenship_no', 'national_id', 'father_name', 'mother_name', 'husband_name', 
        'guardian_name', 'guardian_relation', 'permanent_address', 'temporary_address', 
        'academic_qualifications', 'admission_date', 'photo_url', 'identity_doc_url', 'status', 
        'id_card_status'
    ];

    /**
     * Relationship with the User account
     */
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with Guardians
     */
    public function guardians() {
        return $this->hasMany(Guardian::class, 'student_id');
    }

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
     * Get students by batch (Via Enrollments table)
     */
    public static function getByBatch($batchId) {
        return self::join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->where('enrollments.batch_id', $batchId)
            ->where('enrollments.status', 'active')
            ->select('students.*', 'users.name as full_name', 'users.name as name')
            ->orderBy('roll_no')
            ->get();
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
    public static function generateRollNo($tenantId = null) {
        $maxId = self::withoutGlobalScopes()->withTrashed()->max('id');
        $nextId = (int)$maxId + 1;
        $year = date('Y');
        try { $year = DateUtils::getCurrentYear(); } catch (\Throwable $e) {}

        return "STD-{$year}-" . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Search students (Joining User table for Name search)
     */
    public static function search($term) {
        return self::join('users', 'students.user_id', '=', 'users.id')
            ->where(function($q) use ($term) {
                $q->where('users.name', 'LIKE', "%{$term}%")
                  ->orWhere('students.roll_no', 'LIKE', "%{$term}%");
            })
            ->select('students.*')
            ->limit(20)
            ->get();
    }
}
