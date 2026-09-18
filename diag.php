<?php
declare(strict_types=1);

/**
 * =========================================================
 *  فروشگاه نیلا | NILA FASHION
 *  صفحه‌ی تشخیص سلامت (diag.php)
 *  ------------------------------------------------------------
 *  فقط این آدرس را در مرورگر باز کن:
 *  http://localhost/fashion-store/diag.php
 *  وضعیت PHP و پایگاه داده را نشان می‌دهد و اگر مشکلی
 *  باشد، راه‌حلش را به فارسی می‌نویسد.
 * =========================================================
 */

require_once __DIR__ . '/config/db.php';

$checks = [];   // هر مورد: [title, ok(bool), detail, fix]

/* ۱) اجرای PHP */
$checks[] = [
    'اجرای PHP روی سرور (Apache)',
    true,
    'نسخه‌ی PHP: ' . PHP_VERSION,
    'WAMP را از سینی سیستم روشن کن (آیکون سبز). اگر کد PHP را به‌جای صفحه می‌بینی، Apache درست کانفیگ نیست.',
];

/* ۲) اکستنشن PDO MySQL */
$pdoOk = extension_loaded('pdo_mysql');
$checks[] = [
    'اکستنشن PDO MySQL',
    $pdoOk,
    $pdoOk ? 'نصب و فعال است.' : 'یافت نشد!',
    'در WAMP: WampMenu → PHP → PHP Extensions → pdo_mysql را فعال کن.',
];

/* ۳) اتصال به دیتابیس */
$dbOk = false;
$dbErr = '';
$pdo  = null;
if ($pdoOk) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $dbOk = true;
    } catch (PDOException $e) {
        $dbErr = $e->getMessage();
    }
}
$checks[] = [
    'اتصال به دیتابیس «' . DB_NAME . '» (کاربر: ' . DB_USER . ')',
    $dbOk,
    $dbOk ? 'اتصال برقرار شد.' : 'اتصال نشد: ' . $dbErr,
    '۱) فایل <b>nila_shop.sql</b> را در phpMyAdmin (http://localhost/phpmyadmin) → برگه‌ی <b>Import</b> ایمپورت کن.<br>
     ۲) اگر خطای «Access denied» دیدی: در فایل <b>config/db.php</b> نام کاربری/رمز را با WAMP هماهنگ کن (پیش‌فرض: root با رمز خالی).',
];

$tableMsg = '—';
$rowMsg = '—';
$adminMsg = '—';
$adminOk = false;
if ($dbOk && $pdo) {
    /* ۴) جداول */
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $expected = ['products', 'categories', 'orders', 'order_items', 'users', 'admins', 'admin_levels', 'reviews', 'messages', 'notifications', 'settings'];
    $missing = array_diff($expected, $tables);
    $tableMsg = count($tables) . ' جدول: ' . implode(', ', $tables);
    if ($missing) {
        $checks[] = [
            'جداول دیتابیس',
            false,
            'جداول موجود نیستند: ' . implode(', ', $missing),
            'دوباره فایل <b>nila_shop.sql</b> را در phpMyAdmin ایمپورت کن.',
        ];
    } else {
        $checks[] = ['جداول دیتابیس', true, $tableMsg, ''];
    }

    /* ۵) شمارش سطرهای اصلی */
    $counts = [];
    foreach (['products', 'categories', 'orders', 'users', 'admins', 'reviews'] as $t) {
        if (in_array($t, $tables, true)) {
            $counts[$t] = (int)$pdo->query('SELECT COUNT(*) FROM `' . $t . '`')->fetchColumn();
        }
    }
    $rowMsg = 'محصول: ' . ($counts['products'] ?? 0) . ' | دسته: ' . ($counts['categories'] ?? 0) .
        ' | سفارش: ' . ($counts['orders'] ?? 0) . ' | کاربر: ' . ($counts['users'] ?? 0) .
        ' | مدیر: ' . ($counts['admins'] ?? 0) . ' | نظر: ' . ($counts['reviews'] ?? 0);
    $checks[] = [
        'داده‌های اولیه (سرِ دیتابیس)',
        ($counts['products'] ?? 0) > 0 && ($counts['admins'] ?? 0) > 0,
        $rowMsg,
        'اگر صفر است، فایل <b>nila_shop.sql</b> را دوباره ایمپورت کن.',
    ];

    /* ۶) تست ورود ادمین */
    $st = $pdo->prepare('SELECT password_hash FROM `admins` WHERE email = ?');
    $st->execute(['admin@nila.shop']);
    $row = $st->fetch();
    if ($row) {
        $adminOk = verify_password('nila2026', (string)$row['password_hash']);
        $adminMsg = $adminOk
            ? 'ورود admin@nila.shop / nila2026 با موفقیت راستی‌آزمایی شد.'
            : 'هش رمز ادمین با تابع verify مطابقت ندارد!';
    } else {
        $adminMsg = 'ادمین admin@nila.shop در دیتابیس نیست.';
    }
    $checks[] = [
        'ورود پنل مدیریت (admin@nila.shop)',
        $adminOk,
        $adminMsg,
        'فایل <b>nila_shop.sql</b> را دوباره ایمپورت کن تا مدیران و داده‌های نمونه برگردند.',
    ];
}

