<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * SRS FR-AUTH-007: Tenant Data Isolation
 * Automatically scopes all queries and sets tenant_id on creation.
 */
trait TenantScoped
{
    /**
     * Boot the trait to apply global scopes.
     */
    protected static function bootTenantScoped()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            // Priority 1: User data from Session (Fastest & Safest to avoid recursion)
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            
            $tenantId = $_SESSION['userData']['tenant_id'] ?? null;
            
            // Priority 2: Use Laravel Auth only if session is not set AND we are not scoping the User model
            // This prevents the infinite loop when Laravel tries to load the authenticated user
            if ($tenantId === null && !($builder->getModel() instanceof \App\Models\User)) {
                if (function_exists('auth') && auth()->guard()->check()) {
                    $tenantId = auth()->user()->tenant_id;
                }
            }
            
            // Apply scope if tenant_id is set (Super Admins bypass this if tenant_id is NULL)
            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $tenantId = $_SESSION['userData']['tenant_id'] ?? null;
            
            if ($tenantId === null && function_exists('auth') && auth()->guard()->check()) {
                $tenantId = auth()->user()->tenant_id;
            }
            
            if ($tenantId !== null && !isset($model->tenant_id)) {
                $model->tenant_id = $tenantId;
            }
        });
    }
}
