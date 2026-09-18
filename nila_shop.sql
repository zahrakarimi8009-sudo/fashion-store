-- =========================================================
--  فروشگاه نیلا | NILA FASHION
--  فایل کامل پایگاه داده (آماده‌ی ایمپورت در phpMyAdmin)
--  ------------------------------------------------------------
--  روش ایمپورت:
--   1) وارد phpMyAdmin شوید
--   2) برگه‌ی «Import» را باز کنید
--   3) همین فایل (nila_shop.sql) را انتخاب و «Execute» بزنید
--  ------------------------------------------------------------
--  پس از ایمپورت، اطلاعات ورود پنل مدیریت:
--   admin@nila.shop  /  nila2026
--  رمز نمایشی مشتریان: 123456
--  ------------------------------------------------------------
--  نام کاربری/رمز هاستینگ خود را در config/db.php ویرایش کنید.
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `nila_shop`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_persian_ci;
USE `nila_shop`;

-- ---------------------------------------------------------
-- ۱) دسته‌بندی‌ها
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`  VARCHAR(50)  NOT NULL,
  `name`  VARCHAR(100) NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `categories` (`id`, `slug`, `name`, `image`) VALUES
(1, 'dress',   'لباس مجلسی',      'images/cat-dress.jpg'),
(2, 'manteau', 'مانتو',           'images/cat-manteau.jpg'),
(3, 'blouse',  'شومیز و بلوز',    'images/cat-bluze.jpg'),
(4, 'pants',   'شلوار',           'images/cat-pants.jpg'),
(5, 'skirt',   'دامن',            'images/cat-skirt.jpg'),
(6, 'bag',     'کیف و اکسسوری',   'images/cat-bag.jpg');

-- ---------------------------------------------------------
-- ۲) سطوح دسترسی مدیران
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `admin_levels`;
CREATE TABLE `admin_levels` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50)  NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_level_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `admin_levels` (`id`, `name`) VALUES
(1, 'مدیر کل'),
(2, 'مدیر فروشگاه'),
(3, 'پشتیبانی'),
(4, 'مشتری');

-- ---------------------------------------------------------
-- ۳) کاربران
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100) NOT NULL,
  `phone`         VARCHAR(20)  NOT NULL,
  `email`         VARCHAR(150) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `city`          VARCHAR(100) DEFAULT NULL,
  `zip_code`      VARCHAR(20)  DEFAULT NULL,
  `address`       VARCHAR(255) DEFAULT NULL,
  `role`          VARCHAR(20)  NOT NULL DEFAULT 'customer',
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_phone` (`phone`),
  KEY `idx_user_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- رمز همه‌ی کاربران نمونه: 123456
INSERT INTO `users` (`id`, `name`, `phone`, `email`, `password_hash`, `city`, `zip_code`, `address`, `role`, `created_at`) VALUES
(1, 'مریم احمدی',   '09123456789', 'maryam@example.com',   '3f9b64ca6a04e96baaf97d44e37d728a9550560a638fd3a8ba2b390a580f8f12', 'تهران', '14815', 'تهران، تهران، خیابان ولیعصر، پلاک ۱۲۰، طبقه ۶', 'customer', '2026-08-20 10:12:00'),
(2, 'زهرا کریمی',   '09121112233', 'zahra@example.com',    '3f9b64ca6a04e96baaf97d44e37d728a9550560a638fd3a8ba2b390a580f8f12', 'اصفهان', NULL, 'اصفهان، خیابان چهارباغ بالا، پلاک ۴۵', 'customer', '2026-07-02 18:40:00'),
(3, 'سارا محمدی',   '09129998877', 'sara@example.com',     '3f9b64ca6a04e96baaf97d44e37d728a9550560a638fd3a8ba2b390a580f8f12', 'شیراز', NULL, 'شیراز، بلوار چمران، کوچه‌ی ناهید، پلاک ۸', 'customer', '2026-06-30 09:05:00'),
(4, 'نگار موسوی',   '09125554433', 'negar@example.com',    '3f9b64ca6a04e96baaf97d44e37d728a9550560a638fd3a8ba2b390a580f8f12', 'تبریز', NULL, 'تبریز، خیابان مردان، پلاک ۲۱۰', 'customer', '2026-05-08 14:22:00'),
(5, 'هدیه شریفی',   '09127776655', 'hedyeh@example.com',   '3f9b64ca6a04e96baaf97d44e37d728a9550560a638fd3a8ba2b390a580f8f12', 'مشهد', NULL, 'مشهد، بلوار وکیل‌آباد، پلاک ۷۷', 'customer', '2026-03-25 11:55:00');

-- ---------------------------------------------------------
-- ۴) مدیران
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100) NOT NULL,
  `phone`         VARCHAR(20)  NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `level_id`      INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_email` (`email`),
  KEY `idx_admin_level` (`level_id`),
  CONSTRAINT `fk_admin_level` FOREIGN KEY (`level_id`) REFERENCES `admin_levels` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- رمز ورود پنل مدیریت (admin@nila.shop): nila2026 — بقیه: 123456
