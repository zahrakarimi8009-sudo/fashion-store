<?php
declare(strict_types=1);

/**
 * =========================================================
 *  فروشگاه نیلا | NILA FASHION
 *  بخش ۱ از فاز PHP: ورود و ایجاد حساب — متصل به دیتابیس
 *  ------------------------------------------------------------
 *  - ورود: شماره‌ی موبایل + رمز (جدول users)
 *  - ایجاد حساب: نام + شماره + رمز (تولید هش امن)
 *  - موفقیت: نشست PHP + همگام‌سازی با فرانت‌اند (saveUser)
 *  - خطاها با بازگشت ?err=... به همین صفحه نمایش داده می‌شوند
 * =========================================================
 */

session_start();
require_once __DIR__ . '/config/db.php';

/* کاربران واردشده مستقیم به پنل می‌روند */
if (isset($_SESSION['nila_user'])) {
    header('Location: account.html');
    exit;
}

/* ---------- پیام‌های خطا ---------- */
$ERR = [
    'phone_invalid'  => 'لطفاً شماره‌ی موبایل معتبر وارد کنید.',
    'phone_notfound' => 'کاربری با این شماره پیدا نشد؛ لطفاً دیتابیس را ایمپورت کرده باشید (db_check.php) و شماره را دقیق وارد کنید.',
    'pass_wrong'     => 'رمز عبور واردشده برای این شماره اشتباه است.',
    'name_invalid'  => 'نام و نام خانوادگی را کامل وارد کنید.',
    'pass_short'    => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
    'pass_mismatch' => 'تکرار رمز عبور با رمز عبور یکسان نیست.',
    'phone_exists'  => 'این شماره قبلاً ثبت شده است؛ لطفاً وارد شوید.',
    'db_error'      => 'خطا در ارتباط با پایگاه داده؛ لطفاً بعداً تلاش کنید.',
];
$err = isset($_GET['err']) && isset($ERR[$_GET['err']]) ? $ERR[$_GET['err']] : '';

/* ---------- مقصد بعد از ورود (فقط فایل‌های امن سایت) ---------- */
$next = (string)($_GET['next'] ?? '');
if (!preg_match('/^[\w-]+\.html$/', $next)) {
    $next = '';
}

