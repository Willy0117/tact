<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // 追加
use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    use HasFactory; // 追加

    protected $fillable = [
        'code',
        'name',
        'model',
        'serial_number',
        'disabled',
        'display_order',
        'tenant_id',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

