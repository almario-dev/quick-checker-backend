<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Snapshot extends Model
{
    protected $fillable = [
        'path',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function attachment(): MorphTo
    {
        return $this->morphTo();
    }
}