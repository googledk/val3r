<?php
$code = trim(get('code'));
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? LIMIT 1");
$stmt->execute([$code]);
$order = $stmt->fetch();

if (!$order) {
    $pageTitle = 'سفارش پیدا نشد';
    include __DIR__ . '/header.php';
    echo '<section class="v5-page-hero"><div class="container"><h1>سفارش پیدا نشد</h1><p>کد سفارش معتبر نیست.</p></div></section>';
    include __DIR__ . '/footer.php';
    exit;
}

if (is_post()) {
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'processing' WHERE id = ?");
    $stmt->execute([(int)$order['id']]);
    redirect(BASE_URL . '?page=track&code=' . urlencode($order['order_code']));
}

$pageTitle = 'پرداخت سفارش | VaL3R';
include __DIR__ . '/header.php';
?>

<section class="v5-page-hero compact">
    <div class="container">
        <span class="v5-eyebrow">Payment</span>
        <h1>پرداخت سفارش</h1>
        <p>درگاه پرداخت واقعی بعداً متصل می‌شود. فعلاً می‌توانید پرداخت آزمایشی را ثبت کنید.</p>
    </div>
</section>

<section class="v5-section">
    <div class="container">

        <div class="v5-checkout-steps">
            <a href="<?= BASE_URL ?>?page=cart"><b>1</b><span>سبد خرید</span><small>بررسی محصولات</small></a>
            <i class="done"></i>
            <a href="<?= BASE_URL ?>?page=checkout"><b>2</b><span>ثبت سفارش</span><small>اطلاعات ارسال</small></a>
            <i class="done"></i>
            <div class="active"><b>3</b><span>پرداخت</span><small>نهایی‌سازی خرید</small></div>
        </div>

        <div class="v5-payment-layout">
            <div class="v5-payment-card">
                <div class="v5-payment-icon">
                    <svg viewBox="0 0 24 24" width="34" height="34">
                        <path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h13A2.5 2.5 0 0 1 21 6.5v11A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5v-11Zm2 2h14v-2a.5.5 0 0 0-.5-.5h-13a.5.5 0 0 0-.5.5v2Zm0 2v7a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-7H5Zm2 4h4v2H7v-2Z" fill="currentColor"/>
                    </svg>
                </div>

                <span class="v5-eyebrow">Test Gateway</span>
                <h2>پرداخت آزمایشی سفارش</h2>
                <p>در نسخه فعلی، با زدن دکمه زیر وضعیت سفارش به «پرداخت شده» تغییر می‌کند و سفارش وارد مرحله پردازش می‌شود.</p>

                <form method="post">
                    <button class="v5-pay-btn full" type="submit">پرداخت آزمایشی موفق</button>
                </form>

                <a class="v5-secondary-btn full" href="<?= BASE_URL ?>?page=track&code=<?= urlencode($order['order_code']) ?>">مشاهده وضعیت سفارش</a>
            </div>

            <aside class="v5-payment-summary">
                <span>Order Summary</span>
                <h2>خلاصه پرداخت</h2>

                <div><span>کد سفارش</span><strong><?= e($order['order_code']) ?></strong></div>
                <div><span>تاریخ ثبت</span><strong><?= e(jdate_human($order['created_at'])) ?></strong></div>
                <div><span>نام مشتری</span><strong><?= e($order['customer_name']) ?></strong></div>
                <div><span>شماره همراه</span><strong><?= e($order['customer_mobile']) ?></strong></div>
                <div><span>وضعیت پرداخت</span><strong><?= e(payment_status_fa($order['payment_status'])) ?></strong></div>
                <div class="grand"><span>مبلغ قابل پرداخت</span><strong><?= money($order['total_amount']) ?></strong></div>

                <p>اتصال به درگاه اختصاصی ایرانی بعداً در همین بخش تکمیل می‌شود.</p>
            </aside>
        </div>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
