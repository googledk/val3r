<?php
require_once __DIR__ . '/_init.php';
require_admin();

if (get('mark_read')) {
    $id = (int)get('mark_read');
    $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$id]);
    redirect(BASE_URL . 'admin/contact_messages.php');
}

if (get('mark_unread')) {
    $id = (int)get('mark_unread');
    $pdo->prepare("UPDATE contact_messages SET is_read = 0 WHERE id = ?")->execute([$id]);
    redirect(BASE_URL . 'admin/contact_messages.php');
}

if (get('delete')) {
    $id = (int)get('delete');
    $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$id]);
    redirect(BASE_URL . 'admin/contact_messages.php');
}

$status = get('status', 'all');

$sql = "SELECT * FROM contact_messages";
$params = [];

if ($status === 'unread') {
    $sql .= " WHERE is_read = 0";
} elseif ($status === 'read') {
    $sql .= " WHERE is_read = 1";
}

$sql .= " ORDER BY id DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();

$adminTitle = 'پیام‌های تماس';
include __DIR__ . '/_header.php';
?>

<div class="page-head">
    <div>
        <h1>پیام‌های تماس</h1>
        <p>پیام‌های ارسال‌شده از فرم تماس سایت اینجا ذخیره می‌شوند.</p>
    </div>
    <div class="admin-head-actions">
        <a class="admin-btn ghost" href="?status=all">همه</a>
        <a class="admin-btn ghost" href="?status=unread">خوانده‌نشده <?= $unreadCount ? '(' . $unreadCount . ')' : '' ?></a>
        <a class="admin-btn ghost" href="?status=read">خوانده‌شده</a>
    </div>
</div>

<section class="panel">
    <?php if (!$messages): ?>
        <div class="admin-empty">هنوز پیامی ثبت نشده است.</div>
    <?php else: ?>
        <div class="contact-admin-list">
            <?php foreach ($messages as $m): ?>
                <article class="contact-admin-card <?= $m['is_read'] ? 'is-read' : 'is-unread' ?>">
                    <div class="contact-admin-top">
                        <div>
                            <strong><?= e($m['subject'] ?: 'بدون عنوان') ?></strong>
                            <span><?= e($m['full_name'] ?: 'بدون نام') ?><?= $m['mobile'] ? ' | ' . e($m['mobile']) : '' ?></span>
                        </div>
                        <div class="contact-badges">
                            <b class="<?= $m['is_read'] ? 'gray' : 'green' ?>"><?= $m['is_read'] ? 'خوانده‌شده' : 'جدید' ?></b>
                            <b class="<?= $m['email_sent'] ? 'green' : 'red' ?>">ایمیل <?= $m['email_sent'] ? 'ارسال شد' : 'ناموفق' ?></b>
                            <b class="<?= $m['bale_sent'] ? 'green' : 'red' ?>">بله <?= $m['bale_sent'] ? 'ارسال شد' : 'ناموفق' ?></b>
                        </div>
                    </div>

                    <p><?= nl2br(e($m['message'] ?: '—')) ?></p>

                    <div class="contact-admin-meta">
                        <span>تاریخ: <?= e(jdate_human($m['created_at'])) ?></span>
                        <span>IP: <?= e($m['ip'] ?: '—') ?></span>
                    </div>

                    <div class="contact-admin-actions">
                        <?php if ($m['is_read']): ?>
                            <a class="admin-btn ghost" href="?mark_unread=<?= (int)$m['id'] ?>">خوانده‌نشده شود</a>
                        <?php else: ?>
                            <a class="admin-btn" href="?mark_read=<?= (int)$m['id'] ?>">خوانده شد</a>
                        <?php endif; ?>
                        <a class="admin-btn danger" onclick="return confirm('پیام حذف شود؟')" href="?delete=<?= (int)$m['id'] ?>">حذف</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/_footer.php'; ?>
