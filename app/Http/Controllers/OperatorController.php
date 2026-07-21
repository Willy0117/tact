<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use App\Models\Tenant;

class OperatorController extends Controller
{
    // 一覧ページ
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Operator::query();

        if (!$user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        // 有効/無効フィルタ（デフォルトは「有効のみ」）
        $status = $request->input('status', 'enabled');

        $operators = $query
            ->when(
                $user->hasRole('Super Admin') && $request->tenant,
                fn ($q) => $q->where('tenant_id', $request->tenant)
            )
            ->when($status === 'enabled', fn ($q) => $q->where('disabled', 0))
            ->when($status === 'disabled', fn ($q) => $q->where('disabled', 1))
            ->when($request->code, fn($q, $v) => $q->where('code', 'like', "%$v%"))
            ->when($request->name, fn($q, $v) => $q->where('name', 'like', "%$v%"))
            ->orderBy($request->sort_by ?? 'id', $request->sort_dir ?? 'asc')
            ->paginate(intval($request->input('per_page', 20)))
            ->withQueryString();

        return Inertia::render('Operators/Index', [
            'operators' => $operators,
            'tenants' => $tenants,
            'user' => $user,
            'filters' => $request->only(['tenant', 'code', 'name', 'status', 'per_page', 'sort_by', 'sort_dir']),
        ]);
    }

    // Create画面（Editと共用）
    public function create(Request $request)
    {
        $user = auth()->user()->load('roles');

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $operator = null;

        if ($request->input('mode') === 'copy' && $operatorId = $request->input('operator_id')) {
            $original = Operator::find($operatorId);
            if ($original) {
                $operator = [
                    'id' => null,
                    'code' => $original->code,
                    'name' => $original->name,
                    'disabled' => $original->disabled,
                    'display_order' => $original->display_order,
                    'tenant_id' => $original->tenant_id,
                ];
            }
        }

        return Inertia::render('Operators/Edit', [
            'filters' => $request->only(['tenant', 'code', 'name', 'status', 'per_page', 'sort_by', 'sort_dir', 'page']),
            'operator' => $operator,
            'tenants' => $tenants,
            'user' => $user,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'code' => ['required', 'string', Rule::unique('operators')],
            'name' => ['required', 'string'],
            'disabled' => ['required', 'integer'],
            'display_order' => ['required', 'integer'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ], [
            'code.required' => __('validation.required', ['attribute' => __('Code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('Code')]),
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);

        $validated['tenant_id'] = $user->hasRole('Super Admin')
            ? $validated['tenant_id']
            : $user->tenant_id;

        Operator::create($validated);

        return redirect()->route('operators.index', $request->input('filters', []))
            ->with('success', __('operator has been created.'));
    }

    public function edit(Request $request, Operator $operator)
    {
        $user = $request->user();

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        return Inertia::render('Operators/Edit', [
            'operator' => $operator,
            'tenants' => $tenants,
            'user' => $user,
            'filters' => $request->only(['tenant', 'code', 'name', 'status', 'per_page', 'sort_by', 'sort_dir', 'page']),
        ]);
    }

    public function update(Request $request, Operator $operator)
    {
        $user = $request->user();

        $validated = $request->validate([
            'code' => ['required', 'string', Rule::unique('operators')->ignore($operator->id)],
            'name' => ['required', 'string'],
            'disabled' => ['required', 'integer'],
            'display_order' => ['required', 'integer'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ], [
            'code.required' => __('validation.required', ['attribute' => __('Code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('Code')]),
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);

        $validated['tenant_id'] = $user->hasRole('Super Admin')
            ? $validated['tenant_id']
            : $user->tenant_id;

        $operator->update($validated);

        return redirect()->route('operators.index', $request->input('filters', []))
            ->with('success', __('operator has been updated.'));
    }

    public function destroy(Request $request, Operator $operator)
    {
        $operator->delete();
        return redirect()->route('operators.index', $request->all())
            ->with('success', __('operator has been deleted.'));
    }

    public function bulkDelete(Request $request)
    {
        Operator::whereIn('id', $request->ids)->delete();
        return redirect()->route('operators.index', $request->except('ids'))
            ->with('success', __('Selected operators have been deleted.'));
    }

    public function autocomplete(Request $request)
    {
        $search = $request->input('q');

        $operators = Operator::query()
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
                'label' => "{$m->name} ({$m->code})",
            ]);

        return response()->json($operators);
    }

    public function autocompleteShow(Operator $operator)
    {
        return response()->json([
            'id' => $operator->id,
            'name' => $operator->name,
            'label' => "{$operator->name} ({$operator->code})",
        ]);
    }

    public function checkCode(Request $request)
    {
        $exists = Operator::where('code', $request->code)
            ->when($request->id, fn($q) => $q->where('id', '!=', $request->id))
            ->exists();

        return response()->json(['exists' => $exists]);
    }
}