/* ---------- پردازش فرم ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intent = (($_POST['intent'] ?? '') === 'signup') ? 'signup' : 'login';

    /* تبدیل ارقام فارسی/عربی به انگلیسی (کیبورد فارسی) */
    $faDigits = ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
                 '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'];
    foreach ($_POST as $k => $v) {
        if (is_string($v)) {
            $_POST[$k] = strtr($v, $faDigits);
        }
    }

    try {
        $pdo = db();

        if ($intent === 'login') {
            $raw   = trim((string)($_POST['phone'] ?? ''));
            $pass  = (string)($_POST['pass'] ?? '');

            if (strpos($raw, '@') !== false) {
                /* ورود با ایمیل */
                $email = strtolower($raw);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    header('Location: login.php?err=phone_invalid');
                    exit;
                }
                $stmt = $pdo->prepare('SELECT `id`, `name`, `phone`, `password_hash` FROM `users` WHERE `email` = ? LIMIT 1');
                $stmt->execute([$email]);
            } else {
                /* ورود با شماره‌ی موبایل */
                $phone = preg_replace('/\D/', '', $raw);
                if (strlen($phone) < 10) {
                    header('Location: login.php?err=phone_invalid');
                    exit;
                }
                $stmt = $pdo->prepare('SELECT `id`, `name`, `phone`, `password_hash` FROM `users` WHERE `phone` = ? LIMIT 1');
                $stmt->execute([$phone]);
            }
            $u = $stmt->fetch();

            if (!$u) {
                header('Location: login.php?err=phone_notfound');
                exit;
            }
            if (!verify_password($pass, (string)$u['password_hash'])) {
                header('Location: login.php?err=pass_wrong');
                exit;
            }
            $_SESSION['nila_user'] = [
                'id'    => (int)$u['id'],
                'name'  => (string)$u['name'],
                'phone' => (string)$u['phone'],
            ];
        } else {
            $name  = trim((string)($_POST['name'] ?? ''));
            $phone = preg_replace('/\D/', '', (string)($_POST['phone'] ?? ''));
            $pass  = (string)($_POST['pass'] ?? '');
            $pass2 = (string)($_POST['pass2'] ?? '');

            if (strlen($name) < 3) {
                header('Location: login.php?err=name_invalid');
                exit;
            }
            if (strlen($phone) < 10) {
                header('Location: login.php?err=phone_invalid');
                exit;
            }
            if (strlen($pass) < 6) {
                header('Location: login.php?err=pass_short');
                exit;
            }
            if ($pass !== $pass2) {
                header('Location: login.php?err=pass_mismatch');
                exit;
            }
            $chk = $pdo->prepare('SELECT `id` FROM `users` WHERE `phone` = ? LIMIT 1');
            $chk->execute([$phone]);
            if ($chk->fetch()) {
                header('Location: login.php?err=phone_exists');
                exit;
            }
            $ins = $pdo->prepare('INSERT INTO `users` (`name`, `phone`, `password_hash`) VALUES (?, ?, ?)');
            $ins->execute([$name, $phone, make_password_hash($pass)]);
            $_SESSION['nila_user'] = [
                'id'    => (int)$pdo->lastInsertId(),
                'name'  => $name,
                'phone' => $phone,
            ];
        }
    } catch (Throwable $e) {
        error_log('[nila-login] ' . $e->getMessage());
        header('Location: login.php?err=db_error');
        exit;
    }

    /* موفقیت: همگام‌سازی با فرانت‌اند و ادامه به مقصد */
    $nextPost = (string)($_POST['next'] ?? '');
    if (!preg_match('/^[\w-]+\.html$/', $nextPost)) {
        $nextPost = '';
    }
    header('Location: login_bridge.php?next=' . urlencode($nextPost !== '' ? $nextPost : $next));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ورود / ثبت‌نام | نیلا</title>
  <meta name="description" content="ورود یا ساخت حساب کاربری در فروشگاه نیلا.">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%23ec4d84'/%3E%3Ctext x='32' y='45' font-size='36' text-anchor='middle' fill='%23fff' font-family='Tahoma'%3E%D9%86%3C/text%3E%3C/svg%3E">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <!-- نوار بالا -->
  <div class="topbar">
    <div class="container topbar-inner">
      <p>
        <svg class="ic" viewBox="0 0 24 24"><rect x="1" y="5" width="14" height="11" rx="1"/><path d="M15 8h4l3 4v4h-3"/><circle cx="6" cy="18.5" r="1.8"/><circle cx="18" cy="18.5" r="1.8"/></svg>
        ارسال رایگان برای خریدهای بالای ۲,۰۰۰,۰۰۰ تومان
      </p>
      <p class="topbar-mid">
        <svg class="ic" viewBox="0 0 24 24"><path d="M12 22s8-3.5 8-10V5l-8-3-8 3v7c0 6.5 8 10 8 10z"/></svg>
        ۷ روز ضمانت بازگشت کالا
      </p>
      <p class="topbar-end">
        <svg class="ic" viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.5 2.9.7a2 2 0 0 1 1.6 1.9z"/></svg>
        <span dir="ltr">021-91000000</span>
      </p>
    </div>
  </div>

  <!-- هدر -->
  <header class="site-header" id="siteHeader">
    <div class="container header-inner">
      <button class="icon-btn nav-toggle" id="navToggle" aria-label="باز کردن منو">
        <svg class="ic" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>

      <a href="index.html" class="logo"><span class="lg-main">NILA</span></a>

      <nav class="main-nav" id="mainNav" aria-label="منوی اصلی">
    <button class="nav-close" id="navClose" type="button" aria-label="بستن منو"><svg class="ic" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        <ul class="nav-list">
          <li><a href="index.html" class="active">صفحه اصلی</a></li>
          <li><a href="shop.html">فروشگاه</a></li>
          <li class="has-drop">
            <a href="shop.html">لباس زنانه
              <svg class="ic" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
            </a>
            <!-- مگا منو -->
            <div class="mega" aria-label="دسته‌بندی لباس زنانه">
              <div class="mega-col">
                <h5>لباس</h5>
                <a href="shop.html?category=dress">لباس مجلسی</a>
                <a href="shop.html">لباس روزمره</a>
                <a href="shop.html?type=new">لباس تابستانی</a>
                <a href="shop.html?category=dress">لباس رسمی</a>
              </div>
              <div class="mega-col">
                <h5>پوشاک</h5>
                <a href="shop.html?category=manteau">مانتو</a>
                <a href="shop.html?category=blouse">شومیز</a>
                <a href="shop.html?category=blouse">بلوز</a>
                <a href="shop.html?category=pants">شلوار</a>
                <a href="shop.html?category=skirt">دامن</a>
              </div>
              <div class="mega-col">
                <h5>اکسسوری</h5>
                <a href="shop.html?category=bag">کیف</a>
                <a href="shop.html?category=bag">کفش</a>
                <a href="shop.html?category=bag">شال</a>
                <a href="shop.html?category=bag">زیورآلات</a>
              </div>
              <a class="mega-media" href="shop.html?type=new">
                <img src="images/mega.jpg" alt="کالکشن جدید نیلا" loading="lazy">
                <span>کالکشن جدید ۱۴۰۵</span>
              </a>
            </div>
          </li>
          <li><a href="shop.html?type=new">جدیدترین‌ها</a></li>
          <li><a href="about.html">درباره ما</a></li>
        </ul>
      </nav>

      <div class="header-actions">
        <button class="icon-btn" id="searchToggle" aria-label="جستجو">
          <svg class="ic" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
        </button>
        <button class="icon-btn" id="wishBtn" aria-label="علاقه‌مندی‌ها">
          <svg class="ic" viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.2l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
          <span class="wish-badge" id="wishBadge">۰</span>
        </button>
        <button class="icon-btn" id="accountBtn" aria-label="حساب کاربری">
          <svg class="ic" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.2 3.6-6.5 8-6.5s8 2.3 8 6.5"/></svg>
        </button>
        <button class="icon-btn" id="cartBtn" type="button" aria-label="سبد خرید">
          <svg class="ic" viewBox="0 0 24 24"><path d="M6 8h12l1.2 13H4.8L6 8z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/></svg>
          <span class="cart-badge" id="cartBadge">۰</span>
        </button>
      </div>
    </div>

    <!-- پنل جستجو -->
    <div class="search-panel" id="searchPanel">
      <div class="container search-inner">
        <form class="search-form" id="searchForm" role="search">
          <input type="search" id="searchInput" placeholder="جستجو در نیلا... (مثلاً مانتو یا دامن پلیسه)" autocomplete="off">
          <button class="btn btn-primary" type="submit">جستجو</button>
        </form>
        <p class="search-hints">
          پرجستجوها:
          <a href="shop.html?q=مانتو">مانتو</a>
          <a href="shop.html?q=مجلسی">لباس مجلسی</a>
          <a href="shop.html?q=دامن">دامن</a>
          <a href="shop.html?q=کیف">کیف</a>
          <a href="shop.html?q=بلوز">بلوز</a>
        </p>
      </div>
    </div>
  <div class="nav-overlay" id="navOverlay"></div>
  </header>

  <main class="lg-wrap">
    <div class="lg-blob b1" aria-hidden="true"></div>
    <div class="lg-blob b2" aria-hidden="true"></div>
    <div class="lg-card">
      <div class="lg-side">
        <h2 class="reveal">به خانواده‌ی نیلا بپیوند</h2>
        <p class="reveal">با ورود یا ساخت حساب کاربری، سفارش‌ها و علاقه‌مندی‌هایت را ببین و از تخفیف‌های اختصاصی بهره‌مند شو.</p>
        <ul class="lg-perks">
          <li class="reveal"><svg class="ic" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> پیگیری وضعیت سفارش‌ها</li>
          <li class="reveal"><svg class="ic" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> ذخیره‌ی علاقه‌مندی‌ها در حساب</li>
          <li class="reveal"><svg class="ic" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> کد تخفیف خوش‌آمد برای اعضا</li>
        </ul>
      </div>
      <div class="lg-main">
        <?php if ($err !== ''): ?>
        <div class="lg-notice error show" role="alert"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="lg-logged" id="lgLogged">
          <span class="lg-av" id="lgAv"></span>
          <h3 id="lgLoggedName"></h3>
          <p id="lgLoggedPhone"></p>
          <button class="btn btn-outline" type="button" id="lgLogout">خروج از حساب کاربری</button>
        </div>
        <div class="lg-auth reveal">
          <div class="lg-tabs">
            <button class="lg-tab active" type="button" data-pane="login">ورود</button>
            <button class="lg-tab" type="button" data-pane="signup">ایجاد حساب</button>
          </div>

          <form class="lg-form lg-pane active" data-pane="login" action="login.php" method="post" novalidate>
            <input type="hidden" name="intent" value="login">
            <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">
            <div class="field">
              <label for="lgPhone">شماره‌ی موبایل یا ایمیل</label>
              <input type="text" id="lgPhone" name="phone" placeholder="09xxxxxxxxx یا ایمیل شما">
            </div>
            <div class="field">
              <label for="lgPass">رمز عبور</label>
              <input type="password" id="lgPass" name="pass" placeholder="••••••••">
            </div>
            <div class="lg-remember">
              <label><input type="checkbox" checked> مرا به خاطر بسپار</label>
              <a href="#">رمز را فراموش کرده‌ای؟</a>
            </div>
            <button class="btn btn-primary" type="submit">ورود به حساب</button>
          </form>

          <form class="lg-form lg-pane" data-pane="signup" action="login.php" method="post" novalidate>
            <input type="hidden" name="intent" value="signup">
            <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">
            <div class="field">
              <label for="suName">نام و نام خانوادگی</label>
              <input type="text" id="suName" name="name" placeholder="مثلاً: مریم احمدی">
            </div>
            <div class="field">
              <label for="suPhone">شماره‌ی موبایل</label>
              <input type="tel" id="suPhone" name="phone" placeholder="09xxxxxxxxx" dir="ltr" style="text-align:right">
            </div>
            <div class="field">
              <label for="suPass">رمز عبور</label>
              <input type="password" id="suPass" name="pass" placeholder="حداقل ۶ کاراکتر">
            </div>
            <div class="field">
              <label for="suPass2">تکرار رمز عبور</label>
              <input type="password" id="suPass2" name="pass2" placeholder="رمز را دوباره وارد کن">
            </div>
            <button class="btn btn-primary" type="submit">ایجاد حساب کاربری</button>
          </form>

          <div class="lg-divider"><span>یا</span></div>
          <button class="lg-google reveal" type="button">
            <svg class="g-logo" viewBox="0 0 48 48" aria-hidden="true">
              <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
              <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
              <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
              <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            ورود سریع با گوگل
          </button>
          <p class="lg-hint">برای تست با داده‌های نمونه: شماره <b dir="ltr">09123456789</b> یا ایمیل <b dir="ltr">maryam@example.com</b><br>رمز هر دو: <b dir="ltr">123456</b></p>
        </div>
      </div>
    </div>
  </main>
  <!-- ═══════════ فوتر ═══════════ -->
  <footer class="site-footer">
    <div class="container footer-grid">
      <div class="f-col f-brand">
        <a href="index.html" class="logo logo-light"><span class="lg-main">NILA</span></a>
        <p>نیلا؛ خانه‌ی پوشاک زنانه‌ی شاد و مدرن. طراحی روز ایران، پارچه‌های درجه‌ی یک و استایلی که از کنارش نمی‌گذرید.</p>
        <div class="socials">
          <a href="#" aria-label="اینستاگرام"><svg class="ic" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
          <a href="#" aria-label="تلگرام"><svg class="ic" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></a>
          <a href="#" aria-label="واتساپ"><svg class="ic" viewBox="0 0 24 24"><path d="M21 11.5a8.4 8.4 0 0 1-8.5 8.4 8.6 8.6 0 0 1-3.9-.9L3 21l2-5.6a8.3 8.3 0 0 1-1-4.1A8.4 8.4 0 0 1 12.5 3a8.4 8.4 0 0 1 8.5 8.5z"/></svg></a>
          <a href="#" aria-label="ایکس"><svg class="ic" viewBox="0 0 24 24"><line x1="4" y1="4" x2="20" y2="20"/><line x1="20" y1="4" x2="4" y2="20"/></svg></a>
        </div>
      </div>

      <div class="f-col">
        <h4>دسترسی سریع</h4>
        <ul>
          <li><a href="index.html">صفحه اصلی</a></li>
          <li><a href="shop.html">فروشگاه</a></li>
          <li><a href="shop.html?type=new">جدیدترین‌ها</a></li>
          <li><a href="about.html">درباره ما</a></li>
          <li><a href="contact.html">تماس با ما</a></li>
          <li><a href="account.html">پنل کاربری</a></li>
        </ul>
      </div>

      <div class="f-col">
        <h4>دسته‌بندی‌ها</h4>
        <ul>
          <li><a href="shop.html?category=dress">لباس مجلسی</a></li>
          <li><a href="shop.html?category=manteau">مانتو</a></li>
          <li><a href="shop.html?category=blouse">شومیز و بلوز</a></li>
          <li><a href="shop.html?category=pants">شلوار</a></li>
          <li><a href="shop.html?category=skirt">دامن</a></li>
          <li><a href="shop.html?category=bag">کیف و اکسسوری</a></li>
        </ul>
      </div>

      <div class="f-col">
        <h4>اطلاعات تماس</h4>
        <ul class="f-contact">
          <li><svg class="ic" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> تهران، خیابان ولیعصر، برج آفتاب، طبقه‌ی ۶</li>
          <li><svg class="ic" viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.5 2.9.7a2 2 0 0 1 1.6 1.9z"/></svg> <span dir="ltr">021-91000000</span></li>
          <li><svg class="ic" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="22 6 12 13 2 6"/></svg> hello@nila.shop</li>
          <li><svg class="ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> هر روز، ۹ صبح تا ۹ شب</li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="container footer-bottom-inner">
        <p>© ۱۴۰۵ فروشگاه نیلا — تمامی حقوق محفوظ است.</p>
        <div class="f-legal">
          <a href="terms.html">قوانین و حقوق</a>
          <a href="terms.html#privacy">حریم خصوصی</a>
          <a href="terms.html#returns">شرایط بازگشت کالا</a>
        </div>
      </div>
    </div>
  </footer>

  <button class="to-top" id="toTop" aria-label="بازگشت به بالا">
    <svg class="ic" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
  </button>

  <!-- دراور سبد خرید (کاشه‌شونده از سمت چپ) -->
  <div class="drawer-overlay" id="cartOverlay"></div>
  <aside class="cart-drawer" id="cartDrawer" aria-label="سبد خرید">
    <div class="cd-head">
      <h3>سبد خرید <span class="cd-count" id="cdCount"></span></h3>
      <button class="icon-btn" id="cartClose" type="button" aria-label="بستن سبد">
        <svg class="ic" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="cd-items" id="cdItems"><!-- با جاوااسکریپت پر می‌شود --></div>
    <div class="cd-foot" id="cdFoot">
      <div class="cd-row"><span>جمع اقلام</span><b id="cdSubtotal"></b></div>
      <div class="cd-row" id="cdSaveRow" style="display:none"><span>سود شما</span><b id="cdSave" style="color:var(--green)"></b></div>
      <div class="cd-row"><span>هزینه‌ی ارسال</span><b id="cdShipping"></b></div>
      <div class="cd-row total"><span>مبلغ قابل پرداخت</span><b id="cdTotal"></b></div>
      <button class="btn btn-primary btn-block" id="cdCheckout" type="button">
        <svg class="ic" viewBox="0 0 24 24"><path d="M6 8h12l1.2 13H4.8L6 8z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/></svg>
        تکمیل خرید
      </button>
      <a class="cd-link" href="cart.html">مشاهده‌ی کامل سبد خرید</a>
    </div>
  </aside>

  <script src="js/products.js"></script>
  <script src="js/main.js"></script>
  <script src="js/login-ui.js"></script>
</body>
</html>
