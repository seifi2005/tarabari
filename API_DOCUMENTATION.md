# 📚 مستندات کامل API - پنل مدیریت

## 📋 فهرست مطالب

- [مقدمه](#مقدمه)
- [Base URL](#base-url)
- [احراز هویت](#احراز-هویت)
- [Endpoint های عمومی](#endpoint-های-عمومی)
- [احراز هویت User (Sanctum)](#احراز-هویت-user-sanctum)
- [احراز هویت Receptor (JWT)](#احراز-هویت-receptor-jwt)
- [مدیریت کاربران](#مدیریت-کاربران)
- [مدیریت پذیرنده‌ها](#مدیریت-پذیرنده-ها)
- [کدهای خطا](#کدهای-خطا)
- [مثال‌های Workflow](#مثال-های-workflow)

---

## 🎯 مقدمه

این API برای مدیریت کاربران و پذیرنده‌ها (Receptors) طراحی شده است. سیستم دارای دو نوع احراز هویت است:

- **Sanctum**: برای ادمین‌ها (super_admin و operator)
- **JWT**: برای پذیرنده‌ها (receptor)

---

## 🌐 Base URL

```
http://localhost/panel/public/api
```

یا در production:
```
https://your-domain.com/api
```

---

## 🔐 احراز هویت

### Sanctum Token (برای ادمین‌ها)
```
Authorization: Bearer {sanctum_token}
```

### JWT Token (برای پذیرنده‌ها)
```
Authorization: Bearer {jwt_token}
```

---

## 📡 Endpoint های عمومی

### 1. تست API

**Request:**
```http
GET /api/test
```

**Response:**
```json
{
    "message": "Laravel API is working!",
    "status": "success"
}
```

---

## 👤 احراز هویت User (Sanctum)

### 1. ارسال OTP

**Request:**
```http
POST /api/auth/send-otp
```

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
    "mobile": "09123456789"
}
```

**Response:**
```json
{
    "message": "OTP sent successfully",
    "code": "123456"
}
```

> **نکته:** در production، فیلد `code` حذف می‌شود.

---

### 2. ورود با OTP

**Request:**
```http
POST /api/auth/login/otp
```

**Body:**
```json
{
    "mobile": "09123456789",
    "otp": "123456"
}
```

**Response:**
```json
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "مدیر",
        "username": "superadmin",
        "role": "super_admin"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
}
```

---

### 3. ورود با Username و Password

**Request:**
```http
POST /api/auth/login/password
```

**Body:**
```json
{
    "username": "superadmin",
    "password": "superadmin123"
}
```

**Response:** (همانند ورود با OTP)

---

### 4. خروج

**Request:**
```http
POST /api/auth/logout
```

**Headers:**
```
Authorization: Bearer {sanctum_token}
```

**Response:**
```json
{
    "message": "Logged out successfully"
}
```

---

### 5. اطلاعات کاربر فعلی

**Request:**
```http
GET /api/auth/me
```

**Headers:**
```
Authorization: Bearer {sanctum_token}
```

**Response:**
```json
{
    "user": {
        "id": 1,
        "name": "مدیر",
        "username": "superadmin",
        "role": "super_admin"
    }
}
```

---

## 🔑 احراز هویت Receptor (JWT)

### 1. دریافت Token

**Request:**
```http
POST /api/get_token
```

**Body:**
```json
{
    "username": "fati_receptor",
    "password": "MyPassword123"
}
```

**Response:**
```json
{
    "message": "Token generated successfully",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 3600
}
```

> **نکته:** اگر `allowed_ip` تنظیم شده باشد، فقط از آن IP می‌توانید لاگین کنید.

---

### 2. اطلاعات Receptor

**Request:**
```http
GET /api/receptor/me
```

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
    "user": {
        "id": 7,
        "name": "فاطمه",
        "role": "receptor"
    },
    "receptor": {
        "id": 5,
        "first_name": "فاطمه",
        "company_name": "خدمات پرداخت"
    }
}
```

---

### 3. Refresh Token

**Request:**
```http
POST /api/receptor/refresh
```

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
    "message": "Token refreshed successfully",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer"
}
```

---

## 👥 مدیریت کاربران

**نیازمند:** Sanctum Token + نقش `super_admin` یا `operator`

### 1. لیست کاربران

**Request:**
```http
GET /api/users
```

**Query Parameters (اختیاری):**
- `role`: فیلتر بر اساس نقش (`super_admin`, `operator`, `receptor`)
- `page`: شماره صفحه

**مثال:**
```
GET /api/users?role=operator&page=1
```

**Headers:**
```
Authorization: Bearer {sanctum_token}
```

**Response:**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "name": "مدیر",
            "username": "superadmin",
            "role": "super_admin"
        }
    ],
    "per_page": 15,
    "total": 10
}
```

---

### 2. مشاهده کاربر

**Request:**
```http
GET /api/users/{id}
```

**Headers:**
```
Authorization: Bearer {sanctum_token}
```

**Response:**
```json
{
    "id": 1,
    "name": "مدیر",
    "username": "superadmin",
    "role": "super_admin"
}
```

---

### 3. ایجاد کاربر

**Request:**
```http
POST /api/users
```

**Headers:**
```
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Body:**
```json
{
    "name": "علی",
    "last_name": "احمدی",
    "national_code": "1234567890",
    "mobile": "09123456789",
    "username": "operator_new",
    "password": "SecurePass123",
    "role": "operator"
}
```

**Validation:**
- `name`: required, string, max:255
- `last_name`: required, string, max:255
- `national_code`: required, string, size:10, unique
- `mobile`: required, string, regex:/^09\d{9}$/, unique
- `username`: required, string, unique
- `password`: required, string, min:8
- `role`: required, in:super_admin,operator

**Response:**
```json
{
    "message": "User created successfully",
    "user": {
        "id": 8,
        "name": "علی",
        "username": "operator_new"
    }
}
```

> **نکته:** فقط می‌توانید کاربر با نقش `super_admin` یا `operator` ایجاد کنید.

---

### 4. ویرایش کاربر

**Request:**
```http
PUT /api/users/{id}
PATCH /api/users/{id}
```

**Headers:**
```
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Body:**
```json
{
    "name": "علی",
    "mobile": "09111111111",
    "password": "NewPassword123"
}
```

> **نکته:** فقط فیلدهایی که می‌خواهید تغییر دهید را ارسال کنید.

**Validation:**
- همه فیلدها `sometimes` هستند (اختیاری)
- `role`: in:super_admin,operator,receptor

**Response:**
```json
{
    "message": "User updated successfully",
    "user": {
        ...
    }
}
```

---

### 5. حذف کاربر

**Request:**
```http
DELETE /api/users/{id}
```

**Headers:**
```
Authorization: Bearer {sanctum_token}
```

**Response:**
```json
{
    "message": "User deleted successfully"
}
```

> **نکته:** نمی‌توانید خودتان را حذف کنید.

---

## 🏢 مدیریت پذیرنده‌ها (Receptors)

**نیازمند:** Sanctum Token + نقش `super_admin` یا `operator`

### 1. لیست پذیرنده‌ها

**Request:**
```http
GET /api/receptors
```

**Query Parameters (اختیاری):**
- `page`: شماره صفحه

**Headers:**
```
Authorization: Bearer {sanctum_token}
```

**Response:**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 5,
            "first_name": "فاطمه",
            "last_name": "کریمی",
            "company_name": "خدمات پرداخت",
            "mobile": "09125692149",
            "username": "fati_receptor",
            "user": {
                "id": 7,
                "name": "فاطمه",
                "role": "receptor"
            }
        }
    ],
    "per_page": 15
}
```

---

### 2. مشاهده پذیرنده

**Request:**
```http
GET /api/receptors/{id}
```

**Headers:**
```
Authorization: Bearer {sanctum_token}
```

**Response:**
```json
{
    "id": 5,
    "first_name": "فاطمه",
    "company_name": "خدمات پرداخت",
    "user": {
        ...
    }
}
```

---

### 3. ایجاد پذیرنده

**Request:**
```http
POST /api/receptors
```

**Headers:**
```
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Body:**
```json
{
    "first_name": "محمد",
    "last_name": "رضایی",
    "company_name": "فروشگاه آنلاین",
    "mobile": "09444444444",
    "allowed_ip": "192.168.1.100",
    "username": "shop_receptor",
    "password": "SecurePass123"
}
```

**Validation:**
- `first_name`: required, string, max:255
- `last_name`: required, string, max:255
- `company_name`: required, string, max:255
- `mobile`: required, string, regex:/^09\d{9}$/, unique
- `allowed_ip`: nullable, ip
- `username`: required, string, unique
- `password`: required, string, min:8

> **نکته:** `allowed_ip` اختیاری است. اگر وارد نشود، از هر IP می‌تواند لاگین کند.

**Response:**
```json
{
    "message": "Receptor created successfully",
    "receptor": {
        "id": 6,
        "first_name": "محمد",
        "username": "shop_receptor",
        "user": {
            "id": 8,
            "role": "receptor"
        }
    }
}
```

> **نکته:** User مرتبط به‌صورت خودکار ایجاد می‌شود.

---

### 4. ویرایش پذیرنده

**Request:**
```http
PUT /api/receptors/{id}
PATCH /api/receptors/{id}
```

**Headers:**
```
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Body:**
```json
{
    "first_name": "محمد",
    "company_name": "فروشگاه جدید",
    "password": "NewPassword123"
}
```

**Response:**
```json
{
    "message": "Receptor updated successfully",
    "receptor": {
        ...
    }
}
```

> **نکته:** User مرتبط نیز به‌صورت خودکار آپدیت می‌شود.

---

### 5. حذف پذیرنده

**Request:**
```http
DELETE /api/receptors/{id}
```

**Headers:**
```
Authorization: Bearer {sanctum_token}
```

**Response:**
```json
{
    "message": "Receptor deleted successfully"
}
```

> **نکته:** User مرتبط نیز حذف می‌شود.

---

## ⚠️ کدهای خطا

| کد | معنی | توضیح |
|----|------|-------|
| 200 | OK | درخواست موفق |
| 201 | Created | ایجاد موفق |
| 401 | Unauthorized | Token معتبر نیست یا منقضی شده |
| 403 | Forbidden | دسترسی ندارید |
| 404 | Not Found | منبع پیدا نشد |
| 422 | Validation Error | داده‌های ارسالی معتبر نیستند |
| 500 | Server Error | خطای سرور |

### مثال خطای Validation:

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "mobile": ["The mobile has already been taken."],
        "username": ["The username has already been taken."]
    }
}
```

---

## 📝 مثال‌های Workflow

### سناریو 1: لاگین ادمین و مشاهده لیست کاربران

1. **لاگین:**
```http
POST /api/auth/login/password
Content-Type: application/json

{
    "username": "superadmin",
    "password": "superadmin123"
}
```

2. **ذخیره Token از Response:**
```json
{
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

3. **مشاهده لیست کاربران:**
```http
GET /api/users
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

### سناریو 2: ایجاد پذیرنده و تست لاگین

1. **لاگین ادمین** (همانند بالا)

2. **ایجاد پذیرنده:**
```http
POST /api/receptors
Authorization: Bearer {token}
Content-Type: application/json

{
    "first_name": "علی",
    "last_name": "احمدی",
    "company_name": "شرکت نمونه",
    "mobile": "09555555555",
    "username": "ali_receptor",
    "password": "Password123"
}
```

3. **دریافت Token برای پذیرنده:**
```http
POST /api/get_token
Content-Type: application/json

{
    "username": "ali_receptor",
    "password": "Password123"
}
```

4. **مشاهده اطلاعات پذیرنده:**
```http
GET /api/receptor/me
Authorization: Bearer {jwt_token}
```

---

## 🔑 کاربران پیش‌فرض

از Seeder، این کاربران به‌صورت پیش‌فرض ایجاد می‌شوند:

| Username | Password | Role | Mobile |
|----------|----------|------|--------|
| `superadmin` | `superadmin123` | super_admin | 09123456789 |
| `operator1` | `operator123` | operator | 09111111111 |
| `operator2` | `operator123` | operator | 09222222222 |
| `operator3` | `operator123` | operator | 09333333333 |

برای اجرای Seeder:
```bash
php artisan db:seed --class=UserSeeder
```

---

## 📌 نکات مهم

1. **Sanctum Token** برای ادمین‌ها (`super_admin` و `operator`) استفاده می‌شود
2. **JWT Token** برای پذیرنده‌ها (`receptor`) استفاده می‌شود
3. کاربران `receptor` در لیست `/api/users` نمایش داده می‌شوند
4. هر پذیرنده یک User مرتبط دارد که به‌صورت خودکار ایجاد می‌شود
5. اگر `allowed_ip` تنظیم شود، فقط از آن IP می‌توان لاگین کرد
6. OTP کد 6 رقمی است و 5 دقیقه اعتبار دارد
7. تمام endpoint های محافظت‌شده نیازمند ارسال Token در Header هستند

---

## 🛠️ تنظیمات Postman

### Environment Variables

می‌توانید در Postman Environment Variables تعریف کنید:

```
base_url: http://localhost/panel/public/api
sanctum_token: {token_شما}
jwt_token: {token_شما}
```

سپس در URL ها استفاده کنید:
```
{{base_url}}/users
```

و در Headers:
```
Authorization: Bearer {{sanctum_token}}
```

---

## 📊 خلاصه Endpoint ها

| Method | Endpoint | احراز هویت | توضیح |
|--------|----------|------------|-------|
| GET | `/api/test` | ❌ | تست API |
| POST | `/api/auth/send-otp` | ❌ | ارسال OTP |
| POST | `/api/auth/login/otp` | ❌ | ورود با OTP |
| POST | `/api/auth/login/password` | ❌ | ورود با Password |
| POST | `/api/auth/logout` | ✅ Sanctum | خروج |
| GET | `/api/auth/me` | ✅ Sanctum | اطلاعات کاربر |
| POST | `/api/get_token` | ❌ | دریافت JWT Token |
| GET | `/api/receptor/me` | ✅ JWT | اطلاعات Receptor |
| POST | `/api/receptor/refresh` | ✅ JWT | Refresh Token |
| GET | `/api/users` | ✅ Sanctum | لیست کاربران |
| GET | `/api/users/{id}` | ✅ Sanctum | مشاهده کاربر |
| POST | `/api/users` | ✅ Sanctum | ایجاد کاربر |
| PUT/PATCH | `/api/users/{id}` | ✅ Sanctum | ویرایش کاربر |
| DELETE | `/api/users/{id}` | ✅ Sanctum | حذف کاربر |
| GET | `/api/receptors` | ✅ Sanctum | لیست پذیرنده‌ها |
| GET | `/api/receptors/{id}` | ✅ Sanctum | مشاهده پذیرنده |
| POST | `/api/receptors` | ✅ Sanctum | ایجاد پذیرنده |
| PUT/PATCH | `/api/receptors/{id}` | ✅ Sanctum | ویرایش پذیرنده |
| DELETE | `/api/receptors/{id}` | ✅ Sanctum | حذف پذیرنده |

---

## 🔒 نقش‌ها و دسترسی‌ها

### Super Admin
- دسترسی کامل به همه endpoint ها
- می‌تواند کاربران و پذیرنده‌ها را مدیریت کند
- نمی‌تواند خودش را حذف کند

### Operator
- دسترسی به مدیریت کاربران و پذیرنده‌ها
- نمی‌تواند خودش را حذف کند

### Receptor
- فقط می‌تواند از endpoint های JWT استفاده کند
- دسترسی محدود به داده‌های خودش

---

## 📞 پشتیبانی

برای سوالات و مشکلات، با تیم توسعه تماس بگیرید.

---

**آخرین بروزرسانی:** 2025-12-08




