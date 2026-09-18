<?php
declare(strict_types=1);

/**
 * =========================================================
 *  فروشگاه نیلا | NILA FASHION
 *  API: نظرات کاربران محصولات (JSON)
 *  ------------------------------------------------------------
 *  GET  api/reviews.php?product_id=1  -> آرایه‌ی نظرات محصول
 *  POST api/reviews.php  (JSON: product_id, name, rating, body)
 *       -> ثبت نظر جدید (خریدار واقعی «خریدار تأییدشده» می‌شود)
 * =========================================================
 */

require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = db();

    /* سازگاری با دیتابیس‌های قدیمی که ستون user_id ندارند */
    $revCols = [];
    foreach ($pdo->query('SHOW COLUMNS FROM `reviews`') as $c) {
        $revCols[] = $c['Field'];
    }
    $hasUid = in_array('user_id', $revCols, true);

    if ($method === 'GET') {
        $pid = (int)($_GET['product_id'] ?? 0);
        if ($pid < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'bad_request'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $selUid = $hasUid ? ', user_id' : '';
        $stmt = $pdo->prepare(
            "SELECT id, user_name{$selUid}, rating, body, is_verified, created_at
             FROM `reviews` WHERE product_id = ?
             ORDER BY created_at DESC, id DESC"
        );
        $stmt->execute([$pid]);
        /* «خریدار تأییدشده» پویا: اگر کاربر لاگین‌شده همین محصول را سفارش داده،
           نظرهای او (و نظراتی که هنگام ثبت تأیید شده) برچسب می‌گیرند */
        $uid = null;
        $u = $_SESSION['nila_user'] ?? null;
        if (is_array($u) && isset($u['id'])) {
            $uid = (int)$u['id'];
        }
        $out = [];
        foreach ($stmt as $r) {
            $verified = (bool)$r['is_verified']
                || ($hasUid && $uid !== null && (int)$r['user_id'] === $uid);
            $out[] = [
                'id'       => (int)$r['id'],
                'name'     => (string)$r['user_name'],
                'rating'   => (int)$r['rating'],
                'body'     => (string)$r['body'],
                'verified' => $verified,
                'date'     => (string)$r['created_at'],
            ];
        }
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ---------- POST: ثبت نظر جدید ---------- */
    $in = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($in)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'bad_request'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $pid = (int)($in['product_id'] ?? 0);
    $name = trim((string)($in['name'] ?? ''));
    $rating = (int)($in['rating'] ?? 0);
    $body = trim((string)($in['body'] ?? ''));

    if ($pid < 1 || $rating < 1 || $rating > 5
        || !preg_match('/^.{2,60}$/u', $name) || !preg_match('/^.{5,1000}$/u', $body)) {
        if ($pid < 1) {
            $err = 'bad_request';
        } elseif (strlen($name) < 4) {
            $err = 'name_invalid';
        } elseif ($rating < 1 || $rating > 5) {
            $err = 'rating_invalid';
        } else {
            $err = 'body_short';
        }
        echo json_encode(['ok' => false, 'error' => $err], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $prod = $pdo->prepare('SELECT id FROM `products` WHERE id = ? AND is_active = 1');
    $prod->execute([$pid]);
    if (!$prod->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'product_notfound'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* خریدار واقعی = کاربر لاگین‌شده‌ای که قبلاً همین محصول را سفارش داده */
    $verified = 0;
    $uid = null;
    $u = $_SESSION['nila_user'] ?? null;
    if (is_array($u) && isset($u['id'])) {
        $uid = (int)$u['id'];
        $chk = $pdo->prepare(
            'SELECT 1 FROM `order_items` oi
             INNER JOIN `orders` o ON o.id = oi.order_id
             WHERE o.user_id = ? AND oi.product_id = ? LIMIT 1'
        );
        $chk->execute([$uid, $pid]);
        $verified = $chk->fetch() ? 1 : 0;
    }

    if ($hasUid) {
        $ins = $pdo->prepare(
            'INSERT INTO `reviews` (`product_id`, `user_id`, `user_name`, `rating`, `body`, `is_verified`)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$pid, $uid, $name, $rating, $body, $verified]);
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO `reviews` (`product_id`, `user_name`, `rating`, `body`, `is_verified`)
             VALUES (?, ?, ?, ?, ?)'
        );
        $ins->execute([$pid, $name, $rating, $body, $verified]);
    }
    echo json_encode([
        'ok'     => true,
        'review' => [
            'id'       => (int)$pdo->lastInsertId(),
            'name'     => $name,
            'rating'   => $rating,
            'body'     => $body,
            'verified' => (bool)$verified,
            'date'     => date('Y-m-d H:i:s'),
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[nila-api-reviews] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_unavailable'], JSON_UNESCAPED_UNICODE);
}
