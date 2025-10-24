<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SetLocaleController extends Controller
{
    public function locale(Request $request)
    {
        $locale = $request->get('locale', 'ja');
        Session::put('locale', $locale);
        return response()->json(['locale' => $locale]);
    }
}
