<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/cart.php';
require_once __DIR__ . '/../functions/auth.php';

session_start();

if (!is_logged_in()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'redirect' => BASE_URL . '?page=login'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)current_user_id();
$section = $_GET['section'] ?? $_POST['section'] ?? 'dashboard';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $section === 'profile') {
    try {
        $name = trim($_POST['name'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');

        $stmt = $pdo->prepare("
            UPDATE users
            SET name = ?, province = ?, city = ?, address = ?, postal_code = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $province, $city, $address, $postalCode, $userId]);

        $message = 'اطلاعات حساب و آدرس با موفقیت ذخیره شد.';
    } catch (Throwable $e) {
        $error = 'ذخیره اطلاعات انجام نشد. اگر تازه این نسخه را نصب کرده‌ای، migration آدرس کاربر را بررسی کن.';
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$ordersStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? OR customer_mobile = ? ORDER BY id DESC LIMIT 20");
$ordersStmt->execute([$userId, current_user_mobile()]);
$orders = $ordersStmt->fetchAll();

function v5_account_order_thumbs(PDO $pdo, int $orderId): string {
    $stmt = $pdo->prepare("
        SELECT oi.product_name, pi.image_path
        FROM order_items oi
        LEFT JOIN product_images pi ON pi.product_id = oi.product_id AND pi.is_main = 1
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
        LIMIT 4
    ");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();

    if (!$items) return '';

    ob_start();
    echo '<div class="v5-order-thumbs">';
    foreach ($items as $item) {
        echo '<span><img src="' . e(product_image_url($item['image_path'] ?? null)) . '" alt="' . e($item['product_name']) . '"></span>';
    }
    echo '</div>';
    return ob_get_clean();
}

ob_start();
?>

<?php if ($message): ?><div class="v5-account-alert success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="v5-account-alert error"><?= e($error) ?></div><?php endif; ?>

<?php if ($section === 'dashboard'): ?>
    <?php
    $totalOrders = count($orders);
    $paidOrders = 0;
    $pendingOrders = 0;
    foreach ($orders as $o) {
        if ($o['payment_status'] === 'paid') $paidOrders++;
        if ($o['payment_status'] === 'pending') $pendingOrders++;
    }
    ?>
    <div class="v5-account-stats">
        <div><span>کل سفارش‌ها</span><strong><?= $totalOrders ?></strong></div>
        <div><span>پرداخت‌شده</span><strong><?= $paidOrders ?></strong></div>
        <div><span>در انتظار پرداخت</span><strong><?= $pendingOrders ?></strong></div>
    </div>

    <div class="v5-panel">
        <div class="v5-panel-head">
            <div>
                <span>Recent orders</span>
                <h2>آخرین سفارش‌ها</h2>
            </div>
            <a href="<?= BASE_URL ?>?page=account&section=orders" data-account-link data-section="orders">مشاهده همه</a>
        </div>

        <?php if (!$orders): ?>
            <div class="v5-empty-state">
                <strong>هنوز سفارشی ثبت نکرده‌اید.</strong>
                <p>محصولات والر را ببینید و اولین سفارش خود را ثبت کنید.</p>
                <a href="<?= BASE_URL ?>?page=products">مشاهده محصولات</a>
            </div>
        <?php endif; ?>

        <div class="v5-orders-list">
            <?php foreach (array_slice($orders, 0, 5) as $o): ?>
                <a class="v5-order-row" href="<?= BASE_URL ?>?page=account_order&code=<?= urlencode($o['order_code']) ?>">
                    <?= v5_account_order_thumbs($pdo, (int)$o['id']) ?>
                    <div><strong><?= e($o['order_code']) ?></strong><span><?= e(jdate_human($o['created_at'])) ?></span></div>
                    <div><span>مبلغ</span><strong><?= money($o['total_amount']) ?></strong></div>
                    <div><span>پرداخت</span><strong><?= e(payment_status_fa($o['payment_status'])) ?></strong></div>
                    <div><span>وضعیت</span><strong><?= e(order_status_fa($o['order_status'])) ?></strong></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($section === 'orders'): ?>
    <div class="v5-panel">
        <div class="v5-panel-head">
            <div>
                <span>My orders</span>
                <h2>سفارش‌های من</h2>
            </div>
            <a href="<?= BASE_URL ?>?page=products">خرید محصول جدید</a>
        </div>

        <?php if (!$orders): ?>
            <div class="v5-empty-state">
                <strong>هنوز سفارشی ثبت نکرده‌اید.</strong>
                <p>بعد از ثبت سفارش، جزئیات آن اینجا نمایش داده می‌شود.</p>
            </div>
        <?php endif; ?>

        <div class="v5-orders-list">
            <?php foreach ($orders as $o): ?>
                <a class="v5-order-row" href="<?= BASE_URL ?>?page=account_order&code=<?= urlencode($o['order_code']) ?>">
                    <?= v5_account_order_thumbs($pdo, (int)$o['id']) ?>
                    <div><strong><?= e($o['order_code']) ?></strong><span><?= e(jdate_human($o['created_at'])) ?></span></div>
                    <div><span>مبلغ</span><strong><?= money($o['total_amount']) ?></strong></div>
                    <div><span>پرداخت</span><strong><?= e(payment_status_fa($o['payment_status'])) ?></strong></div>
                    <div><span>وضعیت</span><strong><?= e(order_status_fa($o['order_status'])) ?></strong></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($section === 'profile'): ?>
    <form class="v5-panel v5-profile-form" method="post" action="<?= BASE_URL ?>ajax/account.php" data-account-profile-form>
        <input type="hidden" name="section" value="profile">
        <div class="v5-panel-head">
            <div>
                <span>Profile & address</span>
                <h2>اطلاعات حساب و آدرس</h2>
            </div>
        </div>

        <div class="v5-profile-grid">
            <label>نام و نام خانوادگی
                <input name="name" value="<?= e($user['name'] ?? '') ?>" placeholder="مثلاً نواب شمسی">
            </label>
            <label>شماره موبایل
                <input value="<?= e($user['mobile']) ?>" disabled>
            </label>
            <label>استان
                <input name="province" value="<?= e($user['province'] ?? '') ?>" placeholder="مثلاً تهران">
            </label>
            <label>شهر
                <input name="city" value="<?= e($user['city'] ?? '') ?>" placeholder="مثلاً تهران">
            </label>
            <label>کد پستی
                <input name="postal_code" value="<?= e($user['postal_code'] ?? '') ?>" placeholder="کد پستی">
            </label>
        </div>

        <label>آدرس کامل
            <textarea name="address" rows="5" placeholder="آدرس کامل پستی"><?= e($user['address'] ?? '') ?></textarea>
        </label>

        <button class="v5-save-btn" type="submit">ذخیره اطلاعات</button>
    </form>
<?php endif; ?>

<?php if ($section === 'track'): ?>
    <div class="v5-panel">
        <div class="v5-panel-head">
            <div>
                <span>Track order</span>
                <h2>پیگیری سفارش</h2>
            </div>
            <a href="<?= BASE_URL ?>?page=account&section=orders" data-account-link data-section="orders">سفارش‌های من</a>
        </div>

        <div class="v5-track-box">
            <p>برای پیگیری سریع سفارش، کد سفارش را وارد کنید یا از بخش سفارش‌های من جزئیات را ببینید.</p>
            <form method="post" action="<?= BASE_URL ?>?page=track">
                <input name="code" placeholder="مثلاً VLR26062512345">
                <button type="submit">پیگیری</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$html = ob_get_clean();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'section' => $section,
    'html' => $html,
], JSON_UNESCAPED_UNICODE);
