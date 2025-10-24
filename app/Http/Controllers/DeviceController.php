<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index()
    {
        return Inertia::render('Devices/Index');
    }

    public function create()
    {
        return Inertia::render('Devices/Create');
    }

    public function store(Request $request)
    {
        // 仮保存（後で DB 実装）
        return redirect()->route('devices.index');
    }

    public function edit($id)
    {
        return Inertia::render('Devices/Edit', ['id' => $id]);
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('devices.index');
    }

    public function destroy($id)
    {
        return redirect()->route('devices.index');
    }
}

