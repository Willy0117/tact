<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Operator extends Model
{
    use HasFactory;

    protected $table = 'operators';

    protected $fillable = [
        'code',
        'name',
        'disabled',
        'display_order',
        'tenant_id',
    ];

    protected $casts = [
        'disabled' => 'boolean',
        'display_order' => 'integer',
    ];

    public $timestamps = true; // これがデフォルトで true なら created_at, updated_at が自動更新


    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

}
