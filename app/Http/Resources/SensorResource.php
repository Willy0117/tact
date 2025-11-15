<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SensorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'serial_number' => $this->serial_number,
            'code' => $this->code,
            'name' => $this->name,
            'model' => $this->model,
            'disabled' => $this->disabled,
            'display_order' => $this->display_order,
            /* 2025.11.14 不要データ削除
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            */
        ];
    }
}
