<?php

namespace Database\Seeders;

use App\Enums\DeliveryStatus;
use App\Enums\ImportBatchStatus;
use App\Enums\SalesFileStatus;
use App\Enums\StockMatchStatus;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\DeliveryLog;
use App\Models\InventoryImportBatch;
use App\Models\InventorySnapshot;
use App\Models\InventorySnapshotItem;
use App\Models\SalesFile;
use App\Models\SalesFileItem;
use App\Models\User;
use App\Support\ProductCode;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BarbinDemoThreeMonthSeeder extends Seeder
{
    /** @var list<array{code: string, title: string, unit: string, price: float, purchase: float, base_qty: float}> */
    private array $catalog = [];

    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@barbin.local')->first();
        if (! $admin) {
            $this->command?->error('ابتدا RolesAndPermissionsSeeder را اجرا کنید.');

            return;
        }

        $warehouse = User::query()->updateOrCreate(
            ['email' => 'warehouse@barbin.local'],
            ['name' => 'مسئول انبار', 'password' => Hash::make('password'), 'is_active' => true],
        );
        $warehouse->syncRoles(['warehouse_manager']);

        $sales = User::query()->updateOrCreate(
            ['email' => 'sales@barbin.local'],
            ['name' => 'مدیر فروش نمونه', 'password' => Hash::make('password'), 'is_active' => true],
        );
        $sales->syncRoles(['sales_manager']);

        User::query()->updateOrCreate(
            ['email' => 'viewer@barbin.local'],
            ['name' => 'بیننده سازمان', 'password' => Hash::make('password'), 'is_active' => true],
        )->syncRoles(['organization_viewer']);

        User::query()->updateOrCreate(
            ['email' => 'operator@barbin.local'],
            [
                'name' => 'اپراتور فروش',
                'password' => Hash::make('password'),
                'is_active' => true,
                'operator_permissions' => ['notify' => true],
            ],
        )->syncRoles(['sales_operator']);

        $this->buildCatalog();

        $groups = $this->seedCustomerGroups($sales);
        $this->seedCustomers($sales, $groups);

        $snapshots = $this->seedInventorySnapshots($warehouse, 90);

        $this->seedSalesFiles($sales, $snapshots);
        $this->command?->info('Demo seeded. Users: admin / warehouse / sales / viewer / operator @barbin.local — password');
    }

    private function buildCatalog(): void
    {
        $base = [
            ['00123', 'سرویس والاس ۶ نفره', 'عدد', 4500000, 24],
            ['00124', 'سرویس والاس ۱۲ نفره', 'عدد', 7800000, 8],
            ['00456', 'ظرف بلور ۱۲ پارچه', 'ست', 2200000, 0],
            ['00999', 'ماهیتابه گرانیت ۲۶', 'عدد', 890000, 15],
            ['A-100', 'چاقوی آشپزخانه ست ۵', 'ست', 350000, 42],
            ['A-101', 'سینی مستطیل استیل', 'عدد', 180000, 3],
            ['B-200', 'پتوی مسافرتی', 'عدد', 520000, 0],
            ['B-201', 'بالش میکروفایبر', 'عدد', 145000, 120],
            ['C-050', 'کولر آبی ۴۰۰۰', 'عدد', 12500000, 5],
            ['C-051', 'پنکه رومیزی', 'عدد', 890000, 18],
            ['D-010', 'سطل زباله پدالی', 'عدد', 420000, 2],
            ['D-011', 'جا لهستنی پلاستیک', 'عدد', 65000, 0],
            ['E-700', 'قوری چدنی', 'عدد', 1100000, 11],
            ['E-701', 'قابلمه تفلون ۲۰', 'عدد', 760000, 7],
            ['F-310', 'لیوان دسته‌دار ۶تایی', 'ست', 280000, 55],
        ];

        foreach ($base as $row) {
            $this->catalog[] = [
                'code' => $row[0],
                'title' => $row[1],
                'unit' => $row[2],
                'price' => (float) $row[3],
                'purchase' => round((float) $row[3] * 0.65, 0),
                'base_qty' => (float) $row[4],
            ];
        }
    }

    /**
     * @return array<string, CustomerGroup>
     */
    private function seedCustomerGroups(User $sales): array
    {
        $defs = [
            'chinese' => 'مشتریان سرویس چینی',
            'crystal' => 'مشتریان بلور',
            'utility' => 'محصولات کاربردی',
            'vip' => 'مشتریان ویژه',
        ];

        $out = [];
        foreach ($defs as $slug => $name) {
            $out[$slug] = CustomerGroup::query()->updateOrCreate(
                ['owner_user_id' => $sales->id, 'slug' => $slug.'-'.$sales->id],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $out;
    }

  /**
     * @param  array<string, CustomerGroup>  $groups
     */
    private function seedCustomers(User $sales, array $groups): void
    {
        $samples = [
            ['رضا', 'احمدی', '09121234567', 'chinese'],
            ['مریم', 'کریمی', '09129876543', 'crystal'],
            ['علی', 'محمدی', '09351230001', 'utility'],
            ['سارا', 'نوری', '09361230002', 'vip'],
            ['حسین', 'جعفری', '09171230003', 'chinese'],
            ['لیلا', 'رضایی', '09021230004', 'crystal'],
            ['امیر', 'حسینی', '09381230005', 'utility'],
            ['نرگس', 'موسوی', '09191230006', 'vip'],
            ['پریسا', 'قاسمی', '09201230007', 'chinese'],
            ['مهدی', 'زارعی', '09211230008', 'utility'],
        ];

        foreach ($samples as $i => [$fn, $ln, $mobile, $groupKey]) {
            Customer::query()->updateOrCreate(
                ['owner_user_id' => $sales->id, 'mobile' => $mobile],
                [
                    'first_name' => $fn,
                    'last_name' => $ln,
                    'customer_group_id' => $groups[$groupKey]->id,
                    'is_active' => true,
                    'notify_consent' => true,
                    'last_notified_at' => $i < 4 ? now()->subDays(rand(1, 45)) : null,
                ],
            );
        }
    }

    /**
     * @return list<InventorySnapshot>
     */
    private function seedInventorySnapshots(User $warehouse, int $days): array
    {
        $snapshots = [];
        $start = Carbon::now()->subDays($days);

        for ($d = 0; $d <= $days; $d += 7) {
            $at = $start->copy()->addDays($d);
            $batch = InventoryImportBatch::query()->create([
                'uploaded_by' => $warehouse->id,
                'original_filename' => 'demo-stock-'.$at->format('Y-m-d').'.csv',
                'stored_path' => 'inventory-uploads/demo-'.$at->format('Ymd').'.csv',
                'status' => ImportBatchStatus::Confirmed,
                'row_count' => count($this->catalog),
                'duplicate_codes_count' => 0,
                'invalid_rows_count' => 0,
                'zero_stock_count' => 0,
                'positive_stock_count' => 0,
                'confirmed_at' => $at,
            ]);

            $progress = $d / max($days, 1);
            $inStock = 0;
            $zeroStock = 0;
            $rows = [];

            foreach ($this->catalog as $idx => $item) {
                $wave = sin($idx + $progress * 3.14) * 5;
                $decay = (int) floor($progress * 3);
                $qty = max(0, $item['base_qty'] - $decay * ($idx % 3) + $wave);
                if ($qty > 0) {
                    $inStock++;
                } else {
                    $zeroStock++;
                }

                $rows[] = [
                    'product_code' => $item['code'],
                    'description' => $item['title'],
                    'unit' => $item['unit'],
                    'quantity' => round($qty, 2),
                    'purchase_price' => $item['purchase'],
                ];
            }

            $snapshot = InventorySnapshot::query()->create([
                'inventory_import_batch_id' => $batch->id,
                'uploaded_by' => $warehouse->id,
                'original_filename' => $batch->original_filename,
                'recorded_at' => $at,
                'items_count' => count($rows),
                'in_stock_count' => $inStock,
                'zero_stock_count' => $zeroStock,
                'import_status' => 'confirmed',
            ]);

            $batch->update(['inventory_snapshot_id' => $snapshot->id]);

            foreach (array_chunk($rows, 50) as $chunk) {
                $insert = array_map(fn (array $r) => [
                    'inventory_snapshot_id' => $snapshot->id,
                    'product_code' => $r['product_code'],
                    'description' => $r['description'],
                    'unit' => $r['unit'],
                    'quantity' => $r['quantity'],
                    'purchase_price' => $r['purchase_price'],
                    'created_at' => $at,
                    'updated_at' => $at,
                ], $chunk);
                InventorySnapshotItem::query()->insert($insert);
            }

            $snapshots[] = $snapshot;
        }

        return $snapshots;
    }

    /**
     * @param  list<InventorySnapshot>  $snapshots
     */
    private function seedSalesFiles(User $sales, array $snapshots): void
    {
        $titles = [
            'لیست سرویس‌های چینی — آبان',
            'لیست ظروف بلور — آبان',
            'محصولات کاربردی — آذر',
            'ویژه نوروز — دی',
            'شگفت‌انگیز زمستان',
            'ظروف و سرویس — بهمن',
        ];

        $customers = Customer::query()->where('owner_user_id', $sales->id)->pluck('id');

        foreach ($titles as $i => $title) {
            $snapshot = $snapshots[min($i, count($snapshots) - 1)] ?? $snapshots[array_key_last($snapshots)];
            $created = Carbon::now()->subDays(85 - ($i * 14));

            $pick = collect($this->catalog)->shuffle()->take(10)->values();
            $stockMap = $snapshot->stockMapForCodes($pick->pluck('code'));

            $salesFile = SalesFile::query()->create([
                'user_id' => $sales->id,
                'title' => $title,
                'original_filename' => 'sales-'.Str::slug($title).'.csv',
                'source_path' => 'sales-uploads/demo-'.$i.'.csv',
                'product_count' => $pick->count(),
                'processing_status' => SalesFileStatus::Ready,
                'inventory_snapshot_id' => $snapshot->id,
                'notification_status' => $i % 2 === 0 ? 'partial_or_sent' : 'none',
                'created_at' => $created,
                'updated_at' => $created,
                'pdf_generated_at' => $i % 3 !== 2 ? $created->copy()->addHour() : null,
                'pdf_path' => $i % 3 !== 2 ? 'sales-pdfs/demo/'.$i.'.pdf' : null,
            ]);

            $rowNum = 0;
            foreach ($pick as $item) {
                $rowNum++;
                $code = $item['code'];
                $qty = $stockMap[$code] ?? null;
                $status = $qty === null ? StockMatchStatus::NotFound : ($qty > 0 ? StockMatchStatus::Matched : StockMatchStatus::Zero);

                SalesFileItem::query()->create([
                    'sales_file_id' => $salesFile->id,
                    'row_number' => $rowNum,
                    'product_code' => $code,
                    'description' => $item['title'],
                    'unit' => $item['unit'],
                    'price' => $item['price'],
                    'stock_quantity' => $qty,
                    'stock_status' => $status,
                ]);
            }

            if ($customers->isNotEmpty() && $i < 4) {
                foreach ($customers->take(3) as $customerId) {
                    DeliveryLog::query()->firstOrCreate(
                        [
                            'sales_file_id' => $salesFile->id,
                            'customer_id' => $customerId,
                            'channel' => 'sms',
                            'idempotency_key' => sha1($salesFile->id.'|'.$customerId.'|sms|demo'),
                        ],
                        [
                            'user_id' => $sales->id,
                            'status' => $i % 3 === 0 ? DeliveryStatus::Failed : DeliveryStatus::Sent,
                            'attempts' => 1,
                            'message_text' => 'پیام نمایشی دمو',
                            'created_at' => $created->copy()->addHours(2),
                            'updated_at' => $created->copy()->addHours(2),
                        ],
                    );
                }
            }
        }
    }
}