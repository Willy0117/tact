<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * ユーザー一覧
     */
 public function index(Request $request)
    {
        $query = User::with('roles'); // ← roles を eager load

        // 検索条件: 名前、メール、Role名
        if ($name = $request->input('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        if ($email = $request->input('email')) {
            $query->where('email', 'like', "%{$email}%");
        }
        if ($role = $request->input('role')) {
            // ロール名で検索（1ユーザー1ロール前提）
            $query->whereHas('roles', function($q) use ($role) {
                $q->where('name', 'like', "%{$role}%");
            });
        }

        // ソート
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');

        if ($sortBy === 'role') {
            // Role名でソート
            $query->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->orderBy('roles.name', $sortDir)
                ->select('users.*');
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        // ページあたり件数
        $perPage = intval($request->input('per_page', 10));
        $users = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['name', 'email', 'role', 'per_page', 'sort_by', 'sort_dir']),
        ]);
    }


    /**
     * ユーザー作成画面
     */
    public function create()
    {
        return Inertia::render('Users/Create');
    }

    /**
     * ユーザー登録
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:8'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', __('User has been created.'));
    }

    /**
     * ユーザー編集画面
     */
    public function edit(User $user)
    {
        // 該当ユーザーの tenant_id に紐づくロールのみ取得
        $roles = Role::where('tenant_id', $user->tenant_id)->get(['id', 'name']);

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => $roles,
            'currentRole' => $user->roles->first()?->name, // ユーザーの現在のロール（1つだけ）
        ]);
    }

    /**
     * ユーザー更新
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable','string','min:8'],
            'role' => ['nullable','string'], // 追加
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // ✅ ユーザーデータ更新
        $user->update($validated);

        // ✅ ロール更新処理
        if ($request->filled('role')) {
            // role name を使ってロール付与
            $user->syncRoles([$request->role]);
        } else {
            // ロールが選択されていない場合 → 全ロール削除
            $user->syncRoles([]);
        }

        return redirect()->route('users.index')
            ->with('success', __('User has been updated.'));
    }
    /**
     * ユーザー削除
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')
            ->with('success', __('User has been deleted.'));
    }

    /**
     * 複数削除
     */
    public function bulkDelete(Request $request)
    {
        User::whereIn('id', $request->ids)->delete();
        return redirect()->route('users.index')
            ->with('success', __('Selected users have been deleted.'));
    }
}
