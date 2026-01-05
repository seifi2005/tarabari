<?php

namespace App\Services\Providers;

use App\Models\Provider;
use Illuminate\Support\Facades\Log;

abstract class BaseProvider
{
    protected Provider $provider;
    protected string $baseUrl;
    protected string $username;
    protected string $password;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        $this->baseUrl = rtrim($provider->api_base_url ?? '', '/');
        $this->username = $provider->api_username ?? '';
        $this->password = $provider->api_password ?? '';
    }

    /**
     * Log درخواست
     */
    protected function logRequest(string $method, string $url, array $data = []): void
    {
        Log::debug('Provider API Request', [
            'provider' => $this->provider->code,
            'method' => $method,
            'url' => $url,
            'data' => $this->sanitizeLogData($data),
        ]);
    }

    /**
     * Log پاسخ
     */
    protected function logResponse(string $url, $response): void
    {
        Log::debug('Provider API Response', [
            'provider' => $this->provider->code,
            'url' => $url,
            'status' => is_object($response) && method_exists($response, 'status') 
                ? $response->status() 
                : null,
        ]);
    }

    /**
     * پاکسازی داده‌ها برای لاگ (حذف اطلاعات حساس)
     */
    protected function sanitizeLogData(array $data): array
    {
        $sensitive = ['password', 'api_password', 'token'];

        foreach ($sensitive as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***';
            }
        }

        return $data;
    }
}