/* ۷) نوشتن در پوشه‌ی تصاویر (برای آپلود عکس محصول) */
$dir = __DIR__ . '/images';
$wOk = is_dir($dir) && is_writable($dir);
if ($wOk) {
    $tmp = $dir . '/.nila-write-test';
    $wOk = (bool)@file_put_contents($tmp, 'x') && @unlink($tmp);
}
$checks[] = [
    'پوشه‌ی تصاویر (images/) برای آپلود قابل نوشتن',
    $wOk,
    $wOk ? 'قابلیت نوشتن دارد.' : 'برای نوشتن در دسترس نیست!',
    'در ویندوز معمولاً مشکل نیست؛ اگر با آیکون‌های قرمز Administrator اجرا می‌شود، WAMP را هم با همان سطح اجرا کن.',
];

$allOk = $dbOk && $pdoOk && $adminOk && $wOk && !array_filter($checks, fn($c) => !$c[1]);

?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تشخیص سلامت | نیلا</title>
<style>
  *{ box-sizing:border-box; }
  body{ margin:0; font-family:Tahoma,'Segoe UI',sans-serif; background:#fff7f2; color:#3b2530; padding:2rem 1rem; }
  .wrap{ max-width:760px; margin:0 auto; }
  h1{ font-size:1.3rem; margin:0 0 .4rem; }
  .sub{ color:#8a6a76; font-size:.85rem; margin:0 0 1.6rem; }
  .card{ background:#fff; border:1px solid #f3d3de; border-radius:16px; padding:1.1rem 1.3rem; margin-bottom:.9rem; box-shadow:0 8px 24px rgba(180,60,100,.06); }
  .card .t{ display:flex; align-items:center; gap:.6rem; font-weight:800; font-size:.95rem; }
  .dot{ width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.85rem; font-weight:900; color:#fff; flex:0 0 26px; }
  .ok .dot{ background:#2f9e6e; } .bad .dot{ background:#e0455e; }
  .card .d{ font-size:.8rem; color:#6b4a57; margin-top:.45rem; line-height:1.8; word-break:break-word; }
  .card .fix{ font-size:.8rem; background:#fff4e6; border:1px solid #ffd9a8; border-radius:10px; padding:.6rem .9rem; margin-top:.55rem; line-height:1.9; }
  .verdict{ border-radius:16px; padding:1.2rem 1.4rem; font-weight:800; font-size:1rem; margin-bottom:1.2rem; line-height:1.9; }
  .verdict.good{ background:#e7f7ef; border:1px solid #bfe8d2; color:#1d6b46; }
  .verdict.badv{ background:#fdecef; border:1px solid #f6c2cd; color:#a12742; }
  a.back{ display:inline-block; margin-top:1.2rem; color:#ec4d84; font-size:.85rem; font-weight:700; }
</style>
</head>
<body>
<div class="wrap">
  <h1>تشخیص سلامت فروشگاه نیلا</h1>
  <p class="sub">این صفحه وضعیت PHP و پایگاه داده را بررسی می‌کند. اگر همه سبز بود، همه‌ی بخش‌های PHP (پنل مدیریت و پنل کاربری) واقعاً کار می‌کنند.</p>

  <?php if ($allOk): ?>
    <div class="verdict good">✅ همه‌چیز درست است — اتصال به دیتابیس برقرار است و همه‌ی بخش‌های واقعی (PHP) فعال‌اند.<br>
    <span style="font-weight:400; font-size:.85rem;">اگر با این حال پنل‌ها داده‌ی نمایشی نشان می‌دهند، مرورگر را یک‌بار با Ctrl + F5 رفرش کن.</span></div>
  <?php else: ?>
    <div class="verdict badv">⚠ هنوز همه‌چیز آماده نیست — موارد قرمز بالا را به ترتیب درست کن (معمولاً ایمپورت nila_shop.sql مشکل را حل می‌کند).</div>
  <?php endif; ?>

  <?php foreach ($checks as $c): ?>
    <div class="card <?= $c[1] ? 'ok' : 'bad' ?>">
      <div class="t"><span class="dot"><?= $c[1] ? '✓' : '✕' ?></span><?= htmlspecialchars($c[0], ENT_QUOTES) ?></div>
      <div class="d"><?= $c[2] ?></div>
      <?php if (!$c[1] && $c[3]): ?><div class="fix"><b>راه‌حل:</b> <?= $c[3] ?></div><?php endif; ?>
    </div>
  <?php endforeach; ?>

  <a class="back" href="admin.html">← بازگشت به پنل مدیریت</a>
</div>
</body>
</html>
