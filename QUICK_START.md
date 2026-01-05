# 🚀 راهنمای سریع شروع

## مراحل اجرا (5 دقیقه)

### 1️⃣ ساخت دیتابیس‌ها
```sql
CREATE DATABASE panel_core CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE panel_orders CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE panel_payment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2️⃣ تنظیم .env
به فایل `.env` اضافه کنید:
```env
DB_CONNECTION=core_db

CORE_DB_HOST=127.0.0.1
CORE_DB_DATABASE=panel_core
CORE_DB_USERNAME=root
CORE_DB_PASSWORD=

ORDERS_DB_HOST=127.0.0.1
ORDERS_DB_DATABASE=panel_orders
ORDERS_DB_USERNAME=root
ORDERS_DB_PASSWORD=

PAYMENT_DB_HOST=127.0.0.1
PAYMENT_DB_DATABASE=panel_payment
PAYMENT_DB_USERNAME=root
PAYMENT_DB_PASSWORD=
```

### 3️⃣ اجرای Migration
```bash
php artisan migrate --database=core_db --path=database/migrations/core_db
php artisan migrate --database=orders_db --path=database/migrations/orders_db
```

### 4️⃣ اجرای Seeder
```bash
php artisan db:seed --class=UserSeeder
```

### 5️⃣ تست
```bash
php artisan tinker
>>> \App\Models\User::count();
>>> \App\Models\Shipment::count();
```

✅ **تمام!** سیستم آماده است.

برای جزئیات بیشتر: `DATABASE_MIGRATION_GUIDE.md`