INSERT INTO `admins` (`id`, `name`, `phone`, `email`, `password_hash`, `level_id`, `created_at`) VALUES
(1, 'علی رضایی',    '09120000001', 'admin@nila.shop',   '5c3fb9bad80eca9b160ac18144d46fea05b17e06fcb931ce21c043660a0d7583', 1, '2026-01-02 09:00:00'),
(2, 'فاطمه قاسمی',  '09120000002', 'manager@nila.shop', '3f9b64ca6a04e96baaf97d44e37d728a9550560a638fd3a8ba2b390a580f8f12', 2, '2026-02-10 12:30:00'),
(3, 'مهدی توکلی',   '09120000003', 'support@nila.shop', '3f9b64ca6a04e96baaf97d44e37d728a9550560a638fd3a8ba2b390a580f8f12', 3, '2026-03-18 08:15:00');

-- ---------------------------------------------------------
-- ۵) محصولات
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name`        VARCHAR(150) NOT NULL,
  `short_desc`  VARCHAR(255) DEFAULT NULL,
  `description` TEXT         DEFAULT NULL,
  `price`       INT UNSIGNED NOT NULL,
  `sale_price`  INT UNSIGNED DEFAULT NULL,
  `image`       VARCHAR(255) NOT NULL,
  `image2`      VARCHAR(255) DEFAULT NULL,
  `sizes`       VARCHAR(100) DEFAULT NULL,
  `colors`      TEXT         DEFAULT NULL,
  `specs`       TEXT         DEFAULT NULL,
  `is_new`      TINYINT(1)   NOT NULL DEFAULT 0,
  `is_popular`  TINYINT(1)   NOT NULL DEFAULT 0,
  `rating`      DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  `sales_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `code`        VARCHAR(20)  DEFAULT NULL,
  `stock`       INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prod_cat` (`category_id`),
  KEY `idx_prod_new` (`is_new`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `products`
(`id`, `category_id`, `name`, `short_desc`, `description`, `price`, `sale_price`, `image`, `image2`, `sizes`, `colors`, `specs`, `is_new`, `is_popular`, `rating`, `sales_count`, `code`, `stock`, `created_at`) VALUES
(1, 1, 'لباس مجلسی گلدار «آرو»',
 'لباس مجلسی گلدار با دوخت A-line و پارچه ویسکوز درجه یک؛ مناسب مهمانی‌های روز و شب.',
 'لباس مجلسی گلدار «آرو» یکی از پرطرفدارترین طرح‌های فصل نیلا است. بافت نرم ویسکوز گلدار و دوخت A-line، فرمی دلنشین و راحت به این لباس داده است که هم برای مهمانی‌های رسمی و هم قرارهای نیمه‌رسمی انتخابی درجه یک است. جیب‌های کناری، زیپ پنهان پشت و آستر نخی، جزئیاتی هستند که «آرو» را از یک لباس زیبا، به یک لباس کاربردی و شیک تبدیل کرده‌اند.',
 3850000, 2990000, 'images/p1.jpg', 'images/cat-dress.jpg', 'S,M,L,XL', '[{"name":"سرمه‌ای","hex":"#3b4a6b"},{"name":"زرشکی","hex":"#8e2f3c"}]', '[["جنس پارچه","ویسکوز گلدار ۹۵٪ + الاستان ۵٪"],["آستر","نخی تمام‌قد"],["شست‌وشو","دستی با آب ۳۰ درجه"],["کشور تولید","ایران"]]', 1, 1, 4.8, 214, 'LYA-1001', 14, '2026-08-20 10:00:00'),
(2, 2, 'مانتو مخمل مجلسی «شب»',
 'مانتو بلند مخمل با یقه کلاسیک و درخشش ملایم؛ انتخابی ویژه برای شب‌های مهمانی.',
 'مانتو مخمل «شب» برای خانم‌هایی طراحی شده که در مهمانی‌ها ظاهر خاص و در عین حال رسمی می‌خواهند. مخمل باکیفیت این مانتو نور را به‌صورت ملایمی بازتاب می‌دهد و حس لوکس بودن را بدون اغراق منتقل می‌کند. کتف‌های تنظیم‌شده، دکمه‌های مخملی یکدست و جیب‌های کار، جزئیاتی هستند که این مانتو را هم مجلسی و هم روزمره کرده‌اند.',
 4200000, 3490000, 'images/p2.jpg', 'images/cat-manteau.jpg', 'S,M,L,XL', '[{"name":"سرمه‌ای","hex":"#33415c"},{"name":"مشکی","hex":"#23211f"}]', '[["جنس پارچه","مخمل ویسکوز درجه یک"],["آستر","ساتن"],["طول","بلند (زیر زانو تا ساق)"],["کشور تولید","ایران"]]', 0, 1, 4.9, 186, 'LYA-1002', 8, '2026-07-15 10:00:00'),
(3, 3, 'بلوز ساتن کرم «بهار»',
 'بلوز ساتن با دراپه‌ی نرم و دکمه‌های ظریف؛ پایه‌ی استایل‌های مجلسی و اداری.',
 'بلوز ساتن «بهار» به دلیل پارچه ابریشم‌گونه‌اش که نرمی و درخشش ظریفی دارد، یکی از پرفروش‌ترین بلوزهای نیلا است. این بلوز با استایل‌های اداری، دامن‌های رسمی و حتی شلوارهای روزمره ست می‌شود و یک انتخاب همه‌فن‌حریف کمد شما خواهد بود. دوخت تمیز درزها و دکمه‌های یکدست، نشانه‌ی دقت نیلا در جزئیات است.',
 1450000, 1190000, 'images/p3.jpg', 'images/cat-bluze.jpg', 'S,M,L,XL', '[{"name":"کرم","hex":"#e8dcc8"},{"name":"سفید","hex":"#f5f2ea"}]', '[["جنس پارچه","ساتن ویسکوز"],["آستین","بلند با مچ دکمه‌ای"],["شست‌وشو","دستی با آب ۳۰ درجه"],["کشور تولید","ایران"]]', 1, 1, 4.6, 98, 'LYA-1003', 21, '2026-08-18 10:00:00'),
(4, 4, 'شلوار پالazzo بژ «ساحل»',
 'شلوار پهن با کمر بلند و افتادگی عالی؛ راحتی و شیک‌بودن در یک شلوار.',
 'شلوار پالazzo «ساحل» با کمر بلند و پارچه‌ای که افتادگی زیبایی دارد، قد شما را بلندتر نشان می‌دهد. این شلوار با بلوز، شومیز و مانتوهای کوتاه ست می‌شود و برای محیط کار و قرارهای دوستانه گزینه‌ی راحتی است. جیب‌های کناری و کمربند یکپارچه، جزئیات کاربردی این مدل هستند.',
 1890000, NULL, 'images/p4.jpg', 'images/p4b.jpg', 'S,M,L,XL', '[{"name":"بژ","hex":"#cbb69b"},{"name":"کرم","hex":"#e5dccd"}]', '[["جنس پارچه","لینن ویسکوز"],["کمر","بلند، جیب‌دار"],["شست‌وشو","ماشین با دمای ۳۰ درجه"],["کشور تولید","ایران"]]', 1, 0, 4.5, 76, 'LYA-1004', 17, '2026-08-10 10:00:00'),
(5, 5, 'دامن پلیسه کاراملی «گلنار»',
 'دامن پلیسه‌ی میدی با چین‌های منظم و پارچه‌ی نرم؛ مناسب استایل‌های کلاسیک.',
 'دامن پلیسه «گلنار» با چین‌های منظم و پارچه‌ای که در حرکت، موج ملایمی می‌گیرد، انتخابی کلاسیک و همیشه‌به‌روز است. این دامن با بلوز، شومیز و حتی تیشرت‌های ساده ست می‌شود و برای محیط کار و قرارهای دوستانه هر دو مناسب است. کمربند داخلی مخفی و زیپ کناری، دوخت این دامن را تمیز و مرتب کرده است.',
 1680000, 1350000, 'images/p5.jpg', 'images/p5b.jpg', 'S,M,L,XL', '[{"name":"کارامل","hex":"#b98a5e"},{"name":"مشکی","hex":"#23211f"}]', '[["جنس پارچه","تنت ویسکوز"],["ارتفاع","میدی (زیر زانو)"],["شست‌وشو","دستی با آب ۳۰ درجه"],["کشور تولید","ایران"]]', 0, 1, 4.7, 152, 'LYA-1005', 4, '2026-06-30 10:00:00'),
(6, 6, 'کیف چرم دست‌دوز «مس»',
 'کیف دستی چرم طبیعی با فرم ساختاری و یراق‌کاری طلایی؛ ساخته‌شده با دست.',
 'کیف «مس» از چرم طبیعی گاوی با فرم ساختاری و یراق‌کاری طلایی ساخته شده است. این کیف یک طرح محدود است و هر عدد آن به‌صورت دست‌دوز تولید می‌شود؛ به همین دلیل ممکن است تفاوت‌های ظریفی بین هر عدد وجود داشته باشد که از زیبایی این کار دستی کم نمی‌کند. داخل کیف، یک جیب مخزنی و یک جیب زیپ‌دار دارد.',
 2750000, NULL, 'images/p6.jpg', 'images/mega.jpg', 'یک‌سایز', '[{"name":"صحفی","hex":"#e3d3c3"}]', '[["جنس","چرم طبیعی گاوی"],["ابعاد","۲۸×۲۰×۱۰ سانتی‌متر"],["ظرفیت","A4، کیف پول و لوازم شخصی"],["نحوه ساخت","دست‌دوز"]]', 1, 1, 4.9, 64, 'LYA-1006', 3, '2026-08-15 10:00:00'),
(7, 2, 'مانتو پاییزه کرم «پاییز»',
 'مانتو بلند پشمی با کمربند و دوخت کلاسیک؛ همراه شما در هوای خنک.',
 'مانتو «پاییز» با پارچه پشمی سنگین و کمربند یکپارچه، هم گرم است و هم فرم زیبایی به تن می‌دهد. کتف‌های محکم و آستر کامل، این مانتو را برای فصل سرد آماده می‌کند. رنگ کرم آن با شلوار جین، دامن‌های تیره و بلوزهای ساده به‌خوبی ست می‌شود و می‌تواند ستون اصلی کمد پاییزی شما باشد.',
 3900000, 3150000, 'images/p7.jpg', 'images/cat-manteau.jpg', 'S,M,L,XL', '[{"name":"کرم","hex":"#ddd2c2"},{"name":"زغالی","hex":"#4a4540"}]', '[["جنس پارچه","پشم ویسکوز ۷۰٪ + پلی‌استر ۳۰٪"],["آستر","پلی‌استر"],["طول","بلند با کمربند"],["کشور تولید","ایران"]]', 0, 1, 4.8, 143, 'LYA-1007', 12, '2026-07-20 10:00:00'),
(8, 3, 'شومیز لینن سفید «نیلوفر»',
 'شومیز لینن با فرم ریلکس و بافت طبیعی؛ خنک، راحت و همه‌فن‌حریف.',
 'شومیز لینن «نیلوفر» با پارچه‌ی لینن-ویسکوز خنک و بافت طبیعی، انتخابی ایده‌آل برای روزهای گرم است. فرم ریلکس و آستین‌های تاخورده، استایلی بی‌دغدغه و در عین حال مرتب به شما می‌دهد. این شومیز با شلوار پالazzo، دامن میدی و حتی جین‌های راست‌پی، ست‌های زیبایی می‌سازد.',
 1280000, NULL, 'images/p8.jpg', 'images/p3.jpg', 'S,M,L,XL', '[{"name":"سفید","hex":"#f4f1ea"},{"name":"آسمانی","hex":"#cfdbe4"}]', '[["جنس پارچه","لینن ۵۵٪ + ویسکوز ۴۵٪"],["فرم","ریلکس (آزاد)"],["شست‌وشو","دستی با آب ۳۰ درجه"],["کشور تولید","ایران"]]', 1, 0, 4.4, 110, 'LYA-1008', 26, '2026-08-05 10:00:00');

-- ---------------------------------------------------------
-- ۶) سفارش‌ها
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code`     VARCHAR(20)  NOT NULL,
  `user_id`        INT UNSIGNED DEFAULT NULL,
  `customer_name`  VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(20)  NOT NULL,
  `city`           VARCHAR(100) DEFAULT NULL,
  `address`        VARCHAR(255) DEFAULT NULL,
  `zip`          VARCHAR(20)  DEFAULT NULL,
  `note`         VARCHAR(255) DEFAULT NULL,
  `payment`      VARCHAR(20)  NOT NULL DEFAULT 'bank',
  `subtotal`       INT UNSIGNED NOT NULL DEFAULT 0,
  `discount`       INT UNSIGNED NOT NULL DEFAULT 0,
  `shipping`       INT UNSIGNED NOT NULL DEFAULT 0,
  `total`          INT UNSIGNED NOT NULL DEFAULT 0,
  `status`         VARCHAR(20)  NOT NULL DEFAULT 'registered',
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_code` (`order_code`),
  KEY `idx_order_user` (`user_id`),
  KEY `idx_order_status` (`status`),
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- وضعیت‌ها: registered | shipped | delivered | cancelled
INSERT INTO `orders`
(`id`, `order_code`, `user_id`, `customer_name`, `customer_phone`, `city`, `address`, `subtotal`, `discount`, `shipping`, `total`, `status`, `created_at`) VALUES
(1, 'NLA-1042', 1, 'مریم احمدی',  '09123456789', 'تهران', 'تهران، تهران، خیابان ولیعصر، پلاک ۱۲۰، طبقه ۶', 6895000, 0, 0, 6895000, 'registered', '2026-08-08 10:20:00'),
(2, 'NLA-1041', 2, 'زهرا کریمی',  '09121112233', 'اصفهان', 'اصفهان، خیابان چهارباغ بالا، پلاک ۴۵',         2850000, 0, 0,      2850000, 'shipped',    '2026-08-06 16:45:00'),
(3, 'NLA-1038', 3, 'سارا محمدی',  '09129998877', 'شیراز', 'شیراز، بلوار چمران، کوچه‌ی ناهید، پلاک ۸',         1500000, 0, 150000, 1650000, 'delivered',  '2026-08-01 12:10:00'),
(4, 'NLA-1035', 4, 'نگار موسوی',  '09125554433', 'تبریز', 'تبریز، خیابان مردان، پلاک ۲۱۰',                   7470000, 0, 0,      7470000, 'delivered',  '2026-07-28 09:35:00'),
(5, 'NLA-1031', 5, 'هدیه شریفی',  '09127776655', 'مشهد', 'مشهد، بلوار وکیل‌آباد، پلاک ۷۷',                   840000,  0, 150000,  990000, 'cancelled',  '2026-07-20 19:00:00');

-- ---------------------------------------------------------
-- ۷) اقلام سفارش (قیمت لحظه‌ی خرید)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`     INT UNSIGNED NOT NULL,
  `product_id`   INT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `price`        INT UNSIGNED NOT NULL,
  `qty`          INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_item_order` (`order_id`),
  CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_prod`  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `price`, `qty`) VALUES
