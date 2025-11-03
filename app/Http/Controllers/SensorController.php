<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class SensorController extends Controller
{
    // 一覧ページ
    public function index(Request $request)
    {
        $query = Sensor::query();

        // 個別検索
        if ($code = $request->input('code')) {
            $query->where('code', 'like', "%{$code}%");
        }
        if ($name = $request->input('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        if ($model = $request->input('model')) {
            $query->where('model', 'like', "%{$model}%");
        }
        if ($serialNumber = $request->input('serial_number')) {
            $query->where('serial_number', 'like', "%{$serialNumber}%");
        }

        // ソート
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // ページあたり件数
        $perPage = intval($request->input('per_page', 10));

        $sensors = Sensor::query()
            ->when($request->code, fn($q,$v)=>$q->where('code','like',"%$v%"))
            ->when($request->name, fn($q,$v)=>$q->where('name','like',"%$v%"))
            ->when($request->model, fn($q,$v)=>$q->where('model','like',"%$v%"))
            ->when($request->serial_number, fn($q,$v)=>$q->where('serial_number','like',"%$v%"))
            ->orderBy($request->sort_by ?? 'id', $request->sort_dir ?? 'asc')
            ->paginate($perPage)
            ->withQueryString(); // 検索条件をページリンクに保持


        return Inertia::render('Sensors/Index', [
            'sensors' => $sensors,
            'filters' => $request->only(['code','name','model','serial_number','per_page','sort_by','sort_dir']),
        ]);
    }

    // Create 画面
    public function create(Request $request)
    {
        $sensor = null;

        // コピー用モードの場合
        if ($request->input('mode') === 'copy' && $sensor_id = $request->input('sensor_id')) {
            $sensor = Sensor::find($sensor_id);
        }

        return Inertia::render('Sensors/Create', [
            'filters' => $request->only(['code','name','model','serial_number','per_page','sort_by','sort_dir','page']),
            'sensor' => $sensor, // コピー元のデータを渡す
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', Rule::unique('sensors')],
            'serial_number' => ['required', 'string', Rule::unique('sensors')],
            'name' => ['required', 'string'],
            'model' => ['nullable', 'string'],
            'disabled' => ['required', 'boolean'],
            'display_order' => ['required', 'integer'],
        ], [
            'code.required' => __('validation.required', ['attribute' => __('Code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('Code')]),
            'serial_number.required' => __('validation.required', ['attribute' => __('Serial Number')]),
            'serial_number.unique' => __('validation.unique', ['attribute' => __('Serial Number')]),
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);

        Sensor::create($validated);

        return redirect()->route('sensors.index', $request->only(['code','name','model','serial_number','per_page','sort_by','sort_dir','page']))
            ->with('success', __('Sensor has been created.'));
    }

    public function edit(Request $request, Sensor $sensor)
    {
        return Inertia::render('Sensors/Edit', [
            'sensor' => $sensor,
            'filters' => $request->only(['code','name','model','serial_number','per_page','sort_by','sort_dir','page'])
        ]);
    }

    public function update(Request $request, Sensor $sensor)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', Rule::unique('sensors')->ignore($sensor->id)],
            'serial_number' => ['required', 'string', Rule::unique('sensors')->ignore($sensor->id)],
            'name' => ['required', 'string'],
            'model' => ['nullable', 'string'],
            'disabled' => ['required', 'boolean'],
            'display_order' => ['required', 'integer'],
        ], [
            'code.required' => __('validation.required', ['attribute' => __('Code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('Code')]),
            'serial_number.required' => __('validation.required', ['attribute' => __('Serial Number')]),
            'serial_number.unique' => __('validation.unique', ['attribute' => __('Serial Number')]),
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);

        $sensor->update($validated);

        return redirect()->route('sensors.index', $request->only(['code','name','model','serial_number','per_page','sort_by','sort_dir','page']))
            ->with('success', __('Sensor has been updated.'));
    }

    public function destroy(Sensor $sensor)
    {
        $sensor->delete();
        return redirect()->route('sensors.index')->with('success', __('Sensor has been deleted.'));
    }

    public function bulkDelete(Request $request)
    {
        Sensor::whereIn('id', $request->ids)->delete();
        return redirect()->route('sensors.index')->with('success', __('Selected sensors have been deleted.'));
    }
    // API Real Check
    public function checkCode(Request $request)
    {
        $exists = Sensor::where('code', $request->code)
            ->when($request->id, fn($q) => $q->where('id','!=',$request->id))
            ->exists();

        return response()->json(['exists' => $exists]);
    }
    public function checkSerialNumber(Request $request)
    {
        $exists = Sensor::where('serial_number', $request->serial_number)
            ->when($request->id, fn($q) => $q->where('id', '!=',$request->id))
            ->exists();

        return response()->json(['exists' => $exists]);
    }


}



