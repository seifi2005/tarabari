# 📚 راهنمای مهاجرت به معماری Multi-Database

## 🎯 خلاصه تغییرات

پروژه از **یک دیتابیس** به **سه دیتابیس جداگانه** مهاجرت داده شده است:

1. **`core_db`** - احراز هویت و کاربران
2. **`orders_db`** - سفارشات و محموله‌ها
3. **`payment_db`** - پرداخت (آماده برای آینده)

همچنین جدول `order_items` به دو جدول تقسیم شده:
- **`order_items`** - اطلاعات محصول
- **`order_item_pricing`** - اطلاعات مالی و قیمت‌گذاری

---

## 🔧 مراحل نصب و راه‌اندازی

### مرحله 1: ایجاد دیتابیس‌ها

```sql
CREATE DATABASE panel_core CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE panel_orders CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE panel_payment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### مرحله 2: تنظیم فایل `.env`

به فایل `.env` خود این متغیرها را اضافه کنید:

```env
# دیتابیس پیش‌فرض
DB_CONNECTION=core_db
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=

# دیتابیس هسته (کاربران و پذیرنده‌ها)
CORE_DB_HOST=127.0.0.1
CORE_DB_DATABASE=panel_core
CORE_DB_USERNAME=root
CORE_DB_PASSWORD=

# دیتابیس سفارشات
ORDERS_DB_HOST=127.0.0.1
ORDERS_DB_DATABASE=panel_orders
ORDERS_DB_USERNAME=root
ORDERS_DB_PASSWORD=

# دیتابیس پرداخت
PAYMENT_DB_HOST=127.0.0.1
PAYMENT_DB_DATABASE=panel_payment
PAYMENT_DB_USERNAME=root
PAYMENT_DB_PASSWORD=
```

### مرحله 3: اجرای Migration ها

```bash
# مهاجرت دیتابیس هسته
php artisan migrate --database=core_db --path=database/migrations/core_db

# مهاجرت دیتابیس سفارشات
php artisan migrate --database=orders_db --path=database/migrations/orders_db

# مهاجرت دیتابیس پرداخت (در آینده)
php artisan migrate --database=payment_db --path=database/migrations/payment_db
```

### مرحله 4: اجرای Seeder ها

```bash
# ایجاد کاربران پیش‌فرض
php artisan db:seed --class=UserSeeder --database=core_db

# ایجاد پذیرنده‌های نمونه (اختیاری)
php artisan db:seed --class=ReceptorSeeder --database=core_db
```

---

## 📊 ساختار دیتابیس‌ها

### دیتابیس 1: `core_db`

```
core_db (هسته سیستم)
├── users                       → کاربران
├── receptors                   → پذیرنده‌ها
├── otp_codes                   → کدهای یکبار مصرف
├── personal_access_tokens      → توکن‌های Sanctum
├── password_reset_tokens       → بازیابی رمز عبور
├── failed_jobs                 → کارهای ناموفق
└── jobs                        → صف کارها
```

### دیتابیس 2: `orders_db`

```
orders_db (سفارشات)
├── shipments                   → محموله‌ها
├── order_items                 → آیتم‌های سفارش (محصول)
└── order_item_pricing          → قیمت‌گذاری آیتم‌ها (مالی)
```

### دیتابیس 3: `payment_db`

```
payment_db (پرداخت - آینده)
└── (خالی - آماده توسعه)
```

---

## ⚠️ نکات مهم

### 1. Foreign Key ها

**قبل:**
```php
$table->foreignId('receptor_id')->constrained();
```

**بعد:**
```php
// FK بین دیتابیس‌های مختلف کار نمی‌کند
$table->unsignedBigInteger('receptor_id')->nullable();
```

### 2. Relationships

**Shipment → Receptor (دیتابیس‌های مختلف):**
```php
public function receptor()
{
    return $this->belongsTo(Receptor::class)->on('core_db');
}
```

### 3. Eager Loading

```php
// قبل
$shipment = Shipment::with(['orderItems', 'receptor'])->find($id);

// بعد
$shipment = Shipment::with(['orderItems.pricing', 'receptor'])->find($id);
```

### 4. دسترسی به داده‌های OrderItem

```php
$orderItem = OrderItem::with('pricing')->find(1);

// دسترسی به قیمت
$total = $orderItem->total_price;  // از attribute helper
$name = $orderItem->name;          // از attribute helper

// یا مستقیم از relation
$total = $orderItem->pricing->total;
$name = $orderItem->pricing->item_name;
```

---

## 🧪 تست

### تست اتصال دیتابیس‌ها

```php
// تست core_db
\DB::connection('core_db')->table('users')->count();

// تست orders_db
\DB::connection('orders_db')->table('shipments')->count();

// تست payment_db
\DB::connection('payment_db')->getPdo();
```

### تست API ها

```bash
# ورود
curl -X POST http://localhost/panel/public/api/auth/login/password \
  -H "Content-Type: application/json" \
  -d '{"username":"superadmin","password":"superadmin123"}'

# دریافت لیست سفارشات
curl -X GET http://localhost/panel/public/api/orders \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🔄 Rollback (بازگشت)

اگر مشکلی پیش آمد:

```bash
# Rollback migration ها
php artisan migrate:rollback --database=core_db --path=database/migrations/core_db
php artisan migrate:rollback --database=orders_db --path=database/migrations/orders_db
```

---

## 📈 مزایای معماری جدید

✅ **Scalability** - هر دیتابیس می‌تواند روی سرور جداگانه باشد  
✅ **Performance** - Query های سریع‌تر با جداول کوچک‌تر  
✅ **Security** - جداسازی داده‌های حساس  
✅ **Backup** - استراتژی backup متفاوت برای هر بخش  
✅ **Development** - تیم‌های مختلف روی بخش‌های مختلف  
✅ **Reporting** - گزارش‌های مالی سریع‌تر از `order_item_pricing`  
✅ **Future-ready** - آماده برای Microservices  

---

## 🆘 عیب‌یابی

### خطا: Connection refused

```bash
# چک کردن اتصال
php artisan tinker
>>> \DB::connection('core_db')->getPdo();
```

### خطا: Table doesn't exist

```bash
# اجرای مجدد migration
php artisan migrate:fresh --database=core_db --path=database/migrations/core_db
```

### خطا: Class OrderItemPricing not found

```bash
# پاک کردن cache
php artisan clear-compiled
composer dump-autoload
```

---

## 📞 پشتیبانی

برای سوالات و مشکلات، با تیم توسعه تماس بگیرید.

**تاریخ آخرین بروزرسانی:** 2025-12-22

