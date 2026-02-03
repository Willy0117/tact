<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Process;
use App\Helpers\ApiResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\ProcessResource;

class ProcessController extends Controller
{
    public function index(Request $request)
    {
        // -------------------------------------------------
        // validation
        // -------------------------------------------------
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
        ]);

        // -------------------------------------------------
        // base query
        // -------------------------------------------------
        $query = Process::where('tenant_id', $validated['tenant_id'])
            ->where('disabled', 1);
        $query->orderBy('display_order', 'asc');

        // -------------------------------------------------
        // no pagination
        // -------------------------------------------------
        $processes = $query->get();

        return ApiResponse::success(
            ProcessResource::collection($processes),
            [
                'page'     => 1,
                'per_page' => $processes->count(),
                'total'    => $processes->count(),
            ]
        );
    }
}
