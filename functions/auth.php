<?php
function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function current_user_mobile(): ?string {
    return $_SESSION['user_mobile'] ?? null;
}

function is_logged_in(): bool {
    return current_user_id() !== null;
}

function generate_otp(PDO $pdo, string $mobile): string {
    $code = (string)random_int(100000, 999999);
    $stmt = $pdo->prepare("INSERT INTO otp_codes (mobile, code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
    $stmt->execute([$mobile, $code]);
    return $code;
}

function verify_otp_code(PDO $pdo, string $mobile, string $code): bool {
    $stmt = $pdo->prepare("
        SELECT id FROM otp_codes
        WHERE mobile = ? AND code = ? AND is_used = 0 AND expires_at >= NOW()
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$mobile, $code]);
    $row = $stmt->fetch();

    if (!$row) return false;

    $update = $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?");
    $update->execute([$row['id']]);
    return true;
}

function login_or_create_user(PDO $pdo, string $mobile): int {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE mobile = ? LIMIT 1");
    $stmt->execute([$mobile]);
    $user = $stmt->fetch();

    if ($user) return (int)$user['id'];

    $insert = $pdo->prepare("INSERT INTO users (mobile) VALUES (?)");
    $insert->execute([$mobile]);
    return (int)$pdo->lastInsertId();
}

function send_sms_otp(string $mobile, string $code): bool {
    // نسخه تستی: پیامک واقعی ارسال نمی‌شود.
    // بعداً API کاوه‌نگار/ملی‌پیامک/فراز SMS اینجا قرار می‌گیرد.
    $_SESSION['last_test_otp'] = $code;
    return true;
}

function can_request_otp(): bool {
    $last = $_SESSION['otp_last_request'] ?? 0;
    return (time() - (int)$last) >= 60;
}

function mark_otp_requested(): void {
    $_SESSION['otp_last_request'] = time();
}

function admin_logged_in(): bool {
    return isset($_SESSION['admin_id']);
}

function require_admin(): void {
    if (!admin_logged_in()) {
        redirect(BASE_URL . 'admin/index.php');
    }
}
