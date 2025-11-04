<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Permission::query();

        // テナント絞り込み（Super Admin は全権限表示）
        if ($user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
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

        return Inertia::render('Permissions/Index', [
            'permissions' => $permissions,
            'filters' => $request->all()
        ]);
    }

    public function create()
    {
        // Edit.vue と兼用する場合は不要
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,NULL,id,tenant_id,' . $request->user()->tenant_id,
        ]);

        $permission = new Permission();
        $permission->name = $request->name;
        $permission->tenant_id = $request->user()->tenant_id;
        $permission->guard_name = 'web';
        $permission->save();

        return redirect()->route('permissions.index', $request->filters ?? []);
    }

    public function edit(Permission $permission)
    {
        // Edit.vue で props として渡す
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id . ',id,tenant_id,' . $request->user()->tenant_id,
        ]);

        $permission->update([
            'name' => $request->name
        ]);

        return redirect()->route('permissions.index', $request->filters ?? []);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('permissions.index');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        Permission::whereIn('id', $ids)->delete();

        return redirect()->route('permissions.index');
    }
}
