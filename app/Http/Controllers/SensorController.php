<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Sensor;

class SensorController extends Controller
{
    // 一覧
    public function index(Request $request)
    {
        // 1ページあたりの件数をリクエストから取得、なければ10件
        $perPage = $request->input('per_page', 10);

        $query = Sensor::query();

        if ($request->search) {
            $query->where('code', 'like', "%{$request->search}%")
                ->orWhere('name', 'like', "%{$request->search}%");
        }

        $sensors = $query->orderBy('id', 'desc')->paginate($perPage)
                        ->withQueryString(); // searchやper_pageをURLに保持

        return Inertia::render('Sensors/Index', [
            'sensors' => $sensors,
            'filters' => $request->only('search', 'per_page'),
        ]);
    }
    // 作成フォーム
    public function create()
    {
        return Inertia::render('Sensors/Create');
    }

    // 保存
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:sensors,code',
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:10',
            'serial_number' => 'required|string|max:7|unique:sensors,serial_number',
            'disabled' => 'boolean',
            'display_order' => 'required|integer|min:1',
        ]);

        Sensor::create($data);

        return redirect()->route('sensors.index')->with('success', __('Sensor created successfully.'));
    }

    // 編集フォーム
    public function edit(Sensor $sensor)
    {
        return Inertia::render('Sensors/Edit', [
            'sensor' => $sensor
        ]);
    }

    // 更新
    public function update(Request $request, Sensor $sensor)
    {
        $data = $request->validate([
            'code' => "required|string|max:10|unique:sensors,code,{$sensor->id}",
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:10',
            'serial_number' => "required|string|max:7|unique:sensors,serial_number,{$sensor->id}",
            'disabled' => 'boolean',
            'display_order' => 'required|integer|min:1',
        ]);

        $sensor->update($data);

        return redirect()->route('sensors.index')->with('success', __('Sensor updated successfully.'));
    }

    // 単一削除
    public function destroy(Sensor $sensor)
    {
        $sensor->delete();
        return redirect()->route('sensors.index')->with('success', __('Sensor deleted successfully.'));
    }

    // 複数削除
    public function bulkDelete(Request $request)
    {
        $ids = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:sensors,id',
        ]);

        Sensor::whereIn('id', $ids['ids'])->delete();

        return redirect()->route('sensors.index')->with('success', __('Selected sensors deleted successfully.'));
    }
}



