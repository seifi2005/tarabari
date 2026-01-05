# 🗄️ معماری Multi-Database - پروژه پنل

## 📋 خلاصه

پروژه از **یک دیتابیس** به **سه دیتابیس جداگانه** مهاجرت داده شده است:

1. 🔐 **`core_db`** - احراز هویت و کاربران
2. 📦 **`orders_db`** - سفارشات و محموله‌ها  
3. 💳 **`payment_db`** - پرداخت (آماده آینده)

همچنین `order_items` به **دو جدول** تقسیم شد:
- **`order_items`** - اطلاعات محصول
- **`order_item_pricing`** - اطلاعات مالی

---

## 🚀 نصب سریع (5 دقیقه)

### 1. ساخت دیتابیس‌ها
```bash
mysql -u root -p < CREATE_DATABASES.sql
```

### 2. تنظیم .env
کپی محتوای `ENV_SAMPLE_FOR_DATABASE.txt` به `.env`

### 3. اجرای Migration
```bash
php artisan migrate --database=core_db --path=database/migrations/core_db
php artisan migrate --database=orders_db --path=database/migrations/orders_db
```

### 4. Seeder
```bash
php artisan db:seed --class=UserSeeder
```

✅ **تمام!**

---

## 📚 مستندات

- 📖 **[DATABASE_MIGRATION_GUIDE.md](DATABASE_MIGRATION_GUIDE.md)** - راهنمای کامل و جزئیات
- ⚡ **[QUICK_START.md](QUICK_START.md)** - شروع سریع
- 🛠️ **[COMMANDS.md](COMMANDS.md)** - دستورات Laravel
- 📊 **[IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)** - خلاصه پیاده‌سازی
- 📝 **[CHANGED_FILES.md](CHANGED_FILES.md)** - لیست فایل‌های تغییر یافته

---

## 📊 ساختار

```
core_db (هسته)
├── users
├── receptors
├── otp_codes
└── ...

orders_db (کسب‌وکار)
├── shipments
├── order_items ⭐
└── order_item_pricing ⭐

payment_db (آینده)
└── (خالی)
```

---

## 🎯 مزایا

✅ Scalability - مقیاس‌پذیری بهتر  
✅ Performance - Query های سریع‌تر  
✅ Security - جداسازی داده‌های حساس  
✅ Backup - استراتژی مستقل  
✅ Development - کار تیمی راحت‌تر  
✅ Future-ready - آماده Microservices  

---

## ⚠️ نکات مهم

- Foreign Key بین دیتابیس‌ها کار نمی‌کند
- Relationship ها با `.on('database')` مشخص می‌شوند
- Eager loading: `with(['orderItems.pricing'])`
- Helper attributes در OrderItem برای دسترسی راحت

---

## 🆘 پشتیبانی

مشکلی پیش آمد؟
- `COMMANDS.md` → دستورات عیب‌یابی
- `DATABASE_MIGRATION_GUIDE.md` → بخش Troubleshooting

---

**نسخه:** 2.0  
**تاریخ:** 2025-12-22  
**وضعیت:** ✅ آماده استفاده

