<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'serial_number' => $this->serial_number,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
