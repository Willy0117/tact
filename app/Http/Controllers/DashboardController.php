<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Temperature;
use App\Models\Tenant;
use App\Models\Menu;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $tenantId = $this->resolveTenantId($user, $request);

        $today = $this->getTemperatureSummary('today', $tenantId);
        $yesterday = $this->getTemperatureSummary('yesterday', $tenantId);
        $month = $this->getTemperatureSummary('month', $tenantId);
        $lastMonth = $this->getTemperatureSummary('last_month', $tenantId);

        $perPage = intval($request->query('per_page', 5));

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $logs = Temperature::query()
            ->when($tenantId > 0, function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->whereDate('created_at', today())
            ->with([
                'menu:id,name',
                'process:id,name',
                'operator:id,name',
            ])
            ->latest()
            ->limit(5)
            ->get();

        $todayMenus = Menu::query()
            ->when($tenantId > 0, fn ($q) => $q->where('menus.tenant_id', $tenantId))
            ->whereDate('menus.serving_date', today())
            ->leftJoin('temperature_logs', 'menus.id', '=', 'temperature_logs.menu_id')
            ->leftJoin('processes', 'temperature_logs.process_id', '=', 'processes.id')
            ->select(
                'menus.id',
                'menus.name',
                'menus.serving_time',
                'menus.tenant_id',
                DB::raw("
                    COUNT(
                        CASE WHEN processes.name = '加熱' THEN temperature_logs.id END
                    ) as heating_count
                "),
                DB::raw("
                    COUNT(
                        CASE WHEN processes.name = '冷却' THEN temperature_logs.id END
                    ) as cooling_count
                ")
            )
            ->groupBy('menus.id', 'menus.name', 'menus.serving_time', 'menus.tenant_id')
            ->orderBy('menus.serving_time')
            ->orderBy('menus.id')
            ->get();
            \Log::info('dashboard debug', [
    'tenantId' => $tenantId,
    'today' => $today,
    'month' => $month,
]);

        return Inertia::render('Dashboard', [
            'today' => $today,
            'yesterday' => $yesterday,
            'month' => $month,
            'lastMonth' => $lastMonth,
            'logs'  => $logs,
            'menus' => $todayMenus,
            'tenants' => $tenants,
            'user' => $user,
        ]);
    }

    private function getTemperatureSummary(string $period, ?int $tenantId = null): array
    {
        $query = Temperature::with('process')
            ->when($tenantId > 0, function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });

        if ($period === 'today') {
            $query->whereDate('created_at', today());
        }

        if ($period === 'yesterday') {
            $query->whereDate('created_at', today()->subDay());
        }

        if ($period === 'month') {
            $query->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month);
        }

        if ($period === 'last_month') {
            $lastMonth = now()->subMonthNoOverflow();
            $query->whereYear('created_at', $lastMonth->year)
                ->whereMonth('created_at', $lastMonth->month);
        }

        $logs = $query->get();

        $success = 0;
        $deviation = 0;

        foreach ($logs as $log) {
            $process = $log->process;

            if (!$process || $process->threshold_type === 'none') {
                continue;
            }

            $okCount = 0;

            foreach ($log->temperatures as $temp) {
                $value = $temp['value'];

                if ($process->name === '冷却') {
                    if ($value <= $process->threshold_value) {
                        $okCount++;
                    }
                } elseif ($process->name === '加熱') {
                    if ($value >= $process->threshold_value) {
                        $okCount++;
                    }
                }
            }
            if ($okCount > 2) {
                $success++;
            } else {
                $deviation++;
            }
        }

        return [
            'success'   => $success,
            'deviation' => $deviation,
            'total'     => $success + $deviation,
        ];
    }

    private function resolveTenantId($user, $request): ?int
    {
        if ($user->tenant_id > 0) {
            return $user->tenant_id;
        }

        if ($request->tenant_id > 0) {
            return $request->tenant_id;
        }

        return null;
    }
}