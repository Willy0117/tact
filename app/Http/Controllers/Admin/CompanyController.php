<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    /**
     * 会社一覧（検索・ページネーション対応）
     */
    public function index(Request $request)
    {
        $query = Company::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        $companies = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Companies/Index', compact('companies'));
    }

    /**
     * 会社作成フォーム
     */
    public function create()
    {
        return Inertia::render('Admin/Companies/Create');
    }

    /**
     * 会社登録
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'fax'     => 'nullable|string|max:50',
        ]);

        Company::create($request->only(['name', 'address', 'phone', 'fax']));

        return redirect()->route('admin.companies.index')
            ->with('success', __('company.created_success'));
    }

    /**
     * 会社編集フォーム
     */
    public function edit(Company $company)
    {
        return Inertia::render('Admin/Companies/Edit', [
            'company' => $company,
        ]);
    }

    /**
     * 会社更新
     */
    public function update(Request $request, Company $company)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'fax'     => 'nullable|string|max:50',
        ]);

        $company->update($request->only(['name', 'address', 'phone', 'fax']));

        return redirect()->route('admin.companies.index')
            ->with('success', __('company.updated_success'));
    }

    /**
     * 会社削除
     */
    public function destroy(Company $company)
    {
        $company->delete();
        return redirect()->route('admin.companies.index');
    }
}
