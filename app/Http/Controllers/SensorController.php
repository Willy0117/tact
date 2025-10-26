<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SensorController extends Controller
{
    // 一覧ページ
    public function index(Request $request)
    {
        $query = Sensor::query();

        // 検索
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
        }

        // ソート
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // ページあたり件数
        $perPage = $request->input('per_page', 10);
        $sensors = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Sensors/Index', [
            'sensors' => $sensors,
            'filters' => $request->only(['search','per_page','sort_by','sort_dir']),
        ]);
    }

    // 作成フォーム
    public function create(Request $request)
    {
        return Inertia::render('Sensors/Create', [
            'filters' => $request->only(['search','per_page','sort_by','sort_dir']),
        ]);
    }

    // 保存
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:10',
            'serial_number' => 'required|string|max:7',
            'disabled' => 'boolean',
            'display_order' => 'integer',
        ]);

        Sensor::create([
            'code' => $request->code,
            'name' => $request->name,
            'model' => $request->model,
            'serial_number' => $request->serial_number,
            'disabled' => $request->disabled ?? 0,
            'display_order' => $request->display_order ?? 1,
        ]);

        return redirect()->route('sensors.index', $request->only(['search','per_page','sort_by','sort_dir']));
    }

    // 編集フォーム
    public function edit(Sensor $sensor, Request $request)
    {
        return Inertia::render('Sensors/Edit', [
            'sensor' => $sensor,
            'filters' => $request->only(['search','per_page','sort_by','sort_dir']),
        ]);
    }

    // 更新
    public function update(Request $request, Sensor $sensor)
    {
        $request->validate([
            'code' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:10',
            'serial_number' => 'required|string|max:7',
            'disabled' => 'boolean',
            'display_order' => 'integer',
        ]);

        $sensor->update([
            'code' => $request->code,
            'name' => $request->name,
            'model' => $request->model,
            'serial_number' => $request->serial_number,
            'disabled' => $request->disabled ?? 0,
            'display_order' => $request->display_order ?? 1,
        ]);

        return redirect()->route('sensors.index', $request->only(['search','per_page','sort_by','sort_dir']));
    }

    // 複数削除
    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);
        Sensor::whereIn('id', $ids)->delete();
        return redirect()->route('sensors.index', $request->only(['search','per_page','sort_by','sort_dir']));
    }

    // 単一削除（必要なら）
    public function destroySingle(Sensor $sensor, Request $request)
    {
        $sensor->delete();
        return redirect()->route('sensors.index', $request->only(['search','per_page','sort_by','sort_dir']));
    }
}



