<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'contact_email',
        'contact_phone',
        'address',
    ];

    // 例: ユーザーとのリレーション
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // 例: メニューとのリレーション
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    // 必要に応じて追加 ↓
}

