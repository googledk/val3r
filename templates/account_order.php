<?php
if (!is_logged_in()) {
    redirect(BASE_URL . '?page=login');
}

$code = trim(get('code'));
$userId = (int)current_user_id();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? AND (user_id = ? OR customer_mobile = ?) LIMIT 1");
$stmt->execute([$code, $userId, current_user_mobile()]);
$order = $stmt->fetch();

if (!$order) {
    $pageTitle = 'سفارش پیدا نشد';
    include __DIR__ . '/header.php';
    echo '<section class="v5-page-hero"><div class="container"><h1>سفارش پیدا نشد</h1><p>این سفارش برای حساب شما قابل مشاهده نیست.</p></div></section>';
    include __DIR__ . '/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT oi.*, pi.image_path FROM order_items oi LEFT JOIN product_images pi ON pi.product_id = oi.product_id AND pi.is_main = 1 WHERE oi.order_id = ? ORDER BY oi.id ASC");
$stmt->execute([(int)$order['id']]);
$items = $stmt->fetchAll();

$pageTitle = 'جزئیات سفارش ' . $order['order_code'];
include __DIR__ . '/header.php';
?>

<section class="v5-account-hero">
    <div class="container v5-account-hero-grid">
        <div>
            <span class="v5-eyebrow">Order Details</span>
            <h1>جزئیات سفارش</h1>
            <p>کد سفارش: <?= e($order['order_code']) ?></p>
        </div>
        <a class="v5-order-back" href="<?= BASE_URL ?>?page=account&section=orders">بازگشت به سفارش‌ها</a>
    </div>
</section>

<section class="v5-account-section">
    <div class="container v5-order-detail-layout">
        <div class="v5-panel">
            <div class="v5-panel-head">
                <div>
                    <span>Purchased items</span>
                    <h2>اقلام سفارش</h2>
                </div>
            </div>

            <div class="v5-order-items">
                <?php foreach ($items as $item): ?>
                    <div class="v5-order-item">
                        <img src="<?= e(product_image_url($item['image_path'] ?? null)) ?>" alt="<?= e($item['product_name']) ?>">
                        <div>
                            <strong><?= e($item['product_name']) ?></strong>
                            <span>تعداد: <?= (int)$item['quantity'] ?></span>
                        </div>
                        <b><?= money($item['total_price']) ?></b>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <aside class="v5-panel v5-order-summary">
            <h2>خلاصه سفارش</h2>
            <div><span>کد سفارش</span><strong><?= e($order['order_code']) ?></strong></div>
            <div><span>تاریخ ثبت</span><strong><?= e(jdate_human($order['created_at'])) ?></strong></div>
            <div><span>مبلغ</span><strong><?= money($order['total_amount']) ?></strong></div>
            <div><span>پرداخت</span><strong><?= e(payment_status_fa($order['payment_status'])) ?></strong></div>
            <div><span>وضعیت</span><strong><?= e(order_status_fa($order['order_status'])) ?></strong></div>
            <p><?= nl2br(e($order['customer_address'])) ?></p>

            <?php if ($order['payment_status'] === 'pending'): ?>
                <a class="v5-save-btn" href="<?= BASE_URL ?>?page=payment&code=<?= urlencode($order['order_code']) ?>">ادامه پرداخت</a>
            <?php endif; ?>
        </aside>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
