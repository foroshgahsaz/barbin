<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $fillable = [
        'owner_user_id',
        'customer_group_id',
        'first_name',
        'last_name',
        'mobile',
        'notes',
        'is_active',
        'notify_consent',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'notify_consent' => 'boolean',
            'last_notified_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}