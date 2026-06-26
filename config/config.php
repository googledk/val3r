<?php
// تنظیمات اصلی سایت
define('DB_HOST', 'localhost');
define('DB_NAME', 'prokem_val3r');
define('DB_USER', 'prokem_val3r');

// بعد از آپلود، رمز واقعی دیتابیس DirectAdmin را اینجا وارد کن
define('DB_PASS', '9NnLDEEkUxVCFfa6ANTJ');

define('BASE_URL', 'https://val3r.ir/');
define('SITE_NAME', 'والر | VaL3R ایران');

define('UPLOAD_PRODUCTS_DIR', __DIR__ . '/../uploads/products/');
define('UPLOAD_SLIDERS_DIR', __DIR__ . '/../uploads/sliders/');
define('UPLOAD_PRODUCTS_URL', BASE_URL . 'uploads/products/');
define('UPLOAD_SLIDERS_URL', BASE_URL . 'uploads/sliders/');

define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
