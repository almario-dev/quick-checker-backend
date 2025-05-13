<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnswerKeyFullResource;
use App\Models\AnswerKey;
use App\Models\Snapshot;
use App\Models\User;
use App\Rules\IsExistsRule;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnswerKeyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $answerKeys = $user->answerKeys()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($a) => $a->basic());

        return $answerKeys;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'subject' => [
                'required',
                'exists:subjects,id',
                new IsExistsRule($user->subjects(), null, false, 'The selected subject isn\'t associated with your account.')
            ],
            'useQuestionnaire' => 'required|boolean',
            'name' => [
                'required',
                'string',
                new IsExistsRule($user->answerKeys(), 'name')
            ],
            'attachments' => 'required|array',
            'attachments.*' => 'image|max:10240',
        ]);

        DB::beginTransaction();

        try {
            $openai = app(OpenAIService::class);
            $aiResult = $openai->scanAnswerKey($request->file('attachments', []));
            $json = json_decode($aiResult, true);

            $score = array_sum(array_column($json['tests'], 'max_points') ?? []);

            $result = $user->answerKeys()->create([
                'name' => $request->name,
                'subject_id' => $request->subject,
                'mode' => $request->useQuestionnaire ? 'USE_QUESTIONNAIRE' : 'ENFORCE_KEY',
                'context' => $json,
                'eval_at' => now(),
                'score' => $score ?? null,
            ]);

            $attachments = [];

            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store('answer-keys/' . $user->id, 'public');
                $attachments[] = [
                    'attachment_type' => AnswerKey::class,
                    'attachment_id' => $result->id,
                    'path' => $path,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Snapshot::insert($attachments);

            DB::commit();

            $data = [
                'basic' => $result->basic(), // default
                'full' =>  new AnswerKeyFullResource($result),
            ];

            return response()->json($data, 201);
        } catch (\Exception $e) {
            DB::rollBack();
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

        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'subject' => [
                'required',
                'exists:subjects,id',
                new IsExistsRule($user->subjects(), null, false, 'The selected subject isn\'t associated with your account.')
            ],
            'name' => [
                'required',
                'string',
                new IsExistsRule($user->answerKeys(), 'name', true, null, fn($q) => $q->whereNot('id', $answerKey->id))
            ],
            'attachments' => 'required_without:existing_paths|array',
            'existing_paths' => 'required_without:attachments|array',
            'attachments.*' => 'image|max:10240',
        ]);

        DB::beginTransaction();

        try {
            $answerKey->subject_id = $request->subject;
            $answerKey->name = $request->name;

            // sync the existing old snapshots
            $answerKey->attachments()->whereNotIn('id', $request->existing_paths)->delete();

            $attachments = [];
            // upload new snapshots
            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store('answer-keys/' . $user->id, 'public');
                $attachments[] = [
                    'attachment_type' => AnswerKey::class,
                    'attachment_id' => $answerKey->id,
                    'path' => $path,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Snapshot::insert($attachments);

            $answerKey->save();

            DB::commit();

            $data = [
                'basic' => $answerKey->basic(), // default
                'full' =>  new AnswerKeyFullResource($answerKey),
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            DB::rollBack();
            return sendErrorResponse($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AnswerKey $answerKey)
    {
        $answerKey->delete();
        return response()->noContent();
    }

    public function fullDetails(AnswerKey $answerKey)
    {
        return response()->json(new AnswerKeyFullResource($answerKey));
    }

    public function reanalyze(AnswerKey $answerKey)
    {
        try {
            $attachments = $answerKey->attachments->map(function ($attachment) {
                return $attachment->getAttributes()['path'];
            })->all();

            $openai = app(OpenAIService::class);
            $aiResult = $openai->scanAnswerKey($attachments ?? [], true);
            $json = json_decode($aiResult, true);

            $score = array_sum(array_column($json['tests'], 'max_points') ?? []);

            $answerKey->update([
                'score' => $score,
                'eval_at' => now(),
                'context' => $json,
            ]);

            return response()->json(new AnswerKeyFullResource($answerKey));
        } catch (\Exception $e) {
            return sendErrorResponse($e);
        }
    }
}
