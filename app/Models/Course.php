<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class Course extends Model {
    use TenantScoped;
    protected $fillable = ['tenant_id', 'name', 'code', 'description', 'status'];
}
