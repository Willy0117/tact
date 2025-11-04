<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'tenant_id', // 追加
    ];

    /**
     * Tenant リレーション
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Tenant スコープ
     */
    public function scopeOfTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}

