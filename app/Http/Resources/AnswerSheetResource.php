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
            'student_name' => $this->student_name,
            'subject' => $this->subject->basicResource(),
            'answer_key' => (int) $this->answer_key_id,
            'score' => $this->score,
            'ai_checked' => $this->ai_checked,
            'eval_at' => $this->eval_at?->format('M d, Y - g:i A') ?? null,
            'created_at' => timeDiffInHumanReadableFormat($this->created_at)
        ];
    }
}
