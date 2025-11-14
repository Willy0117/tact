<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // 追加
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'process',
        'measurement',
        'disabled',
        'display_order',
        'tenant_id',
    ];
    
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

}
