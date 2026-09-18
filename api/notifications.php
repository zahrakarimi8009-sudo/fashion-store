<?php
declare(strict_types=1);

/**
 * =========================================================
 *  فروشگاه نیلا | NILA FASHION
 *  API: اعلان‌های کاربر
 *  ------------------------------------------------------------
 *  GET            -> اعلان‌های کاربر (تازه‌ترین اول)
 *  POST {action:'read_all'}      -> همه خوانده شد
 *  POST {action:'read', id: N}   -> یکی خوانده شد
 * =========================================================
 */

require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo = db();
    $u = $_SESSION['nila_user'] ?? null;
    if (!is_array($u) || empty($u['id'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'not_logged_in'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $uid = (int)$u['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $st = $pdo->prepare(
            'SELECT id, title, body, is_read, created_at
             FROM `notifications` WHERE user_id = ?
             ORDER BY created_at DESC, id DESC LIMIT 50'
        );
        $st->execute([$uid]);
        $out = [];
        foreach ($st as $r) {
            $out[] = [
                'id'    => (int)$r['id'],
                'title' => (string)$r['title'],
                'body'  => (string)$r['body'],
                'read'  => (bool)$r['is_read'],
                'date'  => (string)$r['created_at'],
            ];
        }
        echo json_encode(['ok' => true, 'notifications' => $out], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $in = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($in)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'bad_request'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $action = (string)($in['action'] ?? '');
    if ($action === 'read_all') {
        $pdo->prepare('UPDATE `notifications` SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$uid]);
    } elseif ($action === 'read') {
        $pdo->prepare('UPDATE `notifications` SET is_read = 1 WHERE user_id = ? AND id = ?')->execute([$uid, (int)($in['id'] ?? 0)]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'bad_action'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[nila-api-notifications] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_unavailable'], JSON_UNESCAPED_UNICODE);
}
