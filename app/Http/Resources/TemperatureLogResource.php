<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TemperatureLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'handy_no' => $this->handy_no,
            'device_id' => $this->device_id,
            'operator_id' => $this->operator_id,
            'dish_id' => $this->menu_id,         // DB の menu_id を dish_id に変換
            'sensor_id' => $this->sensor_id,
            'created_at' => $this->created_at,
            'temperatures' => $this->temperatures,
        ];
    }
}
