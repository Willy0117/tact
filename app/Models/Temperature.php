<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Temperature extends Model {
// 実際のテーブル名を指定
    protected $table = 'temperature_logs';

    protected $fillable = [
        'tenant_id',
        'handy_no',
        'device_id',
        'operator_id',
        'menu_id',
        'sensor_id',
        'temperatures', // ← 修正後の項目
    ];

    protected $casts = [
        'temperatures' => 'array',        // JSON配列として扱う
    ];

    // リレーション例（必要に応じて追加）
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id'); // devices.id を参照
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id'); // operators.id を参照
    }
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id'); // menus.id を参照
    }

    public function sensor()
    {
        return $this->belongsTo(Sensor::class, 'sensor_id');
    }
}