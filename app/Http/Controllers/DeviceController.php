<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    // 一覧ページ
    public function index(Request $request)
    {
        $query = Device::query();

        // 個別検索
        if ($code = $request->input('code')) {
            $query->where('code', 'like', "%{$code}%");
        }
        if ($name = $request->input('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        if ($process = $request->input('process')) {
            $query->where('process', 'like', "%{$process}%");
        }
        if ($measurement = $request->input('measurement')) {
            $query->where('measurement', 'like', "%{$measurement}%");
        }

        // ソート
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // ページあたり件数
        $perPage = intval($request->input('per_page', 10));

        $devices = Device::query()
            ->when($request->code, fn($q,$v)=>$q->where('code','like',"%$v%"))
            ->when($request->name, fn($q,$v)=>$q->where('name','like',"%$v%"))
            ->when($request->process, fn($q,$v)=>$q->where('process','like',"%$v%"))
            ->when($request->measurement, fn($q,$v)=>$q->where('measurement','like',"%$v%"))
            ->orderBy($request->sort_by ?? 'id', $request->sort_dir ?? 'asc')
            ->paginate($perPage)
            ->withQueryString(); // 検索条件をページリンクに保持


        return Inertia::render('Devices/Index', [
            'devices' => $devices,
            'filters' => $request->only(['code','name','process','measurement','per_page','sort_by','sort_dir']),
        ]);
    }

    // Create 画面
    public function create(Request $request)
    {
        $device = null;

        // コピー用モードの場合
        if ($request->input('mode') === 'copy' && $device_id = $request->input('device_id')) {
            $device = Device::find($device_id);
        }

        return Inertia::render('Devices/Create', [
            'filters' => $request->only(['code','name','process','measurement','per_page','sort_by','sort_dir','page']),
            'device' => $device, // コピー元のデータを渡す
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', Rule::unique('devices')],
            'measurement' => ['required', 'string', Rule::unique('devices')],
            'name' => ['required', 'string'],
            'process' => ['nullable', 'string'],
            'disabled' => ['required', 'boolean'],
            'display_order' => ['required', 'integer'],
        ], [
            'code.required' => __('validation.required', ['attribute' => __('Code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('Code')]),
            'measurement.required' => __('validation.required', ['attribute' => __('Measurement')]),
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);

        Device::create($validated);

        return redirect()->route('devices.index', $request->only(['code','name','process','measurement','per_page','sort_by','sort_dir','page']))
            ->with('success', __('device has been created.'));
    }

    public function edit(Request $request, Device $device)
    {
        return Inertia::render('Devices/Edit', [
            'device' => $device,
            'filters' => $request->only(['code','name','process','measurement','per_page','sort_by','sort_dir','page'])
        ]);
    }

    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', Rule::unique('devices')->ignore($device->id)],
            'measurement' => ['required', 'string'],
            'name' => ['required', 'string'],
            'process' => ['nullable', 'string'],
            'disabled' => ['required', 'boolean'],
            'display_order' => ['required', 'integer'],
        ], [
            'code.required' => __('validation.required', ['attribute' => __('Code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('Code')]),
            'measurement.required' => __('validation.required', ['attribute' => __('Measurement')]),
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);

        $device->update($validated);

        return redirect()->route('devices.index', $request->only(['code','name','process','measurement','per_page','sort_by','sort_dir','page']))
            ->with('success', __('device has been updated.'));
    }

    public function destroy(Device $device)
    {
        $device->delete();
        return redirect()->route('devices.index')->with('success', __('device has been deleted.'));
    }

    public function bulkDelete(Request $request)
    {
        Device::whereIn('id', $request->ids)->delete();
        return redirect()->route('devices.index')->with('success', __('Selected devices have been deleted.'));
    }
    // API Real Check
    public function checkCode(Request $request)
    {
        $exists = Device::where('code', $request->code)
            ->when($request->id, fn($q) => $q->where('id','!=',$request->id))
            ->exists();

        return response()->json(['exists' => $exists]);
    }

}

