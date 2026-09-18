<?php
declare(strict_types=1);

/**
 * =========================================================
 *  فروشگاه نیلا | NILA FASHION
 *  اتصال به پایگاه داده (PDO)
 * ------------------------------------------------------------
 *  توجه: اگر روی هاستینگ خود، نام کاربری، رمز یا نام
 *  دیتابیس فرق دارد، مقادیر ثابت‌های زیر را ویرایش کنید
 *  (از cPanel → MySQL Databases قابل مشاهده‌اند).
 *  پیش‌فرض برای آزمایش روی لوکال‌هاست: root / خالی
 * =========================================================
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'nila_shop');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/** نمک ثابتِ رمزهای نمایشیِ اولیه (فقط برای داده‌های نمونه) */
define('NILA_SALT', 'nila_salt_1405');

/**
 * اتصال ثابت و یک‌بارِ PDO
 *
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('[nila-db] ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            exit(
                '<!doctype html><html dir="rtl" lang="fa"><head><meta charset="utf-8">'
                . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>نیلا</title></head>'
                . '<body style="font-family:Tahoma,Arial,sans-serif;background:#fff7f2;color:#5b2333;'
                . 'display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1.5rem">'
                . '<div style="max-width:560px;text-align:center;background:#fff;border:1px solid #f3d3de;'
                . 'border-radius:20px;padding:2.5rem 2rem;box-shadow:0 20px 50px rgba(180,60,100,.12)">'
                . '<h2 style="margin:0 0 .8rem">اتصال به پایگاه داده برقرار نشد</h2>'
                . '<p style="line-height:1.9;font-size:.92rem;margin:0">'
                . '۱) فایل <b>nila_shop.sql</b> را در phpMyAdmin ایمپورت کنید.<br>'
                . '۲) نام کاربری، رمز و نام دیتابیس را در فایل <b>config/db.php</b> بررسی کنید.</p>'
                . '</div></body></html>'
            );
        }
    }

    return $pdo;
}

/**
 * بررسی صحت رمز عبور
 * - اگر هش با password_hash ساخته شده باشد (شروع $2y$ / $2b$): از password_verify
 * - وگرنه: روش نمایشیِ sha256 با نمک ثابت (برای داده‌های نمونه‌ی اولیه)
 *
 * @param string $plain  رمزِ واردشده توسط کاربر
 * @param string $stored هشِ ذخیره‌شده در دیتابیس
 * @return bool
 */
function verify_password(string $plain, string $stored): bool
{
    if (strpos($stored, '$2y$') === 0 || strpos($stored, '$2b$') === 0) {
        return password_verify($plain, $stored);
    }

    return hash_equals(hash('sha256', NILA_SALT . $plain), $stored);
}

/**
 * ساخت هش امن برای حساب‌های جدید
 *
 * @param string $plain
 * @return string
 */
function make_password_hash(string $plain): string
{
    return password_hash($plain, PASSWORD_DEFAULT);
}
