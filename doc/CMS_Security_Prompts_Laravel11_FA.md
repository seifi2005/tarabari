# پرامپت‌های مرحله‌ای اجرای CMS خبری امن (Laravel 11)

هدف: ایجاد CMS خبری با **Laravel 11 + MySQL + Bootstrap 5 (Vite)** با رعایت الزامات امنیتی افتا.

راهنما: هر مرحله را کامل کن، سپس پرامپت مرحله بعد را اجرا کن.

---

## مرحله 0 — آماده‌سازی
کار تو: یک ریپوی تمیز Laravel 11 بساز/باز کن و این پرامپت را به AI بده.

**Prompt 0**
```text
تو یک Senior Laravel 11 Architect + Pentester هستی. برای یک CMS خبری که باید تست‌های امنیتی افتا را پاس کند، یک برنامه‌ی اجرایی مرحله‌ای بده و در پایان هر مرحله چک‌لیست “Done Criteria” ارائه کن. محدودیت: Laravel 11 + MySQL + Bootstrap 5 با Vite. خروجی فقط مرحله 1 را بده.
```

---

## مرحله 1 — نصب و سخت‌سازی پایه پروژه
کار تو: خروجی مرحله قبل را اعمال کن، سپس این پرامپت را اجرا کن.

**Prompt 1**
```text
برای پروژه Laravel 11 یک راه‌اندازی امن پیشنهاد بده و دستورات دقیق را بنویس:
- نصب Fortify (با 2FA)
- نصب Spatie Permission
- نصب Spatie MediaLibrary
- نصب reCAPTCHA
- نصب HTML Purifier
- تنظیمات امنیتی پایه: APP_DEBUG=false, secure cookies, session settings, trust proxies
- تعریف RateLimiter های جدا برای login/register/2fa
در پایان: لیست فایل‌هایی که باید تغییر کنند را همراه با snippet کد هر فایل بده. خروجی فقط همین مرحله.
```

---

## مرحله 2 — طراحی دیتابیس (Migrations + Indexing) و مدل‌ها
کار تو: بعد از نصب پکیج‌ها و تنظیمات پایه، migrations و models را اضافه کن.

**Prompt 2**
```text
برای CMS خبری، migrations و مدل‌های Eloquent را طراحی و کد کامل ارائه بده:
- categories (nested)
- news (soft delete + status workflow)
- news_versions (versioning)
- audit_logs
- media (طبق استاندارد Spatie)
همه FK ها، index های لازم، و قواعد حذف (restrict/cascade/nullOnDelete) را دقیق تعیین کن.
همچنین روابط مدل‌ها (belongsTo/hasMany) را کد کن.
خروجی فقط مرحله 2 و کدها باشد.
```

---

## مرحله 3 — RBAC کامل (Role/Permission + Seeder + Policies)
کار تو: بعد از migrate، role/permission و policy ها را پیاده کن.

**Prompt 3**
```text
RBAC را با spatie/permission پیاده کن:
- Roles: super_admin, editor_in_chief, section_editor, reporter
- Permissions ریزدانه برای news/media/audit/workflow
- Seeder برای ساخت role/permission و assign اولیه
- Policy برای News (create/update own/update any/submit/publish/archive/delete/restore)
- Route middleware ها و gate های لازم
Done criteria و چند سناریوی تست دستی هم بده.
```

---

## مرحله 4 — 2FA اجباری برای نقش‌های حساس + جداسازی پنل
کار تو: سخت‌گیری پنل را اعمال کن (اجبار 2FA برای نقش‌های حساس).

**Prompt 4**
```text
با Fortify کاری کن که:
- برای super_admin و editor_in_chief ورود بدون 2FA غیرممکن باشد (اجباری)
- برای بقیه نقش‌ها 2FA اختیاری باشد
- یک middleware بنویس: اگر کاربر نقش حساس دارد و 2FA فعال نیست، فقط به صفحه “فعال‌سازی 2FA” دسترسی داشته باشد
همچنین route group پنل /admin را جدا و سخت‌گیرانه‌تر کن (throttle, verified/2fa).
کد دقیق فایل‌ها را بده.
```

