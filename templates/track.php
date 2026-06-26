<?php
$pageTitle = 'پیگیری سفارش | VaL3R';
$order = null;
$error = '';

$code = trim($_GET['code'] ?? $_POST['code'] ?? '');
$mobile = trim($_GET['mobile'] ?? $_POST['mobile'] ?? '');

if ($code !== '' || $mobile !== '') {
    if ($code !== '') {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? LIMIT 1");
        $stmt->execute([$code]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_mobile = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$mobile]);
    }

    $order = $stmt->fetch();

    if (!$order) {
        $error = 'سفارشی با این اطلاعات پیدا نشد.';
    }
}

include __DIR__ . '/header.php';
?>

<section class="v5-page-hero compact">
    <div class="container">
        <span class="v5-eyebrow">Order Tracking</span>
        <h1>پیگیری سفارش</h1>
        <p>با کد سفارش یا شماره موبایل، آخرین وضعیت سفارش خود را بررسی کنید.</p>
    </div>
</section>

<section class="v5-section">
    <div class="container v5-track-layout">
        <form class="v5-track-card" method="post">
            <div class="v5-form-head">
                <span>Track your order</span>
                <h2>جستجوی سفارش</h2>
                <p>یکی از دو مورد زیر را وارد کنید. اگر شماره موبایل را وارد کنید، آخرین سفارش همان شماره نمایش داده می‌شود.</p>
            </div>

            <?php if ($error): ?><div class="v5-account-alert error"><?= e($error) ?></div><?php endif; ?>

            <label>کد سفارش
                <input name="code" value="<?= e($code) ?>" placeholder="مثلاً VLR26062572590">
            </label>

            <label>شماره موبایل
                <input name="mobile" value="<?= e($mobile) ?>" placeholder="09123456789">
            </label>

            <button class="v5-save-btn full" type="submit">پیگیری سفارش</button>
        </form>

        <aside class="v5-track-result">
            <?php if (!$order): ?>
                <div class="v5-empty-state wide">
                    <strong>وضعیت سفارش اینجا نمایش داده می‌شود.</strong>
                    <p>بعد از وارد کردن کد سفارش یا شماره موبایل، اطلاعات سفارش را مشاهده می‌کنید.</p>
                </div>
            <?php else: ?>
                <span class="v5-eyebrow">Order Status</span>
                <h2>نتیجه پیگیری</h2>

                <div class="v5-track-status-card">
                    <div>
                        <span>کد سفارش</span>
                        <strong><?= e($order['order_code']) ?></strong>
                    </div>
                    <div>
                        <span>مبلغ</span>
                        <strong><?= money($order['total_amount']) ?></strong>
                    </div>
                    <div>
                        <span>پرداخت</span>
                        <strong><?= e(payment_status_fa($order['payment_status'])) ?></strong>
                    </div>
                    <div>
                        <span>وضعیت</span>
                        <strong><?= e(order_status_fa($order['order_status'])) ?></strong>
                    </div>
                    <div>
                        <span>تاریخ ثبت</span>
                        <strong><?= e(jdate_human($order['created_at'])) ?></strong>
                    </div>
                </div>

                <div class="v5-order-progress">
                    <?php
                    $steps = ['new' => 'ثبت سفارش', 'processing' => 'پردازش', 'sent' => 'ارسال', 'delivered' => 'تحویل'];
                    $keys = array_keys($steps);
                    $currentIndex = array_search($order['order_status'], $keys, true);
                    if ($currentIndex === false) $currentIndex = 0;
                    ?>
                    <?php foreach ($steps as $key => $label): ?>
                        <?php $idx = array_search($key, $keys, true); ?>
                        <div class="<?= $idx <= $currentIndex ? 'done' : '' ?>">
                            <b><?= $idx + 1 ?></b>
                            <span><?= $label ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($order['payment_status'] === 'pending'): ?>
                    <a class="v5-pay-btn full" href="<?= BASE_URL ?>?page=payment&code=<?= urlencode($order['order_code']) ?>">ادامه پرداخت</a>
                <?php endif; ?>
            <?php endif; ?>
        </aside>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
