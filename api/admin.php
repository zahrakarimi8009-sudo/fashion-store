<?php
declare(strict_types=1);

/**
 * =========================================================
 *  فروشگاه نیلا | NILA FASHION
 *  API: پنل مدیریت (JSON)
 *  ------------------------------------------------------------
 *  POST {action:'login', email, password} -> ورود مدیر
 *  بقیه‌ی اکشن‌ها فقط با نشست مدیر فعال:
 *    dashboard, orders, order_status, order_delete,
 *    users, user_add, user_delete,
 *    admins, admin_add, admin_delete,
 *    messages, message_read, message_reply,
 *    products, product_add, product_update, product_delete,
 *    product_toggle, settings, settings_save, logout
 * =========================================================
 */

require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function nila_digits($s) {
    $s = strtr((string)$s, '۰۱۲۵۶۸۹', '0123456789');
    $s = strtr($s, '٠١٢٤٥٦٨٩', '0123456789');
    return preg_replace('/\D/', '', $s);
}

function nila_admin_fail($error, $code = 200) {
    if ($code !== 200) {
        http_response_code($code);
    }
    echo json_encode(['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}

function nila_admin_required() {
    $a = $_SESSION['nila_admin'] ?? null;
    if (!is_array($a) || empty($a['id'])) {
        nila_admin_fail('not_logged_in', 401);
    }
}

$VALID_STATUS = ['registered', 'pending_payment', 'shipped', 'delivered', 'cancelled'];

/**
 * ذخیره‌ی تصویر آپلودشده در پوشه‌ی images/ (فقط برای اکشن‌های محصول)
 * @return string|null مسیر نسبی ('images/up-xxx.jpg') یا null اگر فایلی نیست/نامعتبر
 */
function nila_save_upload(string $field): ?string {
    if (empty($_FILES[$field]) || (int)$_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['size'] > 3 * 1024 * 1024) {
        return null; // حداکثر ۳ مگابایت
    }
    $info = @getimagesize($f['tmp_name']);
    if (!$info) {
        return null; // تصویر نیست
    }
    $ext = match ((int)$info[2]) {
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        default        => null,
    };
    if ($ext === null) {
        return null;
    }
    $dir = __DIR__ . '/../images';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return null;
    }
    if (!is_writable($dir)) {
        return null;
    }
    $name = 'up-' . date('ymd') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        return null;
    }
    return 'images/' . $name;
}

/** حذف تصویر آپلودشده‌ی قدیمی (فقط فایل‌های up-، تصاویر نمونه دست‌نخورده می‌مانند) */
function nila_remove_upload(?string $path): void {
    if ($path && strpos($path, 'images/up-') === 0 && is_file(__DIR__ . '/../' . $path)) {
        @unlink(__DIR__ . '/../' . $path);
    }
}

try {
    $pdo = db();
    $in = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($in)) {
        $in = $_POST;
    }
    $action = (string)($in['action'] ?? '');

    /* ---------- ورود مدیر ---------- */
    if ($action === 'login') {
        $email = trim((string)($in['email'] ?? ''));
        $pass = (string)($in['password'] ?? '');
        $st = $pdo->prepare('SELECT id, name, email, password_hash FROM `admins` WHERE email = ?');
        $st->execute([$email]);
        $a = $st->fetch();
        if (!$a || !verify_password($pass, (string)$a['password_hash'])) {
            nila_admin_fail('bad_credentials', 401);
        }
        $_SESSION['nila_admin'] = [
            'id'    => (int)$a['id'],
            'name'  => (string)$a['name'],
            'email' => (string)$a['email'],
        ];
        echo json_encode(['ok' => true, 'admin' => $_SESSION['nila_admin']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'logout') {
        unset($_SESSION['nila_admin']);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    nila_admin_required();

    /* ---------- داشبورد ---------- */
    if ($action === 'dashboard') {
        $cnt = function ($sql) use ($pdo) {
            return (int)$pdo->query($sql)->fetchColumn();
        };
        $orders = $cnt('SELECT COUNT(*) FROM `orders`');
        $pending = $cnt("SELECT COUNT(*) FROM `orders` WHERE status = 'pending_payment'");
        $users  = $cnt('SELECT COUNT(*) FROM `users`');
        $admins = $cnt('SELECT COUNT(*) FROM `admins`');
        $products = $cnt('SELECT COUNT(*) FROM `products`');
        $revenue = (int)$pdo->query("SELECT COALESCE(SUM(total), 0) FROM `orders` WHERE status <> 'cancelled'")->fetchColumn();
        $st = $pdo->query(
            'SELECT order_code, customer_name, total, status, created_at
             FROM `orders` ORDER BY created_at DESC, id DESC LIMIT 4'
        );
        $latest = [];
        foreach ($st as $o) {
            $latest[] = [
                'no'     => (string)$o['order_code'],
                'name'   => (string)$o['customer_name'],
                'total'  => (int)$o['total'],
                'status' => (string)$o['status'],
                'date'   => (string)$o['created_at'],
            ];
        }
        echo json_encode([
            'ok' => true,
            'stats'  => ['orders' => $orders, 'pending' => $pending, 'users' => $users,
                         'admins' => $admins, 'products' => $products, 'revenue' => $revenue],
            'latest' => $latest,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ---------- سفارش‌ها ---------- */
    if ($action === 'orders') {
        $st = $pdo->query(
            'SELECT id, order_code, customer_name, customer_phone, city, subtotal, discount, shipping, total, status, created_at
             FROM `orders` ORDER BY created_at DESC, id DESC LIMIT 200'
        );
        $out = [];
        foreach ($st as $o) {
            $out[] = [
                'id'    => (int)$o['id'],
                'no'    => (string)$o['order_code'],
                'name'  => (string)$o['customer_name'],
                'phone' => (string)$o['customer_phone'],
                'city'  => (string)($o['city'] ?: ''),
                'total' => (int)$o['total'],
                'status'=> (string)$o['status'],
                'date'  => (string)$o['created_at'],
            ];
        }
        echo json_encode(['ok' => true, 'orders' => $out], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'order_status') {
        $id = (int)($in['id'] ?? 0);
        $status = (string)($in['status'] ?? '');
        if ($id < 1 || !in_array($status, $VALID_STATUS, true)) {
            nila_admin_fail('invalid');
        }
        $pdo->prepare('UPDATE `orders` SET status = ? WHERE id = ?')->execute([$status, $id]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'order_delete') {
        $id = (int)($in['id'] ?? 0);
        if ($id < 1) { nila_admin_fail('invalid'); }
        $pdo->prepare('DELETE FROM `orders` WHERE id = ?')->execute([$id]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ---------- کاربران ---------- */
    if ($action === 'users') {
        $st = $pdo->query(
            'SELECT id, name, phone, role, created_at FROM `users` ORDER BY id LIMIT 500'
        );
        $out = [];
        foreach ($st as $u) {
            $out[] = [
                'id'    => (int)$u['id'],
                'name'  => (string)$u['name'],
                'phone' => (string)$u['phone'],
                'role'  => (string)$u['role'],
                'date'  => (string)$u['created_at'],
            ];
        }
        echo json_encode(['ok' => true, 'users' => $out], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'user_add') {
        $name = trim((string)($in['name'] ?? ''));
        $phone = nila_digits($in['phone'] ?? '');
        $role = (string)($in['role'] ?? 'customer');
        if (!in_array($role, ['customer', 'support', 'manager', 'admin'], true)) {
            $role = 'customer';
        }
        if (!preg_match('/^.{3,100}$/u', $name) || !preg_match('/^09\d{9}$/', $phone)) {
            nila_admin_fail('invalid');
        }
        $chk = $pdo->prepare('SELECT 1 FROM `users` WHERE phone = ?');
        $chk->execute([$phone]);
        if ($chk->fetch()) {
            nila_admin_fail('phone_exists');
        }
        $pdo->prepare('INSERT INTO `users` (`name`, `phone`, `password_hash`, `role`) VALUES (?, ?, ?, ?)')
            ->execute([$name, $phone, make_password_hash('123456'), $role]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'user_delete') {
        $id = (int)($in['id'] ?? 0);
        if ($id < 1) { nila_admin_fail('invalid'); }
        $pdo->prepare('DELETE FROM `users` WHERE id = ?')->execute([$id]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ---------- مدیران ---------- */
    if ($action === 'admins') {
        $st = $pdo->query(
            'SELECT a.id, a.name, a.phone, a.email, COALESCE(l.name, "مدیر") AS level
             FROM `admins` a LEFT JOIN `admin_levels` l ON l.id = a.level_id
             ORDER BY a.id'
        );
        $out = [];
        foreach ($st as $a) {
            $out[] = [
                'id'    => (int)$a['id'],
                'name'  => (string)$a['name'],
                'phone' => (string)$a['phone'],
                'email' => (string)$a['email'],
                'level' => (string)$a['level'],
            ];
        }
        echo json_encode(['ok' => true, 'admins' => $out], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'admin_add') {
        $name = trim((string)($in['name'] ?? ''));
        $phone = nila_digits($in['phone'] ?? '');
        $levelName = trim((string)($in['level'] ?? '')) ?: 'پشتیبانی';
        if (!preg_match('/^.{3,100}$/u', $name) || !preg_match('/^09\d{9}$/', $phone)) {
            nila_admin_fail('invalid');
        }
        $st = $pdo->prepare('SELECT id FROM `admin_levels` WHERE name = ?');
        $st->execute([$levelName]);
        $lvl = $st->fetch();
        if (!$lvl) {
            $pdo->prepare('INSERT INTO `admin_levels` (`name`) VALUES (?)')->execute([$levelName]);
            $levelId = (int)$pdo->lastInsertId();
        } else {
            $levelId = (int)$lvl['id'];
        }
        $email = 'ph' . $phone . '@nila.shop';
        $chk = $pdo->prepare('SELECT 1 FROM `admins` WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $email = 'ph' . $phone . rand(100, 999) . '@nila.shop';
        }
        $pdo->prepare('INSERT INTO `admins` (`name`, `phone`, `email`, `password_hash`, `level_id`) VALUES (?, ?, ?, ?, ?)')
            ->execute([$name, $phone, $email, make_password_hash('123456'), $levelId]);
        echo json_encode(['ok' => true, 'email' => $email], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'admin_delete') {
        $id = (int)($in['id'] ?? 0);
        if ($id < 1) { nila_admin_fail('invalid'); }
        $count = (int)$pdo->query('SELECT COUNT(*) FROM `admins`')->fetchColumn();
        if ($count <= 1) {
            nila_admin_fail('last_admin');
        }
        $pdo->prepare('DELETE FROM `admins` WHERE id = ?')->execute([$id]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ---------- پیام‌ها ---------- */
    if ($action === 'messages') {
        $st = $pdo->query(
            'SELECT id, user_id, sender_name, body, is_read, created_at
             FROM `messages` ORDER BY created_at DESC, id DESC LIMIT 100'
        );
        $out = [];
        foreach ($st as $m) {
            $out[] = [
                'id'      => (int)$m['id'],
                'user_id' => $m['user_id'] !== null ? (int)$m['user_id'] : null,
                'from'    => (string)$m['sender_name'],
                'text'    => (string)$m['body'],
                'read'    => (bool)$m['is_read'],
                'date'    => (string)$m['created_at'],
            ];
        }
        echo json_encode(['ok' => true, 'messages' => $out], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'message_read') {
        $id = (int)($in['id'] ?? 0);
        if ($id < 1) { nila_admin_fail('invalid'); }
        $pdo->prepare('UPDATE `messages` SET is_read = 1 WHERE id = ?')->execute([$id]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'message_reply') {
        $userId = (int)($in['user_id'] ?? 0);
        $body = trim((string)($in['body'] ?? ''));
        if ($userId < 1 || !preg_match('/^.{1,1000}$/u', $body)) {
            nila_admin_fail('invalid');
        }
        $pdo->prepare('INSERT INTO `messages` (`user_id`, `sender_name`, `body`, `is_read`) VALUES (?, ?, ?, 0)')
            ->execute([$userId, 'نیلا', $body]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ---------- محصولات ---------- */
    if ($action === 'products') {
        $st = $pdo->query(
            'SELECT p.id, c.slug AS category, p.name, p.short_desc, p.description,
                    p.price, p.sale_price, p.image, p.image2, p.sizes, p.colors, p.specs,
                    p.stock, p.is_active, p.is_new, p.is_popular, p.rating
             FROM `products` p LEFT JOIN `categories` c ON c.id = p.category_id
             ORDER BY p.id'
        );
        $out = [];
        foreach ($st as $p) {
            $out[] = [
                'id'        => (int)$p['id'],
                'category'  => (string)($p['category'] ?: 'dress'),
                'name'      => (string)$p['name'],
                'short'     => (string)($p['short_desc'] ?? ''),
                'desc'      => (string)($p['description'] ?? ''),
                'price'     => (int)$p['price'],
                'salePrice' => $p['sale_price'] !== null ? (int)$p['sale_price'] : null,
                'img'       => (string)$p['image'],
                'img2'      => $p['image2'] ? (string)$p['image2'] : null,
                'sizes'     => array_values(array_filter(array_map('trim', explode(',', (string)$p['sizes'])))),
                'colors'    => (is_array(json_decode((string)$p['colors'], true))) ? json_decode((string)$p['colors'], true) : [['name' => 'صورتی', 'hex' => '#ec4d84']],
                'specs'     => (is_array(json_decode((string)($p['specs'] ?? ''), true))) ? json_decode((string)$p['specs'], true) : [],
                'stock'     => (int)$p['stock'],
                'active'    => (bool)$p['is_active'],
                'isNew'     => (bool)$p['is_new'],
                'isPopular' => (bool)$p['is_popular'],
                'rating'    => (float)$p['rating'],
            ];
        }
        echo json_encode(['ok' => true, 'products' => $out], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $productFields = function (array $in, &$err) {
        $name = trim((string)($in['name'] ?? ''));
        $price = (int)nila_digits($in['price'] ?? '');
        $oldPrice = (int)nila_digits($in['old_price'] ?? '');
        $stock = (int)nila_digits($in['stock'] ?? '');
        $category = (string)($in['category'] ?? 'dress');
        $isSale = $oldPrice > $price && $price > 0;
        $finalPrice = $isSale ? $oldPrice : $price;
        $salePrice = $isSale ? $price : null;
        if (!preg_match('/^.{3,150}$/u', $name)) { $err = 'invalid'; return null; }
        if ($finalPrice < 1000) { $err = 'invalid'; return null; }
        $asArr = function ($v) {
            if (is_array($v)) { return $v; }
            if (is_string($v) && $v !== '') {
                $d = json_decode($v, true);
                if (is_array($d)) { return $d; }
            }
            return [];
        };
        $sizes = $asArr($in['sizes'] ?? null);
        $sizes = array_values(array_filter(array_map(function ($s) { return trim((string)$s); }, $sizes)));
        $colors = $asArr($in['colors'] ?? null);
        $cleanColors = [];
        foreach ($colors as $c) {
            if (is_array($c) && isset($c['name'])) {
                $cleanColors[] = ['name' => (string)$c['name'], 'hex' => (string)($c['hex'] ?? '#ec4d84')];
            }
        }
        if (!$cleanColors) {
            $cleanColors = [['name' => 'صورتی', 'hex' => '#ec4d84']];
        }
        $specs = $asArr($in['specs'] ?? null);
        $cleanSpecs = [];
        foreach ($specs as $s) {
            if (is_array($s) && count($s) === 2) {
                $cleanSpecs[] = [(string)$s[0], (string)$s[1]];
            }
        }
        return [
            'name'       => $name,
            'short'      => trim((string)($in['short'] ?? '')) !== '' ? trim((string)$in['short']) : $name,
            'desc'       => trim((string)($in['desc'] ?? '')) !== '' ? trim((string)$in['desc']) : ('یک محصول جدید از مجموعه‌ی نیلا؛ با پارچه‌ی درجه‌ی یک و دوخت تمیز. ' . $name . ' برای استایل‌های رسمی و نیمه‌رسمی مناسب است.'),
            'category'   => $category,
            'price'      => $finalPrice,
            'salePrice'  => $salePrice,
            'image'      => (string)($in['image'] ?? ''),
            'image2'     => (string)($in['image2'] ?? ''),
            'sizes'      => $sizes ? implode(',', $sizes) : 'M,L',
            'colors'     => json_encode($cleanColors, JSON_UNESCAPED_UNICODE),
            'specs'      => json_encode($cleanSpecs, JSON_UNESCAPED_UNICODE),
            'isNew'      => (int)!!($in['is_new'] ?? 0),
            'isPopular'  => (int)!!($in['is_popular'] ?? 0),
            'rating'     => min(5, max(0, (float)($in['rating'] ?? 4.5))),
            'stock'      => max(0, $stock),
        ];
    };

    if ($action === 'product_add') {
        $err = null;
        $f = $productFields($in, $err);
        if (!$f) { nila_admin_fail($err ?: 'invalid'); }
        $imgUp  = nila_save_upload('image');
        $img2Up = nila_save_upload('image2');
        if ($imgUp !== null)  { $f['image']  = $imgUp; }
        if ($img2Up !== null) { $f['image2'] = $img2Up; }
        $st = $pdo->prepare('SELECT id FROM `categories` WHERE slug = ?');
        $st->execute([$f['category']]);
        $cat = $st->fetch();
        $catId = $cat ? (int)$cat['id'] : 1;
        $img = $f['image'] !== '' ? $f['image'] : 'images/cat-' . $f['category'] . '.jpg';
        $code = 'NLA-' . str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $pdo->prepare(
            'INSERT INTO `products`
             (`category_id`, `name`, `short_desc`, `description`, `price`, `sale_price`,
              `image`, `image2`, `sizes`, `colors`, `specs`, `is_new`, `is_popular`,
              `rating`, `sales_count`, `code`, `stock`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)'
        )->execute([
            $catId, $f['name'], $f['short'], $f['desc'], $f['price'], $f['salePrice'],
            $img, $f['image2'] !== '' ? $f['image2'] : $img, $f['sizes'], $f['colors'], $f['specs'],
            $f['isNew'], $f['isPopular'], $f['rating'], $code, $f['stock'],
        ]);
        echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'code' => $code], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'product_update') {
        $id = (int)($in['id'] ?? 0);
        $err = null;
        $f = $productFields($in, $err);
        if ($id < 1 || !$f) { nila_admin_fail($err ?: 'invalid'); }
        $cur = $pdo->prepare('SELECT image, image2 FROM `products` WHERE id = ?');
        $cur->execute([$id]);
        $curRow = $cur->fetch();
        if (!$curRow) { nila_admin_fail('invalid'); }
        $imgUp  = nila_save_upload('image');
        $img2Up = nila_save_upload('image2');
        $st = $pdo->prepare('SELECT id FROM `categories` WHERE slug = ?');
        $st->execute([$f['category']]);
        $cat = $st->fetch();
        $catId = $cat ? (int)$cat['id'] : 1;
        if ($imgUp !== null || $img2Up !== null) {
            $pdo->prepare('UPDATE `products` SET image = COALESCE(?, image), image2 = COALESCE(?, image2) WHERE id = ?')
                ->execute([$imgUp, $img2Up, $id]);
            $after = $pdo->prepare('SELECT image, image2 FROM `products` WHERE id = ?');
            $after->execute([$id]);
            $kept = $after->fetch();
            foreach ([$curRow['image'], $curRow['image2']] as $oldImg) {
                if ($oldImg && $kept && !in_array($oldImg, [$kept['image'], $kept['image2']], true)) {
                    nila_remove_upload($oldImg);
                }
            }
        }
        $pdo->prepare(
            'UPDATE `products` SET
                category_id = ?, name = ?, short_desc = ?, description = ?,
                price = ?, sale_price = ?, sizes = ?, colors = ?, specs = ?,
                is_new = ?, is_popular = ?, rating = ?, stock = ?
             WHERE id = ?'
        )->execute([
            $catId, $f['name'], $f['short'], $f['desc'], $f['price'], $f['salePrice'],
            $f['sizes'], $f['colors'], $f['specs'], $f['isNew'], $f['isPopular'],
            $f['rating'], $f['stock'], $id,
        ]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'product_delete') {
        $id = (int)($in['id'] ?? 0);
        if ($id < 1) { nila_admin_fail('invalid'); }
        $cur = $pdo->prepare('SELECT image, image2 FROM `products` WHERE id = ?');
        $cur->execute([$id]);
        $curRow = $cur->fetch();
        $pdo->prepare('DELETE FROM `products` WHERE id = ?')->execute([$id]);
        if ($curRow) {
            nila_remove_upload($curRow['image']);
            nila_remove_upload($curRow['image2']);
        }
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'product_toggle') {
        $id = (int)($in['id'] ?? 0);
        if ($id < 1) { nila_admin_fail('invalid'); }
        $pdo->prepare('UPDATE `products` SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ---------- تنظیمات سایت ---------- */
    if ($action === 'settings') {
        $st = $pdo->query('SELECT skey, svalue FROM `settings`');
        $out = [];
        foreach ($st as $r) {
            $out[(string)$r['skey']] = (string)$r['svalue'];
        }
        echo json_encode(['ok' => true, 'settings' => $out], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'settings_save') {
        $vals = is_array($in['settings'] ?? null) ? $in['settings'] : [];
        if (!$vals) { nila_admin_fail('invalid'); }
        $up = $pdo->prepare('INSERT INTO `settings` (`skey`, `svalue`) VALUES (?, ?)
                             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
        foreach ($vals as $k => $v) {
            $k = (string)$k;
            if ($k === '' || strlen($k) > 50) { continue; }
            $up->execute([$k, (string)$v]);
        }
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    nila_admin_fail('bad_action');
} catch (Throwable $e) {
    error_log('[nila-api-admin] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_unavailable'], JSON_UNESCAPED_UNICODE);
}
