<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::find(Auth::user()->id);
        return response()->json($user->subjects()->orderBy('created_at', 'desc')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = User::find(Auth::user()->id);

        $validated = $request->validate([
            'name' => ['required', 'string', function ($attribute, $value, $fail) use ($user) {
                $exists = $user->subjects()->where('name', $value)->exists();

                if ($exists) {
                    $fail('The subject already exists.');
                }
            }]
        ]);

        try {
            $user->subjects()->create($validated);
            return response()->json(['message' => 'Successfully added.']);
        } catch (\Exception $e) {
            return sendErrorResponse($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Subject $subject)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', function ($attribute, $value, $fail) use ($subject) {
                $user = User::findOrFail($subject->user_id);

                $exists = $user->subjects()
                    ->where('name', $value)
                    ->where('id', '!=', $subject->id)  // Use '!=' for checking the ID exclusion
                    ->exists();

                if ($exists) {
                    $fail('The subject already exists.');
                }
            }]
        ]);

        try {
            $subject->update($validated);
            return response()->json(['message' => 'Updated.']);
        } catch (\Exception $e) {
            return sendErrorResponse($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subject $subject)
    {
        $subject->delete();
        return response()->noContent();
    }
}