<?php

namespace App\Services\Customers;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\User;
use App\Services\Excel\ColumnResolver;
use App\Services\Excel\SpreadsheetReader;
use Illuminate\Support\Facades\DB;

class CustomerImportService
{
    /**
     * @return array{imported: int, skipped: int, errors: list<string>}
     */
    public function importFromPath(User $owner, string $absolutePath): array
    {
        $rows = SpreadsheetReader::rows($absolutePath);
        if ($rows === []) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['فایل خالی است']];
        }

        $header = array_map(fn ($c) => (string) $c, $rows[0]);
        $fnIdx = ColumnResolver::resolveIndex($header, ['نام', 'first_name', 'first name']);
        $lnIdx = ColumnResolver::resolveIndex($header, ['نام خانوادگی', 'last_name', 'family']);
        $mobileIdx = ColumnResolver::resolveIndex($header, ['موبایل', 'mobile', 'phone']);
        $groupIdx = ColumnResolver::resolveIndex($header, ['گروه', 'group']);

        if ($mobileIdx === null) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['ستون موبایل یافت نشد']];
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $fnIdx, $lnIdx, $mobileIdx, $groupIdx, $owner, &$imported, &$skipped, &$errors) {
            foreach (array_slice($rows, 1) as $line => $row) {
                $mobile = preg_replace('/\D+/', '', (string) ($row[$mobileIdx] ?? '')) ?? '';
                if (strlen($mobile) < 10) {
                    $skipped++;
                    $errors[] = 'ردیف '.($line + 2).': موبایل نامعتبر';

                    continue;
                }

                if (str_starts_with($mobile, '98')) {
                    $mobile = '0'.substr($mobile, 2);
                } elseif (str_starts_with($mobile, '9') && strlen($mobile) === 10) {
                    $mobile = '0'.$mobile;
                }

                $exists = Customer::query()
                    ->where('owner_user_id', $owner->id)
                    ->where('mobile', $mobile)
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                $groupId = null;
                if ($groupIdx !== null) {
                    $groupName = trim((string) ($row[$groupIdx] ?? ''));
                    if ($groupName !== '') {
                        $slug = \Illuminate\Support\Str::slug($groupName).'-'.$owner->id;
                        $group = CustomerGroup::query()->firstOrCreate(
                            ['owner_user_id' => $owner->id, 'slug' => $slug],
                            ['name' => $groupName, 'is_active' => true],
                        );
                        $groupId = $group->id;
                    }
                }

                Customer::query()->create([
                    'owner_user_id' => $owner->id,
                    'customer_group_id' => $groupId,
                    'first_name' => $fnIdx !== null ? trim((string) ($row[$fnIdx] ?? 'مشتری')) : 'مشتری',
                    'last_name' => $lnIdx !== null ? trim((string) ($row[$lnIdx] ?? '')) : '',
                    'mobile' => $mobile,
                    'is_active' => true,
                    'notify_consent' => true,
                ]);
                $imported++;
            }
        });

        return compact('imported', 'skipped', 'errors');
    }
}