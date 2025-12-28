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
    public function index(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
            'from'      => 'nullable|date',
            'to'        => 'nullable|date',
            'date_type' => 'nullable|in:serving_date,cooking_date',
            'sort'      => 'nullable|string',
        ]);

        $dateType = $validated['date_type'] ?? 'serving_date';

        // sort=serving_date:asc
        $rawSort = $request->query('sort', "{$dateType}:desc");
        [$sortKey, $dir] = array_pad(explode(':', $rawSort, 2), 2, 'desc');
        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';

        $from = ! empty($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : Carbon::today()->startOfDay();

        $to = ! empty($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : null;

        $query = TemperatureLog::query()
            ->where('temperature_logs.tenant_id', $validated['tenant_id'])
            ->leftJoin('processes', 'processes.id', '=', 'temperature_logs.process_id')
            ->select([
                'temperature_logs.*',
                'processes.name as process_name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | 日付絞り込み
        |--------------------------------------------------------------------------
        */
        if ($dateType === 'serving_date') {

            $query->join('menus', 'menus.id', '=', 'temperature_logs.menu_id')
                ->where('menus.serving_date', '>=', $from);

            if ($to) {
                $query->where('menus.serving_date', '<=', $to);
            }

        } else {
            $query->where('temperature_logs.updated_at', '>=', $from);

            if ($to) {
                $query->where('temperature_logs.updated_at', '<=', $to);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | ソート（sort=xxx:asc）
        |--------------------------------------------------------------------------
        */
        switch ($sortKey) {
            case 'serving_date':
                // 献立日 + 配膳時間
                $query->orderByRaw("
                    TIMESTAMP(
                        menus.serving_date,
                        COALESCE(menus.serving_time, '00:00:00')
                    ) {$dir}
                ");
                break;

            case 'cooking_date':
                // 調理（記録）日
                $query->orderBy("temperature_logs.updated_at", $dir);
                break;

            default:
                // 通常カラム
                $query->orderBy("temperature_logs.{$sortKey}", $dir);
                break;
        }

        // 安定ソート
        $query->orderByDesc('temperature_logs.id');

        $logs = $query
            ->with(['menu', 'process:id,name'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => TemperatureLogResource::collection($logs),
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
            'process_id'        => 'required|integer|exists:processes,id',
            'note'              => 'nullable|string|max:1000',
            'temperatures'      => 'required|array',       // [{"value":90.5,"datetime":"..."}, ...]
            'temperatures.*.value'    => 'required|numeric',
            'temperatures.*.datetime' => 'required|date',
        ]);
        // ---------------------------------------------------------
        // ① handy_no（serial_number）から sensor を特定
        // ---------------------------------------------------------
        $sensor = Sensor::find($validated['sensor_id']);//$sensor = Sensor::where('id', $validated['senser_id'])->first();

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
            'sensor_id'        => $validated['sensor_id'],
            'process_id'       => $validated['process_id'],
            'temperatures'     => $validated['temperatures'],
            'note'             => $validated['note'],
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $log,
            'errors' => null,
        ]);
    }
}
