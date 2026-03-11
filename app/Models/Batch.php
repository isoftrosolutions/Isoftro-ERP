<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class Batch extends Model {
    use TenantScoped;
    protected $fillable = ['tenant_id', 'course_id', 'name', 'year', 'status'];
    
    public function course() {
        return $this->belongsTo(Course::class);
    }
}
