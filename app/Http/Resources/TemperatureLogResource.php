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
            // 👇 命名ルールに合わせて dish_* に統一
            'dish_id'   => $this->menu_id,
            'dish_name' => $this->menu?->name,

            'sensor_id' => $this->sensor_id,

            'process_id'   => $this->process_id,
            'process_name' => $this->process?->name,

            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'temperatures' => $this->temperatures,
        ];
    }    
}
