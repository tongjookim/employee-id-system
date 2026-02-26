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

    public function showChangePasswordForm()
    {
        return view('user.password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|string|min:4|confirmed',
        ]);

        $employee = Employee::find(session('employee_id'));

        if (!Hash::check($request->current_password, $employee->password)) {
            return back()->with('error', '현재 비밀번호가 일치하지 않습니다.');
        }

        $employee->update(['password' => Hash::make($request->new_password)]);

        // 뷰에 알림을 띄우기 위해 session flash 사용
        return redirect()->route('user.idcard')
            ->with('success', '비밀번호가 성공적으로 변경되었습니다.');
    }
}
