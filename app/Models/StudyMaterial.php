<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\TenantScoped;

class StudyMaterial extends Model {
    use SoftDeletes, TenantScoped;

    protected $table = 'study_materials';
    protected $fillable = [
        'tenant_id', 'category_id', 'title', 'description',
        'file_name', 'file_path', 'file_type', 'file_size', 'file_extension',
        'external_url', 'content_type', 'access_type', 'visibility',
        'course_id', 'batch_id', 'subject_id', 'tags', 'status', 
        'is_featured', 'sort_order', 'published_at', 'expires_at', 
        'created_by', 'updated_by'
    ];
    
    public function category() {
        return $this->belongsTo(StudyMaterialCategory::class, 'category_id');
    }

    public function subject() {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getMaterials($filters = [], $perPage = 20) {
        $query = self::with(['category', 'subject', 'creator']);
        
        if (!empty($filters['category_id'])) $query->where('category_id', $filters['category_id']);
        if (!empty($filters['subject_id'])) $query->where('subject_id', $filters['subject_id']);
        if (!empty($filters['batch_id'])) $query->where('batch_id', $filters['batch_id']);
        if (!empty($filters['content_type'])) $query->where('content_type', $filters['content_type']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['access_type'])) $query->where('access_type', $filters['access_type']);
        
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }
        
        return $query->orderBy('is_featured', 'DESC')
                     ->orderBy('sort_order', 'ASC')
                     ->orderBy('created_at', 'DESC')
                     ->paginate($perPage);
    }
    
    public static function getStats() {
        return [
            'total_materials' => self::count(),
            'by_type' => self::selectRaw('content_type, count(*) as count')->groupBy('content_type')->get(),
            'total_downloads' => self::sum('download_count'),
            'total_views' => self::sum('view_count')
        ];
    }
    
    public function incrementViews() {
        return $this->increment('view_count');
    }
    
    public function incrementDownloads() {
        return $this->increment('download_count');
    }
}
