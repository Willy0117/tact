<?php
namespace App\Http\Controllers;

use App\Models\Temperature;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Process;

class TemperatureController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Temperature::query();

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

        if ($serialNumber = $request->input('serial_number')) {
            $query->where('serial_number', 'like', "%$serialNumber%");
        }

        // ソート
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);


        // ページネーション
        $perPage = intval($request->input('per_page', 20));

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $logs = Temperature::with(['menu', 'sensor', 'device', 'operator']) // ← 献立情報をロード
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Temperatures/Index', [
            'logs' => $logs,
            'tenants' => $tenants,
            'user' => $request->user(),
            'filters' => $request->only(['menu_id','sensor_id','device_id','operator_id','serial_number','per_page','sort_by','sort_dir']),
        ]);
    }
}
