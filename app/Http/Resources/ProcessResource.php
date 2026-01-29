<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProcessResource extends JsonResource
{
    public function toArray($request)
    {
        try {
            $request->validate([
                'tenant_id' => ['required', 'integer', 'min:1'],
                'page' => 'integer',
                'per_page' => 'integer',
                'q' => 'string|nullable',
                'sort' => 'string|nullable',
            ], [
                'tenant_id.required' => 'Tenant ID not found',
                'tenant_id.integer' => 'Tenant ID not found',
                'tenant_id.min' => 'Tenant ID not found',
            ]);
        } catch (ValidationException $e) {
            return ApiResponse::error($e->errors()['tenant_id'] ?? ['Tenant ID not found']);
        }

        // DBに存在する tenant_id かチェック
        if (!Tenant::where('id', $request->tenant_id)->exists()) {
            return ApiResponse::error(['Tenant ID not found']);
        }
        
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'threshold_value' => $this->threshold_value,
            'threshold_type' => $this->threshold_type,
            'tenant_id' => $this->tenant_id,
        ];
    }
}