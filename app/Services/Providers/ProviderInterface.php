<?php

namespace App\Services\Providers;

use App\Models\Shipment;

interface ProviderInterface
{
    /**
     * ثبت مرسوله در سیستم provider
     * 
     * @param Shipment $shipment
     * @return array ['success' => bool, 'tracking_number' => string, 'reference_number' => string|null, ...]
     * @throws \Exception
     */
    public function createShipment(Shipment $shipment): array;

    /**
     * رهگیری مرسوله
     * 
     * @param string $trackingNumber
     * @return array اطلاعات رهگیری
     * @throws \Exception
     */
    public function getTrackingStatus(string $trackingNumber): array;

    /**
     * ابطال مرسوله
     * 
     * @param string $trackingNumber
     * @param int $reasonId
     * @return bool
     * @throws \Exception
     */
    public function cancelShipment(string $trackingNumber, int $reasonId = 1): bool;

    /**
     * تست اتصال و اعتبارسنجی credentials
     * 
     * @return bool
     */
    public function validateCredentials(): bool;
}

