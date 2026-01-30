<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemperatureLog extends Model {

    protected $fillable = [
        'tenant_id',
        'handy_no',
        'device_id',
        'operator_id',
        'menu_id',
        'sensor_id',
        'process_id',
        'note',
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
        return $this->belongsTo(Device::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
  
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'id');
    }

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    // TemperatureLog.php
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

}


