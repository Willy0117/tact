<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;

class PermissionController extends Controller
{
    /**
     * 一覧画面
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Permission::query();

        // テナント絞り込み（Super Admin は全件表示、選択時のみ絞り込み）
        if (!$user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        } elseif ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // 検索
        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        // ソート
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'asc');
        $query->orderBy($sort, $direction);

        // ページネーション
        $permissions = $query->paginate($request->input('per_page', 20))
                             ->withQueryString();
        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        return Inertia::render('Permissions/Index', [
            'permissions' => $permissions,
            'filters' => $request->all(),
            'tenants' => $tenants,
            'user' => $user,
        ]);
    }

    /**
     * 編集画面用
     */
    public function edit(Permission $permission)
    {
        $user = auth()->user()->load('roles');

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        return Inertia::render('Permissions/Edit', [
            'permission' => $permission,
            'tenants' => $tenants,
            'user' => $user,
            'filters' => request()->all(),
        ]);
    }

    /**
     * 新規作成画面用
     */
    public function create()
    {
        $user = auth()->user()->load('roles');

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        return Inertia::render('Permissions/Edit', [
            'permission' => null,
            'tenants' => $tenants,
            'user' => $user,
            'filters' => request()->all(),
        ]);
    }

    /**
     * 新規作成
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $tenantId = $user->hasRole('Super Admin') ? $request->tenant_id : $user->tenant_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        Permission::firstOrCreate(
            [
                'name' => $request->name,
                'guard_name' => 'web',
                'tenant_id' => $tenantId
            ]
        );

        return redirect()->route('permissions.index', $request->input('filters', []))
            ->with('success', __('Permission created successfully.'));
    }

    /**
     * 更新
     */
    public function update(Request $request, Permission $permission)
    {
        $user = $request->user();

        $tenantId = $user->hasRole('Super Admin') ? $request->tenant_id : $user->tenant_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $exists = Permission::where('name', $request->name)
            ->where('guard_name', 'web')
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $permission->id)
            ->first();

        if ($exists) {
            return back()->withErrors(['name' => '同じテナント内で既に存在する権限です']);
        }

        $permission->update([
            'name' => $request->name,
            'tenant_id' => $tenantId,
            'guard_name' => 'web',
        ]);

        return redirect()->route('permissions.index', $request->filters ?? []);
    }

    public function assign(Request $request, Permission $permission)
    {
        $user = $request->user();

        $users = User::query()
            ->where('tenant_id', $user->tenant_id)
            ->get(['id', 'name', 'email']);

        return Inertia::render('Permissions/Assign', [
            'permission' => $permission,
            'users' => $users,
            'filters' => $request->all(),
        ]);
    }

    // 割当保存（POST）
    public function assignStore(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $targetUser = User::findOrFail($validated['user_id']);

        $targetUser->givePermissionTo($permission);

        return redirect()->route('permissions.index', $request->all())
            ->with('success', __('Permission assigned.'));
    }

    /**
     * 削除
     */
    public function destroy(Request $request, Permission $permission)
    {
        $permission->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('permissions.index', $request->all());
    }

    /**
     * 複数削除
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        Permission::whereIn('id', $ids)->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('permissions.index', $request->all());
    }
}