<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ReceptorLogService
{
    /**
     * لاگ کردن با فایل جداگانه برای هر پذیرنده
     * 
     * @param int $receptorId شناسه پذیرنده
     * @param string $level سطح لاگ (info, error, warning, etc.)
     * @param string $message پیام لاگ
     * @param array $context اطلاعات اضافی
     */
    public function log(int $receptorId, string $level, string $message, array $context = []): void
    {
        $logPath = storage_path("logs/receptors/receptor-{$receptorId}.log");
        
        // اطمینان از وجود دایرکتوری
        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // ساخت Channel داینامیک
        $channel = Log::build([
            'driver' => 'daily', // Rotation خودکار
            'path' => $logPath,
            'level' => 'debug',
            'days' => 30, // نگهداری 30 روز
        ]);
        
        $channel->{$level}($message, $context);
    }

    /**
     * لاگ info
     */
    public function info(int $receptorId, string $message, array $context = []): void
    {
        $this->log($receptorId, 'info', $message, $context);
    }

    /**
     * لاگ error
     */
    public function error(int $receptorId, string $message, array $context = []): void
    {
        $this->log($receptorId, 'error', $message, $context);
    }

    /**
     * لاگ warning
     */
    public function warning(int $receptorId, string $message, array $context = []): void
    {
        $this->log($receptorId, 'warning', $message, $context);
    }
}

