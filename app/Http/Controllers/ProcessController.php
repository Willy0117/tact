<?php

namespace App\Http\Controllers;

use App\Models\Process;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use App\Models\Tenant;

class ProcessController extends Controller
{
    // 一覧ページ
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Process::query();

        if (!$user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($name = $request->input('name')) {
            $query->where('name', 'like', "%{$name}%");
        }

        // 有効/無効フィルタ（デフォルトは「有効のみ」）
        $status = $request->input('status', 'enabled');
        if ($status === 'enabled') {
            $query->where('disabled', 0);
        } elseif ($status === 'disabled') {
            $query->where('disabled', 1);
        }
        // 'all' の場合は絞り込みしない

        $sortBy = $request->input('sort_by', 'display_order');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = intval($request->input('per_page', 20));

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $processes = $query
            ->when(
                $request->tenant_id > 0,
                fn ($q) => $q->where('tenant_id', $request->tenant_id)
            )
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Processes/Index', [
            'processes' => $processes,
            'tenants' => $tenants,
            'user' => $user,
            'filters' => $request->only(['tenant_id', 'name', 'status', 'per_page', 'sort_by', 'sort_dir']),
        ]);
    }

    // Create画面（Editと共用）
    public function create(Request $request)
    {
        $user = auth()->user()->load('roles');

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $process = null;

        // コピー用モードの場合、idを除いたデータを渡す（新規作成扱いにするため）
        if ($request->input('mode') === 'copy' && $processId = $request->input('process_id')) {
            $original = Process::find($processId);
            if ($original) {
                $process = [
                    'id' => null,
                    'name' => $original->name,
                    'threshold_type' => $original->threshold_type,
                    'threshold_value' => $original->threshold_value,
                    'disabled' => $original->disabled,
                    'display_order' => $original->display_order,
                    'tenant_id' => $original->tenant_id,
                ];
            }
        }

        return Inertia::render('Processes/Edit', [
            'filters' => $request->only(['tenant_id', 'name', 'per_page', 'sort_by', 'sort_dir', 'page']),
            'process' => $process,
            'tenants' => $tenants,
            'user' => $user,
            'mode' => $process ? 'copy' : 'create',
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate(
            [
                'name' => ['required', 'string'],
                'threshold_type' => ['required', 'in:upper,lower,none'],
                'threshold_value' => [
                    'nullable',
                    'numeric',
                    'between:-50,150',
                    'required_unless:threshold_type,none',
                ],
                'disabled' => ['required', 'boolean'],
                'display_order' => ['required', 'integer'],
                'tenant_id' => ['nullable', 'exists:tenants,id'],
            ],
            [],
            [
                'name' => __('processes.name'),
                'threshold_type' => __('processes.threshold_type'),
                'threshold_value' => __('processes.threshold_value'),
            ]
        );

        $validated['tenant_id'] = $user->hasRole('Super Admin')
            ? $validated['tenant_id']
            : $user->tenant_id;

        if ($request->threshold_type === 'none') {
            $validated['threshold_value'] = null;
        }

        Process::create($validated);

        return redirect()->route('processes.index', $request->only(['tenant_id', 'name', 'per_page', 'sort_by', 'sort_dir', 'page']))
            ->with('success', __('process has been created.'));
    }

    public function edit(Request $request, Process $process)
    {
        $user = $request->user();

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        return Inertia::render('Processes/Edit', [
            'process' => $process,
            'tenants' => $tenants,
            'user' => $user,
            'mode' => 'edit',
            'filters' => $request->only(['tenant_id', 'name', 'per_page', 'sort_by', 'sort_dir', 'page']),
        ]);
    }

    public function update(Request $request, Process $process)
    {
        $user = $request->user();

        $validated = $request->validate(
            [
                'name' => ['required', 'string'],
                'threshold_type' => ['required', 'in:upper,lower,none'],
                'threshold_value' => [
                    'nullable',
                    'numeric',
                    'between:-50,150',
                    'required_unless:threshold_type,none',
                ],
                'disabled' => ['required', 'boolean'],
                'display_order' => ['required', 'integer'],
                'tenant_id' => ['nullable', 'exists:tenants,id'],
            ],
            [],
            [
                'name' => __('processes.name'),
                'threshold_type' => __('processes.threshold_type'),
                'threshold_value' => __('processes.threshold_value'),
            ]
        );

        $validated['tenant_id'] = $user->hasRole('Super Admin')
            ? $validated['tenant_id']
            : $user->tenant_id;

        if ($request->threshold_type === 'none') {
            $validated['threshold_value'] = null;
        }

        $process->update($validated);

        return redirect()->route('processes.index', $request->only(['tenant_id', 'name', 'per_page', 'sort_by', 'sort_dir', 'page']))
            ->with('success', __('process has been updated.'));
    }

    public function destroy(Request $request, Process $process)
    {
        $process->delete();
        return redirect()->route('processes.index', $request->all())
            ->with('success', __('process has been deleted.'));
    }

    public function bulkDelete(Request $request)
    {
        Process::whereIn('id', $request->ids)->delete();
        return redirect()->route('processes.index', $request->except('ids'))
            ->with('success', __('Selected processes have been deleted.'));
    }

    // テナント紐付け前の名残のメソッド（現状未使用の可能性あり、念のため残置）
    public function byTenant(Request $request)
    {
        $request->validate([
            'tenant' => ['required', 'integer', 'exists:tenants,id'],
        ]);

        return Process::query()
            ->where('tenant_id', $request->tenant)
            ->orderBy('display_order')
            ->get(['id', 'name']);
    }

    public function autocomplete(Request $request)
    {
        $search = $request->input('q');

        $processes = Process::query()
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
                'label' => "{$m->name}",
            ]);

        return response()->json($processes);
    }

    public function autocompleteShow(Process $process)
    {
        return response()->json([
            'id' => $process->id,
            'name' => $process->name,
            'label' => $process->name,
        ]);
    }
}