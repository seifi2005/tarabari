<?php

namespace App\Services\Providers\Deka;

use App\Models\Shipment;
use App\Models\Provider;
use App\Services\Providers\BaseProvider;
use App\Services\Providers\ProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DekaService extends BaseProvider implements ProviderInterface
{
    private ?string $token = null;
    private ?Carbon $tokenExpiresAt = null;
    private string $baseUrl = 'https://services.dekapost.ir';

    public function __construct(Provider $provider)
    {
        parent::__construct($provider);
        $this->username = $provider->api_username;
        $this->password = $provider->api_password;
    }

    /**
     * دریافت Token (با Cache)
     */
    public function authenticate(): string
    {
        // اگر token موجود و معتبر است، برگردان
        if ($this->token && $this->isTokenValid()) {
            return $this->token;
        }

        // چک کردن Cache
        $cacheKey = 'deka_token_' . $this->provider->id;
        $cachedToken = Cache::get($cacheKey);

        if ($cachedToken && $this->isTokenStringValid($cachedToken)) {
            $this->token = $cachedToken;
            $this->parseTokenExpiry();
            return $this->token;
        }

        // درخواست token جدید
        try {
            $response = Http::withHeaders([
                'Referer' => config('app.url'),
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->baseUrl . '/clubapi/token', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->failed()) {
                Log::error('Deka authentication failed', [
                    'provider_id' => $this->provider->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to authenticate with Deka: HTTP ' . $response->status());
            }

            // Response فقط یک string است (JWT Token)
            $this->token = trim($response->body());

            if (empty($this->token)) {
                throw new \Exception('Empty token received from Deka');
            }

            // Parse expiry
            $this->parseTokenExpiry();

            // Cache token (تا 5 دقیقه قبل از expiry)
            $cacheMinutes = $this->tokenExpiresAt
                ? max(1, (int) ($this->tokenExpiresAt->diffInMinutes(now()) - 5))
                : 55; // پیش‌فرض 55 دقیقه

            Cache::put($cacheKey, $this->token, now()->addMinutes($cacheMinutes));

            Log::info('Deka token obtained', [
                'provider_id' => $this->provider->id,
                'expires_at' => $this->tokenExpiresAt?->toDateTimeString(),
            ]);

            return $this->token;

        } catch (\Exception $e) {
            Log::error('Deka authentication error', [
                'provider_id' => $this->provider->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * بررسی معتبر بودن Token (از متغیر)
     */
    private function isTokenValid(): bool
    {
        if (!$this->token || !$this->tokenExpiresAt) {
            return false;
        }

        // 5 دقیقه قبل از انقضا refresh کنیم
        return $this->tokenExpiresAt->copy()->subMinutes(5)->isFuture();
    }

    /**
     * بررسی معتبر بودن Token (از string)
     */
    private function isTokenStringValid(string $token): bool
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }

            $payload = json_decode(base64_decode($parts[1]), true);
            if (!isset($payload['exp'])) {
                return false;
            }

            $expiresAt = Carbon::createFromTimestamp($payload['exp']);
            return $expiresAt->copy()->subMinutes(5)->isFuture();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * استخراج expiry از JWT Token
     */
    private function parseTokenExpiry(): void
    {
        try {
            $parts = explode('.', $this->token);
            if (count($parts) !== 3) {
                throw new \Exception('Invalid JWT format');
            }

            $payload = json_decode(base64_decode($parts[1]), true);

            if (isset($payload['exp'])) {
                $this->tokenExpiresAt = Carbon::createFromTimestamp($payload['exp']);
            } else {
                // اگر exp نداشت، 1 ساعت پیش‌فرض
                $this->tokenExpiresAt = Carbon::now()->addHour();
            }
        } catch (\Exception $e) {
            // اگر decode نشد، 1 ساعت اعتبار در نظر بگیر
            $this->tokenExpiresAt = Carbon::now()->addHour();
            Log::warning('Could not parse Deka token expiry', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ساخت HTTP Client با Token
     */
    private function getHttpClient()
    {
        $token = $this->authenticate();

        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Referer' => config('app.url'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30);
    }

    /**
     * ثبت مرسوله
     */
    public function createShipment(Shipment $shipment): array
    {
        // اعتبارسنجی
        $validator = new DekaValidator();
        $validation = $validator->validateShipmentData($shipment);

        if (!$validation['valid']) {
            throw new \Exception('Invalid shipment data: ' . implode(', ', $validation['errors']));
        }

        // تبدیل به فرمت دکا
        $mapper = new DekaMapper();
        $data = $mapper->mapShipmentToDeka($shipment, $this->provider);

        // لاگ داده‌ها برای دیباگ
        Log::debug('Deka mapped data', [
            'shipment_id' => $shipment->id,
            'data_keys' => array_keys($data),
            'sample_data' => [
                'serviceID' => $data['serviceID'] ?? null,
                'serviceType' => $data['serviceType'] ?? null,
                'contractID' => $data['contractID'] ?? null,
                'destCityID' => $data['destCityID'] ?? null,
                'sourceCityID' => $data['sourceCityID'] ?? null,
                'serialNo' => $data['serialNo'] ?? null,
                'weight' => $data['weight'] ?? null,
                'length' => $data['length'] ?? null,
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
            ],
        ]);

        // اعتبارسنجی داده‌های دکا
        $dekaValidation = $validator->validateDekaData($data);
        if (!$dekaValidation['valid']) {
            Log::error('Deka validation failed', [
                'shipment_id' => $shipment->id,
                'errors' => $dekaValidation['errors'],
                'data' => $data,
            ]);
            throw new \Exception('Invalid Deka data: ' . implode(', ', $dekaValidation['errors']));
        }

        try {
            Log::info('Sending shipment to Deka', [
                'shipment_id' => $shipment->id,
                'provider_id' => $this->provider->id,
            ]);

            $response = $this->getHttpClient()
                ->post($this->baseUrl . '/clubapi/api/Parcels/SaveParcels', $data);

            if ($response->failed()) {
                Log::error('Failed to create shipment in Deka', [
                    'shipment_id' => $shipment->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to create shipment: HTTP ' . $response->status());
            }

            $result = $response->json();

            if (!isset($result['status']) || !$result['status']) {
                $message = $result['message'] ?? 'Unknown error';
                Log::error('Deka API returned error', [
                    'shipment_id' => $shipment->id,
                    'message' => $message,
                    'response' => $result,
                ]);
                throw new \Exception('Deka API error: ' . $message);
            }

            // استخراج اطلاعات
            $parcelCode = $result['data']['parcels'][0]['parcelCode'] ?? null;
            $referenceNumber = $result['data']['referenceNumber'] ?? null;

            if (empty($parcelCode)) {
                throw new \Exception('No parcel code received from Deka');
            }

            Log::info('Shipment created in Deka', [
                'shipment_id' => $shipment->id,
                'parcel_code' => $parcelCode,
                'reference_number' => $referenceNumber,
            ]);

            return [
                'success' => true,
                'tracking_number' => $parcelCode,
                'reference_number' => $referenceNumber,
                'amount' => $result['data']['amount'] ?? null,
                'tax' => $result['data']['tax'] ?? null,
                'response' => $result,
            ];

        } catch (\Exception $e) {
            Log::error('Error creating shipment in Deka', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * رهگیری مرسوله
     */
    public function getTrackingStatus(string $trackingNumber): array
    {
        try {
            $response = $this->getHttpClient()
                ->get($this->baseUrl . '/clubapi/api/Gateway/ClubParcelsTracking', [
                    'parcelCode' => $trackingNumber,
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to get tracking status: HTTP ' . $response->status());
            }

            $result = $response->json();

            if (!isset($result['status']) || !$result['status']) {
                throw new \Exception($result['message'] ?? 'Unknown error');
            }

            return $result['data'] ?? [];

        } catch (\Exception $e) {
            Log::error('Error getting Deka tracking status', [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * ابطال مرسوله
     */
    public function cancelShipment(string $trackingNumber, int $reasonId = 1): bool
    {
        try {
            $response = $this->getHttpClient()
                ->post($this->baseUrl . '/clubapi/api/Parcels/DeleteParcelList', [
                    'parcelCodes' => [$trackingNumber],
                    'voidReasonID' => $reasonId,
                    'companyID' => 1,
                    'postNodeID' => 0,
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to cancel shipment: HTTP ' . $response->status());
            }

            $result = $response->json();

            return isset($result['status']) && $result['status'];

        } catch (\Exception $e) {
            Log::error('Error canceling Deka shipment', [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * دریافت دلایل ابطال
     */
    public function getVoidReasons(): array
    {
        try {
            $response = $this->getHttpClient()
                ->get($this->baseUrl . '/clubapi/api/Parcels/GetCustomerParcelVoidReasons');

            if ($response->failed()) {
                throw new \Exception('Failed to get void reasons: HTTP ' . $response->status());
            }

            $result = $response->json();

            return $result['data'] ?? [];

        } catch (\Exception $e) {
            Log::error('Error getting Deka void reasons', [
                'provider_id' => $this->provider->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * دریافت لیست شهرها
     */
    public function getCities(): array
    {
        try {
            // این API نیاز به token ندارد
            $response = Http::timeout(30)
                ->get('https://services.dekapost.ir/ParcelPrice/api/GetCities');

            if ($response->failed()) {
                throw new \Exception('Failed to get cities: HTTP ' . $response->status());
            }

            $result = $response->json();

            return $result['data'] ?? $result ?? [];

        } catch (\Exception $e) {
            Log::error('Error getting Deka cities', [
                'provider_id' => $this->provider->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * تست اتصال و اعتبارسنجی
     */
    public function validateCredentials(): bool
    {
        try {
            $token = $this->authenticate();
            return !empty($token);
        } catch (\Exception $e) {
            Log::error('Deka credentials validation failed', [
                'provider_id' => $this->provider->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

