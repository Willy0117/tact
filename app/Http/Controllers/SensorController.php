<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use App\Models\Tenant;

class SensorController extends Controller
{
    public function index(Request $request)
    {

        $user = $request->user();

        $query = Sensor::query();

        if (!$user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        // 有効/無効フィルタ（デフォルトは「有効のみ」）
        $status = $request->input('status', 'enabled');

        $sensors = $query
            ->when(
                $user->hasRole('Super Admin') && $request->tenant_id,
                fn ($q) => $q->where('tenant_id', $request->tenant_id)
            )
            ->when($status === 'enabled', fn ($q) => $q->where('disabled', 0))
            ->when($status === 'disabled', fn ($q) => $q->where('disabled', 1))
            ->when($request->name, fn($q, $v) => $q->where('name', 'like', "%$v%"))
            ->when($request->model, fn($q, $v) => $q->where('model', 'like', "%$v%"))
            ->when($request->serial_number, fn($q, $v) => $q->where('serial_number', 'like', "%$v%"))
            ->orderBy($request->sort_by ?? 'display_order', $request->sort_dir ?? 'asc')
            ->paginate(intval($request->input('per_page', 20)))
            ->withQueryString();

        return Inertia::render('Sensors/Index', [
            'sensors' => $sensors,
            'tenants' => $tenants,
            'user' => $user,
            'filters' => $request->only(['tenant_id', 'name', 'model', 'serial_number', 'status', 'per_page', 'sort_by', 'sort_dir']),
        ]);
    }

    // Create画面（Editと共用）
    public function create(Request $request)
    {
        $user = auth()->user()->load('roles');

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $sensor = null;

        // コピー用モードの場合、idを除いたデータをsensorとして渡す（新規作成扱いにするため）
        if ($request->input('mode') === 'copy' && $sensorId = $request->input('sensor_id')) {
            $original = Sensor::find($sensorId);
            if ($original) {
                $sensor = [
                    'id' => null,
                    'name' => $original->name,
                    'model' => $original->model,
                    'serial_number' => $original->serial_number,
                    'disabled' => $original->disabled,
                    'display_order' => $original->display_order,
                    'tenant_id' => $original->tenant_id,
                ];
            }
        }

        return Inertia::render('Sensors/Edit', [
            'filters' => $request->only(['name', 'model', 'serial_number', 'status', 'tenant_id', 'per_page', 'sort_by', 'sort_dir', 'page']),
            'sensor' => $sensor,
            'tenants' => $tenants,
            'user' => $user,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'serial_number' => [
                'required',
                'string',
                Rule::unique('sensors')->where(fn ($query) => $query->where('model', $request->model)),
            ],
            'name' => ['required', 'string'],
            'model' => ['required', 'string'],
            'disabled' => ['required', 'boolean'],
            'display_order' => ['required', 'integer'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ], [
            'serial_number.required' => __('validation.required', ['attribute' => __('Serial Number')]),
            'serial_number.unique' => __('validation.unique', ['attribute' => __('Serial Number')]),
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);

        $validated['tenant_id'] = $user->hasRole('Super Admin')
            ? $validated['tenant_id']
            : $user->tenant_id;

        Sensor::create($validated);

        return redirect()->route('sensors.index', $request->input('filters', []))
            ->with('success', __('Sensor has been created.'));
    }

    public function edit(Request $request, Sensor $sensor)
    {
        $user = $request->user();

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        return Inertia::render('Sensors/Edit', [
            'sensor' => $sensor,
            'tenants' => $tenants,
            'user' => $user,
            'filters' => $request->only(['name', 'model', 'serial_number', 'status', 'tenant_id', 'per_page', 'sort_by', 'sort_dir', 'page']),
        ]);
    }

    public function update(Request $request, Sensor $sensor)
    {
        $user = $request->user();

        $validated = $request->validate([
            'serial_number' => [
                'required',
                'string',
                Rule::unique('sensors')
                    ->where(fn ($query) => $query->where('model', $request->model))
                    ->ignore($sensor->id),
            ],
            'name' => ['required', 'string'],
            'model' => ['required', 'string'],
            'disabled' => ['required', 'boolean'],
            'display_order' => ['required', 'integer'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ], [
            'serial_number.required' => __('validation.required', ['attribute' => __('Serial Number')]),
            'serial_number.unique' => __('validation.unique', ['attribute' => __('Serial Number')]),
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);

        $validated['tenant_id'] = $user->hasRole('Super Admin')
            ? $validated['tenant_id']
            : $user->tenant_id;

        $sensor->update($validated);

        return redirect()->route('sensors.index', $request->input('filters', []))
            ->with('success', __('Sensor has been updated.'));
    }

    public function destroy(Request $request, Sensor $sensor)
    {
        $sensor->delete();
        return redirect()->route('sensors.index', $request->all())
            ->with('success', __('Sensor has been deleted.'));
    }

    public function bulkDelete(Request $request)
    {
        Sensor::whereIn('id', $request->ids)->delete();
        return redirect()->route('sensors.index', $request->except('ids'))
            ->with('success', __('Selected sensors have been deleted.'));
    }

    public function autocomplete(Request $request)
    {
        $search = $request->input('q');

        $sensors = Sensor::query()
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
                'label' => "{$m->name} ({$m->serial_number})",
            ]);

        return response()->json($sensors);
    }

    public function autocompleteShow(Sensor $sensor)
    {
        return response()->json([
            'id' => $sensor->id,
            'name' => $sensor->name,
            'label' => "{$sensor->name} ({$sensor->serial_number})",
        ]);
    }

    public function checkSerialNumber(Request $request)
    {
        $exists = Sensor::where('serial_number', $request->serial_number)
            ->where('model', $request->model)
            ->when($request->id, fn($q) => $q->where('id', '!=', $request->id))
            ->exists();

        return response()->json(['exists' => $exists]);
    }
}