<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public static function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $row = self::query()->where('group', $group)->where('key', $key)->first();
        if (! $row) {
            return $default;
        }

        if ($row->is_encrypted && $row->value !== null) {
            try {
                return Crypt::decryptString($row->value);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $row->value ?? $default;
    }

    public static function putValue(string $group, string $key, mixed $value, bool $encrypt = false): void
    {
        $stored = $encrypt && $value !== null ? Crypt::encryptString((string) $value) : $value;

        self::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $stored, 'is_encrypted' => $encrypt],
        );
    }
}