<?php
namespace App\Http\Controllers;

use App\Models\Temperature;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Process;
use App\Models\Menu;
use App\Models\Device;
use App\Models\Sensor;
use App\Models\Operator;
use Carbon\Carbon;

class TemperatureController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Temperature::query();
        if (! $user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }
        // 検索
        if ($menuId = $request->input('menu_id')) {
            $query->where('menu_id', $menuId);
        }
        if ($sensorId = $request->input('sensor_id')) {
            $query->where('sensor_id', $sensorId);
        }
        if ($deviceId = $request->input('device_id')) {
            $query->where('device_id', $deviceId);
        }
        if ($operatorId = $request->input('operator_id')) {
            $query->where('operator_id', $operatorId);
        }

        if ($handyNo = $request->input('handy_no')) {
            $query->where('handy_no', $handyNo);
        }
        if ($processId = $request->input('process_id')) {
            $query->where('process_id', $processId);
        }

        // 日付絞り込み（献立日 or 調理日）
        $dateFrom = $request->input('date_from', Carbon::today()->toDateString());
        $dateTo   = $request->input('date_to', Carbon::today()->toDateString());
        $dateType = $request->input('date_type', 'serving'); // デフォルト献立日
        /*
        |--------------------------------------------------------------------------
        | 日付絞り込み
        |--------------------------------------------------------------------------
        */
        if ($dateType === 'serving') {

            $query->whereHas('menu', function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) {
                    $q->where('serving_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->where('serving_date', '<=', $dateTo);
                }
            });

        } else {
            // cooking：ログの記録日で絞り込み
            if ($dateFrom) {
                $query->whereDate('temperature_logs.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('temperature_logs.created_at', '<=', $dateTo);
            }
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
            } else {
                // cooking：記録時刻
                $query->orderBy('temperature_logs.created_at', $dir);
            }

            // 安定ソート
            $query->orderBy('temperature_logs.created_at', 'desc');

        } else {
            $query->orderBy($sort, $dir);
        }
        // ページネーション
        $perPage = intval($request->input('per_page', 20));

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $logs = $query->with(['menu', 'sensor', 'device', 'operator', 'process']) // ← 献立情報をロード
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Temperatures/Index', [
            'logs' => $logs,
            'tenants' => $tenants,
            'user' => $request->user(),
            'filters' => [
                'menu_id'     => $request->input('menu_id'),
                'sensor_id'   => $request->input('sensor_id'),
                'device_id'   => $request->input('device_id'),
                'operator_id' => $request->input('operator_id'),
                'handy_no'    => $request->input('handy_no'),
                'process_id'  => $request->input('process_id'),
                'per_page'    => $perPage,
                'sort_by'     => $sort,
                'sort_dir'    => $dir,
                'date_type'   => $dateType,
                'date_from'   => $dateFrom,
                'date_to'     => $dateTo,
            ],
        ]);
    }

    public function edit(Temperature $temperature)
    {
        $temperature->load([
            'menu:id,name',
            'device:id,name',
            'operator:id,name',
            'sensor:id,name',
            'process:id,name',
        ]);

        return Inertia::render('Temperatures/Edit', [
            'temperature' => $temperature,
        ]);
    }

    public function update(Request $request, Temperature $temperature)
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string'],
            'temperatures' => ['required', 'array'],
            'temperatures.*.datetime' => ['required', 'date'],
            'temperatures.*.value' => ['required', 'numeric'],
        ]);

        $temperature->update($validated);

        return redirect()->route('temperatures.index');
    }


    public function updateNote(Request $request, Temperature $temperature)
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $temperature->update([
            'note' => $validated['note'],
        ]);

        return back(); // 一覧に戻す（Inertia的に正解）
    }
}
