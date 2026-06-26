<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sms.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../classes/SmsIr.php';
require_once __DIR__ . '/../classes/Otp.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'درخواست نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$mobile = Otp::normalizeMobile($_POST['mobile'] ?? ($_SESSION['otp_mobile'] ?? ''));
$code = trim($_POST['code'] ?? '');

if (!Otp::validMobile($mobile)) {
    echo json_encode(['ok' => false, 'message' => 'شماره موبایل معتبر نیست.'], JSON_UNESCAPED_UNICODE);
    exit;
}

[$ok, $message] = Otp::verify($pdo, $mobile, $code);

if (!$ok) {
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = Otp::loginOrCreateUser($pdo, $mobile);
$_SESSION['user_id'] = $userId;
unset($_SESSION['otp_mobile'], $_SESSION['last_test_otp']);

echo json_encode([
    'ok' => true,
    'message' => 'ورود موفق بود.',
    'redirect' => BASE_URL . '?page=account',
], JSON_UNESCAPED_UNICODE);
