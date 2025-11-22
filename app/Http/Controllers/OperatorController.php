<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use App\Models\Tenant;
use App\Models\User; // ← これを追加！

class OperatorController extends Controller
{
    // 一覧ページ
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Operator::query();
        // テナント絞り込み（Super Admin は全件表示）
        if (!$user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        // 個別検索
        if ($code = $request->input('code')) {
            $query->where('code', 'like', "%{$code}%");
        }
        if ($name = $request->input('name')) {
            $query->where('name', 'like', "%{$name}%");
        }

        // ソート
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // ページあたり件数
        $perPage = intval($request->input('per_page', 10));

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];                     

        $operators = Operator::query()
            ->when($request->code, fn($q,$v)=>$q->where('code','like',"%$v%"))
            ->when($request->name, fn($q,$v)=>$q->where('name','like',"%$v%"))
            ->orderBy($request->sort_by ?? 'id', $request->sort_dir ?? 'asc')
            ->paginate($perPage)
            ->withQueryString(); // 検索条件をページリンクに保持


        return Inertia::render('Operators/Index', [
            'operators' => $operators,
            'tenants' => $tenants,
            'user' => $user, // Vue 側で判定に必要
            'filters' => $request->only(['code','name','per_page','sort_by','sort_dir']),
        ]);
    }

    // Create 画面
    public function create(Request $request)
    {
        $operator = null;

        $user = auth()->user()->load('roles');

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];                     

        // コピー用モードの場合
        if ($request->input('mode') === 'copy' && $operator_id = $request->input('operator_id')) {
            $operator = Operator::find($operator_id);
        }

        return Inertia::render('Operators/Create', [
            'filters' => $request->only(['code','name','per_page','sort_by','sort_dir','page']),
            'operator' => $operator, // コピー元のデータを渡す
            'tenants' => $tenants,
            'user' => $user, // Vue 側で判定に必要
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'code' => ['required', 'string', Rule::unique('operators')],
            'name' => ['required', 'string'],
            'disabled' => ['required', 'boolean'],
            'display_order' => ['required', 'integer'],
            'tenant_id' => ['nullable', 'exists:tenants,id'], 
        ], [
            'code.required' => __('validation.required', ['attribute' => __('Code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('Code')]),
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);
        // tenant_id を設定（Super Admin は選択、Tenant Admin は自動）
        $validated['tenant_id'] = $user->hasRole('Super Admin') 
            ? $validated['tenant_id'] 
            : $user->tenant_id;

        Operator::create($validated);

        return redirect()->route('operators.index', $request->only(['code','name','per_page','sort_by','sort_dir','page']))
            ->with('success', __('operator has been created.'));
    }

    public function edit(Request $request, Operator $operator)
    {
        $user = $request->user();

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];                     

        return Inertia::render('Operators/Edit', [
            'operator' => $operator,
            'tenants' => $tenants,
            'user' => $user, // Vue 側で判定に必要
            'filters' => $request->only(['code','name','per_page','sort_by','sort_dir','page'])
        ]);
    }

    public function update(Request $request, Operator $operator)
    {
        $user = $request->user();

        $validated = $request->validate([
            'code' => ['required', 'string', Rule::unique('operators')->ignore($operator->id)],
            'name' => ['required', 'string'],
            'disabled' => ['required', 'boolean'],
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

        return redirect()->route('operators.index', $request->only(['code','name','per_page','sort_by','sort_dir','page']))
            ->with('success', __('operator has been updated.'));
    }

    public function destroy(Operator $operator)
    {
        $operator->delete();
        return redirect()->route('operators.index')->with('success', __('operator has been deleted.'));
    }

    public function bulkDelete(Request $request)
    {
        Operator::whereIn('id', $request->ids)->delete();
        return redirect()->route('operators.index')->with('success', __('Selected operators have been deleted.'));
    }

    public function autocomplete(Request $request)
    {
        $search = $request->input('q');

        $operators = Operator::query()
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'label' => "{$m->name} ({$m->code})"
            ]);

        return response()->json($operators);
    }

    // API Real Check
    public function checkCode(Request $request)
    {
        $exists = Operator::where('code', $request->code)
            ->when($request->id, fn($q) => $q->where('id','!=',$request->id))
            ->exists();

        return response()->json(['exists' => $exists]);
    }

}
