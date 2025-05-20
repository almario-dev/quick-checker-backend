<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

define("INVALID", "/err_invalid/");

class OpenAIService
{
    private function prompt(array $content, string $systemPrompt)
    {
        return OpenAI::chat()->create([
            'model' => config('openai.model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ],
        ]);
    }

    public function scanAnswerKey($answerKeys, $isPath = false)
    {
        try {
            $promptContent = Cache::remember('answer-key-extraction-tool', 60, function () {
                return Storage::disk('local')->get('prompts/answer-key-extraction-tool.txt');
            });

            $result = $this->prompt(
                [
                    [
                        'type' => 'text',
                        'text' => "Scan these images for answer key"
                    ],
                    ...array_map(fn($a) => [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => extractImage($a, $isPath),
                        ],
                    ], $answerKeys),
                ],
                $promptContent,
            );

            $content = $result['choices'][0]['message']['content'];

            if (preg_match(INVALID, $content)) {
                throw new \Exception('Invalid document.', 500);
            }

            return $content;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function evaluateAnswerSheets($answerSheets, $answerKeyContext, $isPath = false)
    {
        try {
            $promptContent = Cache::remember('answer-sheet-evaluation-with-enforced-key', 60, function () {
                return Storage::disk('local')->get('prompts/answer-sheet-evaluation-with-enforced-key.txt');
            });

            $result = $this->prompt(
                [
                    [
                        'type' => 'text',
                        'text' => "Use this dataset as your answer key $answerKeyContext"
                    ],
                    [
                        'type' => 'text',
                        'text' => "to evaluate the results of this answer sheets(s)..."
                    ],
                    ...array_map(fn($a) => [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => extractImage($a, $isPath),
                        ],
                    ], $answerSheets)
                ],
                $promptContent
            );

            $content = $result['choices'][0]['message']['content'];

            if (preg_match("/err_invalid/", $content)) {
                throw new \Exception('Invalid document.', 500);
            }

            return $content;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function evaluateAnswerSheetsWithoutKey($answerSheets,)
    {
        try {
            $result = $this->prompt(
                [
                    [
                        'type' => 'text',
                        'text' => "The answer sheets you are to check are the following images..."
                    ],
                    ...array_map(fn($a) => [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => extractImage($a),
                        ],
                    ], $answerSheets)
                ],
                <<<EOD
                    You are an intelligent answer sheet evaluator AI that processes scanned answer sheets (typed or handwritten) and evaluates the correctness of each response based on general knowledge and reasoning, without relying on any provided answer key.

                    Input:

                    One or more scanned images of an answer sheet or test.

                    Output:

                    A plain JSON-encoded string that lists extracted answers, evaluates them for correctness using general world knowledge, and provides a total score.

                    If no image is provided or the input is not a valid answer sheet, return "err_invalid_document".

                    What to do:

                    Extract item numbers, answers, and optionally points (if shown) from the image(s).

                    If an answer is not clear, leave the answer field empty.

                    For each item, infer the correct answer using general knowledge and reasoning.

                    Compare the answer with the inferred correct answer.

                    Assign the extracted item's points if present, otherwise default to 1.

                    Output Format:
                    {
                        "score": 3,
                        "max_points": 5,
                        "results": [
                            {
                                "item": "1",
                                "answer": "A",
                                "correct_answer": "B",
                                "correct": false,
                                "points_awarded": 0,
                                "points_possible": 1
                            },
                            {
                                "item": "2",
                                "answer": "C",
                                "correct_answer": "C",
                                "correct": true,
                                "points_awarded": 1,
                                "points_possible": 1
                            },
                            ...
                        ]
                    }

                    Strict Rules:

                    Return "err_invalid_document" if any image is invalid or unclear.

                    Infer correct answers from context and general knowledge only.

                    Always return compact JSON, no markdown, no extra text or whitespace.

                EOD
            );

            $content = $result['choices'][0]['message']['content'];

            if (preg_match("/err_invalid_document/", $content)) {
                throw new \Exception('Invalid document.', 500);
            }

            return $content;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
