<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    // 一覧ページ
    public function index(Request $request)
    {
        $query = Menu::query();
        if ($dish_name = $request->input('dish_name')) {
            $query->where('dish_name', 'like', "%{$dish_name}%");
        }
        if ($process = $request->input('process')) {
            $query->where('process', 'like', "%{$process}%");
        }

        // 日付検索
        if ($serving_date_from = $request->input('serving_date_from')) {
            $query->where('serving_date', '>=', $serving_date_from);
        }
        if ($serving_date_to = $request->input('serving_date_to')) {
            $query->where('serving_date', '<=', $serving_date_to);
        }
        if ($cooking_date_from = $request->input('cooking_date_from')) {
            $query->where('cooking_date', '>=', $cooking_date_from);
        }
        if ($cooking_date_to = $request->input('cooking_date_to')) {
            $query->where('cooking_date', '<=', $cooking_date_to);
        }
        // ソート
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // ページあたり件数
        $perPage = intval($request->input('per_page', 10));

        $menus = Menu::query()
            ->when($request->dish_name, fn($q, $v) => $q->where('dish_name', 'like', "%$v%"))
            ->when($request->process, fn($q, $v) => $q->where('process', 'like', "%$v%"))
            ->when($request->serving_date_from, fn($q, $v) => $q->where('serving_date', '>=', $v))
            ->when($request->serving_date_to, fn($q, $v) => $q->where('serving_date', '<=', $v))
            ->when($request->cooking_date_from, fn($q, $v) => $q->where('cooking_date', '>=', $v))
            ->when($request->cooking_date_to, fn($q, $v) => $q->where('cooking_date', '<=', $v))
            ->orderBy($request->sort_by ?? 'id', $request->sort_dir ?? 'asc')
            ->paginate($perPage)
            ->withQueryString(); // 検索条件をページリンクに保持


        return Inertia::render('Menus/Index', [
            'menus' => $menus,
            'filters' => $request->only([
                'serving_date_from', 'serving_date_to',
                'cooking_date_from', 'cooking_date_to',
                'dish_name', 'process', 'per_page', 'sort_by', 'sort_dir'
            ]),
        ]);
    }

    // Create 画面
    public function create(Request $request)
    {
        $menu = null;

        // コピー用モードの場合
        if ($request->input('mode') === 'copy' && $menu_id = $request->input('menu_id')) {
            $menu = Menu::find($menu_id);
        }

        return Inertia::render('Menus/Create', [
            'filters' => $request->only([
                'serving_date_from', 'serving_date_to',
                'cooking_date_from', 'cooking_date_to',
                'dish_name', 'process', 'per_page', 'sort_by', 'sort_dir','page'
            ]),
            'menu' => $menu, // コピー元のデータを渡す
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'serving_date' => ['required','date'],
            'serving_time' => ['required','string'],
            'dish_name' => ['required','string'],
            'process' => ['nullable','string'],
            'cooking_date' => ['required','date'],
        ], [
            'serving_date.required' => __('validation.required', ['attribute' => __('配膳日')]),
            'dish_name.required' => __('validation.required', ['attribute' => __('料理名')]),
            'cooking_date.required' => __('validation.required', ['attribute' => __('調理日')]),
        ]);
        Menu::create($validated);
        return redirect()->route('menus.index', $request->only([
                'serving_date_from', 'serving_date_to',
                'cooking_date_from', 'cooking_date_to',
                'dish_name', 'process', 'equipment_name', 'measurement_device',
                'per_page', 'sort_by', 'sort_dir','page'
            ]))->with('success', __('menu has been created.'));
    }

    public function edit(Request $request, Menu $menu)
    {
        return Inertia::render('Menus/Edit', [
            'menu' => $menu,
            'filters' => $request->only([
                'serving_date_from', 'serving_date_to',
                'cooking_date_from', 'cooking_date_to',
                'dish_name', 'process', 'per_page', 'sort_by', 'sort_dir','page'
            ]),
        ]);
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'serving_date' => ['required','date'],
            'serving_time' => ['required','string'],
            'dish_name' => ['required','string'],
            'process' => ['nullable','string'],
            'cooking_date' => ['required','date'],
        ], [
            'serving_date.required' => __('validation.required', ['attribute' => __('配膳日')]),
            'dish_name.required' => __('validation.required', ['attribute' => __('料理名')]),
            'cooking_date.required' => __('validation.required', ['attribute' => __('調理日')]),
        ]);
        $menu->update($validated);

        return redirect()->route('menus.index', $request->only([
            'serving_date_from', 'serving_date_to',
            'cooking_date_from', 'cooking_date_to',
            'dish_name', 'process', 'per_page', 'sort_by', 'sort_dir', 'page'
        ]))->with('success', __('menu has been updated.'));
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return redirect()->route('menus.index')->with('success', __('menu has been deleted.'));
    }

    public function bulkDelete(Request $request)
    {
        Menu::whereIn('id', $request->ids)->delete();
        return redirect()->route('menus.index')->with('success', __('Selected menus have been deleted.'));
    }
    // EXCEL IMPORT
    public function importExcel()
    {
        // Excel取込画面を返す
        return view('menus.import');
    }

    
    public function weekly()
    {
        $startDate = now()->startOfWeek();
        $endDate = $startDate->copy()->addDays(6);

        // メニューを取得し「日付 → 時間 → 配列」に変換
        $menus = Menu::whereBetween('serving_date', [$startDate, $endDate])
            ->orderBy('serving_time')
            ->get()
            ->groupBy(function ($menu) {
                return $menu->serving_date->toDateString();
            })
            ->map(function ($dayGroup) {
                return $dayGroup->groupBy(function ($menu) {
                    return \Carbon\Carbon::parse($menu->serving_time)->format('H:i'); // ← 秒削除
                });
            });

        // distinct で使用する時間も同様に "HH:MM" に揃える
        $servingTimes = Menu::whereBetween('serving_date', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(serving_time, '%H:%i') as serving_time")
            ->distinct()
            ->orderBy('serving_time')
            ->pluck('serving_time');

        return Inertia::render('Menus/Weekly', [
            'menuData' => $menus,
            'servingTimes' => $servingTimes,
            'weekStart' => $startDate->toDateString(),
        ]);
    }

}