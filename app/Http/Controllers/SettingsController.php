<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function save(Request $request)
    {
        $request->validate([
            'similarity_threshold' => 'required|numeric|min:0,max:100'
        ]);

        /** @var User $user */
        $user = $request->user();

        $user->setConfig('similarity_threshold', $request->similarity_threshold);

        return response()->noContent();
    }
}