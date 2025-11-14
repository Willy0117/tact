<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sensor;
use App\Http\Resources\TenantResource;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'serial_number' => 'required|string|exists:sensors,serial_number',
        ]);

        $sensor = Sensor::where('serial_number', $request->serial_number)->first();

        if (!$sensor) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'errors' => ['Serial number not found'],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new TenantResource($sensor),
            'errors' => null,
        ]);
    }
}

