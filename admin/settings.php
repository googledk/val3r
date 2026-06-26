<?php
require_once __DIR__ . '/_init.php';
require_admin();

$message = '';
$error = '';

if (is_post()) {
    $current = post('current_password');
    $new = post('new_password');
    $confirm = post('confirm_password');

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($current, $admin['password'])) {
        $error = 'رمز فعلی اشتباه است.';
    } elseif (strlen($new) < 6) {
        $error = 'رمز جدید باید حداقل ۶ کاراکتر باشد.';
    } elseif ($new !== $confirm) {
        $error = 'تکرار رمز جدید درست نیست.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $_SESSION['admin_id']]);
        $message = 'رمز عبور با موفقیت تغییر کرد.';
    }
}

$adminTitle = 'تنظیمات مدیریت';
include __DIR__ . '/_header.php';
?>
<div class="page-head">
    <div>
        <h1>تنظیمات مدیریت</h1>
        <p>تغییر رمز عبور پنل مدیریت</p>
    </div>
</div>

<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<form class="panel admin-form" method="post">
    <label>رمز فعلی <input type="password" name="current_password" required></label>
    <label>رمز جدید <input type="password" name="new_password" required minlength="6"></label>
    <label>تکرار رمز جدید <input type="password" name="confirm_password" required minlength="6"></label>
    <button class="admin-btn" type="submit">تغییر رمز عبور</button>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