(1, 1, 'لباس مجلسی گلدار «آرو»',     2850000, 1),
(1, 7, 'مانتو پاییزه کرم «پاییز»',    4045000, 1),
(2, 1, 'لباس مجلسی گلدار «آرو»',     2850000, 1),
(3, 3, 'بلوز ساتن کرم «بهار»',        1500000, 1),
(4, 2, 'مانتو مخمل مجلسی «شب»',      3735000, 2),
(5, 8, 'شومیز لینن سفید «نیلوفر»',    840000,  1);

-- ---------------------------------------------------------
-- ۸) پیام‌های پشتیبانی
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `sender_name` VARCHAR(100) NOT NULL,
  `body`       TEXT         NOT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_msg_user` (`user_id`),
  CONSTRAINT `fk_msg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `messages` (`user_id`, `sender_name`, `body`, `is_read`, `created_at`) VALUES
(1, 'مریم احمدی',  'سلام، سفارشم کی ارسال می‌شود؟',                    0, '2026-09-13 10:20:00'),
(2, 'زهرا کریمی',  'آیا امکان تعویض سایز وجود دارد؟',                 0, '2026-09-14 16:45:00'),
(3, 'سارا محمدی',  'ممنون از پیگیری؛ کد رهگیری گرفتم.',               1, '2026-09-12 11:05:00');

