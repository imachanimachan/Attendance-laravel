<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\RegisterResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\RegisterRequest;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request, CreatesNewUsers $creator): RegisterResponse
    {
        $user = $creator->create($request->all());

        event(new Registered($user));

        Auth::login($user);

        return app(RegisterResponse::class);
    }
}