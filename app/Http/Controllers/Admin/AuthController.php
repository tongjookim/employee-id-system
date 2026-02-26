<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login_id' => 'required|string',
            'password'  => 'required|string',
        ]);

        $admin = Admin::where('login_id', $request->login_id)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return back()->with('error', '아이디 또는 비밀번호가 올바르지 않습니다.');
        }

        session([
            'admin_id'   => $admin->id,
            'admin_name' => $admin->name,
            'admin_role' => $admin->role,
        ]);

        $admin->update(['last_login_at' => now()]);

        return redirect()->route('admin.dashboard');
    }

    public function logout()
    {
        session()->forget(['admin_id', 'admin_name', 'admin_role']);
        return redirect()->route('admin.login');
    }
}
