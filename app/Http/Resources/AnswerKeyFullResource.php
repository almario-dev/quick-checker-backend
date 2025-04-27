<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerKeyFullResource extends JsonResource
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
            'name' => $this->name,
            'mode' => $this->mode,
            'context' => $this->context,
            'metadata' => $this->metadata,
            'snapshots' => $this->attachments,
            'eval_at' => $this->eval_at?->format('M d, Y - g:i A') ?? null,
            'subject' => $this->subject->basicResource(),
        ];
    }
}