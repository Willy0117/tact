<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'serving_date',
        'serving_time',  
        'dish_name',
        'materials',     
        'process',
        'cooking_date',
        'tenant_id',
    ];

    protected $casts = [
        'serving_date' => 'date',
        'cooking_date' => 'date',
        'serving_time' => 'string', // time型は string として扱う
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

}
