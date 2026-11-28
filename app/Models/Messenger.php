<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Messenger extends Model
{
    protected $fillable = [
        'driver',
        'name',
        'bot_token',
        'channel_id',
        'channel_name',
        'is_active',
        'last_success_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'bot_token' => 'encrypted',
            'is_active' => 'boolean',
            'last_success_at' => 'datetime',
        ];
    }
}