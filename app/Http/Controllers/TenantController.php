<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TenantController extends Controller
{
    // 一覧ページ
    public function index(Request $request)
    {
        $query = Tenant::query();

        // 検索条件
        if ($name = $request->input('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        if ($email = $request->input('contact_email')) {
            $query->where('contact_email', 'like', "%{$email}%");
        }
        if ($phone = $request->input('contact_phone')) {
            $query->where('contact_phone', 'like', "%{$phone}%");
        }

        // ソート
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // ページあたり件数
        $perPage = intval($request->input('per_page', 10));

        $tenants = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['name','contact_email','contact_phone','per_page','sort_by','sort_dir'])
        ]);
    }

    // Create画面
    public function create(Request $request)
    {
        return Inertia::render('Tenants/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
        ]);

        // 1️⃣ テナントを作成
        $tenant = Tenant::create($validated);

        // 2️⃣ 新テナント用のロール作成（名前は自由）
        $adminRole = Role::create([
            'name' => 'tenant_admin_' . $tenant->id,
            'tenant_id' => $tenant->id,
            'guard_name' => 'web',
        ]);

        $userRole = Role::create([
            'name' => 'tenant_user_' . $tenant->id,
            'tenant_id' => $tenant->id,
            'guard_name' => 'web',
        ]);

        // 3️⃣ 雛形（tenant_id IS NULL の permission）だけを複製して新テナント用に登録
        DB::transaction(function () use ($tenant, $adminRole) {
            $templatePermissions = Permission::whereNull('tenant_id')->get();

            foreach ($templatePermissions as $tpl) {
                // 存在チェック（同名・同guard・同tenantが既にあればスキップ）
                $exists = Permission::where('name', $tpl->name)
                    ->where('guard_name', $tpl->guard_name)
                    ->where('tenant_id', $tenant->id)
                    ->first();

                if ($exists) {
                    $newPerm = $exists;
                } else {
                    // 複製して tenant_id をセットして保存
                    $newPerm = $tpl->replicate();
                    $newPerm->tenant_id = $tenant->id;
                    $newPerm->push(); // save()
                }

                // adminRole にそのテナント用 permission を付与（モデルで渡すと確実）
                $adminRole->givePermissionTo($newPerm);
            }
        });

        return redirect()->route('tenants.index')
            ->with('success', __('Tenant has been created and initialized with roles & permissions.'));
    }


    // Edit画面
    public function edit(Request $request, Tenant $tenant)
    {
        return Inertia::render('Tenants/Edit', [
            'tenant' => $tenant
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
        ]);

        $tenant->update($validated);

        // 既存ロール取得または作成
        $adminRole = Role::firstOrCreate(
            ['name' => 'tenant_admin_' . $tenant->id, 'tenant_id' => $tenant->id, 'guard_name' => 'web']
        );
        $userRole = Role::firstOrCreate(
            ['name' => 'tenant_user_' . $tenant->id, 'tenant_id' => $tenant->id, 'guard_name' => 'web']
        );

        DB::transaction(function () use ($tenant, $adminRole) {
            $templatePermissions = Permission::whereNull('tenant_id')->get();

            foreach ($templatePermissions as $tpl) {
                $exists = Permission::where('name', $tpl->name)
                    ->where('guard_name', $tpl->guard_name)
                    ->where('tenant_id', $tenant->id)
                    ->first();

                if ($exists) {
                    $newPerm = $exists;
                } else {
                    $newPerm = $tpl->replicate();
                    $newPerm->tenant_id = $tenant->id;
                    $newPerm->push();
                }

                $adminRole->givePermissionTo($newPerm);
            }
        });

        return redirect()->route('tenants.index')
            ->with('success', __('Tenant has been updated and permissions synchronized.'));
    }



    // 削除
    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return redirect()->route('tenants.index')->with('success', __('Tenant has been deleted.'));
    }

    // 複数削除
    public function bulkDelete(Request $request)
    {
        Tenant::whereIn('id', $request->ids)->delete();
        return redirect()->route('tenants.index')->with('success', __('Selected tenants have been deleted.'));
    }
}
