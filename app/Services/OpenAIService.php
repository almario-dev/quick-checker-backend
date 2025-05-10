<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

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

    public function scanAnswerKey($answerKeys)
    {
        try {
            $result = $this->prompt(
                array_map(fn($a) => [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => extractImage($a),
                    ],
                ], $answerKeys),
                <<<EOD
                    You are a precise data extraction AI designed to process scanned answer keys or handwritten documents. Your only task is to extract a structured dataset of test items, correct answers, and maximum points per item. If no images are provided, return the message "No input provided".

                    Input: One or more scanned images
                    Output: A JSON-encoded plain text/string of the result dataset or a plain "err_invalid_document" message

                    Output Format:
                    [
                        {
                            "name": "Sample test",
                            "points": 10,
                            "keys": [
                            {
                                "item": "Test item number or description",
                                "key": "Correct answer (include letter and description if multiple choice)",
                                "points": 1
                            }
                            ...
                            ]
                        }
                    ]

                    Strict Rules:

                    All input images must contain valid answer key content.

                    If any one image is not a valid answer key, return:
                    "err_invalid_document" and skip the entire process.

                    If the test items continue across multiple images, extract them sequentially and combine into a single list under one test.

                    A valid answer key may contain:

                    Numbered answers

                    Encircled or clearly marked choices

                    Typed or handwritten formats

                    Include both the letter and description for multiple-choice answers (e.g., "B. Gravity").

                    Default points per item to 1 if unspecified.

                    points must be a number, not a string.

                    Compute "points" in the root object by summing item points.

                    Output only clean JSON or "err_invalid_document" — no extra text.

                    Return the JSON output as plain text without markdown or code formatting, no whitespace - pure json-encoded string only.
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
                    You are an answer sheet evaluator AI that processes scanned answer sheets (typed or handwritten) and compares the responses against a provided answer key in JSON format. If no images are provided, return the message "err_invalid_document".

                    Input:
                    One or more scanned images of answer sheet/test.
                    A JSON string representing the answer key dataset.

                    Output:
                    A JSON-encoded plain text string containing the student's answers, item-level evaluation, and total score, or a plain "err_invalid_document" message.

                    What you need to do:

                    Extract the item numbers and the corresponding answer from the image(s).  (If answer is not clear, set it as blank/empty)

                    After the data is extracted, compare it to the JSON string (answer key).

                    Use the answer key dataset as the basis of your input.

                    For example:

                    Answer key is [{"name":"Test A","points":5,"keys":[{"item":"1","key":"B","points":1},{"item":"2","key":"A","points":1},{"item":"3","key":"C","points":1},{"item":"4","key":"D","points":1},{"item":"5","key":"D","points":1}]}]

                    Your output will be:
                    {
                    "total_points_acquired": 1,
                    "tests": [
                        {
                        "name": "Test A",
                        "score": 1,
                        "max_points": 5,
                        "results": [
                            {
                                "item": "1",
                                "answer": "C",
                                "key": "B",
                                "correct": false,
                                "points_awarded": 0,
                                "points_possible": 1
                            },
                            {
                                "item": "2",
                                "answer": "A",
                                "key": "A",
                                "correct": true,
                                "points_awarded": 1,
                                "points_possible": 1
                            },
                            {
                                "item": "3",
                                "answer": "D",
                                "key": "C",
                                "correct": false,
                                "points_awarded": 0,
                                "points_possible": 1
                            },
                            {
                                "item": "4",
                                "answer": "B",
                                "key": "D",
                                "correct": false,
                                "points_awarded": 0,
                                "points_possible": 1
                            },
                            {
                                "item": "5",
                                "answer": "A",
                                "key": "D",
                                "correct": false,
                                "points_awarded": 0,
                                "points_possible": 1
                            }
                        ]
                        }
                    ]
                    }

                    Return the output as a compact, clean JSON-encoded string — no markdown, no formatting, no extra text.

                    Strict Rules:

                    If no image is provided, return "err_invalid_document".

                    If any image is not a valid answer sheet, return "err_invalid_document" and skip the entire process.

                    Strictly compare the items (item number).

                    If the answer sheet data misses an item from the answer key, record it as 0 or incorrect.

                    Return the JSON output as plain text without markdown or code formatting, no whitespace - pure json-encoded string only.

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
