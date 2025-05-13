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

    public function evaluateAnswerSheets($answerSheets, $answerKeyContext)
    {
        try {
            $result = $this->prompt(
                [
                    [
                        'type' => 'text',
                        'text' => "Use this dataset as your answer key $answerKeyContext"
                    ],
                    [
                        'type' => 'text',
                        'text' => "to evaluate the results of this test image(s)..."
                    ],
                    ...array_map(fn($a) => [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => extractImage($a),
                        ],
                    ], $answerSheets)
                ],
                <<<EOD
                    You are test checker tool with only one specific job: to check answer sheets with a predefined answer key dataset.

                    You will receive one or more images per request. Process them sequentially and merge data across pages if needed.

                    You will also receive the json-encoded string as the answer key, use this to achieve the final score of the test.

                    Expected, the answer key is sent with the following format:
                    {
                        "total_points": 10,
                        "tests": [
                            {
                                "title": "Test A",
                                "max_points": 5,
                                "items": [
                                    {
                                        "item_number": 1,
                                        "description": "What is the sound of dog?",
                                        "key": "A. Aw aw!",
                                        "points": 1
                                    }
                                    ...
                                ]
                            },
                            {
                            "title": "Test B",
                            "max_points": 5,
                            "items": [
                                    {
                                        "item_number": 1,
                                        "description": "What is 4 + 2",
                                        "key": 6,
                                        "points": 1
                                    }
                                    ...
                                ]
                            }
                        ]
                    }

                    Which, you will follow this structure to create your own output: Refer to this example:
                    {
                        "total_points_acquired": 7,
                        "tests": [
                            {
                                "title": "Test A",
                                "points_acquired": 2,
                                "items": [
                                    {
                                        "item_number": 1,
                                        "answer": "A. Aw aw!",
                                        "points": 1,
                                        "points_given": 0,
                                        "is_correct": false,
                                    }
                                    ...
                                ]
                            },
                            {
                            "title": "Test B",
                            "max_points": 5,
                            "items": [
                                    {
                                        "item_number": 1,
                                        "description": "What is 4 + 2",
                                        "answer": 6,
                                        "points": 1
                                        "points_given": 1,
                                        "is_correct": true,
                                    }
                                    ...
                                ]
                            }
                        ]
                    }

                    Notes:

                        If an image is unrecognizable or not a test/exam document, or you're not able to extract data, respond with the string:
                        "err_invalid".

                        The documents may include plain answers only; use it directly to get a finalized score.

                        Extract the item_number which is unique within its own test object. Compare the provided answer to the corresponding key or correct answer from the answer key.

                        Perform OCR to extract text, and ensure accuracy even with handwritten content.

                        Maintain the order of tests and items as presented in the answer key data.

                        Answers may be indicated using highlights, checkmarks, underlines, or color—all are valid indicators.

                        If there are multiple correct answers possible, evaluate the given answer if it is correct/existing in the "key".

                        If an answer is unclear, missing, or conflicting, set points to 0.

                        Follow the answer key points allocation.

                        is_correct is always false if there is no points_given.

                        total_points_acquired is the total points_given from all the tests.

                        points_acquired is the total points_given in a specific test.

                        Send only the json-encoded plain text string (no whitespace) output.
                EOD
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
