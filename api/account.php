<?php
declare(strict_types=1);

/**
 * =========================================================
 *  فروشگاه نیلا | NILA FASHION
 *  API: پنل کاربری (پروفایل + تغییر رمز)
 *  ------------------------------------------------------------
 *  GET              -> پروفایل کاربر لاگین‌شده
 *  POST {action:'profile', name, phone, city, zip, address}
 *  POST {action:'password', old, new}
 * =========================================================
 */

require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function nila_digits($s) {
    $s = strtr((string)$s, '۰۱۲۴۵۶۸۹', '0123456789');
    $s = strtr($s, '٠١٢٤٥٦٨٩', '0123456789');
    return preg_replace('/\D/', '', $s);
}

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
        $st = $pdo->prepare('SELECT name, phone, city, zip_code, address FROM `users` WHERE id = ?');
        $st->execute([$uid]);
        $r = $st->fetch();
        if (!$r) {
            echo json_encode(['ok' => false, 'error' => 'user_notfound'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode([
            'ok' => true,
            'profile' => [
                'name'    => (string)$r['name'],
                'phone'   => (string)$r['phone'],
                'city'    => (string)($r['city'] ?: ''),
                'zip'     => (string)($r['zip_code'] ?: ''),
                'address' => (string)($r['address'] ?: ''),
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $in = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($in)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'bad_request'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $action = (string)($in['action'] ?? '');

    if ($action === 'profile') {
        $name    = trim((string)($in['name'] ?? ''));
        $phone   = nila_digits($in['phone'] ?? '');
        $city    = trim((string)($in['city'] ?? ''));
        $zip     = nila_digits($in['zip'] ?? '');
        $address = trim((string)($in['address'] ?? ''));
        if (!preg_match('/^.{3,100}$/u', $name) || !preg_match('/^09\d{9}$/', $phone)) {
            echo json_encode(['ok' => false, 'error' => 'invalid'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $chk = $pdo->prepare('SELECT 1 FROM `users` WHERE phone = ? AND id <> ?');
        $chk->execute([$phone, $uid]);
        if ($chk->fetch()) {
            echo json_encode(['ok' => false, 'error' => 'phone_exists'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $pdo->prepare(
            'UPDATE `users` SET name = ?, phone = ?, city = ?, zip_code = ?, address = ? WHERE id = ?'
        )->execute([$name, $phone, $city !== '' ? $city : null, $zip !== '' ? $zip : null, $address !== '' ? $address : null, $uid]);
        $_SESSION['nila_user']['name'] = $name;
        $_SESSION['nila_user']['phone'] = $phone;
        echo json_encode([
            'ok' => true,
            'profile' => [
                'name' => $name, 'phone' => $phone,
                'city' => $city, 'zip' => $zip, 'address' => $address,
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'password') {
        $old = (string)($in['old'] ?? '');
        $new = (string)($in['new'] ?? '');
        if (strlen($new) < 6) {
            echo json_encode(['ok' => false, 'error' => 'pass_short'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $st = $pdo->prepare('SELECT password_hash FROM `users` WHERE id = ?');
        $st->execute([$uid]);
        $row = $st->fetch();
        if (!$row || !verify_password($old, (string)$row['password_hash'])) {
            echo json_encode(['ok' => false, 'error' => 'old_wrong'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $pdo->prepare('UPDATE `users` SET password_hash = ? WHERE id = ?')
            ->execute([make_password_hash($new), $uid]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'bad_action'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[nila-api-account] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_unavailable'], JSON_UNESCAPED_UNICODE);
}
