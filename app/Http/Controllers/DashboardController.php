<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Temperature;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Process;
use App\Models\Menu;
use App\Models\Device;
use App\Models\Sensor;
use App\Models\Operator;
use Carbon\Carbon;

class DashboardController extends Controller
{
   public function index(Request $request)
    {
        $user = $request->user();
        $query = Temperature::query();

        // テナント制限
        if (! $user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        /*
        |--------------------------------------------------------------------------
        | 検索条件（URLクエリから取得）
        |--------------------------------------------------------------------------
        */
        $menuId     = $request->query('menu_id');
        $sensorId   = $request->query('sensor_id');
        $deviceId   = $request->query('device_id');
        $operatorId = $request->query('operator_id');
        $handyNo    = $request->query('handy_no');
        $processId  = $request->query('process_id');

        if ($menuId) {
            $query->where('menu_id', $menuId);
        }
        if ($sensorId) {
            $query->where('sensor_id', $sensorId);
        }
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
        if ($operatorId) {
            $query->where('operator_id', $operatorId);
        }
        if ($handyNo) {
            $query->where('handy_no', $handyNo);
        }
        if ($processId) {
            $query->where('process_id', $processId);
        }

        // 日付絞り込み（デフォルトは今日）
        $dateFrom = $request->query('date_from', Carbon::today()->toDateString());
        $dateTo   = $request->query('date_to', Carbon::today()->toDateString());
        $dateType = $request->query('date_type', 'serving'); // デフォルト献立日

        if ($dateType === 'serving') {
            $query->whereHas('menu', function ($q) use ($dateFrom, $dateTo) {
                $q->when($dateFrom, fn($q) => $q->where('serving_date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->where('serving_date', '<=', $dateTo));
            });
        } elseif ($dateType === 'cooking') { 
            $query->whereHas('menu', function ($q) use ($dateFrom, $dateTo) {
                $q->when($dateFrom, fn($q) => $q->where('cooking_date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->where('cooking_date', '<=', $dateTo));
            });
        } else {
            $query->when($dateFrom, fn($q) => $q->whereDate('temperature_logs.created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('temperature_logs.created_at', '<=', $dateTo));
        }

        /*
        |--------------------------------------------------------------------------
        | ソート
        |--------------------------------------------------------------------------
        */
        $allowedSorts = [
            'menu_date',
            'menu_id',
            'device_id',
            'sensor_id',
            'operator_id',
            'handy_no',
            'process_id',
            'created_at',
        ];

        $sort = $request->query('sort_by', 'created_at');
        $dir  = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        if ($sort === 'menu_date') {
            if ($dateType === 'serving') {
                // 配膳日 + 配膳時間
                $query->orderBy(
                    Menu::selectRaw("TIMESTAMP(serving_date, COALESCE(serving_time, '00:00:00'))")
                        ->whereColumn('menus.id', 'temperature_logs.menu_id'),
                    $dir
                );
            } elseif ($dateType === 'cooking') {
                // 配膳日 + 配膳時間
                $query->orderBy(
                    Menu::selectRaw("TIMESTAMP(cooking_date, COALESCE(serving_time, '00:00:00'))")
                        ->whereColumn('menus.id', 'temperature_logs.menu_id'),
                    $dir
                );
            } else {
                $query->orderBy('temperature_logs.created_at', $dir);
            }
            // 安定ソート
            $query->orderBy('temperature_logs.created_at', 'desc');
        } else {
            $query->orderBy($sort, $dir);
        }

        /*
        |--------------------------------------------------------------------------
        | ページネーション
        |--------------------------------------------------------------------------
        */
        $perPage = intval($request->query('per_page', 20));

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $logs = $query
            ->when(
                $request->tenant_id > 0,
                fn ($q) => $q->where('tenant_id', $request->tenant_id)
            )
            ->with(['menu', 'sensor', 'device', 'operator', 'process'])
            ->paginate($perPage)
            ->withQueryString(); // URL クエリ保持

        /*
        |--------------------------------------------------------------------------
        | Inertia レンダリング
        |--------------------------------------------------------------------------
        */
        return Inertia::render('Dashboard', [
            'logs' => $logs,
            'tenants' => $tenants,
            'user' => $user,
            'filters' => [
                'menu_id'     => $menuId,
                'sensor_id'   => $sensorId,
                'device_id'   => $deviceId,
                'operator_id' => $operatorId,
                'handy_no'    => $handyNo,
                'process_id'  => $processId,
                'per_page'    => $perPage,
                'sort_by'     => $sort,
                'sort_dir'    => $dir,
                'date_type'   => $dateType,
                'date_from'   => $dateFrom,
                'date_to'     => $dateTo,
            ],
        ]);
    }
}