-- ---------------------------------------------------------
-- ۹) اعلان‌ها
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `title`      VARCHAR(150) NOT NULL,
  `body`       VARCHAR(255) NOT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `notifications` (`user_id`, `title`, `body`, `is_read`, `created_at`) VALUES
(1, 'سفارش شما ارسال شد',  'سفارش NLA-1041 توسط پست پیشتاز ارسال گردید.',           0, '2026-09-14 09:00:00'),
(1, 'تخفیف ویژه',          'کد تخفیف NILA10 تا پایان هفته فعال است.',                0, '2026-09-12 10:00:00'),
(1, 'محصول جدید',          'کالکشن پاییزه‌ی نیلا رونمایی شد.',                         1, '2026-09-08 10:00:00'),
(1, 'کاهش قیمت',           '«لباس مجلسی گلدار آرو» تخفیف شده است.',                  1, '2026-09-01 10:00:00');

-- ---------------------------------------------------------
-- ۱۰) نظرات کاربران
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `user_name`  VARCHAR(100) NOT NULL,
  `rating`     TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `body`       TEXT         NOT NULL,
  `is_verified` TINYINT(1)  NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rev_prod` (`product_id`),
  KEY `idx_rev_user` (`user_id`),
  CONSTRAINT `fk_rev_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `reviews` (`product_id`, `user_id`, `user_name`, `rating`, `body`, `is_verified`, `created_at`) VALUES
