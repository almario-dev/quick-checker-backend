<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnswerKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'subject_id',
        'name',
        'context',
        'score',
        'mode',
        'metadata',
        'eval_at',
    ];

    protected $casts = [
        'eval_at' => 'datetime',
        'score' => 'double',
        'metadata' => 'array',
        'context' => 'array',
    ];

    public function basic(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subject' => $this->subject->basicResource(),
            'mode' => $this->mode,
            'score' => $this->score,
            'scans' => $this->answerSheets()->count(),
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    public function answerSheets()
    {
        return $this->hasMany(AnswerSheet::class);
    }

    public function attachments()
    {
        return $this->morphMany(Snapshot::class, 'attachment');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
