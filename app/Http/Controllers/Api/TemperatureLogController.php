<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TemperatureLog;
use App\Models\Sensor;
use App\Models\Device;
use App\Models\Menu;
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
            ->leftJoin('menus', 'menus.id', '=', 'temperature_logs.menu_id')
            ->leftJoin('processes', 'processes.id', '=', 'temperature_logs.process_id')
            ->select([
                    'temperature_logs.*',
                    'menus.serving_date',
                    'menus.serving_time',
                    'menus.name as menu_name',
                    'processes.name as process_name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | 日付絞り込み
        |--------------------------------------------------------------------------
        */
        if ($dateType === 'serving_date') {
            $query->whereHas('menu', function ($q) use ($from, $to) {
                $q->when($from, fn($q) => $q->where('serving_date', '>=', $from))
                ->when($to, fn($q) => $q->where('serving_date', '<=', $to));
            });
        } elseif ($dateType === 'cooking_date') { 
            $query->whereHas('menu', function ($q) use ($from, $to) {
                $q->when($from, fn($q) => $q->where('cooking_date', '>=', $from))
                ->when($to, fn($q) => $q->where('cooking_date', '<=', $to));
            });
        } else {
            $query->when($from, fn($q) => $q->whereDate('temperature_logs.created_at', '>=', $from))
                ->when($to, fn($q) => $q->whereDate('temperature_logs.created_at', '<=', $to));
        }
        /*
        |--------------------------------------------------------------------------
        | ソート（sort=xxx:asc）
        |--------------------------------------------------------------------------
        */
        switch ($sortKey) {
            case 'serving_date':
                $query
                    ->orderBy('menus.serving_date', $dir)
                    ->orderBy('menus.serving_time', $dir)
                    ->orderBy('temperature_logs.id', $dir); // ← 同一日時のみの安定用
                break;

            case 'cooking_date':
                // 優先順位：①調理日 → ②調理時間
                $query->orderByRaw('DATE(temperature_logs.created_at) ' . $dir)
                    ->orderByRaw('TIME(temperature_logs.created_at) ' . $dir);
                break;

            default:
                // 通常カラム
                $query->orderBy("temperature_logs.{$sortKey}", $dir);
                break;
        }
       /*
        |--------------------------------------------------------------------------
        | ソート
        |--------------------------------------------------------------------------
        */
        if (!empty($sortKey)) {

            switch ($sortKey) {

                // 配膳日 + 配膳時間
                case 'serving_date':
                    $query->orderBy(
                        Menu::selectRaw(
                            "TIMESTAMP(serving_date, COALESCE(serving_time, '00:00:00'))"
                        )->whereColumn('menus.id', 'temperature_logs.menu_id'),
                        $dir
                    );
                    break;

                // 優先順位：①調理日 → ②調理時間
                case 'cooking_date':
                    $query->orderBy(
                        Menu::selectRaw(
                            "TIMESTAMP(cooking_date, COALESCE(serving_time, '00:00:00'))"
                        )->whereColumn('menus.id', 'temperature_logs.menu_id'),
                        $dir
                    );
                    break;

                default:
                    // 通常カラム
                    $query->orderBy("temperature_logs.{$sortKey}", $dir);
                    break;
            }
        }

        /**
         * sortKey があってもなくても
         * 最後は必ずこれ（安定 & デフォルト）
         */
        $query->orderBy('temperature_logs.created_at', 'desc');


        $logs = $query
            ->with(['menu:id,name', 'process:id,name'])
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
//2026.01.30改良
//　sensor_idを必須から外す
// その代わり、その場合はtenant_idは必須
//  temperatureも必須から外す　温度記録なしの場合もある

        $validated = $request->validate([
            'handy_no'          => 'required|integer',     
            'device_id'         => 'required|integer|exists:devices,id',
            'operator_id'       => 'required|integer|exists:operators,id',
            'dish_id'           => 'required|integer|exists:menus,id',

            'sensor_id' => 'nullable|integer|exists:sensors,id',
            'tenant_id' => 'required_without:sensor_id|integer|exists:tenants,id',

            'process_id'        => 'required|integer|exists:processes,id',
            'note'              => 'nullable|string|max:1000',
            'temperatures'      => 'nullable|array',       // [{"value":90.5,"datetime":"..."}, ...]
            'temperatures.*.value'    => 'required_with:temperatures|numeric',
            'temperatures.*.datetime' => 'required_with:temperatures|date',
        ]);

        if (!empty($validated['sensor_id'])) {
            $sensor = Sensor::find($validated['sensor_id']);
            if (!$sensor) {
                return response()->json([
                    'status' => 'error',
                    'data' => null,
                    'errors' => ['Sensor not found.'],
                ], 404);
            }
            $tenantId = $sensor->tenant_id;
        } else {
            // sensor_id が無い場合は tenant_id を必須にする
            if (empty($validated['tenant_id'])) {
                return response()->json([
                    'status' => 'error',
                    'data' => null,
                    'errors' => ['Either sensor_id or tenant_id is required.'],
                ], 422);
            }
            $tenantId = $validated['tenant_id'];
        }
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
            'sensor_id'        => $validated['sensor_id'] ?? null,
            'process_id'       => $validated['process_id'],
            'temperatures'     => $validated['temperatures'] ?? [],
            'note'             => $validated['note'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $log,
            'errors' => null,
        ]);
    }
}
