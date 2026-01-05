# 📋 خلاصه پیاده‌سازی - جداسازی دیتابیس

## ✅ کارهای انجام شده

### 1. ساختار پوشه‌ها
```
database/migrations/
├── core_db/          ✅ ساخته شد (7 migration)
├── orders_db/        ✅ ساخته شد (3 migration)
└── payment_db/       ✅ ساخته شد (1 migration)
```

### 2. Migration ها

#### دیتابیس `core_db` (7 فایل):
- ✅ `2024_01_01_000001_create_users_table.php`
- ✅ `2024_01_01_000002_create_receptors_table.php`
- ✅ `2024_01_01_000003_create_otp_codes_table.php`
- ✅ `2024_01_01_000004_create_password_reset_tokens_table.php`
- ✅ `2024_01_01_000005_create_personal_access_tokens_table.php`
- ✅ `2024_01_01_000006_create_failed_jobs_table.php`
- ✅ `2024_01_01_000007_create_jobs_table.php`

#### دیتابیس `orders_db` (3 فایل):
- ✅ `2024_01_02_000001_create_shipments_table.php`
- ✅ `2024_01_02_000002_create_order_items_table.php` (جدید - فقط محصول)
- ✅ `2024_01_02_000003_create_order_item_pricing_table.php` (جدید - فقط مالی)

#### دیتابیس `payment_db` (1 فایل):
- ✅ `2024_01_03_000001_create_payment_structure.php` (خالی - آماده آینده)

### 3. تنظیمات

#### `config/database.php`:
- ✅ اضافه شدن connection `core_db`
- ✅ اضافه شدن connection `orders_db`
- ✅ اضافه شدن connection `payment_db`
- ✅ تغییر default connection به `core_db`

### 4. Models

#### Models موجود (آپدیت شده):
- ✅ `User.php` → اضافه `protected $connection = 'core_db';`
- ✅ `Receptor.php` → اضافه `protected $connection = 'core_db';`
- ✅ `OtpCode.php` → اضافه `protected $connection = 'core_db';`
- ✅ `Shipment.php` → اضافه `protected $connection = 'orders_db';`
- ✅ `OrderItem.php` → آپدیت کامل (حذف فیلدهای مالی + اضافه helpers)

#### Models جدید:
- ✅ `OrderItemPricing.php` → Model جدید برای قیمت‌گذاری

### 5. Jobs

#### `ProcessOrderJob.php`:
- ✅ اضافه `use App\Models\OrderItemPricing;`
- ✅ تغییر منطق ذخیره‌سازی: ایجاد `OrderItem` + `OrderItemPricing`

### 6. Controllers

#### `OrderController.php`:
- ✅ آپدیت `index()` → eager load `orderItems.pricing`
- ✅ آپدیت `show()` → eager load `orderItems.pricing`
- ✅ آپدیت `search()` → eager load `orderItems.pricing`

### 7. مستندات

- ✅ `DATABASE_MIGRATION_GUIDE.md` → راهنمای کامل مهاجرت
- ✅ `QUICK_START.md` → راهنمای سریع 5 دقیقه‌ای

---

## 📊 تغییرات ساختاری

### قبل:
```
دیتابیس واحد
├── users
├── receptors
├── otp_codes
├── shipments
└── order_items (همه اطلاعات در یک جدول)
```

### بعد:
```
core_db
├── users
├── receptors
├── otp_codes
└── ...

orders_db
├── shipments
├── order_items (فقط محصول)
└── order_item_pricing (فقط مالی)

payment_db
└── (آماده آینده)
```

---

## 🔑 نکات کلیدی

### 1. OrderItem تقسیم شد:
**قبل:**
```php
OrderItem::create([
    'shipment_id' => $id,
    'name' => 'محصول',
    'price' => 1000,
    'quantity' => 2,
    'total' => 2000,
]);
```

**بعد:**
```php
// ایجاد آیتم
$item = OrderItem::create([
    'shipment_id' => $id,
    'product_id' => 123,
    'quantity' => 2,
]);

// ایجاد قیمت
OrderItemPricing::create([
    'order_item_id' => $item->id,
    'item_name' => 'محصول',
    'unit_price' => 1000,
    'total' => 2000,
]);
```

### 2. Relationship بین دیتابیس‌ها:
```php
// Shipment (orders_db) → Receptor (core_db)
public function receptor()
{
    return $this->belongsTo(Receptor::class)->on('core_db');
}
```

### 3. Eager Loading:
```php
// حتماً pricing را هم load کنید
$shipment = Shipment::with(['orderItems.pricing', 'receptor'])->find($id);
```

---

## 🚀 دستورات نصب

### 1. ساخت دیتابیس‌ها:
```bash
mysql -u root -p
```
```sql
CREATE DATABASE panel_core CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE panel_orders CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE panel_payment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. تنظیم .env:
```env
DB_CONNECTION=core_db
CORE_DB_DATABASE=panel_core
ORDERS_DB_DATABASE=panel_orders
PAYMENT_DB_DATABASE=panel_payment
```

### 3. اجرای Migration:
```bash
php artisan migrate --database=core_db --path=database/migrations/core_db
php artisan migrate --database=orders_db --path=database/migrations/orders_db
```

### 4. Seeder:
```bash
php artisan db:seed --class=UserSeeder
```

---

## ✅ چک‌لیست نهایی

- [x] ساختار پوشه‌ها ساخته شد
- [x] Migration های core_db ساخته شد (7 فایل)
- [x] Migration های orders_db ساخته شد (3 فایل)
- [x] Migration های payment_db ساخته شد (1 فایل)
- [x] config/database.php آپدیت شد
- [x] Models موجود آپدیت شدند (5 فایل)
- [x] Model OrderItemPricing ساخته شد
- [x] ProcessOrderJob آپدیت شد
- [x] OrderController آپدیت شد
- [x] مستندات ساخته شد

---

## 📞 مراحل بعدی

### برای کاربر:
1. ✅ **دیتابیس‌ها را دستی بسازید**
2. ⏳ **تنظیمات .env را اعمال کنید**
3. ⏳ **Migration ها را اجرا کنید**
4. ⏳ **Seeder ها را اجرا کنید**
5. ⏳ **تست کنید**

### توصیه‌ها:
- قبل از شروع، backup از دیتابیس فعلی بگیرید
- Migration ها را ابتدا روی دیتابیس تست اجرا کنید
- پس از اجرا، API ها را تست کنید

---

## 🎯 نتیجه

✅ پروژه از 1 دیتابیس به 3 دیتابیس مهاجرت داده شد  
✅ جدول order_items به 2 جدول منطقی تقسیم شد  
✅ تمام Model ها و Controller ها آپدیت شدند  
✅ مستندات کامل ایجاد شد  
✅ آماده اجرا و تست  

**تاریخ:** 2025-12-22  
**وضعیت:** ✅ کامل

