<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'tvg_id',
        'source',
        'category',
        'description',
        'logo_url',
        'stream_url',
        'tested_stream_url',
        'stream_tested_at',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'stream_tested_at' => 'datetime',
    ];
}
