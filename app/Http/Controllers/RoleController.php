<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Tenant;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Role::with('permissions');

        if (! $user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        // 検索
        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        // ソート・ページング
        $sortField = $request->get('sort', 'id');
        $sortOrder = $request->get('direction', 'asc');

        $roles = $query->orderBy($sortField, $sortOrder)
                       ->paginate($request->get('per_page', 20))
                       ->withQueryString();

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'filters' => $request->only(['name', 'per_page', 'sort', 'direction']),
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        $permissions = $user->hasRole('Super Admin')
            ? Permission::all()
            : Permission::where('tenant_id', $user->tenant_id)->orWhereNull('tenant_id')->get();

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        return Inertia::render('Roles/Edit', [
            'role' => null,
            'permissions' => $permissions,
            'tenants' => $tenants,
            'user' => $user,
            'filters' => $request->all(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $tenantId = $user->hasRole('Super Admin')
            ? ($request->tenant_id ?? null)
            : $user->tenant_id;

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,NULL,id,tenant_id,' . ($tenantId ?? 'NULL'),
            'permissions' => 'array',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'tenant_id' => $tenantId,
            'guard_name' => 'web',
        ]);

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('roles.index', $request->filters ?? [])
            ->with('success', __('Role created successfully.'));
    }

    public function edit(Request $request, Role $role)
    {
        $user = Auth::user();

        if (! $user->hasRole('Super Admin') && $role->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        $permissions = ($user->hasRole('Super Admin')
            ? Permission::all()
            : Permission::where('tenant_id', $user->tenant_id)
                ->orWhereNull('tenant_id')->get()
        )->map(function ($perm) {
            $tenantName = $perm->tenant_id ? Tenant::find($perm->tenant_id)?->name : null;
            return [
                'id' => $perm->id,
                'name' => $perm->name,
                'tenant_id' => $perm->tenant_id,
                'tenant_label' => $tenantName ? '(' . $tenantName . ')' : '(Global)',
            ];
        });

        $tenants = $user->hasRole('Super Admin') ? Tenant::all() : [];

        $role->load('permissions');

        return Inertia::render('Roles/Edit', [
            'role' => $role,
            'permissions' => $permissions,
            'tenants' => $tenants,
            'user' => $user,
            'filters' => $request->all(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $user = Auth::user();

        if (! $user->hasRole('Super Admin') && $role->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id . ',id,tenant_id,' . ($role->tenant_id ?? 'NULL'),
            'permissions' => 'array',
        ]);

        // Super Adminはtenant_idの変更も許可
        $updateData = ['name' => $request->name];
        if ($user->hasRole('Super Admin') && $request->has('tenant_id')) {
            $updateData['tenant_id'] = $request->tenant_id ?: null;
        }
        $role->update($updateData);

        $permissions = $request->permissions ?? [];
        if (! $user->hasRole('Super Admin')) {
            $permissions = Permission::whereIn('id', $permissions)
                                    ->where('tenant_id', $user->tenant_id)
                                    ->pluck('id')
                                    ->toArray();
        }

        $role->syncPermissions($permissions);

        return redirect()->route('roles.index', $request->filters ?? [])
            ->with('success', __('Role updated successfully.'));
    }

    public function destroy(Request $request, Role $role)
    {
        $user = Auth::user();

        if (! $user->hasRole('Super Admin') && $role->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        $role->delete();

        return redirect()->route('roles.index', $request->all())
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

        return redirect()->route('roles.index', $request->except('ids'))
            ->with('success', __('Selected roles deleted successfully.'));
    }
}