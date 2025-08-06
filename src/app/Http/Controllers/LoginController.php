<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function store(LoginRequest $request, LoginResponse $response)
    {

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['ログイン情報が登録されていません。'],
            ]);
        }
        $user = Auth::user();

        if (!($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail) || !$user->hasVerifiedEmail()) {
            Auth::logout();

            return redirect('/email/verify');
        }

        $request->session()->regenerate();

        return $response->toResponse($request);
    }
}