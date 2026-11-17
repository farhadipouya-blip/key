# راهنمای نصب سامانه کلید (KEY)

## ۱. پیش‌نیازها

- PHP **8.2 به بالا** با افزونه‌ها: `mbstring`, `xml`, `mysql (pdo_mysql)`, `zip`, `gd`, `curl`, `bcmath`, `fileinfo`
- Composer 2
- MySQL 8 یا MariaDB 10.6+ (روی هاست معمولی اشتراکی/پوشه‌ای نیز قابل اجراست — نیاز به Docker ندارد)
- وب‌سرور Apache/Nginx با پشتیبانی PHP (یا `php artisan serve` برای توسعه)

## ۲. دریافت و نصب کد

```bash
cd /path/to/your/hosting/folder    # پوشه‌ای خارج از public_html مناسب است
composer install --no-dev          # روی هاست بدون composer: پوشه vendor را همراه بسته کپی کنید
```

## ۳. تنظیمات

```bash
cp .env.example .env
php artisan key:generate
```

در `.env` مقادیر زیر را متناسب هاست خود تنظیم کنید:

```env
APP_URL=https://your-domain.com
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=keydb
DB_USERNAME=key_user
DB_PASSWORD=********
```

نکته امنیتی برای هاست اشتراکی: `APP_DEBUG=false` و `APP_ENV=production` بگذارید.
درگاه پیامک و کلید هوش مصنوعی را می‌توانید بعداً از **پنل مدیریت ← تنظیمات سامانه** ثبت کنید؛ کلیدها در پایگاه داده (رمزشده با کش) نگه‌داری می‌شوند و بدون آن‌ها سامانه کامل کار می‌کند.

## ۴. پایگاه داده

```bash
php artisan migrate --seed
```

- ۱۷ migration شامل ۷۸ جدول ایجاد می‌شود.
- Seeder ها نقش‌ها (۷ نقش)، مجوزها (۳۰ مجوز)، تنظیمات پایه و محتوای نمونه (درس‌ها، سؤال‌ها، آزمون، دوره، مقاله، پلن اشتراک، کوپن، کاربران آزمایشی) را می‌سازند.

## ۵. فایل‌ها و لینک storage

```bash
php artisan storage:link
chmod -R ug+rwX storage bootstrap/cache
```

اگر هاست اجازه symlink نمی‌دهد، مسیر `storage/app/public` را به `public/storage` کپی‌آدرس دهید (کد به‌صورت خودکار fallback دارد).

## ۶. اجرا

**توسعه:**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**تولید (Apache):** DocumentRoot را روی `public/` بگذارید (محتوای `public/.htaccess` موجود است).
**تولید (Nginx):**
```nginx
root /path/to/key/public;
location / { try_files $uri $uri/ /index.php?$query_string; }
```

## ۷. صف پس‌زمینه و زمان‌بند

```bash
# صف (پیامک‌ها، جاب‌های گروهی):
php artisan queue:work --tries=3 &          # با supervisor پایدارتر است

# زمان‌بند — در crontab:
* * * * * cd /path/to/key && php artisan schedule:run >> /dev/null 2>&1
```

عملیات زمان‌بندی‌شده:
| دستور | زمان | کار |
|---|---|---|
| `key:review-reminders` | روزانه ۰۸:۰۰ | یادآوری مرورهای سررسید (اعلان + پیامک) |
| `key:expire-subscriptions` | ساعتی | انقضای اشتراک‌ها و هشدار ۳ روز مانده |
| `key:plan-reminders` | روزانه ۱۷:۰۰ | یادآوری جلسات مطالعه انجام‌نشده امروز |

## ۸. پس از نصب — چک‌لیست

1. ورود با `admin@key.test / Admin@1234` و **تغییر فوری رمز** (پروفایل).
2. ثبت درگاه واقعی پیامک در تنظیمات (درایور log = شبیه‌سازی).
3. ساخت کلاس‌ها/دوره‌های واقعی و اتصال به اسکای‌روم/الوکام (لینک جلسه).
4. تست خرید آزمایشی با درگاه داخلی تا مطمئن شوید فعال‌سازی خودکار کار می‌کند؛ سپس درگاه واقعی (زرین‌پال/…) در `ShopController` قابل اتصال است.
5. پشتیبان‌گیری منظم: `mysqldump -u USER -p keydb > backup.sql` + پوشه `storage/app`.

## ۹. عیب‌یابی

- خطای 500: `storage/logs/laravel.log`
- پیامک ارسال نمی‌شود: بدون worker صف، پیامک‌های درایور واقعی در جدول `jobs` می‌مانند.
- «دستیار هوش مصنوعی هنوز تنظیم نشده»: کلید API را در تنظیمات ثبت کنید (ماژول AI اختیاری است).
