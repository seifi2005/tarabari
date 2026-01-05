# 🚀 دستورات Laravel

## 📦 مراحل نصب کامل

### 1️⃣ ساخت دیتابیس‌ها
```bash
# روش 1: با mysql command line
mysql -u root -p < CREATE_DATABASES.sql

# روش 2: با mysql interactive
mysql -u root -p
```
```sql
CREATE DATABASE panel_core CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE panel_orders CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE panel_payment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 2️⃣ تنظیم .env
```bash
# کپی محتوای ENV_SAMPLE_FOR_DATABASE.txt به .env
# یا دستی اضافه کنید
```

### 3️⃣ پاک کردن Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
composer dump-autoload
```

### 4️⃣ اجرای Migration
```bash
# دیتابیس core_db
php artisan migrate --database=core_db --path=database/migrations/core_db

# دیتابیس orders_db
php artisan migrate --database=orders_db --path=database/migrations/orders_db

# دیتابیس payment_db (اختیاری - خالی است)
php artisan migrate --database=payment_db --path=database/migrations/payment_db
```

### 5️⃣ اجرای Seeder
```bash
# ایجاد کاربران پیش‌فرض
php artisan db:seed --class=UserSeeder

# ایجاد پذیرنده‌های نمونه (اختیاری)
php artisan db:seed --class=ReceptorSeeder
```

---

## 🧪 تست و بررسی

### چک اتصال دیتابیس‌ها
```bash
php artisan tinker
```
```php
// تست core_db
\DB::connection('core_db')->getPdo();
\App\Models\User::count();

// تست orders_db
\DB::connection('orders_db')->getPdo();
\App\Models\Shipment::count();

// تست payment_db
\DB::connection('payment_db')->getPdo();

// خروج
exit
```

### تست Models
```bash
php artisan tinker
```
```php
// ساخت یک User
$user = \App\Models\User::create([
    'name' => 'تست',
    'email' => 'test@test.com',
    'password' => bcrypt('123456'),
    'role' => 'super_admin'
]);

// ساخت یک Receptor
$receptor = \App\Models\Receptor::create([
    'first_name' => 'علی',
    'last_name' => 'احمدی',
    'company_name' => 'شرکت تست',
    'mobile' => '09123456789',
    'username' => 'test_receptor',
    'password' => bcrypt('123456')
]);

// ساخت یک Shipment
$shipment = \App\Models\Shipment::create([
    'receptor_id' => $receptor->id,
    'source_order_id' => 'TEST-001',
    'customer_first_name' => 'محمد',
    'customer_last_name' => 'رضایی',
    'destination_city' => 'تهران',
    'address' => 'خیابان آزادی',
    'postcode' => '1234567890',
    'mobile' => '09111111111',
    'total_price' => 100000
]);

// ساخت OrderItem + Pricing
$item = \App\Models\OrderItem::create([
    'shipment_id' => $shipment->id,
    'source_item_id' => 'ITEM-001',
    'product_id' => 123,
    'quantity' => 2,
]);

\App\Models\OrderItemPricing::create([
    'order_item_id' => $item->id,
    'item_name' => 'محصول تستی',
    'unit_price' => 50000,
    'quantity' => 2,
    'subtotal' => 100000,
    'total' => 100000,
]);

// تست Relationship
$shipment->load('orderItems.pricing', 'receptor');
$shipment->orderItems->first()->pricing;

exit
```

---

## 🔄 Rollback و Fresh

### Rollback تک تک
```bash
# rollback core_db
php artisan migrate:rollback --database=core_db --path=database/migrations/core_db

# rollback orders_db
php artisan migrate:rollback --database=orders_db --path=database/migrations/orders_db
```

### Fresh (حذف و ساخت مجدد)
```bash
# ⚠️ خطرناک! همه داده‌ها حذف می‌شوند
php artisan migrate:fresh --database=core_db --path=database/migrations/core_db
php artisan migrate:fresh --database=orders_db --path=database/migrations/orders_db
```

### Fresh + Seed
```bash
php artisan migrate:fresh --database=core_db --path=database/migrations/core_db --seed
```

---

## 🛠️ دستورات مفید

### چک وضعیت Migration
```bash
php artisan migrate:status --database=core_db
php artisan migrate:status --database=orders_db
```

### لیست دیتابیس‌ها
```bash
mysql -u root -p -e "SHOW DATABASES LIKE 'panel_%';"
```

### لیست جداول
```bash
# core_db
mysql -u root -p panel_core -e "SHOW TABLES;"

# orders_db
mysql -u root -p panel_orders -e "SHOW TABLES;"
```

### Backup دیتابیس
```bash
# backup core_db
mysqldump -u root -p panel_core > backup_core_$(date +%Y%m%d).sql

# backup orders_db
mysqldump -u root -p panel_orders > backup_orders_$(date +%Y%m%d).sql

# backup همه
mysqldump -u root -p --databases panel_core panel_orders panel_payment > backup_all_$(date +%Y%m%d).sql
```

### Restore دیتابیس
```bash
mysql -u root -p panel_core < backup_core_20251222.sql
```

---

## 🚨 عیب‌یابی

### خطا: Database doesn't exist
```bash
# چک کنید دیتابیس ساخته شده باشد
mysql -u root -p -e "SHOW DATABASES LIKE 'panel_%';"
```

### خطا: Access denied
```bash
# چک کردن username و password در .env
cat .env | grep DB_
```

### خطا: Class not found
```bash
# پاک کردن cache
php artisan config:clear
composer dump-autoload
```

### خطا: Connection refused
```bash
# چک کردن MySQL
# Windows:
net start MySQL80

# Linux:
sudo service mysql status
```

---

## 📊 Query های مفید

```sql
-- تعداد کاربران
SELECT COUNT(*) FROM panel_core.users;

-- تعداد محموله‌ها
SELECT COUNT(*) FROM panel_orders.shipments;

-- تعداد آیتم‌ها
SELECT COUNT(*) FROM panel_orders.order_items;

-- Join بین دیتابیس‌ها (فقط در SQL)
SELECT 
    s.*,
    r.company_name
FROM panel_orders.shipments s
LEFT JOIN panel_core.receptors r ON s.receptor_id = r.id
LIMIT 10;
```

---

## ✅ چک‌لیست نهایی

- [ ] دیتابیس‌ها ساخته شدند
- [ ] .env تنظیم شد
- [ ] Cache پاک شد
- [ ] Migration های core_db اجرا شد
- [ ] Migration های orders_db اجرا شد
- [ ] Seeder اجرا شد
- [ ] تست در tinker انجام شد
- [ ] API ها تست شدند

---

**تاریخ:** 2025-12-22

