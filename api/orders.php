<?php
declare(strict_types=1);

/**
 * =========================================================
 *  فروشگاه نیلا | NILA FASHION
 *  API: سفارش‌ها (JSON)
 *  ------------------------------------------------------------
 *  GET  api/orders.php -> سفارش‌های کاربر لاگین‌شده + اقلام هرکدام
 *  POST api/orders.php (JSON: name, phone, city, zip, address, note,
 *                          payment, coupon_code, items[{id, qty, size, color}])
 *       -> ثبت سفارش واقعی: قیمت‌ها و موجودی فقط از دیتابیس
 *          (هیچ عددی از سمت کاربر قابل اعتماد نیست).
 *       قواعد: کد تخفیف NILA10 = ۱۰٪؛ ارسال رایگان از ۲٬۰۰٬۰۰ تومان
 *          (وگرنه ۱۵۰۰۰ تومان)؛ موجودی محصولات کم می‌شود.
 * =========================================================
 */

require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const NILA_FREE_SHIPPING = 2000000;
const NILA_SHIPPING_COST = 150000;
const NILA_COUPON_10     = 'NILA10';

function nila_digits($s) {
    $s = strtr((string)$s, '۰۱۲۳۴۵۶۷۸۹', '0123456789');
    $s = strtr($s, '٠١٢٣٤٥٦٧٨٩', '0123456789');
    return preg_replace('/\D/', '', $s);
}

