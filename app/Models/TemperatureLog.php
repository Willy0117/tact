<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemperatureLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'menu_id',
        'process_id',
        'device_id',
        'operator_id',
        'temperature',
        'measured_at',
    ];

    // リレーション
    public function menu() {
        return $this->belongsTo(Menu::class);
    }

    public function process() {
        return $this->belongsTo(Process::class);
    }

    public function device() {
        return $this->belongsTo(Device::class);
    }

    public function operator() {
        return $this->belongsTo(Operator::class);
    }
}