(1, 1, 'مریم احمدی',   5, 'دوختش واقعاً تمیزه و پارچه‌ش هم از اون جنس‌هاییه که بعد از شست‌وشو همون‌طور می‌مونه. سایزش دقیقاً طبق جدول بود. برای مهمانی گرفتم و خیلی‌ها پرسیدن از کجا خریدم.', 1, '2026-09-07 10:00:00'),
(1, 3, 'سارا محمدی',   5, 'لباسش از عکس‌ها هم بهتره. فرم A-line خیلی عالی می‌شینه و زیپ پنهانش هم تمیز کار شده. ارسالش هم سریع‌تر از چیزی بود که فکر می‌کردم.', 1, '2026-09-09 10:00:00'),
(1, NULL, 'نگار حسینی',   4, 'خود لباس عالیه، فقط رنگ سرمه‌ای کمی تیره‌تر از عکس به نظر می‌رسه. به‌هرحال با این قیمت، کیفیتش واقعاً خوبه.', 0, '2026-09-15 10:00:00'),
(2, NULL, 'الهام کریمی',  5, 'مخملش فوق‌العاده‌ست؛ هم سنگینی مناسبی داره و هم درخشش ملایمی. برای مهمانی گرفتم و خیلی‌ها تعریف کردن.', 1, '2026-08-25 10:00:00'),
(3, 3, 'سارا محمدی',   5, 'ساتنش نرمه و شفاف نیست؛ همون‌قدر که باید باشه. فرم یقه و مچ‌های دکمه‌ای‌ش خیلی شیک شده. برای استایل روزمره‌ی کاری عالیه.', 1, '2026-09-05 10:00:00'),
(4, 1, 'مریم احمدی',   5, 'رنگ بژش خیلی مجلسی و راحت ست می‌شود. کمرش بلند و راحت است و جیب‌هایش هم کاربردی‌اند. سایزش دقیق است؛ همان سایز معمولی‌تان را بگیرید.', 1, '2026-09-10 10:00:00'),
(5, NULL, 'پارسا نادری',  5, 'برای مادرم خریدم. چین‌های دامن کاملاً منظم دوخته شده و پارچه‌ش هم نه خیلی نازکه و نه سنگین. سایزگیری‌اش هم درست بود.', 1, '2026-08-12 10:00:00'),
(6, NULL, 'شیرین رستگار', 5, 'کیف دست‌دوز، دقیقاً همون‌جوری که توی عکس‌ها دیده بودم. جوش‌ها یکنواخن و یراق‌های طلایش باکیفیت. بسته‌بندی هم عالی بود.', 1, '2026-08-30 10:00:00'),
(7, 1, 'مریم احمدی',   5, 'پارچه‌اش هم وزن و هم ضدچروکِ خوب است و کمربندش کل استایل را جمع می‌کند. با آن به مهمانی رفتم و خیلی تعریف شد. برای پاییز دقیقاً همین می‌خواستم.', 1, '2026-08-15 10:00:00'),
(8, 5, 'هدیه شریفی',   4, 'لیننش نرم و خنکه، برای تابستان عالی است. سفیدش کمی روشن است پس با دست و آب سرد شست‌وشوش دادم. وگرنه فرم آزادش خیلی راحت و قشنگ است.', 1, '2026-08-18 10:00:00');

-- وصل‌کردن نظرات موجود به حساب کاربری بر اساس نام (نمایش «خریدار تأییدشده» پویا می‌شود)
UPDATE `reviews` r INNER JOIN `users` u ON u.`name` = r.`user_name` SET r.`user_id` = u.`id` WHERE r.`user_id` IS NULL;

-- ---------------------------------------------------------
-- ۱۱) تنظیمات سایت
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `skey`    VARCHAR(50)  NOT NULL,
  `svalue`  VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

INSERT INTO `settings` (`skey`, `svalue`) VALUES
('site_name',        'نیلا | NILA FASHION'),
('site_phone',       '021-91000000'),
('support_email',    'hello@nila.shop'),
('site_address',     'تهران، خیابان ولیعصر، برج آفتاب، طبقه ۶'),
('shipping_cost',    '150000'),
('free_shipping_min','2000000'),
('coupon_code',      'NILA10'),
('coupon_percent',   '10');

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
--  پایان فایل | ۱۱ جدول + داده‌های نمونه فروشگاه نیلا
-- =========================================================
