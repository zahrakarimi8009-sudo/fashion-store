<?php
declare(strict_types=1);

/**
 * =========================================================
 *  فروشگاه نیلا | NILA FASHION
 *  API: لیست محصولات فعال از دیتابیس (JSON)
 *  ------------------------------------------------------------
 *  آدرس: api/products.php
 *  خروجی: آرایه‌ی JSON با ساختار دقیق فرانت‌اند (products.js)
 *  اگر دیتابیس در دسترس نباشد، فرانت‌اند خودکار
 *  به داده‌های نمایشی برمی‌گردد (بدون نمایش خطا).
 * =========================================================
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo = db();
    $stmt = $pdo->query(
        'SELECT p.id, c.slug AS category, p.name, p.short_desc, p.description,
                p.price, p.sale_price, p.image, p.image2, p.sizes, p.colors,
                p.is_new, p.is_popular, p.rating, p.sales_count, p.code, p.stock,
                p.specs, p.created_at
         FROM `products` p
         LEFT JOIN `categories` c ON c.id = p.category_id
         WHERE p.is_active = 1
         ORDER BY p.id'
    );

    $out = [];
    foreach ($stmt as $r) {
        $colors = $r['colors'] ? json_decode((string)$r['colors'], true) : null;
        if (!is_array($colors) || !$colors) {
            $colors = [['name' => 'صورتی', 'hex' => '#ec4d84']];
        }
        $specs = $r['specs'] ? json_decode((string)$r['specs'], true) : null;
        if (!is_array($specs)) {
            $specs = [['جنس', 'پارچه درجه یک'], ['کشور تولید', 'ایران']];
        }
        $sizes = array_values(array_filter(array_map('trim', explode(',', (string)($r['sizes'] ?? '')))));
        $gallery = [(string)$r['image']];
        if ($r['image2']) {
            $gallery[] = (string)$r['image2'];
        }
        $out[] = [
            'id'        => (int)$r['id'],
            'name'      => (string)$r['name'],
            'short'     => (string)($r['short_desc'] ?? ''),
            'desc'      => (string)($r['description'] ?? ''),
            'category'  => (string)($r['category'] ?: 'dress'),
            'price'     => (int)$r['price'],
            'salePrice' => ($r['sale_price'] !== null) ? (int)$r['sale_price'] : null,
            'img'       => (string)$r['image'],
            'img2'      => $r['image2'] ? (string)$r['image2'] : null,
            'gallery'   => $gallery,
            'sizes'     => $sizes ? $sizes : ['یک‌سایز'],
            'colors'    => $colors,
            'isNew'     => (bool)$r['is_new'],
            'isPopular' => (bool)$r['is_popular'],
            'rating'    => (float)$r['rating'],
            'sales'     => (int)$r['sales_count'],
            'code'      => (string)($r['code'] ?? ''),
            'stock'     => (int)$r['stock'],
            'specs'     => $specs,
            /* برای مرتب‌سازی «جدیدترین‌ها» کافی است (مقایسه‌ی رشته‌ای YYYY-MM-DD) */
            'date'      => substr((string)$r['created_at'], 0, 10),
        ];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[nila-api-products] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'db-unavailable'], JSON_UNESCAPED_UNICODE);
}