function nila_fail($error, $code = 200) {
    if ($code !== 200) {
        http_response_code($code);
    }
    echo json_encode(['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'];

    /* ---------- GET: سفارش‌های کاربر ---------- */
    if ($method === 'GET') {
        $u = $_SESSION['nila_user'] ?? null;
        if (!is_array($u) || empty($u['id'])) {
            echo json_encode(['orders' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $stmt = $pdo->prepare(
            'SELECT id, order_code, status, subtotal, discount, shipping, total, city, created_at
             FROM `orders` WHERE user_id = ?
             ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([(int)$u['id']]);
        $itemsStmt = $pdo->prepare(
            'SELECT oi.product_name AS name, oi.qty, oi.price, p.image
             FROM `order_items` oi
             LEFT JOIN `products` p ON p.id = oi.product_id
             WHERE oi.order_id = ? ORDER BY oi.id'
        );
        $orders = [];
        foreach ($stmt as $o) {
            $itemsStmt->execute([(int)$o['id']]);
            $items = [];
            foreach ($itemsStmt as $it) {
                $items[] = [
                    'name'  => (string)$it['name'],
                    'qty'   => (int)$it['qty'],
                    'price' => (int)$it['price'],
                    'img'   => $it['image'] ? (string)$it['image'] : null,
                ];
            }
            $orders[] = [
                'id'       => (int)$o['id'],
                'code'     => (string)$o['order_code'],
                'status'   => (string)$o['status'],
                'subtotal' => (int)$o['subtotal'],
                'discount' => (int)$o['discount'],
                'shipping' => (int)$o['shipping'],
                'total'    => (int)$o['total'],
                'city'     => (string)($o['city'] ?: ''),
                'date'     => (string)$o['created_at'],
                'items'    => $items,
            ];
        }
        echo json_encode(['orders' => $orders], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ---------- POST: ثبت سفارش جدید ---------- */
    $in = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($in)) {
        nila_fail('bad_request', 400);
    }
    $name    = trim((string)($in['name'] ?? ''));
    $phone   = nila_digits($in['phone'] ?? '');
    $city    = trim((string)($in['city'] ?? ''));
    $zip     = nila_digits($in['zip'] ?? '');
    $address = trim((string)($in['address'] ?? ''));
    $note    = trim((string)($in['note'] ?? ''));
    $payment = (($in['payment'] ?? 'bank') === 'cod') ? 'cod' : 'bank';
    $coupon  = strtoupper(trim((string)($in['coupon_code'] ?? '')));
    $itemsIn = $in['items'] ?? [];

    if (!preg_match('/^.{3,100}$/u', $name)) { nila_fail('name_invalid'); }
    if (!preg_match('/^09\d{9}$/', $phone)) { nila_fail('phone_invalid'); }
    if (strlen($address) < 10)               { nila_fail('address_invalid'); }
    if (!is_array($itemsIn) || count($itemsIn) < 1 || count($itemsIn) > 20) { nila_fail('items_empty'); }

    /* قیمت‌ها و موجودی فقط از دیتابیس */
    $subtotal = 0;
    $items = [];
    $pStmt = $pdo->prepare(
        'SELECT id, name, price, sale_price, stock, image
         FROM `products` WHERE id = ? AND is_active = 1'
    );
    foreach ($itemsIn as $it) {
        if (!is_array($it)) { nila_fail('items_empty'); }
        $pid = (int)($it['id'] ?? 0);
        $qty = (int)($it['qty'] ?? 0);
        if ($pid < 1 || $qty < 1 || $qty > 10) { nila_fail('items_empty'); }
        $pStmt->execute([$pid]);
        $p = $pStmt->fetch();
        if (!$p) { nila_fail('item_invalid'); }
        $unit = ($p['sale_price'] !== null) ? (int)$p['sale_price'] : (int)$p['price'];
        if ((int)$p['stock'] < $qty) { nila_fail('stock_low'); }
        $subtotal += $unit * $qty;
        $items[] = ['pid' => $pid, 'qty' => $qty, 'name' => (string)$p['name'], 'unit' => $unit];
    }

    $discount = ($coupon === NILA_COUPON_10) ? (int)round($subtotal * 0.10) : 0;
    $after    = $subtotal - $discount;
    $shipping = ($after >= NILA_FREE_SHIPPING) ? 0 : NILA_SHIPPING_COST;
    $total    = $after + $shipping;

    /* کد یکتای سفارش */
    $code = '';
    $cStmt = $pdo->prepare('SELECT 1 FROM `orders` WHERE order_code = ?');
    for ($t = 0; $t < 6; $t++) {
        $code = 'NLA-' . str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $cStmt->execute([$code]);
        if (!$cStmt->fetch()) { break; }
    }

    $u = $_SESSION['nila_user'] ?? null;
    $uid = is_array($u) && isset($u['id']) ? (int)$u['id'] : null;
    $status = ($payment === 'bank') ? 'pending_payment' : 'registered';

    $pdo->beginTransaction();
    try {
        $oIns = $pdo->prepare(
            'INSERT INTO `orders`
             (`order_code`, `user_id`, `customer_name`, `customer_phone`, `city`, `zip`, `address`,
              `note`, `payment`, `subtotal`, `discount`, `shipping`, `total`, `status`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $oIns->execute([
            $code, $uid, $name, $phone,
            $city !== '' ? $city : null,
            $zip !== '' ? $zip : null,
            $address,
            $note !== '' ? $note : null,
            $payment, $subtotal, $discount, $shipping, $total, $status,
        ]);
        $oid = (int)$pdo->lastInsertId();

        $iIns   = $pdo->prepare(
            'INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `price`, `qty`)
             VALUES (?, ?, ?, ?, ?)'
        );
        $uStock = $pdo->prepare('UPDATE `products` SET stock = stock - ? WHERE id = ? AND stock >= ?');
        foreach ($items as $it) {
            $iIns->execute([$oid, $it['pid'], $it['name'], $it['unit'], $it['qty']]);
            $uStock->execute([$it['qty'], $it['pid'], $it['qty']]);
        }
        $pdo->commit();

        /* اعلان برای کاربر (فاز PHP بخش ۶) */
        if ($uid !== null) {
            $pdo->prepare('INSERT INTO `notifications` (`user_id`, `title`, `body`) VALUES (?, ?, ?)')
                ->execute([$uid, 'ثبت سفارش', 'سفارش ' . $code . ' شما با موفقیت ثبت شد؛ کارشناسان نیلا به‌زودی برای هماهنگی ارسال با شما تماس می‌گیرند.']);
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }

    echo json_encode([
        'ok'         => true,
        'order_code' => $code,
        'status'     => $status,
        'subtotal'   => $subtotal,
        'discount'   => $discount,
        'shipping'   => $shipping,
        'total'      => $total,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[nila-api-orders] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_unavailable'], JSON_UNESCAPED_UNICODE);
}
