<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnswerKey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnswerKeyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'subject' => 'required|exists:subjects,id',
            'useQuestionnaire' => 'required|boolean',
            'name' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($user) {
                    $exists = $user->subjects()->where('name', $value)->exists();

                    if ($exists) {
                        $fail('Answer key with this name already exists.');
                    }
                }
            ],
            'attachments' => 'required|array',
            'attachments.*' => 'image|max:10240',
        ]);

        try {
            $result = $user->answerKeys()->create([
                'name' => $request->name,
                'subject_id' => $request->subject,
                'mode' => $request->useQuestionnaire ? 'USE_QUESTIONNAIRE' : 'ENFORCE_KEY',
            ]);

            return response()->json($result->basic(), 201);
        } catch (\Exception $e) {
            return sendErrorResponse($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AnswerKey $answerKey)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AnswerKey $answerKey)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AnswerKey $answerKey)
    {
        //
    }
}