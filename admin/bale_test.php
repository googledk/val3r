<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/bale.php';
require_once __DIR__ . '/../classes/BaleBot.php';
require_admin();

$adminTitle = 'تست اتصال بله';
$resultMessage = '';
$resultType = '';
$updates = [];

$bot = new BaleBot(BALE_BOT_TOKEN, BALE_CHAT_ID);

if (is_post() && post('action') === 'send_test') {
    $ok = $bot->sendMessage("✅ تست اتصال بله از سایت VaL3R\nزمان: " . jdate_human(date('Y-m-d H:i:s')));
    $resultType = $ok ? 'success' : 'error';
    $resultMessage = $ok ? 'پیام تست با موفقیت به بله ارسال شد.' : ('ارسال پیام ناموفق بود: ' . $bot->getLastError());
}

if (is_post() && post('action') === 'get_updates') {
    $updates = $bot->getUpdates();
    if ($updates) {
        $resultType = 'success';
        $resultMessage = 'لیست آخرین پیام‌های دریافتی بات نمایش داده شد.';
    } else {
        $resultType = 'error';
        $resultMessage = 'آپدیتی پیدا نشد یا دریافت ناموفق بود: ' . $bot->getLastError();
    }
}

function mask_token(string $token): string {
    if (strlen($token) < 12) return '***';
    return substr($token, 0, 8) . '...' . substr($token, -6);
}

include __DIR__ . '/_header.php';
?>

<div class="page-head">
    <div>
        <h1>تست اتصال بله</h1>
        <p>برای اینکه بات بتواند به شما پیام بفرستد، اول باید داخل بله به بات پیام بدهید یا در گروه عضو باشد.</p>
    </div>
    <a class="admin-btn ghost" href="contact_messages.php">پیام‌های تماس</a>
</div>

<?php if ($resultMessage): ?>
    <div class="alert <?= $resultType === 'success' ? 'success' : 'error' ?>"><?= e($resultMessage) ?></div>
<?php endif; ?>

<section class="panel bale-test-panel">
    <h2>تنظیمات فعلی</h2>
    <div class="bale-config-grid">
        <div><span>Token</span><strong dir="ltr"><?= e(mask_token(BALE_BOT_TOKEN)) ?></strong></div>
        <div><span>Chat ID</span><strong dir="ltr"><?= e(BALE_CHAT_ID) ?></strong></div>
        <div><span>وضعیت</span><strong><?= BALE_ENABLED ? 'فعال' : 'غیرفعال' ?></strong></div>
    </div>

    <div class="bale-actions">
        <form method="post">
            <input type="hidden" name="action" value="send_test">
            <button class="admin-btn" type="submit">ارسال پیام تست</button>
        </form>

        <form method="post">
            <input type="hidden" name="action" value="get_updates">
            <button class="admin-btn ghost" type="submit">گرفتن Chat ID از getUpdates</button>
        </form>
    </div>
</section>

<section class="panel">
    <h2>راهنمای سریع</h2>
    <div class="bale-help">
        <p>اگر ارسال با <b>@navvab313</b> انجام نشد، معمولاً یعنی بله به جای username نیاز به chat_id عددی دارد.</p>
        <ol>
            <li>در اپ بله، وارد بات خودت شو.</li>
            <li>یک پیام مثل «سلام» به بات بفرست.</li>
            <li>اینجا روی دکمه «گرفتن Chat ID از getUpdates» بزن.</li>
            <li>عدد chat.id را بردار و در فایل <code>config/bale.php</code> جایگزین <code>BALE_CHAT_ID</code> کن.</li>
        </ol>
    </div>
</section>

<?php if ($updates): ?>
<section class="panel">
    <h2>آخرین آپدیت‌های بات</h2>
    <div class="bale-updates">
        <?php foreach ($updates as $u): ?>
            <?php
                $msg = $u['message'] ?? [];
                $chat = $msg['chat'] ?? [];
                $from = $msg['from'] ?? [];
            ?>
            <div>
                <strong>chat.id:</strong>
                <code dir="ltr"><?= e($chat['id'] ?? '—') ?></code>
                <br>
                <strong>chat.type:</strong>
                <code dir="ltr"><?= e($chat['type'] ?? '—') ?></code>
                <br>
                <strong>username:</strong>
                <code dir="ltr"><?= e($from['username'] ?? $chat['username'] ?? '—') ?></code>
                <br>
                <strong>text:</strong>
                <span><?= e($msg['text'] ?? '—') ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/_footer.php'; ?>
