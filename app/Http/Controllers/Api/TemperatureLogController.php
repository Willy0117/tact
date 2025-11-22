<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TemperatureLog;
use App\Models\Sensor;
use App\Models\Device;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\TemperatureLogResource;
use Carbon\Carbon;

class TemperatureLogController extends Controller
{
    // GET /v1/temperature-logs → 温度ログ一覧
    public function index(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
            'from'      => 'nullable|date',
            'to'        => 'nullable|date',
        ]);

        $query = TemperatureLog::where('tenant_id', $validated['tenant_id']);

        // from がなければ今日の0時から
        $from = !empty($validated['from']) 
            ? Carbon::parse($validated['from'])->startOfDay()
            : Carbon::today();

        $query->where('created_at', '>=', $from);

        if (!empty($validated['to'])) {
            $query->where('created_at', '<=', Carbon::parse($validated['to'])->endOfDay());
        }

        $logs = $query->orderByDesc('created_at')->get();

        return response()->json([
            'status' => 'success',
            'data' => TemperatureLogResource::collection($logs),
            'errors' => null,
        ]);
    }
    // POST /v1/temperature-logs → 温度ログ登録
    public function store(Request $request)
    {
        Log::info('TemperatureLogController@store request', $request->all());

        $validated = $request->validate([
            'handy_no'          => 'required|integer',     
            'device_id'         => 'required|integer|exists:devices,id',
            'operator_id'       => 'required|integer|exists:operators,id',
            'dish_id'           => 'required|integer|exists:menus,id',
            'sensor_id'         => 'required|integer|exists:sensors,id',
            'temperatures'      => 'required|array',       // [{"value":90.5,"datetime":"..."}, ...]
            'temperatures.*.value'    => 'required|numeric',
            'temperatures.*.datetime' => 'required|date',
        ]);
        // ---------------------------------------------------------
        // ① handy_no（serial_number）から sensor を特定
        // ---------------------------------------------------------
        $sensor = Sensor::where('serial_number', $validated['handy_no'])->first();

        if (!$sensor) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'errors' => ['Sensor (handy_no) not found.'],
            ], 404);
        }

        // ---------------------------------------------------------
        // ② sensor から tenant_id を確定
        // ---------------------------------------------------------
        $tenantId = $sensor->tenant_id;

        // ---------------------------------------------------------
        // ③ device の tenant_id と矛盾がないかチェック
        // ---------------------------------------------------------
        $device = Device::find($validated['device_id']);

        if (!$device || $device->tenant_id !== $tenantId) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'errors' => ['Device does not belong to this tenant.'],
            ], 400);
        }

        // ---------------------------------------------------------
        // ④ TemperatureLog レコード作成
        // ---------------------------------------------------------
        $log = TemperatureLog::create([
            'tenant_id'        => $tenantId,
            'handy_no'         => $validated['handy_no'],
            'device_id'        => $validated['device_id'],
            'operator_id'      => $validated['operator_id'],
            'menu_id'          => $validated['dish_id'],
            'sensor_id'        => $sensor->id,
            'temperatures'     => $validated['temperatures'],
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $log,
            'errors' => null,
        ]);
    }
}
