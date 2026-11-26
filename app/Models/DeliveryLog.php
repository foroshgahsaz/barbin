<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryLog extends Model
{
    protected $fillable = [
        'sales_file_id',
        'user_id',
        'customer_id',
        'messenger_id',
        'channel',
        'message_text',
        'status',
        'attempts',
        'external_message_id',
        'response_payload',
        'error_message',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'response_payload' => 'array',
        ];
    }

    public function salesFile(): BelongsTo
    {
        return $this->belongsTo(SalesFile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function messenger(): BelongsTo
    {
        return $this->belongsTo(Messenger::class);
    }
}