---

## مرحله 5 — Captcha Progressive + Brute Force Defense
کار تو: کپچا را تدریجی و بر اساس تلاش ناموفق فعال کن.

**Prompt 5**
```text
Captcha progressive پیاده کن:
- تا قبل از N تلاش ناموفق کپچا نشان داده نشود
- بعد از N تلاش ناموفق، login/register/forgot-password بدون captcha رد شود
- کلید شمارنده ترکیبی ip+username باشد
- پیام خطا و UX مناسب
کد تغییرات Fortify actions / controllers / validation را ارائه بده.
```

---

## مرحله 6 — Audit Logging استاندارد (Middleware + Event Listener)
کار تو: لاگ امنیتی را هم در middleware و هم در event ها پوشش بده.

**Prompt 6**
```text
Audit Logging را استاندارد و قابل دفاع برای افتا پیاده کن:
- Middleware برای ثبت درخواست‌های تغییر‌دهنده با correlation_id و status_code
- Event listeners برای created/updated/deleted/restored روی News/Category/Role/Permission
- لاگ‌ها باید sensitive data را whitelist/blacklist کند (هرگز password/token/captcha/2fa secrets)
- قابلیت جستجو و فیلتر (از سمت دیتابیس)
کد کامل مدل AuditLog، middleware، listeners و ثبت service provider را بده.
```

---

## مرحله 7 — CMS Core: News CRUD امن + Versioning + Workflow
کار تو: CRUD اخبار را با FormRequest + policy + workflow پیاده کن.

**Prompt 7**
```text
CRUD امن اخبار را با این قیود بساز:
- همه ورودی‌ها FormRequest
- Purifier برای body
- Versioning: در هر update یک نسخه ذخیره شود + امکان rollback با permission جدا
- Workflow rules: reporter فقط draft->pending_review، section_editor تایید اولیه، editor_in_chief publish/archive
- Soft delete + restore
همراه با routes, controllers, requests, policies usage و چند feature test.
```

---

## مرحله 8 — Media Library امن (MIME/Content Validation + Storage امن)
کار تو: آپلود و دانلود را امن و با مجوز کنترل کن.

**Prompt 8**
```text
مدیریت رسانه امن با Spatie MediaLibrary:
- محدودیت mime + بررسی واقعی تصویر (image validation) + max size
- نام‌گذاری امن فایل‌ها، جلوگیری از SVG/HTML upload
- ذخیره در disk خصوصی و endpoint دانلود با authorization + Content-Disposition
- تنظیم conversions (thumbnail) امن
کد مدل News برای media، request آپلود، controller و config/filesystems را بده.
```

---

## مرحله 9 — Security Headers + Hardening نهایی
کار تو: هدرها و سخت‌سازی نهایی را برای محیط production آماده کن.

**Prompt 9**
```text
برای پاس‌کردن تست‌های امنیتی:
- middleware برای CSP, HSTS, X-Frame-Options, Referrer-Policy, Permissions-Policy
- تنظیمات cookie/session سخت‌گیرانه
- جلوگیری از info leak (error pages, debug, stack traces)
- logging امن
کد middleware و تنظیمات config را بده + چک‌لیست نهایی افتا.
```

---

## مرحله 10 — UI/UX (Bootstrap 5 + Vite) پنل و فرانت
کار تو: صفحات پنل و قالب فرانت را بساز (با رعایت امنیت Blade).

**Prompt 10**
```text
UI را با Bootstrap 5 و Vite بساز:
- Admin dashboard: dark sidebar, layout تمیز، صفحات news list/create/edit + workflow badges
- Front theme: corporate header, breaking news slider, categories section, footer حرفه‌ای
- رعایت امنیت در Blade (escape output) و فرم‌ها (CSRF, validation errors)
ساختار فایل‌های view و کد Blade را مرحله‌ای بده.
```

