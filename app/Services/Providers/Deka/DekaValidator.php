<?php

namespace App\Services\Providers\Deka;

use App\Models\Shipment;
use Illuminate\Support\Facades\Validator;

class DekaValidator
{
    /**
     * اعتبارسنجی داده‌های Shipment برای دکا
     */
    public function validateShipmentData(Shipment $shipment): array
    {
        $errors = [];

        // بررسی فیلدهای اجباری
        if (empty($shipment->customer_first_name)) {
            $errors[] = 'نام گیرنده الزامی است';
        }

        if (empty($shipment->customer_last_name)) {
            $errors[] = 'نام خانوادگی گیرنده الزامی است';
        }

        if (empty($shipment->mobile)) {
            $errors[] = 'شماره موبایل گیرنده الزامی است';
        }

        if (empty($shipment->address)) {
            $errors[] = 'آدرس گیرنده الزامی است';
        }

        if (empty($shipment->destination_city)) {
            $errors[] = 'شهر مقصد الزامی است';
        }

        // بررسی فرمت موبایل
        if (!preg_match('/^09\d{9}$/', $shipment->mobile)) {
            $errors[] = 'فرمت شماره موبایل صحیح نیست';
        }

        // بررسی receptor
        if (!$shipment->receptor) {
            $errors[] = 'پذیرنده (receptor) مشخص نشده است';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * اعتبارسنجی فیلدهای داده دکا
     */
    public function validateDekaData(array $data): array
    {
        // اگر payload به شکل SaveParcels باشد، هر parcel را جداگانه بررسی می‌کنیم
        if (isset($data['Parcels']) && is_array($data['Parcels'])) {
            $errors = [];

            foreach ($data['Parcels'] as $index => $parcel) {
                if (!is_array($parcel)) {
                    $errors[] = "فیلد Parcels[{$index}] نامعتبر است";
                    continue;
                }

                $parcelValidation = $this->validateSingleParcel($parcel);
                foreach ($parcelValidation['errors'] as $err) {
                    // برای دیباگ بهتر، ایندکس پارسل را به خطا اضافه می‌کنیم
                    $errors[] = "Parcels[{$index}]: {$err}";
                }
            }

            return [
                'valid' => empty($errors),
                'errors' => $errors,
            ];
        }

        // در غیر این صورت، فرض می‌کنیم یک parcel (flat) دریافت کرده‌ایم
        return $this->validateSingleParcel($data);
    }

    /**
     * اعتبارسنجی یک parcel تکی (flat)
     */
    private function validateSingleParcel(array $data): array
    {
        $errors = [];

        // بررسی فیلدهای اجباری با نام واقعی (camelCase)
        $requiredFields = [
            'serviceID' => 'serviceID',
            'serviceType' => 'serviceType',
            'contractID' => 'contractID',
            'destCityID' => 'destCityID',
            'sourceCityID' => 'sourceCityID',
            'serialNo' => 'serialNo',
            'receiverFirstName' => 'receiverFirstName',
            'receiverLastName' => 'receiverLastName',
            'receiverAddress' => 'receiverAddress',
            'receiverMobile' => 'receiverMobile',
            'weight' => 'weight',
            'length' => 'length',
            'width' => 'width',
            'height' => 'height',
            'contentAmount' => 'contentAmount',
            'contents' => 'contents',
        ];

        foreach ($requiredFields as $field => $fieldName) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[] = "فیلد {$fieldName} الزامی است";
            }
        }

        // بررسی نوع داده‌ها
        if (isset($data['serviceID']) && !is_numeric($data['serviceID'])) {
            $errors[] = 'serviceID باید عدد باشد';
        }

        if (isset($data['serviceType']) && !in_array($data['serviceType'], [1, 2])) {
            $errors[] = 'serviceType باید 1 یا 2 باشد';
        }

        if (isset($data['weight']) && (!is_numeric($data['weight']) || $data['weight'] < 1)) {
            $errors[] = 'weight باید عدد مثبت باشد';
        }

        if (isset($data['receiverMobile']) && !preg_match('/^09\d{9}$/', $data['receiverMobile'])) {
            $errors[] = 'فرمت receiverMobile صحیح نیست';
        }

        if (isset($data['contents']) && mb_strlen($data['contents']) > 300) {
            $errors[] = 'contents نباید بیشتر از 300 کاراکتر باشد';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

