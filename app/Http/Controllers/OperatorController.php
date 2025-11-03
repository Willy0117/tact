<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class OperatorController extends Controller
{
    // 一覧ページ
    public function index(Request $request)
    {
        $query = Operator::query();

        // 個別検索
        if ($code = $request->input('code')) {
            $query->where('code', 'like', "%{$code}%");
        }
        if ($name = $request->input('name')) {
            $query->where('name', 'like', "%{$name}%");
        }

        // ソート
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // ページあたり件数
        $perPage = intval($request->input('per_page', 10));

        $operators = Operator::query()
            ->when($request->code, fn($q,$v)=>$q->where('code','like',"%$v%"))
            ->when($request->name, fn($q,$v)=>$q->where('name','like',"%$v%"))
            ->orderBy($request->sort_by ?? 'id', $request->sort_dir ?? 'asc')
            ->paginate($perPage)
            ->withQueryString(); // 検索条件をページリンクに保持


        return Inertia::render('Operators/Index', [
            'operators' => $operators,
            'filters' => $request->only(['code','name','per_page','sort_by','sort_dir']),
        ]);
    }

    // Create 画面
    public function create(Request $request)
    {
        $operator = null;

        // コピー用モードの場合
        if ($request->input('mode') === 'copy' && $operator_id = $request->input('operator_id')) {
            $operator = Operator::find($operator_id);
        }

        return Inertia::render('Operators/Create', [
            'filters' => $request->only(['code','name','per_page','sort_by','sort_dir','page']),
            'operator' => $operator, // コピー元のデータを渡す
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', Rule::unique('operators')],
            'name' => ['required', 'string'],
            'disabled' => ['required', 'boolean'],
            'display_order' => ['required', 'integer'],
        ], [
            'code.required' => __('validation.required', ['attribute' => __('Code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('Code')]),
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);

        Operator::create($validated);

        return redirect()->route('operators.index', $request->only(['code','name','per_page','sort_by','sort_dir','page']))
            ->with('success', __('operator has been created.'));
    }

    public function edit(Request $request, Operator $operator)
    {
        return Inertia::render('Operators/Edit', [
            'operator' => $operator,
            'filters' => $request->only(['code','name','per_page','sort_by','sort_dir','page'])
        ]);
    }

    public function update(Request $request, Operator $operator)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', Rule::unique('operators')->ignore($operator->id)],
            'name' => ['required', 'string'],
            'disabled' => ['required', 'boolean'],
            'display_order' => ['required', 'integer'],
        ], [
            'code.required' => __('validation.required', ['attribute' => __('Code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('Code')]),
            'name.required' => __('validation.required', ['attribute' => __('Name')]),
            'display_order.required' => __('validation.required', ['attribute' => __('Display Order')]),
        ]);

        $operator->update($validated);

        return redirect()->route('operators.index', $request->only(['code','name','per_page','sort_by','sort_dir','page']))
            ->with('success', __('operator has been updated.'));
    }

    public function destroy(Operator $operator)
    {
        $operator->delete();
        return redirect()->route('operators.index')->with('success', __('operator has been deleted.'));
    }

    public function bulkDelete(Request $request)
    {
        Operator::whereIn('id', $request->ids)->delete();
        return redirect()->route('operators.index')->with('success', __('Selected operators have been deleted.'));
    }
    // API Real Check
    public function checkCode(Request $request)
    {
        $exists = Operator::where('code', $request->code)
            ->when($request->id, fn($q) => $q->where('id','!=',$request->id))
            ->exists();

        return response()->json(['exists' => $exists]);
    }

}
