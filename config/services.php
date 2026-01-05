<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'kavenegar' => [
        'api_key' => env('KAVENEGAR_API_KEY'),
        'template' => env('KAVENEGAR_TEMPLATE', 'hamtayar-otp'),
        'sender' => env('KAVENEGAR_SENDER', null), // شماره خط ارسال کننده (اختیاری)
        'sms_template_customer_lookup' => env('KAVENEGAR_SMS_TEMPLATE_CUSTOMER_LOOKUP', 'register-cargo'), // نام template در Kavenegar برای SMS به مشتری
        'sms_template_customer' => env('KAVENEGAR_SMS_TEMPLATE_CUSTOMER', 'خریدار گرامی {customer_name}. سفارش شماره {order_id} در تاریخ {order_register_date} سامانه ترابری برای ارسال ثبت اولیه شد'),
        'sms_template_admin' => env('KAVENEGAR_SMS_TEMPLATE_ADMIN', 'مدیر سیستم. سفارش شماره {order_id} در تاریخ {order_register_date} سامانه ترابری برای ارسال ثبت اولیه شد'),
    ],

];
