<?php
declare(strict_types=1);

/**
 * =========================================================
 *  فروشگاه نیلا | NILA FASHION
 *  خروج از حساب
 *  ------------------------------------------------------------
 *  - نشست PHP را کاملاً نابود می‌کند
 *  - کاربر فرانت‌اند (localStorage) را هم پاک می‌کند
 *  - کاربر به مقصد (next) یا صفحه‌ی اصلی برمی‌گردد
 * =========================================================
 */

session_start();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

$next = (string)($_GET['next'] ?? 'index.html');
if (!preg_match('/^[\w-]+\.html$/', $next)) {
    $next = 'index.html';
}
?>
<!doctype html>
<html dir="rtl" lang="fa">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="refresh" content="2;url=<?= $next ?>">
  <title>خروج از حساب | نیلا</title>
  <style>
    *{ box-sizing:border-box; }
    body{
      margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
      font-family:Tahoma, Arial, sans-serif; color:#3b2a31;
      background:radial-gradient(640px 400px at 90% -10%, rgba(156,91,214,.12), transparent 60%),
                 radial-gradient(560px 400px at 5% 110%, rgba(236,77,132,.12), transparent 60%), #fdf6f2;
      padding:1.5rem;
    }
    .card{
      width:min(420px, 100%); background:#fff; border:1px solid #f0d8e2; border-radius:26px;
      box-shadow:0 30px 70px rgba(150,40,90,.14); padding:2.6rem 2rem; text-align:center;
      animation:rise .5s cubic-bezier(.22,.9,.3,1) both;
    }
    @keyframes rise{ from{ opacity:0; transform:translateY(20px);} to{ opacity:1; transform:none; } }
    .mark{
      width:64px; height:64px; border-radius:50%; margin-bottom:1rem;
      background:var(--soft, #fdeef4); background:#fdeef4; color:#c2336e;
      display:inline-flex; align-items:center; justify-content:center;
      font-size:1.6rem; font-weight:800;
    }
    h1{ margin:0 0 .4rem; font-size:1.25rem; }
    p{ margin:0; font-size:.86rem; color:#8a7079; line-height:1.9; }
    .dots span{ display:inline-block; width:6px; height:6px; border-radius:50%; background:#9c5bd6; margin:0 2px; animation:blink 1.2s infinite; }
    .dots span:nth-child(2){ animation-delay:.2s; } .dots span:nth-child(3){ animation-delay:.4s; }
    @keyframes blink{ 0%,100%{ opacity:.25;} 50%{ opacity:1; } }
  </style>
</head>
<body>
  <div class="card">
    <span class="mark">→</span>
    <h1>از حساب خارج شدید</h1>
    <p>ممنون از همراهی‌تان.<br>تا لحظه‌ای دیگر به فروشگاه برمی‌گردید…</p>
    <div class="dots" style="margin-top:1rem"><span></span><span></span><span></span></div>
  </div>

  <script src="js/main.js"></script>
  <script>
    /* پاک‌سازی سمت فرانت‌اند (حساب، همگام‌سازی وضعیت) */
    try { clearUser(); } catch (e) {}
    setTimeout(function () { location.replace('<?= $next ?>'); }, 900);
  </script>
</body>
</html>
