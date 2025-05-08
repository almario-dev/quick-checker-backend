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
}
