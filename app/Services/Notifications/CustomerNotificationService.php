<?php

namespace App\Services\Notifications;

use App\Enums\DeliveryStatus;
use App\Models\Customer;
use App\Models\DeliveryLog;
use App\Models\SalesFile;
use App\Models\User;
use App\Services\Pdf\SecurePdfLinkService;
use App\Services\Sms\SmsIrService;
class CustomerNotificationService
{
    public function __construct(
        private readonly SmsIrService $smsIr,
        private readonly SecurePdfLinkService $pdfLinks,
    ) {}

    /**
     * @param  list<int>  $customerIds
     */
    public function notifySms(User $actor, SalesFile $salesFile, array $customerIds, bool $allowDuplicate = false): array
    {
        if (! $this->smsIr->isEnabled()) {
            return ['sent' => 0, 'skipped' => count($customerIds), 'errors' => ['SMS غیرفعال است.']];
        }

        $customers = Customer::query()
            ->where('owner_user_id', $salesFile->user_id)
            ->whereIn('id', $customerIds)
            ->where('is_active', true)
            ->where('notify_consent', true)
            ->get();

        $token = $this->pdfLinks->create($salesFile);
        $link = $this->pdfLinks->publicUrl($token);

        $sent = 0;
        $errors = [];

        foreach ($customers as $customer) {
            $idempotency = sha1($salesFile->id.'|'.$customer->id.'|sms');

            $exists = DeliveryLog::query()
                ->where('sales_file_id', $salesFile->id)
                ->where('customer_id', $customer->id)
                ->where('channel', 'sms')
                ->where('status', DeliveryStatus::Sent)
                ->exists();

            if ($exists && ! $allowDuplicate) {
                continue;
            }

            $params = $this->buildTemplateParameters($customer, $salesFile, $link);

            $log = DeliveryLog::query()->create([
                'sales_file_id' => $salesFile->id,
                'user_id' => $actor->id,
                'customer_id' => $customer->id,
                'channel' => 'sms',
                'message_text' => json_encode($params, JSON_UNESCAPED_UNICODE),
                'status' => DeliveryStatus::Processing,
                'idempotency_key' => $idempotency,
            ]);

            $result = $this->smsIr->sendTemplate($customer->mobile, $params);

            if ($result['success'] ?? false) {
                $log->update([
                    'status' => DeliveryStatus::Sent,
                    'attempts' => $log->attempts + 1,
                    'response_payload' => $result,
                ]);
                $customer->update(['last_notified_at' => now()]);
                $sent++;
            } else {
                $log->update([
                    'status' => DeliveryStatus::Failed,
                    'attempts' => $log->attempts + 1,
                    'error_message' => $result['message'] ?? json_encode($result),
                    'response_payload' => $result,
                ]);
                $errors[] = $customer->mobile;
            }
        }

        $salesFile->update(['notification_status' => $sent > 0 ? 'partial_or_sent' : 'failed']);

        return ['sent' => $sent, 'skipped' => count($customerIds) - $sent, 'errors' => $errors];
    }

    private function buildTemplateParameters(Customer $customer, SalesFile $salesFile, string $link): array
    {
        $configured = json_decode((string) \App\Models\SystemSetting::getValue('sms_ir', 'template_parameters', '[]'), true);
        if (is_array($configured) && $configured !== []) {
            $map = [
                'CUSTOMER_NAME' => $customer->full_name,
                'SALES_LIST' => $salesFile->title,
                'PDF_LINK' => $link,
            ];

            return array_map(function (array $item) use ($map) {
                $name = $item['name'] ?? $item['Name'] ?? 'param';
                $value = $map[strtoupper($name)] ?? ($item['value'] ?? '');

                return ['name' => $name, 'value' => $value];
            }, $configured);
        }

        return [
            ['name' => 'CUSTOMER_NAME', 'value' => $customer->full_name],
            ['name' => 'SALES_LIST', 'value' => $salesFile->title],
            ['name' => 'PDF_LINK', 'value' => $link],
        ];
    }
}