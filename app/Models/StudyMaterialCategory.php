<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class StudyMaterialCategory extends Model {
    use TenantScoped;
    protected $table = 'study_material_categories';
    protected $fillable = ['tenant_id', 'parent_id', 'name', 'slug', 'icon', 'color', 'sort_order'];
}
