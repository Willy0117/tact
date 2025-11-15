<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TemperatureLog;
use App\Models\Device;

class TemperatureLogController extends Controller
{
    // GET /v1/temperature-logs → 温度ログ一覧
    public function index(Request $request)
    {
        $tenantId = $request->query('tenant_id');

        $logs = TemperatureLog::where('tenant_id', $tenantId)
            ->with(['menu', 'process', 'device', 'operator'])
            ->orderByDesc('measured_at')
            ->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $logs,
            'errors' => null
        ]);
    }

    // POST /v1/temperature-logs → 温度ログ登録
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'     => 'required|integer',
            'serial_number' => 'required|string',
            'menu_id'       => 'required|integer|exists:menus,id',
            'process_id'    => 'required|integer|exists:processes,id',
            'operator_id'   => 'required|integer|exists:operators,id',
            'temperature'   => 'required|numeric',
            'measured_at'   => 'required|date',
        ]);

        // serial_number から device と tenant を特定
        $device = Device::where('serial_number', $validated['serial_number'])
                        ->where('tenant_id', $validated['tenant_id'])
                        ->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'errors' => ['Device not found for this tenant']
            ], 404);
        }

        // tenant_id 確認
        if ($device->tenant_id != $validated['tenant_id']) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'errors' => ['Tenant ID mismatch with device']
            ], 400);
        }

        $validated['device_id'] = $device->id;
        unset($validated['serial_number']);

        $log = TemperatureLog::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $log,
            'errors' => null
        ]);
    }
}
