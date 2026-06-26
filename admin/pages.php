<?php
require_once __DIR__ . '/_init.php';
require_admin();

if (is_post()) {
    foreach (['about','contact'] as $slug) {
        $title = $slug === 'about' ? 'درباره والر ایران' : 'تماس با ما';
        $content = post($slug);
        $stmt = $pdo->prepare("INSERT INTO pages (slug, title, content) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE content=VALUES(content)");
        $stmt->execute([$slug, $title, $content]);
    }
    redirect(BASE_URL . 'admin/pages.php?saved=1');
}

$about = page_content($pdo, 'about');
$contact = page_content($pdo, 'contact');
$adminTitle = 'مدیریت صفحات';
include __DIR__ . '/_header.php';
?>
<div class="page-head"><div><h1>مدیریت صفحات</h1><p>ویرایش محتوای صفحه درباره ما و تماس با ما</p></div></div>
<?php if (get('saved')): ?><div class="alert success">محتوای صفحات ذخیره شد.</div><?php endif; ?>
<form class="panel admin-form wide" method="post">
    <label>درباره ما <textarea name="about" rows="10"><?= e($about) ?></textarea></label>
    <label>تماس با ما <textarea name="contact" rows="10"><?= e($contact) ?></textarea></label>
    <button class="admin-btn">ذخیره صفحات</button>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
