<?php
declare(strict_types=1);

/**
 * =========================================================
 *  فروشگاه نیلا | NILA FASHION
 *  API: پیام‌های پشتیبانی کاربر
 *  ------------------------------------------------------------
 *  GET  -> گفت‌وگوی کاربر با پشتیبانی (قدیمی‌ترین اول)
 *  POST {body}      -> ارسال پیام کاربر
 *  POST {action:'read'} -> علامت‌زدن همه به عنوان خوانده‌شده
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
            'SELECT id, user_id, sender_name, body, is_read, created_at
             FROM `messages` WHERE user_id = ?
             ORDER BY created_at ASC, id ASC LIMIT 100'
        );
        $st->execute([$uid]);
        $out = [];
        foreach ($st as $r) {
            $out[] = [
                'id'    => (int)$r['id'],
                'from'  => (string)$r['sender_name'],
                'mine'  => (int)$r['user_id'] === $uid && (string)$r['sender_name'] !== 'نیلا',
                'text'  => (string)$r['body'],
                'read'  => (bool)$r['is_read'],
                'date'  => (string)$r['created_at'],
            ];
        }
        echo json_encode(['ok' => true, 'messages' => $out], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $in = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($in)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'bad_request'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (($in['action'] ?? '') === 'read') {
        $pdo->prepare('UPDATE `messages` SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$uid]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $body = trim((string)($in['body'] ?? ''));
    if (!preg_match('/^.{1,1000}$/u', $body)) {
        echo json_encode(['ok' => false, 'error' => 'body_invalid'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $pdo->prepare(
        'INSERT INTO `messages` (`user_id`, `sender_name`, `body`, `is_read`) VALUES (?, ?, ?, 0)'
    )->execute([$uid, (string)$u['name'], $body]);
    echo json_encode(['ok' => true, 'message' => [
        'from' => (string)$u['name'], 'text' => $body, 'date' => date('Y-m-d H:i:s'),
    ]], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[nila-api-messages] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_unavailable'], JSON_UNESCAPED_UNICODE);
}
