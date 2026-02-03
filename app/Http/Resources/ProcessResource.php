<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProcessResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'threshold_value' => $this->threshold_value,
            'threshold_type' => $this->threshold_type,
            'tenant_id' => $this->tenant_id,
        ];
    }
}