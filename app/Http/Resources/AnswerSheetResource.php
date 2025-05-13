<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerSheetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'studentName' => $this->student_name,
            'subject' => $this->subject->basicResource(),
            'answerKey' => (int) $this->answer_key_id,
            'aiChecked' => $this->ai_checked,
            'evalStatus' => $this->eval_status,
            'score' => $this->score,
            'evalAt' => $this->eval_at?->format('M d, Y - g:i A') ?? null,
            'createdAt' => timeDiffInHumanReadableFormat($this->created_at)
        ];
    }
}
