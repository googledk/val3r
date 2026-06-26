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

$mobile = Otp::normalizeMobile($_POST['mobile'] ?? '');

if (!Otp::validMobile($mobile)) {
    echo json_encode(['ok' => false, 'message' => 'شماره موبایل معتبر نیست.'], JSON_UNESCAPED_UNICODE);
    exit;
}

[$canSend, $remain, $message] = Otp::canSend($pdo, $mobile);
if (!$canSend) {
    echo json_encode([
        'ok' => false,
        'message' => $message,
        'remain' => $remain,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$code = Otp::create($pdo, $mobile);

$sms = new SmsIr(SMSIR_API_KEY, SMSIR_TEMPLATE_ID, SMSIR_CODE_PARAMETER);
$result = $sms->sendVerifyCode($mobile, $code);

if (!$result['ok']) {
    // keep debug information out of UI, but allow login test if debug mode is enabled
    if (defined('OTP_DEBUG_MODE') && OTP_DEBUG_MODE) {
        echo json_encode([
            'ok' => true,
            'message' => 'کد تست ساخته شد، اما ارسال پیامک واقعی انجام نشد.',
            'debug_code' => $code,
            'mobile' => $mobile,
            'remain' => OTP_RESEND_SECONDS,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'ok' => false,
        'message' => 'ارسال پیامک انجام نشد. تنظیمات SMS.ir را بررسی کنید.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'کد تایید ارسال شد.',
    'mobile' => $mobile,
    'remain' => OTP_RESEND_SECONDS,
] + ((defined('OTP_DEBUG_MODE') && OTP_DEBUG_MODE) ? ['debug_code' => $code] : []), JSON_UNESCAPED_UNICODE);
