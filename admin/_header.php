<?php $adminTitle = $adminTitle ?? 'پنل مدیریت والر'; ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($adminTitle) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin.css?v=8.1.0">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= BASE_URL ?>admin/dashboard.php">
            <strong>VaL3R</strong>
            <span>Admin Panel</span>
        </a>
        <nav>
            <a href="<?= BASE_URL ?>admin/dashboard.php">داشبورد</a>
            <a href="<?= BASE_URL ?>admin/products.php">محصولات</a>
            <a href="<?= BASE_URL ?>admin/product_form.php">افزودن محصول</a>
            <a href="<?= BASE_URL ?>admin/import_digikala.php">ایمپورت از دیجی‌کالا</a>
            <a href="<?= BASE_URL ?>admin/categories.php">دسته‌بندی‌ها</a>
            <a href="<?= BASE_URL ?>admin/sliders.php">اسلایدر</a>
            <a href="<?= BASE_URL ?>admin/orders.php">سفارش‌ها</a>
            <a href="<?= BASE_URL ?>admin/pages.php">صفحات</a>
            <a href="<?= BASE_URL ?>admin/contact_messages.php">پیام‌های تماس</a>
            <a href="<?= BASE_URL ?>admin/bale_test.php">تست بله</a>
            <a href="<?= BASE_URL ?>admin/settings.php">تنظیمات</a>
            <a href="<?= BASE_URL ?>" target="_blank">مشاهده سایت</a>
            <a href="<?= BASE_URL ?>admin/logout.php">خروج</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <span>مدیریت وب‌سایت رسمی</span>
                <strong>VaL3R Iran</strong>
            </div>
            <a href="<?= BASE_URL ?>" target="_blank">باز کردن سایت</a>
        </div>
