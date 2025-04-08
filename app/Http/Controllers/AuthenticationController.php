<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticationController extends Controller
{
    public function register(AuthRequest $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed'
        ]);

        User::create($validated);

        return response()->json(['message' => 'You have successfully created your account!'], 201);
    }

    public function login(AuthRequest $request)
    {
        $request->validate([
            'email' => 'required|exists:users,email',
            'password' => 'required|string'
        ]);

        try {
            $request->authenticate();
            return $request->sendCurrentUser();
        } catch (\Exception $e) {
            return sendErrorResponse($e);
        }
    }

    public function refresh(AuthRequest $request)
    {
        try {
            return $request->sendCurrentUser();
        } catch (\Exception $e) {
            return sendErrorResponse($e);
        }
    }

    public function logout(AuthRequest $request)
    {
        $request->revokeToken();
        return response()->noContent();
    }
}
