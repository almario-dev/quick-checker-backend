<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnswerSheet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'answer_key_id',
        'student_name',
        'score',
        'context',
        'metadata',
        'ai_checked',
        'eval_at',
    ];

    protected $casts = [
        'score' => 'double',
        'metadata' => 'array',
        'ai_checked' => 'boolean',
        'eval_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    public function answerKey()
    {
        return $this->belongsTo(AnswerKey::class);
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