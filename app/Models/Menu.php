<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menus';

    protected $fillable = [
        'serving_date',
        'serving_time',
        'dish_name',
        'process',
        'cooking_date',
    ];

    protected $casts = [
        'serving_date' => 'date',
        'cooking_date' => 'date',
    ];
}