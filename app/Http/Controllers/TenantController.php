<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

    // Store
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string'],
            'contact_email' => ['nullable','email'],
            'contact_phone' => ['nullable','string'],
            'address' => ['nullable','string'],
        ]);

        Tenant::create($validated);

        return redirect()->route('tenants.index')->with('success', __('Tenant has been created.'));
    }

    // Edit画面
    public function edit(Request $request, Tenant $tenant)
    {
        return Inertia::render('Tenants/Edit', [
            'tenant' => $tenant
        ]);
    }

    // Update
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => ['required','string'],
            'contact_email' => ['nullable','email'],
            'contact_phone' => ['nullable','string'],
            'address' => ['nullable','string'],
        ]);

        $tenant->update($validated);

        return redirect()->route('tenants.index')->with('success', __('Tenant has been updated.'));
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
