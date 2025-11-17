<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TemperatureLog;
use App\Models\Tenant;

class TemperatureLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = TemperatureLog::query();

        if (!$user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($menuId = $request->input('menu_id')) {
            $query->where('menu_id', $menuId);
        }
        if ($serialNumber = $request->input('serial_number')) {
            $query->where('serial_number', $serialNumber);
        }

        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = intval($request->input('per_page', 20));
        $logs = $query->paginate($perPage)->withQueryString();
dd($logs);

        return Inertia::render('Dashboard/TemperatureLogs', [
            'logs' => $logs,
            'filters' => $request->only(['menu_id', 'serial_number', 'model', 'per_page','sort_by','sort_dir']),
            'user' => $user,
        ]);
    }
}



