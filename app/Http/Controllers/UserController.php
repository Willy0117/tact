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
        $query = User::query();

        // 検索条件: 名前、メール
        if ($name = $request->input('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        if ($email = $request->input('email')) {
            $query->where('email', 'like', "%{$email}%");
        }

        // ソート
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // ページあたり件数
        $perPage = intval($request->input('per_page', 10));

        $users = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['name', 'email', 'per_page', 'sort_by', 'sort_dir']),
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
        return Inertia::render('Users/Edit', [
            'user' => $user,
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
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

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
