<?php
declare(strict_types=1);
/**
 * =========================================================
 *  فروشگاه نیلا | پنل مدیریت (صفحه‌ی اصلی پنل — PHP واقعی)
 *  ------------------------------------------------------------
 *  این صفحه هنگام هر بار لود، اتصال به پایگاه داده را
 *  در سمت سرور بررسی می‌کند؛ اگر اتصال نباشد، یک نوار
 *  قرمز با دلیل دقیق و راه‌حل بالای صفحه نمایش داده می‌شود
 *  (بدون وابستگی به جاوااسکریپت).
 * =========================================================
 */
require_once __DIR__ . '/config/db.php';

$nilaDbOk = true;
$nilaDbErr = '';
try {
    $t = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
    $t = null;
} catch (Throwable $e) {
    $nilaDbOk = false;
    $nilaDbErr = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>پنل مدیریت | نیلا</title>
  <script>window.__NILA_DB__ = <?= $nilaDbOk ? 'true' : 'false' ?>; window.__NILA_DB_ERR__ = <?= json_encode($nilaDbErr, JSON_UNESCAPED_UNICODE) ?>;</script>
  <meta name="description" content="پنل مدیریت فروشگاه نیلا؛ داشبورد، سفارش‌ها، کاربران، محصولات و تنظیمات سایت.">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%23ec4d84'/%3E%3Ctext x='32' y='45' font-size='36' text-anchor='middle' fill='%23fff' font-family='Tahoma'%3E%D9%86%3C/text%3E%3C/svg%3E">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php if (!$nilaDbOk): ?>
  <div class="db-down">
    <b>⚠ اتصال به پایگاه داده برقرار نیست</b>
    <span>هیچ تغییری (محصول، سفارش، کاربر، پیام، رمز و...) ذخیره نمی‌شود. این صفحه در حالت نمایشی است.</span>
    <details><summary>مشخصات فنی خطا و راه‌حل</summary>
      <code dir="ltr"><?= htmlspecialchars($nilaDbErr, ENT_QUOTES) ?></code>
      <ul>
        <li>فایل <b dir="ltr">nila_shop.sql</b> داخل همین پوشه را در <b>phpMyAdmin</b> (آدرس: http://localhost/phpmyadmin) → برگه‌ی <b>Import</b> ایمپورت کن.</li>
        <li>اگر خطا Access denied بود: در فایل <b dir="ltr">config/db.php</b> نام کاربری و رمز MySQL را با WAMP هماهنگ کن (پیش‌فرض: root با رمز خالی).</li>
        <li>بعد از ایمپورت، همین صفحه را رفرش کن (Ctrl + F5).</li>
      </ul>
    </details>
  </div>
<?php endif; ?>

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

  <main>
    <section class="admin-gate" id="adminGate">
      <div class="ag-card" id="agCard">
        <div class="ag-brand">
          <span class="ag-mark">N</span>
          <div>
            <b>پنل مدیریت نیلا</b>
            <span>دسترسی محدود به مدیران فروشگاه</span>
          </div>
        </div>
        <form id="adminLoginForm" novalidate>
          <div class="field">
            <label for="agEmail">ایمیل سازمانی</label>
            <input type="email" id="agEmail" placeholder="admin@nila.shop" dir="ltr" style="text-align:right">
          </div>
          <div class="field">
            <label for="agPass">رمز عبور</label>
            <input type="password" id="agPass" placeholder="••••••••">
          </div>
          <button class="btn btn-primary" type="submit">ورود به پنل مدیریت</button>
        </form>
        <div class="ag-demo">
          <span>ورود نمایشی:</span>
          <b dir="ltr">admin@nila.shop</b>
          <i>/</i>
          <b dir="ltr">nila2026</b>
        </div>
        <a class="ag-back" href="index.html">بازگشت به فروشگاه نیلا</a>
      </div>
    </section>
    <div class="admin-body" id="adminBody" hidden>
    <section class="page-hero page-hero--center">
      <div class="container">
        <h1>پنل مدیریت</h1>
        <p>مرکز کنترل فروشگاه نیلا — سفارش‌ها، کاربران، محصولات و تنظیمات.</p>
        <nav class="breadcrumb" aria-label="مسیر صفحه">
          <a href="index.html">خانه</a>
          <span class="sep">/</span>
          <span>پنل مدیریت</span>
        </nav>
      </div>
    </section>

    <section class="container" style="padding-bottom:3rem;">
      <div class="demo-note">
        <svg class="ic" viewBox="0 0 24 24"><path d="M12 22s8-3.5 8-10V5l-8-3-8 3v7c0 6.5 8 10 8 10z"/></svg>
        <span id="admStatus" class="adm-status">بررسی اتصال...</span>
        <button class="btn btn-outline btn-xs" type="button" id="adminLogout">خروج از پنل</button>
      </div>
      <div class="adm-mode" id="admModeBanner" hidden></div>
      <div class="panel-layout">
        <aside class="panel-side">
          <div class="panel-user">
            <span class="pu-av admin-av" id="admAv">م</span>
            <div>
              <b class="pu-name" id="admName">مدیر</b>
              <span class="pu-phone" id="admEmail" dir="ltr" style="display:block">—</span>
            </div>
          </div>
          <nav class="panel-nav" aria-label="بخش‌های پنل مدیریت">
            <a href="admin.php?tab=dashboard" class="active" data-tab="dashboard"><svg class="ic" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> داشبورد</a>
            <a href="admin.php?tab=orders" data-tab="orders"><svg class="ic" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg> سفارش‌ها</a>
            <a href="admin.php?tab=users" data-tab="users"><svg class="ic" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> کاربران</a>
            <a href="admin.php?tab=admins" data-tab="admins"><svg class="ic" viewBox="0 0 24 24"><path d="M12 22s8-3.5 8-10V5l-8-3-8 3v7c0 6.5 8 10 8 10z"/></svg> مدیران و دسترسی</a>
            <a href="admin.php?tab=messages" data-tab="messages"><svg class="ic" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> پیام‌ها</a>
            <a href="admin.php?tab=products" data-tab="products"><svg class="ic" viewBox="0 0 24 24"><path d="M20.6 13.4 12 22 2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/></svg> محصولات</a>
            <a href="admin.php?tab=settings" data-tab="settings"><svg class="ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg> تنظیمات سایت</a>
          </nav>
        </aside>

        <div class="panel-content">
          <div class="ptab active" data-tab="dashboard">
            <div class="stat-grid">
              <div class="stat-card"><span class="stat-ic pink"><svg class="ic" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></span><div><b id="stOrders">۰</b><span>سفارش‌ها</span></div></div>
              <div class="stat-card"><span class="stat-ic lilac"><svg class="ic" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span><div><b id="stUsers">۰</b><span>کاربران</span></div></div>
              <div class="stat-card"><span class="stat-ic peach"><svg class="ic" viewBox="0 0 24 24"><path d="M20.6 13.4 12 22 2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/></svg></span><div><b id="stProducts">۰</b><span>محصولات</span></div></div>
              <div class="stat-card"><span class="stat-ic green"><svg class="ic" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/></svg></span><div><b id="stRevenue">۰</b><span>درآمد (تومان)</span></div></div>
            </div>
            <div class="pcard">
              <h3>آخرین سفارش‌ها</h3>
              <div class="mini-order-list" id="dashOrders"></div>
            </div>
          </div>

          <div class="ptab" data-tab="orders">
            <div class="pcard">
              <div class="pcard-head"><h3>سفارش‌های ثبت شده</h3><span class="badge pink" id="ordCount"></span></div>
              <div class="table-wrap">
                <table class="ptable">
                  <thead><tr><th>شماره</th><th>مشتری</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr></thead>
                  <tbody id="ordBody"></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="ptab" data-tab="users">
            <div class="pcard">
              <div class="pcard-head"><h3>مدیریت کاربران</h3><span class="badge pink" id="usrCount"></span></div>
              <div class="table-wrap">
                <table class="ptable">
                  <thead><tr><th>نام</th><th>موبایل</th><th>نقش</th><th>تاریخ پیوستن</th><th>عملیات</th></tr></thead>
                  <tbody id="usrBody"></tbody>
                </table>
              </div>
              <form class="add-form" id="addUserForm">
                <h4>افزودن کاربر جدید</h4>
                <div class="add-row">
                  <input type="text" id="nuName" placeholder="نام و نام خانوادگی">
                  <input type="tel" id="nuPhone" placeholder="09xxxxxxxxx" dir="ltr" style="text-align:right">
                  <select id="nuRole">
                    <option value="customer">مشتری</option>
                    <option value="support">پشتیبانی</option>
                    <option value="manager">مدیر فروشگاه</option>
                    <option value="admin">مدیر کل</option>
                  </select>
                  <button class="btn btn-primary" type="submit">افزودن</button>
                </div>
              </form>
            </div>
          </div>

          <div class="ptab" data-tab="admins">
            <div class="pcard">
              <div class="pcard-head"><h3>مدیران و سطوح دسترسی</h3><span class="badge pink" id="admCount"></span></div>
              <div class="table-wrap">
                <table class="ptable">
                  <thead><tr><th>نام مدیر</th><th>موبایل</th><th>سطح دسترسی</th><th>عملیات</th></tr></thead>
                  <tbody id="admBody"></tbody>
                </table>
              </div>
              <form class="add-form" id="addAdminForm">
                <h4>افزودن مدیر جدید</h4>
                <div class="add-row">
                  <input type="text" id="naName" placeholder="نام مدیر">
                  <input type="tel" id="naPhone" placeholder="09xxxxxxxxx" dir="ltr" style="text-align:right">
                  <select id="naRole">
                    <option value="admin">مدیر کل</option>
                    <option value="manager">مدیر فروشگاه</option>
                    <option value="support">پشتیبانی</option>
                  </select>
                  <input type="text" id="naNewRole" placeholder="یا سطح دسترسی جدید بنویسید (مثلاً: محاسب)">
                  <button class="btn btn-primary" type="submit">افزودن مدیر</button>
                </div>
              </form>
              <div class="sub-card">
                <h4>سطوح دسترسی فعلی</h4>
                <div class="role-list" id="roleList"></div>
              </div>
            </div>
          </div>

          <div class="ptab" data-tab="messages">
            <div class="pcard">
              <h3>مدیریت پیام‌ها</h3>
              <p class="pcard-sub">پیام‌های دریافتی از مشتریان و پاسخ پشتیبانی.</p>
              <div class="msg-list" id="admMsgs"></div>
              <form class="msg-reply" id="admMsgForm">
                <input type="text" id="admMsgText" placeholder="پاسخ پشتیبانی بنویسید...">
                <button class="btn btn-primary btn-sm" type="submit">ارسال پاسخ</button>
              </form>
            </div>
          </div>

          <div class="ptab" data-tab="products">
            <div class="pcard">
              <div class="pcard-head"><h3>مدیریت محصولات</h3><span class="badge pink" id="prodCount"></span></div>
              <div class="table-wrap">
                <table class="ptable">
                  <thead><tr><th></th><th>محصول</th><th>قیمت</th><th>موجودی</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                  <tbody id="prodBody"></tbody>
                </table>
              </div>
              <form class="add-form" id="addProdForm">
                <h4>افزودن محصول جدید — با تمام ویژگی‌های کارت فروشگاه</h4>
                <div class="np-grid">
                  <div class="np-preview" id="npPreview"></div>
                  <div class="np-fields">
                    <div class="np-row2">
                      <div class="field"><label for="npName">نام محصول</label><input type="text" id="npName" placeholder="مثلاً: شومیز کرپ «ابریشم»"></div>
                      <div class="field"><label for="npCat">دسته‌بندی</label>
                        <select id="npCat">
                          <option value="dress">لباس مجلسی</option>
                          <option value="manteau">مانتو</option>
                          <option value="blouse">شومیز و بلوز</option>
                          <option value="pants">شلوار</option>
                          <option value="skirt">دامن</option>
                          <option value="bag">کیف و اکسسوری</option>
                        </select>
                      </div>
                    </div>
                    <div class="np-row2">
                      <div class="field"><label for="npPrice">قیمت فعلی (تومان)</label><input type="text" id="npPrice" placeholder="3500000" dir="ltr" style="text-align:right"></div>
                      <div class="field"><label for="npOldPrice">قیمت قبل از تخفیف — اختیاری</label><input type="text" id="npOldPrice" placeholder="4200000" dir="ltr" style="text-align:right"></div>
                    </div>
                    <div class="np-row2">
                      <div class="field"><label for="npStock">موجودی</label><input type="text" id="npStock" placeholder="10" value="10" dir="ltr" style="text-align:right"></div>
                      <div class="field"><label for="npRating">امتیاز خریداران (از ۵)</label><input type="text" id="npRating" placeholder="4.6" value="4.6" dir="ltr" style="text-align:right"></div>
                    </div>
                    <div class="field">
                      <label>سایزها</label>
                      <div class="np-checks">
                        <label><input type="checkbox" value="S"> S</label>
                        <label><input type="checkbox" value="M" checked> M</label>
                        <label><input type="checkbox" value="L" checked> L</label>
                        <label><input type="checkbox" value="XL"> XL</label>
                        <label><input type="checkbox" value="XXL"> XXL</label>
                      </div>
                    </div>
                    <div class="np-row2">
                      <div class="field"><label for="npColors">رنگ‌ها (با ویرگول جدا کنید)</label><input type="text" id="npColors" placeholder="صورتی، کرم، سرمه‌ای"></div>
                      <div class="field"><label for="npBadge">برچسب کارت</label>
                        <select id="npBadge">
                          <option value="">بدون برچسب</option>
                          <option value="new">جدید</option>
                          <option value="popular">پرفروش</option>
                        </select>
                      </div>
                    </div>
                    <div class="np-row2">
                      <div class="field">
                        <label for="npImage">تصویر محصول (آپلود)</label>
                        <input type="file" id="npImage" accept="image/jpeg,image/png,image/webp">
                        <small class="np-hint" id="npImageHint">خالی بگذاری، تصویر پیش‌فرض دسته استفاده می‌شود</small>
                      </div>
                      <div class="field">
                        <label for="npImage2">تصویر دوم (هنگام هاور — اختیاری)</label>
                        <input type="file" id="npImage2" accept="image/jpeg,image/png,image/webp">
                        <small class="np-hint">JPEG / PNG / WEBP — حداکثر ۳ مگابایت</small>
                      </div>
                    </div>
                    <div class="field"><label for="npShort">توضیح کوتاه</label><textarea id="npShort" rows="2" placeholder="یک یا دو جمله درباره‌ی محصول..."></textarea></div>
                    <button class="btn btn-primary" type="submit" id="npSubmit">ثبت و افزودن محصول</button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <div class="ptab" data-tab="settings">
            <div class="pcard">
              <h3>تنظیمات سایت</h3>
              <p class="pcard-sub">اطلاعات عمومی و پارامترهای ارسال فروشگاه.</p>
              <form id="siteSettingsForm" novalidate>
                <div class="form-grid">
                  <div class="field"><label>نام فروشگاه</label><input type="text" id="ssName" value="نیلا | NILA FASHION"></div>
                  <div class="field"><label>شماره تماس</label><input type="text" id="ssPhone" dir="ltr" style="text-align:right" value="021-91000000"></div>
                  <div class="field"><label>ایمیل پشتیبانی</label><input type="email" id="ssEmail" dir="ltr" style="text-align:right" value="hello@nila.shop"></div>
                  <div class="field"><label>آدرس فروشگاه</label><input type="text" id="ssAddress" value="تهران، خیابان ولیعصر، برج آفتاب، طبقه ۶"></div>
                  <div class="field"><label>هزینه‌ی ارسال (تومان)</label><input type="text" id="ssShipping" dir="ltr" style="text-align:right" value="150000"></div>
                  <div class="field"><label>سقف ارسال رایگان (تومان)</label><input type="text" id="ssFreeShip" dir="ltr" style="text-align:right" value="2000000"></div>
                </div>
                <button class="btn btn-primary" type="submit">ذخیره‌ی تنظیمات</button>
              </form>
              <div class="danger-zone">
                <div>
                  <h4>منطقه‌ی حساس</h4>
                  <p>بازگردانی داده‌های نمایشی فروشگاه به حالت اولیه.</p>
                </div>
                <button class="btn btn-outline" type="button" id="resetDemo">ریست داده‌های نمایشی</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
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
  <script src="js/admin.js"></script>
</body>
</html>
