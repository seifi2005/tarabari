-- ================================================
-- اسکریپت ساخت دیتابیس‌ها
-- ================================================
-- این فایل را با mysql یا phpMyAdmin اجرا کنید
-- ================================================

-- حذف دیتابیس‌های قبلی (اختیاری - فقط برای شروع از صفر)
-- DROP DATABASE IF EXISTS panel_core;
-- DROP DATABASE IF EXISTS panel_orders;
-- DROP DATABASE IF EXISTS panel_payment;

-- ساخت دیتابیس هسته سیستم (احراز هویت و کاربران)
CREATE DATABASE IF NOT EXISTS panel_core 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- ساخت دیتابیس سفارشات (محموله‌ها و آیتم‌ها)
CREATE DATABASE IF NOT EXISTS panel_orders 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- ساخت دیتابیس پرداخت (آماده برای آینده)
CREATE DATABASE IF NOT EXISTS panel_payment 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- نمایش دیتابیس‌های ساخته شده
SHOW DATABASES LIKE 'panel_%';

-- ================================================
-- دستورات اجرا:
-- ================================================
-- روش 1: از خط فرمان
--   mysql -u root -p < CREATE_DATABASES.sql
--
-- روش 2: از MySQL CLI
--   mysql -u root -p
--   source CREATE_DATABASES.sql
--
-- روش 3: از phpMyAdmin
--   SQL → کپی محتوای این فایل → اجرا
-- ================================================

