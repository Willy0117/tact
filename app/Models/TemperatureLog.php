<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemperatureLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'sensor_id',
        'device_id', 
        'menu_id',
        'process_id',
        'operator_id',
        'temperature',
        'measured_at',
    ];

    protected $dates = [
        'measured_at',
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}

