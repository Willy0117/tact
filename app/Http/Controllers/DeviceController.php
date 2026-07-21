<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Tenant;
use App\Models\Process;

class DeviceController extends Controller
{
    // 一覧ページ
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Device::query()->with('process');

        if (!$user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];
        $processes = Process::all(['id', 'name']);

        // 有効/無効フィルタ（デフォルトは「有効のみ」）
        $status = $request->input('status', 'enabled');

        $devices = $query
            ->when(
                $user->hasRole('Super Admin') && $request->tenant_id,
                fn ($q) => $q->where('tenant_id', $request->tenant_id)
            )
            ->when($status === 'enabled', fn ($q) => $q->where('disabled', 0))
            ->when($status === 'disabled', fn ($q) => $q->where('disabled', 1))
            ->when($request->name, fn($q, $v) => $q->where('name', 'like', "%$v%"))
            ->when($request->process_id, fn($q, $v) => $q->where('process_id', $v))
            ->when(
                $request->measurement !== null && $request->measurement !== '',
                fn ($q) => $q->where('measurement', (int) $request->measurement)
            )
            ->orderBy($request->sort_by ?? 'id', $request->sort_dir ?? 'asc')
            ->paginate(intval($request->input('per_page', 20)))
            ->withQueryString();

        return Inertia::render('Devices/Index', [
            'devices' => $devices,
            'tenants' => $tenants,
            'user' => $user,
            'processes' => $processes,
            'filters' => $request->only(['name', 'process_id', 'measurement', 'status', 'per_page', 'sort_by', 'sort_dir', 'tenant_id']),
        ]);
    }

    // Create画面（Editと共用）
    public function create(Request $request)
    {
        $user = auth()->user()->load('roles');
        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $device = null;

        // コピー用モードの場合、idを除いたデータを渡す（新規作成扱いにするため）
        if ($request->input('mode') === 'copy' && $deviceId = $request->input('device_id')) {
            $original = Device::find($deviceId);
            if ($original) {
                $device = [
                    'id' => null,
                    'name' => $original->name,
                    'process_id' => $original->process_id,
                    'measurement' => $original->measurement,
                    'disabled' => $original->disabled,
                    'display_order' => $original->display_order,
                    'tenant_id' => $original->tenant_id,
                ];
            }
        }

        $processes = Process::query()
            ->when($request->filled('tenant_id'), function ($q) use ($request) {
                $q->where('tenant_id', $request->tenant_id);
            })
            ->get(['id', 'name']);

        return Inertia::render('Devices/Edit', [
            'filters' => $request->only(['name', 'process_id', 'measurement', 'per_page', 'sort_by', 'sort_dir', 'page']),
            'tenants' => $tenants,
            'user' => $user,
            'device' => $device,
            'processes' => $processes,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string'],
            'process_id' => ['required', 'integer', 'exists:processes,id'],
            'measurement' => ['required', 'boolean'],
            'disabled' => ['required', 'boolean'],
            'display_order' => ['required', 'integer'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ], [
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);

        $validated['tenant_id'] = $user->hasRole('Super Admin')
            ? $validated['tenant_id']
            : $user->tenant_id;

        Device::create($validated);

        return redirect()->route('devices.index', $request->input('filters', []))
            ->with('success', __('device has been created.'));
    }

    public function edit(Request $request, Device $device)
    {
        $user = $request->user();

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $processes = Process::query()
            ->when($request->filled('tenant_id'), function ($q) use ($request) {
                $q->where('tenant_id', $request->tenant_id);
            })
            ->get(['id', 'name']);

        return Inertia::render('Devices/Edit', [
            'device' => $device,
            'tenants' => $tenants,
            'user' => $user,
            'processes' => $processes,
            'filters' => $request->only(['name', 'process_id', 'measurement', 'per_page', 'sort_by', 'sort_dir', 'page']),
        ]);
    }

    public function update(Request $request, Device $device)
    {
        $user = $request->user();

        $validated = $request->validate([
            'measurement' => ['required', 'boolean'],
            'name' => ['required', 'string'],
            'process_id' => ['required', 'integer', 'exists:processes,id'],
            'disabled' => ['required', 'boolean'],
            'display_order' => ['required', 'integer'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ], [
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);

        $validated['tenant_id'] = $user->hasRole('Super Admin')
            ? $validated['tenant_id']
            : $user->tenant_id;

        $device->update($validated);

        return redirect()->route('devices.index', $request->input('filters', []))
            ->with('success', __('device has been updated.'));
    }

    public function destroy(Request $request, Device $device)
    {
        $device->delete();
        return redirect()->route('devices.index', $request->all())
            ->with('success', __('device has been deleted.'));
    }

    public function bulkDelete(Request $request)
    {
        Device::whereIn('id', $request->ids)->delete();
        return redirect()->route('devices.index', $request->except('ids'))
            ->with('success', __('Selected devices have been deleted.'));
    }

    public function autocomplete(Request $request)
    {
        $search = $request->input('q');

        $devices = Device::query()
            ->when(auth()->user()->tenant_id, fn($q, $tenantId) =>
                $q->where('tenant_id', $tenantId)
            )
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('display_order', 'asc')
            ->limit(20)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'label' => $m->name,
            ]);

        return response()->json($devices);
    }

    public function autocompleteShow(Device $device)
    {
        return response()->json([
            'id' => $device->id,
            'name' => $device->name,
            'label' => $device->name,
        ]);
    }
}