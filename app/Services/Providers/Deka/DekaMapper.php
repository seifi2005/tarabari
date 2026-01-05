<?php

namespace App\Services\Providers\Deka;

use App\Models\Shipment;
use App\Models\Provider;

class DekaMapper
{
    /**
     * تبدیل Shipment به فرمت دکا
     */
    public function mapShipmentToDeka(Shipment $shipment, Provider $provider): array
    {
        $config = $provider->config ?? [];

        // تبدیل شهرها
        $sourceCityId = $this->getCityId($shipment->origin ?? 'تهران');
        $destCityId = $this->getCityId($shipment->destination_city);

        // تبدیل واحدها
        $weight = $this->estimateWeight($shipment); // به گرم
        $dimensions = $this->estimateDimensions($shipment); // به سانتی‌متر
        $contentAmount = $this->tomanToRial($shipment->total_price); // به ریال

        // ساخت serialNo یکتا
        $serialNo = $this->generateSerialNo($shipment);

        // تبدیل orderItems به contents
        $contents = $this->convertOrderItemsToContents($shipment);

        return [
            // فیلدهای از config Provider (مطمئن شویم که همه integer هستند)
            'serviceID' => (int) ($config['service_id'] ?? 5),
            'serviceType' => (int) ($config['service_type'] ?? 2), // 1: درون‌شهری، 2: بین‌شهری
            'contractID' => (int) ($config['contract_id'] ?? 0),
            'parcelTypeID' => (int) ($config['parcel_type_id'] ?? 1),
            'paymentTypeID' => (int) ($config['payment_type_id'] ?? 3),
            'characterType' => (int) ($config['character_type'] ?? 1), // 1: حقیقی، 2: حقوقی
            'needPacking' => (int) ($config['need_packing'] ?? 0), // 0: نیاز دارد، 1: نیاز ندارد

            // اطلاعات شهر (مطمئن شویم که integer هستند)
            'destCityID' => (int) $destCityId,
            'sourceCityID' => (int) $sourceCityId,

            // شماره سریال (یکتا) - مطمئن شویم که string است
            'serialNo' => (string) $serialNo,

            // اطلاعات فرستنده (از Receptor)
            'senderFirstName' => $shipment->receptor->first_name ?? 'فرستنده',
            'senderLastName' => $shipment->receptor->last_name ?? '',
            'senderAddress' => $shipment->receptor->company_name ?? 'تهران',
            'senderMobile' => $shipment->receptor->mobile ?? '09123456789',
            'senderStreet' => '',
            'senderZone' => '',
            'senderNID' => '',
            'senderPhone' => '',
            'senderPostalCode' => '',

            // اطلاعات گیرنده (از Shipment) - مطمئن شویم که string هستند
            'receiverFirstName' => (string) ($shipment->customer_first_name ?? ''),
            'receiverLastName' => (string) ($shipment->customer_last_name ?? ''),
            'receiverAddress' => (string) ($shipment->address ?? ''),
            'receiverMobile' => (string) ($shipment->mobile ?? ''),
            'receiverStreet' => $this->extractStreet($shipment->address),
            'receiverZone' => '',
            'receiverNID' => '',
            'receiverPhone' => '',
            'receiverPostalCode' => $shipment->postcode ?? '',

            // وزن و ابعاد (مطمئن شویم که همه integer هستند)
            'weight' => (int) $weight,
            'length' => (int) $dimensions['length'],
            'width' => (int) $dimensions['width'],
            'height' => (int) $dimensions['height'],
            'outsizeFlag' => $dimensions['is_oversize'] ? 1 : 0,

            // ارزش و محتویات
            'contentAmount' => (int) $contentAmount,
            'contents' => (string) $contents,

            // سایر فیلدها
            'lstSideServices' => [],
            'sendPlaceFlag' => 1,
            'Lat' => '',
            'Lon' => '',
            'boxID' => $config['box_id'] ?? null,
            'paymentDate' => '',
            'customerHasBox' => $config['customer_has_box'] ?? 1,
            'SenderLockerID' => '',
            'ReceiverLockerID' => '',
            'codTypeID' => 0,
            'categoryTypeID' => null,
            'codServiceAmount' => 0,
            'suggestedDateTime' => '',
        ];
    }

    /**
     * تبدیل تومان به ریال
     */
    public function tomanToRial(float $toman): int
    {
        return (int) ($toman * 10);
    }

    /**
     * تبدیل کیلوگرم به گرم
     */
    public function kgToGram(float $kg): int
    {
        return (int) ($kg * 1000);
    }

    /**
     * تخمین وزن (مقادیر تستی - فعلاً)
     */
    private function estimateWeight(Shipment $shipment): int
    {
        // TODO: بعداً از فیلد weight در Shipment استفاده می‌شود
        // فعلاً مقدار تستی
        $defaultWeight = 500; // 500 گرم پیش‌فرض
        return $defaultWeight;
    }

    /**
     * تخمین ابعاد (مقادیر تستی - فعلاً)
     */
    private function estimateDimensions(Shipment $shipment): array
    {
        // TODO: بعداً از فیلدهای length, width, height در Shipment استفاده می‌شود
        // فعلاً مقادیر تستی
        return [
            'length' => 20,
            'width' => 15,
            'height' => 10,
            'is_oversize' => false,
        ];
    }

    /**
     * ساخت serialNo یکتا
     */
    public function generateSerialNo(Shipment $shipment): string
    {
        // استفاده از system_order_id یا تولید یکتا
        return $shipment->system_order_id ?? 'ORD-' . time() . '-' . $shipment->id;
    }

    /**
     * تبدیل orderItems به contents
     */
    private function convertOrderItemsToContents(Shipment $shipment): string
    {
        $items = $shipment->orderItems->load('pricing');

        if ($items->isEmpty()) {
            return 'مرسوله';
        }

        $contents = $items->map(function ($item) {
            $name = $item->pricing->item_name ?? 'محصول';
            $quantity = $item->quantity ?? 1;
            return "{$name} ({$quantity})";
        })->implode('، ');

        // حداکثر 300 کاراکتر
        return mb_substr($contents, 0, 300);
    }

    /**
     * استخراج نام خیابان از آدرس
     */
    private function extractStreet(string $address): string
    {
        // ساده: برگرداندن آدرس کامل (یا می‌توان بهبود داد)
        return mb_substr($address, 0, 100);
    }

    /**
     * تبدیل نام شهر به کد شهر (نیاز به API یا جدول mapping)
     * TODO: استفاده از API GetCities یا جدول mapping
     */
    public function getCityId(string $cityName): int
    {
        // Mapping موقت (باید از API GetCities یا جدول cities استفاده شود)
        $cityMapping = [
            'تهران' => 447,
            'اصفهان' => 123,
            'مشهد' => 138,
            'شیراز' => 149,
            'تبریز' => 165,
            'قم' => 173,
            'اهواز' => 180,
            'کرج' => 189,
            'رشت' => 198,
            'کرمانشاه' => 207,
        ];

        return $cityMapping[$cityName] ?? 447; // پیش‌فرض: تهران
    }
}

