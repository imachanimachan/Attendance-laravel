<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Requests\LoginRequest;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません。',
            ]);
        }

        if (!Auth::user()->isAdmin()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません。',
            ]);
        }
        return redirect()->intended('/admin/attendance/list');
    }
}
