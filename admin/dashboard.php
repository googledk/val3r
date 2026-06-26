<?php
require_once __DIR__ . '/_init.php';
require_admin();

$productsCount = (int)$pdo->query("SELECT COUNT(*) c FROM products")->fetch()['c'];
$ordersCount = (int)$pdo->query("SELECT COUNT(*) c FROM orders")->fetch()['c'];
$usersCount = (int)$pdo->query("SELECT COUNT(*) c FROM users")->fetch()['c'];
$lowStockCount = (int)$pdo->query("SELECT COUNT(*) c FROM products WHERE stock <= 3 AND is_active = 1")->fetch()['c'];

$latestOrders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 6")->fetchAll();
$latestProducts = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 6")->fetchAll();

$adminTitle = 'داشبورد مدیریت';
include __DIR__ . '/_header.php';
?>
<div class="page-head">
    <div>
        <h1>داشبورد مدیریت</h1>
        <p>نمای کلی فروشگاه و آخرین تغییرات سایت والر ایران</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><span>محصولات</span><strong><?= $productsCount ?></strong></div>
    <div class="stat-card"><span>سفارش‌ها</span><strong><?= $ordersCount ?></strong></div>
    <div class="stat-card"><span>کاربران</span><strong><?= $usersCount ?></strong></div>
    <div class="stat-card danger"><span>کم‌موجود</span><strong><?= $lowStockCount ?></strong></div>
</div>

<div class="admin-grid two">
    <section class="panel">
        <div class="panel-head"><h2>آخرین سفارش‌ها</h2><a href="orders.php">همه</a></div>
        <?php if (!$latestOrders): ?><div class="empty-admin">هنوز سفارشی ثبت نشده است.</div><?php endif; ?>
        <?php foreach ($latestOrders as $o): ?>
            <div class="list-row">
                <div><strong><?= e($o['order_code']) ?></strong><span><?= e($o['customer_name']) ?> - <?= e($o['customer_mobile']) ?></span></div>
                <b><?= money($o['total_amount']) ?></b>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>آخرین محصولات</h2><a href="products.php">همه</a></div>
        <?php foreach ($latestProducts as $p): ?>
            <div class="list-row">
                <div><strong><?= e($p['name']) ?></strong><span>موجودی: <?= (int)$p['stock'] ?></span></div>
                <b><?= money($p['price']) ?></b>
            </div>
        <?php endforeach; ?>
    </section>
</div>

<?php include __DIR__ . '/_footer.php'; ?>
