<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale', config('app.locale'));
        app()->setLocale($locale);

        // HTMLタグのlang属性用
        view()->share('locale', $locale);

        return $next($request);        
    }
}


