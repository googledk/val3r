<?php
$pageTitle = $pageTitle ?? SITE_NAME;
$headerCategories = [];
try {
    if (isset($pdo)) {
        $headerCategories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 12")->fetchAll();
    }
} catch (Throwable $e) {
    $headerCategories = [];
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="وب‌سایت رسمی VaL3R ایران؛ فروش محصولات لوازم شخصی برقی والر.">
    <meta name="theme-color" content="#ffffff">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css?v=5.1.0">
</head>
<body>
<header class="v5-header" data-header>
    <div class="container v5-header-inner">
        <a class="v5-brand" href="<?= BASE_URL ?>">
            <strong>VaL3R</strong>
            <span>Iran Official</span>
        </a>

        <button class="v5-menu-btn" type="button" data-menu-toggle aria-label="باز کردن منو">
            <span></span><span></span><span></span>
        </button>

        <nav class="v5-nav" data-menu>
            <a href="<?= BASE_URL ?>">خانه</a>
            <div class="v5-mega-wrap">
                <a href="<?= BASE_URL ?>?page=products">محصولات</a>
                <div class="v5-mega">
                    <div class="v5-mega-title">
                        <strong>دسته‌بندی محصولات</strong>
                        <span>انتخاب سریع محصولات VaL3R</span>
                    </div>
                    <div class="v5-mega-grid">
                        <a href="<?= BASE_URL ?>?page=products">همه محصولات</a>
                        <?php foreach ($headerCategories as $cat): ?>
                            <a href="<?= BASE_URL ?>?page=products&category=<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <a href="<?= BASE_URL ?>?page=about">درباره</a>
            <a href="<?= is_logged_in() ? BASE_URL . '?page=account&section=track' : BASE_URL . '?page=track' ?>">پیگیری سفارش</a>
            <a href="<?= BASE_URL ?>?page=contact">تماس</a>
        </nav>

        <div class="v5-actions">
            <div class="v6-user-menu-wrap">
                <a class="v5-icon-link" href="<?= is_logged_in() ? BASE_URL . '?page=account' : BASE_URL . '?page=login' ?>" aria-label="حساب کاربری">
                    <svg viewBox="0 0 24 24" width="19" height="19"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor"/></svg>
                </a>

                <div class="v6-user-dropdown">
                    <?php if (is_logged_in()): ?>
                        <div class="v6-user-dropdown-head">
                            <strong>حساب کاربری</strong>
                            <span><?= e(current_user_mobile()) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>?page=account">داشبورد من</a>
                        <a href="<?= BASE_URL ?>?page=account&section=orders">سفارش‌های من</a>
                        <a href="<?= BASE_URL ?>?page=account&section=profile">اطلاعات حساب و آدرس</a>
                        <a href="<?= BASE_URL ?>?page=account&section=track">پیگیری سفارش</a>
                        <a class="danger" href="<?= BASE_URL ?>?page=logout">خروج</a>
                    <?php else: ?>
                        <div class="v6-user-dropdown-head">
                            <strong>ورود به حساب</strong>
                            <span>با شماره موبایل وارد شوید</span>
                        </div>
                        <a href="<?= BASE_URL ?>?page=login">ورود / ثبت‌نام</a>
                        <a href="<?= BASE_URL ?>?page=track">پیگیری سفارش</a>
                    <?php endif; ?>
                </div>
            </div>
            <a class="v5-cart-link" href="<?= BASE_URL ?>?page=cart">
                <span>سبد خرید</span>
                <b><?= cart_count() ?></b>
            </a>
        </div>
    </div>
</header>
<main class="v5-main">
