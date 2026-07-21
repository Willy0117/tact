<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\Tenant;

class UserController extends Controller
{
    /**
     * ユーザー一覧
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = User::with(['roles', 'tenant']);

        $tenantId = $request->input('tenant_id');

        if ($user->hasRole('Super Admin')) {
            if (!empty($tenantId)) {
                $query->where('tenant_id', $tenantId);
            }
        } else {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($name = $request->input('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        if ($email = $request->input('email')) {
            $query->where('email', 'like', "%{$email}%");
        }
        if ($role = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', 'like', "%{$role}%");
            });
        }

        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');

        if ($sortBy === 'role') {
            $query->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->orderBy('roles.name', $sortDir)
                ->select('users.*');
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $tenants = $user->hasRole('Super Admin')
            ? Tenant::orderBy('name')->get(['id', 'name'])
            : collect();

        $perPage = intval($request->input('per_page', 20));
        $users = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'user' => $user->load('roles'),
            'filters' => $request->only(['name', 'email', 'tenant_id', 'per_page', 'sort_by', 'sort_dir', 'page']),
            'tenants' => $tenants,
        ]);
    }

    /**
     * ユーザー作成画面
     */
    public function create(Request $request)
    {
        $currentUser = $request->user();

        $roles = $currentUser->hasRole('Super Admin')
            ? Role::all()
            : Role::where('tenant_id', $currentUser->tenant_id)->get();

        $tenants = Tenant::all()->keyBy('id');
        $roles = $roles->map(function ($role) use ($tenants) {
            $role->tenant_name = $role->tenant_id ? ($tenants[$role->tenant_id]->name ?? '(Global)') : '(Global)';
            return $role;
        });

        $availableTenants = $currentUser->hasRole('Super Admin') ? Tenant::all() : [];

        return Inertia::render('Users/Edit', [
            'user' => null,
            'auth' => ['user' => $currentUser->load('roles')],
            'roles' => $roles,
            'selected_role' => null,
            'tenants' => $availableTenants,
            'filters' => $request->only(['name', 'email', 'tenant_id', 'per_page', 'sort_by', 'sort_dir', 'page']),
        ]);
    }

    /**
     * 保存処理
     */
    public function store(Request $request)
    {
        $currentUser = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
            'role_id' => 'required|exists:roles,id',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $tenantId = $currentUser->hasRole('Super Admin')
            ? $request->tenant_id
            : $currentUser->tenant_id;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'tenant_id' => $tenantId,
            'password' => Hash::make($request->password),
        ]);

        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);

        return redirect()->route('users.index', $request->filters ?? [])
            ->with('success', __('User created successfully.'));
    }

    /**
     * 編集画面
     */
    public function edit(Request $request, User $user)
    {
        $currentUser = $request->user();

        $roles = $currentUser->hasRole('Super Admin')
            ? Role::all()
            : Role::where('tenant_id', $currentUser->tenant_id)->get();

        $tenants = Tenant::all()->keyBy('id');
        $roles = $roles->map(function ($role) use ($tenants) {
            $role->tenant_name = $role->tenant_id ? ($tenants[$role->tenant_id]->name ?? '(Global)') : '(Global)';
            return $role;
        });

        $availableTenants = $currentUser->hasRole('Super Admin') ? Tenant::all() : [];

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'auth' => ['user' => $currentUser->load('roles')],
            'roles' => $roles,
            'selected_role' => $user->roles->first()?->id,
            'tenants' => $availableTenants,
            'filters' => $request->only(['name', 'email', 'tenant_id', 'per_page', 'sort_by', 'sort_dir', 'page']),
        ]);
    }

    /**
     * 更新処理
     */
    public function update(Request $request, User $user)
    {
        $currentUser = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|string|email|max:255|unique:users,email,{$user->id}",
            'password' => 'nullable|string|confirmed|min:8',
            'role_id' => 'required|exists:roles,id',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $tenantId = $currentUser->hasRole('Super Admin')
            ? $validated['tenant_id']
            : $currentUser->tenant_id;

        $user->name = $request->name;
        $user->email = $request->email;
        $user->tenant_id = $tenantId;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if ($request->filled('role_id')) {
            $role = Role::findOrFail($request->role_id);
            $user->syncRoles([$role]);
        }

        return redirect()->route('users.index', $request->filters ?? [])
            ->with('success', __('User updated successfully.'));
    }

    /**
     * ユーザー削除
     */
    public function destroy(Request $request, User $user)
    {
        $user->delete();
        return redirect()->route('users.index', $request->all())
            ->with('success', __('User has been deleted.'));
    }

    /**
     * 複数削除
     */
    public function bulkDelete(Request $request)
    {
        User::whereIn('id', $request->ids)->delete();
        return redirect()->route('users.index', $request->except('ids'))
            ->with('success', __('Selected users have been deleted.'));
    }
}