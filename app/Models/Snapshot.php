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

    protected $hidden = [
        'metadata',
        'attachment_type',
        'attachment_id',
        'created_at',
        'updated_at',
    ];

    public function getPathAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function attachment(): MorphTo
    {
        return $this->morphTo();
    }
}