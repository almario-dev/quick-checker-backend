<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnswerSheetResource;
use App\Models\AnswerKey;
use App\Models\AnswerSheet;
use App\Models\Snapshot;
use App\Rules\IsExistsRule;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnswerSheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $answerSheets = $user->answerSheets()
            ->with(['subject'])
            ->orderBy('eval_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(AnswerSheetResource::collection($answerSheets));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'student_name' =>  'nullable|string',
            'ai_check' => 'required|boolean',
            'answer_key' => [
                'required_if:ai_check,false',
                'exists:answer_keys,id',
                new IsExistsRule($user->answerKeys(), null, false, 'The answer key is not associated with the current account.')
            ],
            'subject' => [
                'required',
                'exists:subjects,id',
                new IsExistsRule($user->subjects(), null, false, 'The selected subject isn\'t associated with your account.')
            ],
            'documents' => 'required|array',
            'documents.*' => 'bail|image|max:10240',
        ], [
            'answer_key.required_if' => 'You must select an answer key if AI-based evaluation is disabled.',
        ]);

        DB::beginTransaction();

        try {
            $answerSheet = $user->answerSheets()->create([
                'answer_key_id' => $request->answer_key,
                'subject_id' => $request->subject,
                'student_name' => $request->student_name,
                'ai_checked' => $request->ai_check,
            ]);

            $attachments = [];

            foreach ($request->file('documents', []) as $file) {
                $path = $file->store('answer-sheets/' . $user->id, 'public');
                $attachments[] = [
                    'attachment_type' => AnswerSheet::class,
                    'attachment_id' => $answerSheet->id,
                    'path' => $path,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Snapshot::insert($attachments);

            DB::commit();

            $result = new AnswerSheetResource($answerSheet);
            $status = null;
            try {
                $aiResult = null;
                $answerKey = AnswerKey::findOrFail($request->answer_key);
                $openai = app(OpenAIService::class);

                $answerSheet->eval_at = now();

                $aiResult = $openai->evaluateAnswerSheets($request->file('documents', []), json_encode($answerKey->context));
                // if ($request->ai_checked) {
                // } else {
                //     $aiResult = $openai->evaluateAnswerSheetsWithoutKey($request->file('documents', []));
                // }

                $json = json_decode($aiResult, true);

                $answerSheet->eval_status = 'success';
                $answerSheet->context = $json;
                $answerSheet->score =  $json['total_points'] ?? null;
                $status = 'success';
            } catch (\Exception $e) {
                $status = $e->getMessage();
                $answerSheet->eval_status = 'fail';
            }

            $answerSheet->save();

            return response()->json([
                ...$result->toArray($request),
                'eval_status' => $status
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return sendErrorResponse($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(AnswerSheet $answerSheet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AnswerSheet $answerSheet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AnswerSheet $answerSheet)
    {
        //
    }

    public function info(AnswerSheet $answerSheet)
    {
        $snapshots = $answerSheet->attachments;

        return response()->json([
            'documents' => $snapshots,
            'context' => $answerSheet->context,
        ]);
    }
}
