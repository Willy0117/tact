<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function index()
    {
        return Inertia::render('Operators/Index');
    }

    public function create()
    {
        return Inertia::render('Operators/Create');
    }

    public function store(Request $request)
    {
        // 仮保存（後で DB 実装）
        return redirect()->route('Operators.index');
    }

    public function edit($id)
    {
        return Inertia::render('Operators/Edit', ['id' => $id]);
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('Operators.index');
    }

    public function destroy($id)
    {
        return redirect()->route('Operators.index');
    }
}
