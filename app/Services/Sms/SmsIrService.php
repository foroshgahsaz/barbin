<?php

namespace App\Services\Sms;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsIrService
{
    public function isEnabled(): bool
    {
        return (bool) SystemSetting::getValue('sms_ir', 'is_active', false);
    }

    public function sendTemplate(string $mobile, array $parameters): array
    {
        $apiKey = SystemSetting::getValue('sms_ir', 'api_key');
        $templateId = SystemSetting::getValue('sms_ir', 'template_id');

        if (! $apiKey || ! $templateId) {
            return ['success' => false, 'message' => 'تنظیمات SMS.ir کامل نیست.'];
        }

        $endpoint = rtrim(config('barbin.sms_ir.base_url'), '/').'/send/verify';

        $payload = [
            'mobile' => $this->normalizeMobile($mobile),
            'templateId' => (int) $templateId,
            'parameters' => $parameters,
        ];

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->post($endpoint, $payload);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::warning('sms.ir send failed', ['message' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function testConnection(string $testMobile): array
    {
        $sampleParams = json_decode((string) SystemSetting::getValue('sms_ir', 'template_parameters', '[]'), true);
        if (! is_array($sampleParams) || $sampleParams === []) {
            return [
                'success' => false,
                'message' => 'پارامترهای الگو در تنظیمات تعریف نشده است.',
            ];
        }

        $result = $this->sendTemplate($testMobile, $sampleParams);
        SystemSetting::putValue('sms_ir', 'last_test_at', now()->toIso8601String());
        SystemSetting::putValue('sms_ir', 'last_error', $result['success'] ? null : json_encode($result, JSON_UNESCAPED_UNICODE));

        return $result;
    }

    public function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';
        if (str_starts_with($digits, '98')) {
            return '0'.substr($digits, 2);
        }
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '0'.$digits;
        }

        return $digits;
    }
}