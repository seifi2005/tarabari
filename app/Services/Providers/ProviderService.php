<?php

namespace App\Services\Providers;

use App\Models\Provider;
use App\Services\Providers\Deka\DekaService;

class ProviderService
{
    /**
     * دریافت سرویس provider مناسب
     */
    public function getProvider(Provider $provider): ProviderInterface
    {
        switch ($provider->code) {
            case 'deka':
                return new DekaService($provider);
            
            // TODO: اضافه کردن provider های دیگر
            // case 'tipax':
            //     return new TipaxService($provider);
            // case 'mahex':
            //     return new MahexService($provider);
            
            default:
                throw new \Exception("Provider '{$provider->code}' not found");
        }
    }

    /**
     * ارسال محموله به provider مناسب
     */
    public function sendShipment(\App\Models\Shipment $shipment, Provider $provider): array
    {
        $providerService = $this->getProvider($provider);
        return $providerService->createShipment($shipment);
    }

    /**
     * رهگیری محموله از provider
     */
    public function trackShipment(string $trackingNumber, Provider $provider): array
    {
        $providerService = $this->getProvider($provider);
        return $providerService->getTrackingStatus($trackingNumber);
    }

    /**
     * ابطال محموله در provider
     */
    public function cancelShipment(string $trackingNumber, Provider $provider, int $reasonId = 1): bool
    {
        $providerService = $this->getProvider($provider);
        return $providerService->cancelShipment($trackingNumber, $reasonId);
    }
}

