<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class Subject extends Model {
    use TenantScoped;
    protected $fillable = ['tenant_id', 'course_id', 'name', 'code', 'credit_hours', 'type'];
}
