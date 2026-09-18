<?php
declare(strict_types=1);

/**
 * =========================================================
 *  فروشگاه نیلا | NILA FASHION
 *  پل ورود موفق
 *  ------------------------------------------------------------
 *  بعد از ورود/ثبت‌نام موفق، این صفحه:
 *   1) کاربر نشست PHP را با saveUser() به فرانت‌اند همگام می‌کند
 *   2) کاربر را به مقصد (next) یا صفحه‌ی اصلی می‌برد
 * =========================================================
 */

session_start();

$next = (string)($_GET['next'] ?? 'index.html');
if (!preg_match('/^[\w-]+\.html$/', $next)) {
    $next = 'index.html';
}

if (!isset($_SESSION['nila_user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['nila_user'];
$nameHtml = htmlspecialchars((string)$user['name'], ENT_QUOTES, 'UTF-8');
$userJson = json_encode($user, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$destLabel = ($next === 'index.html') ? 'صفحه‌ی اصلی' : 'مقصد';
?>
<!doctype html>
<html dir="rtl" lang="fa">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="refresh" content="2.5;url=<?= $next ?>">
  <title>ورود موفق | نیلا</title>
  <style>
    *{ box-sizing:border-box; }
    body{
      margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
      font-family:Tahoma, Arial, sans-serif; color:#3b2a31;
      background:radial-gradient(640px 400px at 90% -10%, rgba(156,91,214,.14), transparent 60%),
                 radial-gradient(560px 400px at 5% 110%, rgba(236,77,132,.14), transparent 60%), #fdf6f2;
      padding:1.5rem;
    }
    .card{
      width:min(440px, 100%); background:#fff; border:1px solid #f0d8e2; border-radius:26px;
      box-shadow:0 30px 70px rgba(150,40,90,.16); padding:2.6rem 2rem; text-align:center;
      animation:rise .55s cubic-bezier(.22,.9,.3,1) both;
    }
    @keyframes rise{ from{ opacity:0; transform:translateY(22px);} to{ opacity:1; transform:none; } }
    .ring{
      width:74px; height:74px; border-radius:50%; margin-bottom:1.1rem;
      background:linear-gradient(135deg, #9c5bd6, #ec4d84); color:#fff;
      display:inline-flex; align-items:center; justify-content:center;
      font-size:2rem; font-weight:800; box-shadow:0 16px 36px rgba(236,77,132,.35);
      animation:pop .5s .15s cubic-bezier(.22,.9,.3,1) both;
    }
    @keyframes pop{ from{ transform:scale(.6); opacity:0;} to{ transform:scale(1); opacity:1; } }
    h1{ margin:0 0 .4rem; font-size:1.35rem; }
    p{ margin:0; font-size:.88rem; color:#8a7079; line-height:1.9; }
    .dots span{ display:inline-block; width:6px; height:6px; border-radius:50%; background:#ec4d84; margin:0 2px; animation:blink 1.2s infinite; }
    .dots span:nth-child(2){ animation-delay:.2s; } .dots span:nth-child(3){ animation-delay:.4s; }
    @keyframes blink{ 0%,100%{ opacity:.25;} 50%{ opacity:1; } }
  </style>
</head>
<body>
  <div class="card">
    <span class="ring">✓</span>
    <h1>خوش آمدی، <?= $nameHtml ?></h1>
    <p>ورود شما با موفقیت انجام شد.<br>در حال انتقال به <?= $destLabel ?>…</p>
    <div class="dots" style="margin-top:1rem"><span></span><span></span><span></span></div>
  </div>

  <script src="js/main.js"></script>
  <script>
    /* همگام‌سازی با فرانت‌اند تا بقیه‌ی سایت (سبد، پنل) کاربر را بشناسد */
    try { saveUser(<?= $userJson ?>); } catch (e) {}
    setTimeout(function () { location.replace('<?= $next ?>'); }, 1100);
  </script>
</body>
</html>
