<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::query();

        // Super Admin は全テナント、それ以外は自テナント
        if (!Auth::user()->hasRole('Super Admin')) {
            $query->where('tenant_id', Auth::user()->tenant_id);
        }

        // 検索
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // ソート
        $sortField = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // ページネーション
        $roles = $query->paginate($request->get('per_page', 10))
                       ->withQueryString();

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'filters' => $request->only(['search', 'per_page', 'sort', 'order']),
        ]);
    }

    public function create()
    {
        $permissions = Permission::all();
        return Inertia::render('Roles/Edit', [
            'role' => null,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,NULL,id,tenant_id,' . Auth::user()->tenant_id,
            'permissions' => 'array',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'tenant_id' => Auth::user()->tenant_id,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('roles.index')
                         ->with('success', __('Role created successfully.'));
    }

    public function edit(Role $role)
    {
        // 自テナント Role のみ編集可能（Super Admin 除く）
        if (!Auth::user()->hasRole('Super Admin') && $role->tenant_id != Auth::user()->tenant_id) {
            abort(403);
        }

        $permissions = Permission::all();

        return Inertia::render('Roles/Edit', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        if (!Auth::user()->hasRole('Super Admin') && $role->tenant_id != Auth::user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id . ',id,tenant_id,' . $role->tenant_id,
            'permissions' => 'array',
        ]);

        $role->update([
            'name' => $request->name,
        ]);

        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')
                         ->with('success', __('Role updated successfully.'));
    }

    public function destroy(Role $role)
    {
        if (!Auth::user()->hasRole('Super Admin') && $role->tenant_id != Auth::user()->tenant_id) {
            abort(403);
        }

        $role->delete();

        return redirect()->route('roles.index')
                         ->with('success', __('Role deleted successfully.'));
    }

    public function bulkDelete(Request $request)
    {
        $roleIds = $request->input('ids', []);

        $roles = Role::whereIn('id', $roleIds);

        if (!Auth::user()->hasRole('Super Admin')) {
            $roles->where('tenant_id', Auth::user()->tenant_id);
        }

        $roles->delete();

        return redirect()->route('roles.index')
                         ->with('success', __('Selected roles deleted successfully.'));
    }
}
