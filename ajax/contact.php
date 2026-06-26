<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/bale.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/mail.php';
require_once __DIR__ . '/../classes/BaleBot.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'درخواست نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [
    'name' => trim($_POST['name'] ?? ''),
    'subject' => trim($_POST['subject'] ?? ''),
    'mobile' => trim($_POST['mobile'] ?? ''),
    'message' => trim($_POST['message'] ?? ''),
];

$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);

try {
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (full_name, subject, mobile, message, ip, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['name'],
        $data['subject'],
        $data['mobile'],
        $data['message'],
        $ip,
        $userAgent
    ]);
    $messageId = (int)$pdo->lastInsertId();

    $emailSent = false;
    try {
        $emailSent = send_contact_mail($data);
    } catch (Throwable $e) {
        $emailSent = false;
    }

    $baleSent = false;
    if (defined('BALE_ENABLED') && BALE_ENABLED) {
        $baleText =
            "📩 پیام جدید از فرم تماس VaL3R\n\n" .
            "👤 نام: " . ($data['name'] ?: '—') . "\n" .
            "📌 عنوان: " . ($data['subject'] ?: '—') . "\n" .
            "📱 موبایل: " . ($data['mobile'] ?: '—') . "\n" .
            "🕒 زمان: " . jdate_human(date('Y-m-d H:i:s')) . "\n" .
            "🌐 IP: " . ($ip ?: '—') . "\n\n" .
            "📝 توضیحات:\n" . ($data['message'] ?: '—');

        try {
            $bot = new BaleBot(BALE_BOT_TOKEN, BALE_CHAT_ID);
            $baleSent = $bot->sendMessage($baleText);
        } catch (Throwable $e) {
            $baleSent = false;
        }
    }

    $pdo->prepare("UPDATE contact_messages SET email_sent = ?, bale_sent = ? WHERE id = ?")
        ->execute([$emailSent ? 1 : 0, $baleSent ? 1 : 0, $messageId]);

    echo json_encode([
        'ok' => true,
        'message' => 'پیام شما با موفقیت ثبت شد.',
        'email_sent' => $emailSent,
        'bale_sent' => $baleSent,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'message' => 'ثبت پیام انجام نشد.',
    ], JSON_UNESCAPED_UNICODE);
}
