<?php
class Otp
{
    public static function normalizeMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D+/', '', $mobile);
        if (str_starts_with($mobile, '989')) {
            $mobile = '0' . substr($mobile, 2);
        }
        return $mobile;
    }

    public static function validMobile(string $mobile): bool
    {
        return (bool)preg_match('/^09\d{9}$/', $mobile);
    }

    public static function clientIp(): string
    {
        return $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    public static function canSend(PDO $pdo, string $mobile): array
    {
        $stmt = $pdo->prepare("SELECT created_at FROM otp_codes WHERE mobile = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$mobile]);
        $last = $stmt->fetch();

        if ($last) {
            $lastTime = strtotime($last['created_at']);
            $remain = OTP_RESEND_SECONDS - (time() - $lastTime);
            if ($remain > 0) {
                return [false, $remain, 'برای دریافت مجدد کد کمی صبر کنید.'];
            }
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM otp_codes WHERE mobile = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)");
        $stmt->execute([$mobile]);
        $count = (int)$stmt->fetchColumn();

        if ($count >= OTP_MAX_SENDS_PER_HOUR) {
            return [false, 3600, 'تعداد درخواست‌های پیامک زیاد است. لطفاً بعداً تلاش کنید.'];
        }

        return [true, 0, ''];
    }

    public static function create(PDO $pdo, string $mobile): string
    {
        $code = (string)random_int(100000, 999999);
        $ip = self::clientIp();

        // Invalidate old unused codes
        $stmt = $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE mobile = ? AND is_used = 0");
        $stmt->execute([$mobile]);

        $stmt = $pdo->prepare("
            INSERT INTO otp_codes (mobile, code, ip, attempts, expires_at, is_used)
            VALUES (?, ?, ?, 0, DATE_ADD(NOW(), INTERVAL " . OTP_EXPIRE_MINUTES . " MINUTE), 0)
        ");
        $stmt->execute([$mobile, password_hash($code, PASSWORD_DEFAULT), $ip]);

        $_SESSION['otp_mobile'] = $mobile;
        if (defined('OTP_DEBUG_MODE') && OTP_DEBUG_MODE) {
            $_SESSION['last_test_otp'] = $code;
        } else {
            unset($_SESSION['last_test_otp']);
        }

        return $code;
    }

    public static function verify(PDO $pdo, string $mobile, string $code): array
    {
        $code = preg_replace('/\D+/', '', $code);

        if (!preg_match('/^\d{6}$/', $code)) {
            return [false, 'کد تایید باید ۶ رقم باشد.'];
        }

        $stmt = $pdo->prepare("
            SELECT * FROM otp_codes
            WHERE mobile = ? AND is_used = 0 AND expires_at >= NOW()
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$mobile]);
        $otp = $stmt->fetch();

        if (!$otp) {
            return [false, 'کد تایید منقضی شده یا معتبر نیست.'];
        }

        if ((int)$otp['attempts'] >= OTP_MAX_ATTEMPTS) {
            $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?")->execute([$otp['id']]);
            return [false, 'تعداد تلاش ناموفق زیاد بود. دوباره کد دریافت کنید.'];
        }

        if (!password_verify($code, $otp['code'])) {
            $pdo->prepare("UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?")->execute([$otp['id']]);
            return [false, 'کد واردشده اشتباه است.'];
        }

        $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?")->execute([$otp['id']]);

        return [true, 'ورود موفق بود.'];
    }

    public static function loginOrCreateUser(PDO $pdo, string $mobile): int
    {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE mobile = ? LIMIT 1");
        $stmt->execute([$mobile]);
        $userId = (int)$stmt->fetchColumn();

        if ($userId > 0) {
            return $userId;
        }

        $stmt = $pdo->prepare("INSERT INTO users (mobile) VALUES (?)");
        $stmt->execute([$mobile]);
        return (int)$pdo->lastInsertId();
    }
}
