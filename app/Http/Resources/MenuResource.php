<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'dish_name' => $this->dish_name,
            'materials' => $this->materials,
            'process' => $this->process,
            'serving_date' => $this->serving_date->format('Y-m-d'),
            'serving_time' => $this->serving_time,
            'cooking_date' => $this->cooking_date?->format('Y-m-d'),
            'tenant_id' => $this->tenant_id,
            'disabled' => $this->disabled,
            'display_order' => $this->display_order,
            /* 2025.11.14 不要データ削除
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            */
        ];
    }
}
