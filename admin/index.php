<?php
require_once __DIR__ . '/_init.php';

if (admin_logged_in()) redirect(BASE_URL . 'admin/dashboard.php');

$error = '';
if (is_post()) {
    $username = trim(post('username'));
    $password = post('password');

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = (int)$admin['id'];
        redirect(BASE_URL . 'admin/dashboard.php');
    }
    $error = 'نام کاربری یا رمز عبور اشتباه است.';
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>ورود مدیریت</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin.css">
</head>
<body class="login-page">
    <form class="login-card" method="post">
        <h1>ورود مدیریت والر</h1>
        <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
        <label>نام کاربری <input name="username" required></label>
        <label>رمز عبور <input type="password" name="password" required></label>
        <button type="submit">ورود</button>
        <small>پیش‌فرض: admin / admin123</small>
    </form>
</body>
</html>
