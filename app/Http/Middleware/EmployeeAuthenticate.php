<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EmployeeAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('employee_id')) {
            return redirect()->route('user.login')->with('error', '로그인이 필요합니다.');
        }
        return $next($request);
    }
}
