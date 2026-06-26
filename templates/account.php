<?php
if (!is_logged_in()) {
    redirect(BASE_URL . '?page=login');
}

$userId = (int)current_user_id();
$section = $_GET['section'] ?? 'dashboard';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$pageTitle = 'پنل کاربری | VaL3R';
include __DIR__ . '/header.php';
?>

<section class="v5-account-hero">
    <div class="container v5-account-hero-grid">
        <div>
            <span class="v5-eyebrow">VaL3R Account</span>
            <h1>پنل کاربری</h1>
            <p>سفارش‌ها، اطلاعات ارسال و پیگیری خریدهای شما در یک داشبورد حرفه‌ای و ساده.</p>
        </div>

        <div class="v5-account-profile-mini">
            <div class="v5-avatar">
                <svg viewBox="0 0 24 24" width="30" height="30"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor"/></svg>
            </div>
            <div>
                <strong><?= e($user['name'] ?: 'کاربر والر') ?></strong>
                <span><?= e($user['mobile']) ?></span>
            </div>
        </div>
    </div>
</section>

<section class="v5-account-section">
    <div class="container v5-account-layout">
        <aside class="v5-account-sidebar">
            <div class="v5-account-card">
                <div class="v5-account-card-head">
                    <div class="v5-avatar small">
                        <svg viewBox="0 0 24 24" width="22" height="22"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor"/></svg>
                    </div>
                    <div>
                        <strong><?= e($user['name'] ?: 'کاربر والر') ?></strong>
                        <span><?= e($user['mobile']) ?></span>
                    </div>
                </div>
            </div>

            <nav class="v5-account-menu" data-account-menu>
                <a class="<?= $section === 'dashboard' ? 'active' : '' ?>" href="<?= BASE_URL ?>?page=account" data-account-link data-section="dashboard">
                    <span>داشبورد</span>
                </a>
                <a class="<?= $section === 'orders' ? 'active' : '' ?>" href="<?= BASE_URL ?>?page=account&section=orders" data-account-link data-section="orders">
                    <span>سفارش‌های من</span>
                </a>
                <a class="<?= $section === 'profile' ? 'active' : '' ?>" href="<?= BASE_URL ?>?page=account&section=profile" data-account-link data-section="profile">
                    <span>اطلاعات حساب و آدرس</span>
                </a>
                <a class="<?= $section === 'track' ? 'active' : '' ?>" href="<?= BASE_URL ?>?page=account&section=track" data-account-link data-section="track">
                    <span>پیگیری سفارش</span>
                </a>
                <a href="<?= BASE_URL ?>?page=logout" class="danger">
                    <span>خروج</span>
                </a>
            </nav>
        </aside>

        <div class="v5-account-content" data-account-content data-initial-section="<?= e($section) ?>">
            <div class="v5-account-loading">
                <span></span>
                در حال بارگذاری پنل کاربری...
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
