<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $apiKey;
    private TemplateService $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->apiKey = config('services.kavenegar.api_key');
        $this->templateService = $templateService;
    }

    /**
     * ارسال SMS به خریدار
     * 
     * @param string $mobile شماره موبایل خریدار
     * @param array $variables متغیرهای قالب
     * @param string|null $customTemplate قالب سفارشی (اگر null باشد از default استفاده می‌شود)
     * @return bool موفقیت ارسال
     */
    public function sendToCustomer(string $mobile, array $variables, ?string $customTemplate = null): bool
    {
        // استفاده از Lookup API با template register-cargo
        // اگر customTemplate تنظیم شده باشد، از آن استفاده می‌کنیم
        $templateName = $customTemplate ?? config('services.kavenegar.sms_template_customer_lookup', 'register-cargo');
        
        // تبدیل متغیرها به فرمت token و token2 برای Kavenegar Lookup
        // token = نام پذیرنده (receptor_name)
        // token2 = شماره سفارش (system_order_id)
        $token = $variables['receptor_name'] ?? '';
        $token2 = $variables['order_id'] ?? '';
        
        return $this->sendSmsViaLookup($mobile, $token, $token2, $templateName, 'customer');
    }

    /**
     * ارسال SMS به ادمین
     * 
     * @param string $mobile شماره موبایل ادمین
     * @param array $variables متغیرهای قالب
     * @param string|null $customTemplate قالب سفارشی (اگر null باشد از default استفاده می‌شود)
     * @return bool موفقیت ارسال
     */
    public function sendToAdmin(string $mobile, array $variables, ?string $customTemplate = null): bool
    {
        $template = $customTemplate ?? config('services.kavenegar.sms_template_admin');
        $message = $this->templateService->replaceVariables($template, $variables);

        // اگر از Lookup API استفاده کنیم (نیازی به sender نیست)
        $useLookup = config('services.kavenegar.use_lookup_for_sms', false);
        
        if ($useLookup) {
            return $this->sendSmsViaLookup($mobile, $message, 'admin');
        }

        return $this->sendSms($mobile, $message, 'admin');
    }

    /**
     * ارسال SMS با استفاده از Kavenegar Send API
     * 
     * @param string $mobile شماره موبایل
     * @param string $message متن پیام
     * @param string $type نوع (customer/admin) برای لاگ
     * @return bool موفقیت ارسال
     */
    private function sendSms(string $mobile, string $message, string $type = 'customer'): bool
    {
        if (empty($this->apiKey)) {
            Log::error('Kavenegar API key not configured', [
                'mobile' => $mobile,
                'type' => $type,
            ]);
            return false;
        }

        // بررسی فرمت شماره موبایل
        if (!preg_match('/^09\d{9}$/', $mobile)) {
            Log::error('Invalid mobile number format', [
                'mobile' => $mobile,
                'type' => $type,
                'message' => 'Mobile number must start with 09 and be 11 digits',
                'format_expected' => '09XXXXXXXXX',
            ]);
            return false;
        }

        try {
            // استفاده از Send API
            // توجه: Kavenegar نیاز به شماره sender دارد
            // اگر sender تنظیم نشده باشد، از شماره پیش‌فرض استفاده می‌شود
            $sender = config('services.kavenegar.sender', null);
            
            $params = [
                'receptor' => $mobile,
                'message' => $message,
            ];
            
            // اگر شماره sender تنظیم شده باشد، اضافه می‌کنیم
            // اگر تنظیم نشده باشد، Kavenegar از شماره پیش‌فرض استفاده می‌کند
            if ($sender) {
                $params['sender'] = $sender;
            }

            $response = Http::asForm()->post("https://api.kavenegar.com/v1/{$this->apiKey}/sms/send.json", $params);

            if ($response->failed()) {
                Log::error('Kavenegar SMS send failed', [
                    'mobile' => $mobile,
                    'type' => $type,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'message_preview' => substr($message, 0, 50),
                    'sender' => $sender,
                ]);
                return false;
            }

            $result = $response->json();

            // بررسی وضعیت پاسخ
            if (isset($result['return']['status']) && $result['return']['status'] != 200) {
                $errorMessage = $result['return']['message'] ?? 'Unknown error';
                Log::error('Kavenegar API error', [
                    'mobile' => $mobile,
                    'type' => $type,
                    'error' => $errorMessage,
                    'full_response' => $result,
                    'sender' => $sender,
                ]);
                return false;
            }

            Log::info('SMS sent successfully', [
                'mobile' => $mobile,
                'type' => $type,
                'message_id' => $result['entries'][0]['messageid'] ?? null,
                'message_preview' => substr($message, 0, 50),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error sending SMS', [
                'mobile' => $mobile,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'message_preview' => substr($message, 0, 50),
            ]);
            return false;
        }
    }

    /**
     * ارسال SMS با استفاده از Kavenegar Lookup API
     * این متد از template های Kavenegar استفاده می‌کند و نیازی به شماره خط ندارد
     * 
     * @param string $mobile شماره موبایل
     * @param string $token مقدار token (مثلاً نام پذیرنده)
     * @param string $token2 مقدار token2 (مثلاً شماره سفارش)
     * @param string $templateName نام template در Kavenegar
     * @param string $type نوع (customer/admin) برای لاگ
     * @return bool موفقیت ارسال
     */
    private function sendSmsViaLookup(string $mobile, string $token, string $token2, string $templateName, string $type = 'customer'): bool
    {
        if (empty($this->apiKey)) {
            Log::error('Kavenegar API key not configured', [
                'mobile' => $mobile,
                'type' => $type,
            ]);
            return false;
        }

        // بررسی فرمت شماره موبایل
        if (!preg_match('/^09\d{9}$/', $mobile)) {
            Log::error('Invalid mobile number format', [
                'mobile' => $mobile,
                'type' => $type,
                'message' => 'Mobile number must start with 09 and be 11 digits',
            ]);
            return false;
        }

        try {
            // استفاده از Lookup API با template
            $response = Http::asForm()->post("https://api.kavenegar.com/v1/{$this->apiKey}/verify/lookup.json", [
                'receptor' => $mobile,
                'token' => $token,
                'token2' => $token2,
                'template' => $templateName,
            ]);

            if ($response->failed()) {
                Log::error('Kavenegar Lookup API failed', [
                    'mobile' => $mobile,
                    'type' => $type,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'template' => $templateName,
                    'token' => $token,
                    'token2' => $token2,
                ]);
                return false;
            }

            $result = $response->json();

            // بررسی وضعیت پاسخ
            if (isset($result['return']['status']) && $result['return']['status'] != 200) {
                $errorMessage = $result['return']['message'] ?? 'Unknown error';
                Log::error('Kavenegar Lookup API error', [
                    'mobile' => $mobile,
                    'type' => $type,
                    'error' => $errorMessage,
                    'full_response' => $result,
                    'template' => $templateName,
                ]);
                return false;
            }

            Log::info('SMS sent successfully via Kavenegar Lookup', [
                'mobile' => $mobile,
                'type' => $type,
                'template' => $templateName,
                'token' => $token,
                'token2' => $token2,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error sending SMS via Kavenegar Lookup', [
                'mobile' => $mobile,
                'type' => $type,
                'template' => $templateName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}

