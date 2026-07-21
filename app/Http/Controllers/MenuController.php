<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MenuController extends Controller
{
    // 一覧ページ
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Menu::query();

        if (!$user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $sortBy = $request->input('sort_by', 'serving_date');
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';
        $perPage = intval($request->input('per_page', 20));

        $menus = $query
            ->when(
                $user->hasRole('Super Admin') && $request->tenant_id,
                fn ($q) => $q->where('tenant_id', $request->tenant_id)
            )
            ->when($request->name, fn($q, $v) => $q->where('name', 'like', "%$v%"))
            ->when($request->serving_date_from, fn($q, $v) => $q->where('serving_date', '>=', $v))
            ->when($request->serving_date_to, fn($q, $v) => $q->where('serving_date', '<=', $v))
            ->when($request->cooking_date_from, fn($q, $v) => $q->where('cooking_date', '>=', $v))
            ->when($request->cooking_date_to, fn($q, $v) => $q->where('cooking_date', '<=', $v))
            ->when($request->materials, fn($q, $v) => $q->where('materials', 'like', "%$v%"))
            ->when(
                in_array($sortBy, ['serving_date', 'cooking_date']),
                function ($q) use ($sortBy, $sortDir) {
                    $q->orderBy($sortBy, $sortDir)->orderBy('serving_time', $sortDir);
                },
                function ($q) use ($sortBy, $sortDir) {
                    $q->orderBy($sortBy, $sortDir);
                }
            )
            ->paginate($perPage)
            ->withQueryString();

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        return Inertia::render('Menus/Index', [
            'menus' => $menus,
            'tenants' => $tenants,
            'user' => $user,
            'filters' => $request->only([
                'serving_date_from', 'serving_date_to',
                'cooking_date_from', 'cooking_date_to',
                'name', 'materials', 'per_page', 'sort_by', 'sort_dir', 'tenant_id',
            ]),
            'redirect_to' => '',
        ]);
    }

    // Create画面（Editと共用）
    public function create(Request $request)
    {
        $user = $request->user();
        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $menu = null;

        if ($request->input('mode') === 'copy' && $menuId = $request->input('menu_id')) {
            $original = Menu::find($menuId);
            if ($original) {
                $menu = [
                    'id' => null,
                    'serving_date' => $original->serving_date,
                    'serving_time' => $original->serving_time,
                    'name' => $original->name,
                    'materials' => $original->materials,
                    'cooking_date' => $original->cooking_date,
                    'tenant_id' => $original->tenant_id,
                ];
            }
        }

        return Inertia::render('Menus/Edit', [
            'filters' => $request->only([
                'serving_date_from', 'serving_date_to',
                'cooking_date_from', 'cooking_date_to',
                'name', 'materials', 'per_page', 'sort_by', 'sort_dir', 'page', 'tenant_id',
            ]),
            'menu' => $menu,
            'tenants' => $tenants,
            'user' => $user,
            'redirect_to' => $request->query('redirect_to', ''),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'serving_date' => ['required', 'date'],
            'serving_time' => ['required', 'string'],
            'name' => ['required', 'string'],
            'materials' => ['nullable', 'string'],
            'cooking_date' => ['required', 'date'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ], [
            'serving_date.required' => __('validation.required', ['attribute' => __('配膳日')]),
            'name.required' => __('validation.required', ['attribute' => __('料理名')]),
            'cooking_date.required' => __('validation.required', ['attribute' => __('調理日')]),
        ]);

        $validated['tenant_id'] = $user->hasRole('Super Admin')
            ? $validated['tenant_id']
            : $user->tenant_id;

        Menu::create($validated);

        $redirectTo = $request->input('redirect_to');

        if ($redirectTo) {
            return redirect($redirectTo)->with('success', __('menu has been created.'));
        }

        return redirect()->route('menus.index', $request->input('filters', []))
            ->with('success', __('menu has been created.'));
    }

    public function edit(Request $request, Menu $menu)
    {
        $user = $request->user();
        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        return Inertia::render('Menus/Edit', [
            'menu' => $menu,
            'tenants' => $tenants,
            'user' => $user,
            'filters' => $request->only([
                'serving_date_from', 'serving_date_to',
                'cooking_date_from', 'cooking_date_to',
                'name', 'materials', 'per_page', 'sort_by', 'sort_dir', 'page', 'tenant_id',
            ]),
            'redirect_to' => $request->query('redirect_to', ''),
        ]);
    }

    public function update(Request $request, Menu $menu)
    {
        $user = $request->user();

        $validated = $request->validate([
            'serving_date' => ['required', 'date'],
            'serving_time' => ['required', 'string'],
            'name' => ['required', 'string'],
            'materials' => ['nullable', 'string'],
            'cooking_date' => ['required', 'date'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ], [
            'serving_date.required' => __('validation.required', ['attribute' => __('配膳日')]),
            'name.required' => __('validation.required', ['attribute' => __('料理名')]),
            'cooking_date.required' => __('validation.required', ['attribute' => __('調理日')]),
        ]);

        $validated['tenant_id'] = $user->hasRole('Super Admin')
            ? $validated['tenant_id']
            : $user->tenant_id;

        $menu->update($validated);

        $redirectTo = $request->input('redirect_to');

        if ($redirectTo) {
            return redirect($redirectTo)->with('success', __('menu has been updated.'));
        }

        return redirect()->route('menus.index', $request->input('filters', []))
            ->with('success', __('menu has been updated.'));
    }

    public function destroy(Request $request, Menu $menu)
    {
        $menu->delete();
        return redirect()->route('menus.index', $request->all())
            ->with('success', __('menu has been deleted.'));
    }

    public function bulkDelete(Request $request)
    {
        Menu::whereIn('id', $request->ids)->delete();
        return redirect()->route('menus.index', $request->except('ids'))
            ->with('success', __('Selected menus have been deleted.'));
    }

    public function showImportForm()
    {
        return Inertia::render('Menus/Import');
    }

    public function importExcel(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => '認証されていません'], 401);
        }

        $tenantId = $user->tenant_id ?? 1;
        $menus = $request->menus ?? [];

        foreach ($menus as $m) {
            $servingTime = $m['serving_time'];

            if ($servingTime) {
                $servingTime = Carbon::parse($servingTime)->format('H:i');
            }
            Menu::create([
                'tenant_id' => $tenantId,
                'name' => $m['name'],
                'serving_date' => $m['serving_date'],
                'serving_time' => $servingTime,
                'cooking_date' => $m['cooking_date'] ?? $m['serving_date'],
                'materials' => null,
                'disabled' => 1,
                'display_order' => 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => '献立データを保存しました',
        ]);
    }

    public function weekly(Request $request)
    {
        $weekStart = $request->input('weekStart');
        $startDate = $weekStart ? Carbon::parse($weekStart) : now()->startOfWeek(Carbon::MONDAY);
        $endDate = $startDate->copy()->addDays(6);

        $user = $request->user();
        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $query = Menu::query();

        if (!$user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $query->whereBetween('serving_date', [$startDate, $endDate]);

        $menus = (clone $query)
            ->when(
                $user->hasRole('Super Admin') && $request->tenant_id,
                fn ($q) => $q->where('tenant_id', $request->tenant_id)
            )
            ->orderBy('serving_time')
            ->get()
            ->groupBy(function ($menu) {
                return $menu->serving_date->toDateString();
            })
            ->map(function ($dayGroup) {
                return $dayGroup->groupBy(function ($menu) {
                    return Carbon::parse($menu->serving_time)->format('H:i');
                });
            });

        $servingTimes = (clone $query)
            ->selectRaw("DATE_FORMAT(serving_time, '%H:%i') as serving_time")
            ->distinct()
            ->orderBy('serving_time')
            ->pluck('serving_time');

        return Inertia::render('Menus/Weekly', [
            'user' => $user,
            'tenant_id' => $request->tenant_id,
            'tenants' => $tenants,
            'menuData' => $menus,
            'servingTimes' => $servingTimes,
            'weekStart' => $startDate->toDateString(),
            'redirect_to' => route('menus.weekly', ['weekStart' => $startDate->toDateString()]),
        ]);
    }

    public function autocomplete(Request $request)
    {
        $search = $request->input('q');

        $menus = Menu::query()
            ->when(auth()->user()->tenant_id, fn($q, $tenantId) =>
                $q->where('tenant_id', $tenantId)
            )
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('serving_date', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'label' => "{$m->name} ({$m->serving_date})",
            ]);

        return response()->json($menus);
    }

    public function autocompleteShow(Menu $menu)
    {
        return response()->json([
            'id' => $menu->id,
            'name' => $menu->name,
            'label' => "{$menu->name} ({$menu->serving_date})",
        ]);
    }
}