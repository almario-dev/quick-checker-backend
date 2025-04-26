<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'user_id',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function basicResource(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    public function answerKeys()
    {
        return $this->hasMany(AnswerKey::class);
    }

    public function answerSheets()
    {
        return $this->hasMany(AnswerSheet::class);
    }
}