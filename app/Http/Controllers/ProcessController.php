<?php

namespace App\Http\Controllers;

use App\Models\Process;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use App\Models\User; 
use App\Models\Tenant;

class ProcessController extends Controller
{
    // 一覧ページ
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Process::query();
        // テナント絞り込み（Super Admin は全件表示）
        if (!$user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($name = $request->input('name')) {
            $query->where('name', 'like', "%{$name}%");
        }

        // ソート
        $sortBy = $request->input('sort_by', 'display_order');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // ページあたり件数
        $perPage = intval($request->input('per_page', 20));

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $processes = $query
            ->when(
                $request->tenant_id > 0,
                fn ($q) => $q->where('tenant_id', $request->tenant_id)
            )
            ->when($request->name, fn($q,$v)=>$q->where('name','like',"%$v%"))
            ->orderBy($request->sort_by ?? 'display_order', $request->sort_dir ?? 'asc')
            ->paginate($perPage)
            ->withQueryString(); // 検索条件をページリンクに保持


        return Inertia::render('Processes/Index', [
            'processes' => $processes,
            'tenants' => $tenants,
            'user' => $user, // Vue 側で判定に必要
            'filters' => $request->only(['tenant_id','name','per_page','sort_by','sort_dir','tenant_id']),
        ]);
    }

    // Create 画面
    public function create(Request $request)
    {
        $process = null;

        $user = auth()->user()->load('roles');

        // コピー用モードの場合
        if ($request->input('mode') === 'copy' && $process_id = $request->input('process_id')) {
            $process = Process::find($process_id);
        }

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];                     

        return Inertia::render('Processes/Edit', [
            'filters' => $request->only(['tenant_id','name','per_page','sort_by','sort_dir','page']),
            'process' => $process, // コピー元のデータを渡す
            'tenants' => $tenants,
            'user' => $user, // Vue 側で判定に必要
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
                'tenant_id' => ['nullable', 'exists:tenants,id'], // 追加
            ],
            [],
            [
                'name' => __('processes.name'),
                'threshold_type' => __('processes.threshold_type'),
                'threshold_value' => __('processes.threshold_value'),
            ]
        );
        // tenant_id を設定（Super Admin は選択、Tenant Admin は自動）
        $validated['tenant_id'] = $user->hasRole('Super Admin') 
            ? $validated['tenant_id'] 
            : $user->tenant_id;

        if ($request->threshold_type === 'none') {
            $validated['threshold_value'] = null;
        }

        Process::create($validated);

        return redirect()->route('processes.index', $request->only(['tenant_id','name','per_page','sort_by','sort_dir','page']))
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
            'filters' => $request->only(['tenant_id','name','per_page','sort_by','sort_dir','page'])
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
                'tenant_id' => ['nullable', 'exists:tenants,id'], // 追加
            ],
            [],
            [
                'name' => __('processes.name'),
                'threshold_type' => __('processes.threshold_type'),
                'threshold_value' => __('processes.threshold_value'),
            ]
        );
        // tenant_id を設定（Super Admin は選択、Tenant Admin は自動）
        $validated['tenant_id'] = $user->hasRole('Super Admin') 
            ? $validated['tenant_id'] 
            : $user->tenant_id;

        if ($request->threshold_type === 'none') {
            $validated['threshold_value'] = null;
        }

        $process->update($validated);

        return redirect()->route('processes.index', $request->only(['tenant_id','name','per_page','sort_by','sort_dir','page']))
            ->with('success', __('process has been updated.'));
    }

    public function destroy(Process $process)
    {
        $process->delete();
        return redirect()->route('processes.index')->with('success', __('process has been deleted.'));
    }

    public function bulkDelete(Request $request)
    {
        Process::whereIn('id', $request->ids)->delete();
        return redirect()->route('processes.index')->with('success', __('Selected processes have been deleted.'));
    }

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
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('display_order', 'asc')
            ->limit(20)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'label' => "{$m->name}"
            ]);

        return response()->json($processes);
    }    
}