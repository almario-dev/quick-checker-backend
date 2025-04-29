<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\IsCurrentPasswordRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function changeName(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string'
        ]);

        /** @var User $user */
        $user = Auth::user();

        $user->update($validated);

        return response()->noContent();
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => ['bail', 'required', new IsCurrentPasswordRule,],
        ]);

        $request->validate([
            'password' => [
                'bail',
                'required',
                'min:8',
                'confirmed',
                new IsCurrentPasswordRule(true, 'New password cannot be the same as the old one.'),
            ]
        ]);

        $user = $request->user();

        $user->password = $request->password;
        $user->save();

        return response()->noContent();
    }
}