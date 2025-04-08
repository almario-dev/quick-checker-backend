<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class QuickCheckerController extends Controller
{
    public function quickCheck(Request $request)
    {
        $request->validate([
            'answer_key' => 'required|file|mimes:jpg,jpeg,png',
            'student_answer_sheet' => 'required|array',
            'student_answer_sheet.*' => 'file|mimes:jpg,jpeg,png',
        ]);

        try {
            // Convert the uploaded file to base64
            $fileContent = base64_encode(file_get_contents($request->file('answer_key')));
            $mimeType = $request->file('answer_key')->getMimeType();

            $imageData = [
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => "data:$mimeType;base64,$fileContent",
                    ],
                ]
            ];

            foreach ($request->file('student_answer_sheet') as $image) {
                $fileContent = base64_encode(file_get_contents($image));
                $mimeType = $image->getMimeType();

                $imageData[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => "data:$mimeType;base64,$fileContent",
                    ],
                ];
            }

            $result = OpenAI::chat()->create([
                'model' => config('openai.model'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => config('openai.prompt'),
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'This is an answer key image. Please analyze it.',
                            ],
                            ...$imageData
                        ],
                    ],
                ],
            ]);

            $content = $result['choices'][0]['message']['content'];

            // Decode the JSON content
            $data = json_decode($content, true);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
