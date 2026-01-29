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
        'serving_date' => 'date:Y-m-d',
        'cooking_date' => 'date:Y-m-d',
        'serving_time' => 'string', // time型は string として扱う
    ];
    // serving_date を常に Y-m-d 形式で返す
    protected function servingDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null
        );
    }

    // cooking_date も同様に Y-m-d 形式
    protected function cookingDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null
        );
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

}
