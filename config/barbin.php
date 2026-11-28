<?php

return [
    'files_disk' => env('BARBIN_FILES_DISK', 'barbin'),
    'inventory' => [
        'code_headers' => ['کد کالا', 'کد', 'product_code', 'code'],
        'description_headers' => ['شرح کالا', 'شرح', 'description', 'title'],
        'unit_headers' => ['واحد', 'واحد کالا', 'unit'],
        'quantity_headers' => ['موجودی', 'مانده', 'qty', 'quantity', 'مقدار'],
        'purchase_price_headers' => ['قیمت خرید', 'قیمت تمام شده', 'purchase_price', 'cost'],
    ],
    'sales' => [
        'code_headers' => ['کد کالا', 'کد', 'product_code', 'code'],
        'description_headers' => ['شرح کالا', 'شرح', 'description'],
        'unit_headers' => ['واحد کالا', 'واحد', 'unit'],
        'price_headers' => ['مبلغ', 'قیمت', 'price', 'amount'],
    ],
    'pdf' => [
        'font_path' => storage_path('fonts/Vazirmatn-Regular.ttf'),
        'font_family' => 'vazirmatn',
    ],
    'sms_ir' => [
        'base_url' => env('SMS_IR_BASE_URL', 'https://api.sms.ir/v1'),
    ],
    'telegram' => [
        'api_base' => env('TELEGRAM_API_BASE', 'https://api.telegram.org'),
    ],
];