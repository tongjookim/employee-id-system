<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('user.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'employee_number' => 'required|string',
            'password'        => 'required|string',
        ]);

        $employee = Employee::where('employee_number', $request->employee_number)
            ->where('status', 'active')
            ->first();

        if (!$employee || !Hash::check($request->password, $employee->password)) {
            return back()->with('error', '사번 또는 비밀번호가 올바르지 않습니다.');
        }

        session([
            'employee_id'     => $employee->id,
            'employee_name'   => $employee->name,
            'employee_number' => $employee->employee_number,
        ]);

        return redirect()->route('user.idcard');
    }

    public function logout()
    {
        session()->forget(['employee_id', 'employee_name', 'employee_number']);
        return redirect()->route('user.login');
    }
}
