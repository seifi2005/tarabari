<?php

namespace App\Services;

use App\Models\OtpCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OtpService
{
    private string $apiKey;
    private string $template;

    public function __construct()
    {
        $this->apiKey = config('services.kavenegar.api_key');
        $this->template = config('services.kavenegar.template', 'otp');
    }

    public function generateOtp(string $mobile): array
    {
        // حذف OTP های قبلی استفاده نشده
        OtpCode::where('mobile', $mobile)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->delete();

        // تولید کد 6 رقمی
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // ذخیره OTP
        $otp = OtpCode::create([
            'mobile' => $mobile,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(5),
            'used' => false,
        ]);

        // ارسال SMS با Kavenegar Lookup
        try {
            $this->sendSmsViaLookup($mobile, $code);
            
            return [
                'success' => true,
                'message' => 'OTP code sent successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send OTP SMS via Kavenegar: ' . $e->getMessage(), [
                'mobile' => $mobile,
                'code' => $code,
                'error' => $e->getTraceAsString(),
            ]);

            // در حالت debug کد را در لاگ نگه دارید
            if (config('app.debug')) {
                Log::info("OTP Code for {$mobile}: {$code}");
            }

            throw $e;
        }
    }

    /**
     * ارسال SMS با استفاده از متد Lookup Kavenegar
     * این متد نیازی به شماره خط ندارد و از شماره پیش‌فرض Kavenegar استفاده می‌کند
     */
    private function sendSmsViaLookup(string $mobile, string $code): void
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->post("https://api.kavenegar.com/v1/{$this->apiKey}/verify/lookup.json", [
            'receptor' => $mobile,
            'token' => $code,
            'template' => $this->template,
        ]);

        if ($response->failed()) {
            $errorBody = $response->body();
            Log::error('Kavenegar API request failed', [
                'status' => $response->status(),
                'body' => $errorBody,
            ]);
            throw new \Exception('Kavenegar API error: ' . $errorBody);
        }

        $result = $response->json();
        
        // بررسی وضعیت پاسخ
        if (isset($result['return']['status']) && $result['return']['status'] != 200) {
            $errorMessage = $result['return']['message'] ?? 'Unknown error';
            throw new \Exception('Kavenegar API error: ' . $errorMessage);
        }

        Log::info('OTP SMS sent successfully via Kavenegar Lookup', [
            'mobile' => $mobile,
            'template' => $this->template,
        ]);
    }

    public function verifyOtp(string $mobile, string $code): bool
    {
        $otp = OtpCode::where('mobile', $mobile)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if ($otp && $otp->isValid()) {
            $otp->markAsUsed();
            return true;
        }

        return false;
    }
}

