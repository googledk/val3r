<?php
$pageTitle = 'ثبت سفارش | VaL3R';
$cart = cart_items();
$error = '';

if (!$cart) redirect(BASE_URL . '?page=cart');

$user = null;
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([current_user_id()]);
    $user = $stmt->fetch();
}

$ids = array_map('intval', array_keys($cart));
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND is_active = 1");
$stmt->execute($ids);
$products = $stmt->fetchAll();

$total = 0;
foreach ($products as $p) $total += ((int)$cart[$p['id']]) * (int)$p['price'];

if (is_post()) {
    $name = trim(post('name'));
    $mobile = trim(post('mobile'));
    $province = trim(post('province'));
    $city = trim(post('city'));
    $address = trim(post('address'));
    $postalCode = trim(post('postal_code'));

    if ($name === '' || !preg_match('/^09\d{9}$/', $mobile) || $province === '' || $city === '' || $address === '' || $postalCode === '') {
        $error = 'لطفاً نام، موبایل صحیح، استان، شهر، آدرس و کد پستی را کامل وارد کنید.';
    } else {
        $orderCode = 'VLR' . date('ymd') . random_int(10000, 99999);
        $userId = current_user_id();

        if ($userId) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, province = ?, city = ?, address = ?, postal_code = ? WHERE id = ?");
            $stmt->execute([$name, $province, $city, $address, $postalCode, $userId]);
        }

        $fullAddress = "استان: {$province}\nشهر: {$city}\nآدرس: {$address}\nکد پستی: {$postalCode}";
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_code, customer_name, customer_mobile, customer_address, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $orderCode, $name, $mobile, $fullAddress, $total]);
            $orderId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($products as $p) {
                $qty = (int)$cart[$p['id']];
                $unit = (int)$p['price'];
                $itemStmt->execute([$orderId, $p['id'], $p['name'], $qty, $unit, $qty * $unit]);
            }

            $pdo->commit();
            cart_clear();
            redirect(BASE_URL . '?page=payment&code=' . urlencode($orderCode));
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = 'ثبت سفارش انجام نشد. دوباره تلاش کنید.';
        }
    }
}

$nameValue = post('name', $user['name'] ?? '');
$mobileValue = current_user_mobile() ?: post('mobile', $user['mobile'] ?? '');
$provinceValue = post('province', $user['province'] ?? '');
$cityValue = post('city', $user['city'] ?? '');
$addressValue = post('address', $user['address'] ?? '');
$postalValue = post('postal_code', $user['postal_code'] ?? '');

include __DIR__ . '/header.php';
?>

<section class="v5-page-hero compact">
    <div class="container">
        <span class="v5-eyebrow">Checkout</span>
        <h1>ثبت سفارش</h1>
        <p>اطلاعات ارسال را تکمیل کنید تا سفارش برای پرداخت آماده شود.</p>
    </div>
</section>

<section class="v5-section">
    <div class="container">

        <div class="v5-checkout-steps">
            <a href="<?= BASE_URL ?>?page=cart"><b>1</b><span>سبد خرید</span><small>بررسی محصولات</small></a>
            <i class="done"></i>
            <div class="active"><b>2</b><span>ثبت سفارش</span><small>اطلاعات ارسال</small></div>
            <i></i>
            <div><b>3</b><span>پرداخت</span><small>نهایی‌سازی خرید</small></div>
        </div>

        <div class="v5-checkout-layout">
            <form class="v5-checkout-form" method="post">
                <div class="v5-form-head">
                    <span>Shipping information</span>
                    <h2>اطلاعات ارسال</h2>
                    <p>این اطلاعات برای ارسال سفارش ضروری است و در پنل کاربری ذخیره می‌شود.</p>
                </div>

                <?php if ($error): ?><div class="v5-account-alert error"><?= e($error) ?></div><?php endif; ?>

                <div class="v5-profile-grid">
                    <label>نام و نام خانوادگی <input name="name" required value="<?= e($nameValue) ?>"></label>
                    <label>شماره موبایل <input name="mobile" required pattern="09[0-9]{9}" value="<?= e($mobileValue) ?>"></label>
                    <label>استان <input name="province" required value="<?= e($provinceValue) ?>"></label>
                    <label>شهر <input name="city" required value="<?= e($cityValue) ?>"></label>
                    <label>کد پستی <input name="postal_code" required value="<?= e($postalValue) ?>"></label>
                </div>

                <label>آدرس کامل
                    <textarea name="address" rows="5" required><?= e($addressValue) ?></textarea>
                </label>

                <div class="v5-checkout-actions">
                    <a class="v5-secondary-btn" href="<?= BASE_URL ?>?page=cart">برگشت به سبد خرید</a>
                    <button class="v5-pay-btn" type="submit">ثبت سفارش و رفتن به پرداخت</button>
                </div>
            </form>

            <aside class="v5-checkout-summary">
                <span>Order Summary</span>
                <h2>خلاصه سفارش</h2>
                <small><?= cart_count() ?> کالا در سبد خرید شما</small>

                <div class="v5-checkout-items">
                    <?php foreach ($products as $p):
                        $qty = (int)$cart[$p['id']];
                        $rowTotal = $qty * (int)$p['price'];
                        $img = get_primary_image($pdo, (int)$p['id']);
                    ?>
                        <a class="v5-checkout-item" href="<?= BASE_URL ?>?page=product&id=<?= (int)$p['id'] ?>">
                            <img src="<?= e(product_image_url($img)) ?>" alt="<?= e($p['name']) ?>">
                            <div>
                                <strong><?= e($p['name']) ?></strong>
                                <span>تعداد: <?= $qty ?></span>
                            </div>
                            <b><?= money($rowTotal) ?></b>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="v5-total-box">
                    <div><span>جمع کالاها</span><strong><?= money($total) ?></strong></div>
                    <div><span>هزینه ارسال</span><strong>بعداً محاسبه می‌شود</strong></div>
                    <div class="grand"><span>مبلغ قابل پرداخت</span><strong><?= money($total) ?></strong></div>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
