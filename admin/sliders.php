<?php
require_once __DIR__ . '/_init.php';
require_admin();

$error = '';

if (is_post()) {
    try {
        $title = trim(post('title'));
        $subtitle = trim(post('subtitle'));
        $link = trim(post('link_url'));
        $sort = (int)post('sort_order');
        $active = isset($_POST['is_active']) ? 1 : 0;
        $img = upload_image($_FILES['image'] ?? [], UPLOAD_SLIDERS_DIR);
        if (!$img) throw new RuntimeException('عکس اسلایدر الزامی است.');
        $stmt = $pdo->prepare("INSERT INTO sliders (title, subtitle, image_path, link_url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $subtitle, $img, $link, $sort, $active]);
        redirect(BASE_URL . 'admin/sliders.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

if (get('delete')) {
    $stmt = $pdo->prepare("SELECT image_path FROM sliders WHERE id=?");
    $stmt->execute([(int)get('delete')]);
    $s = $stmt->fetch();
    if ($s && $s['image_path'] && is_file(UPLOAD_SLIDERS_DIR . $s['image_path'])) @unlink(UPLOAD_SLIDERS_DIR . $s['image_path']);
    $pdo->prepare("DELETE FROM sliders WHERE id=?")->execute([(int)get('delete')]);
    redirect(BASE_URL . 'admin/sliders.php');
}

$items = $pdo->query("SELECT * FROM sliders ORDER BY sort_order ASC, id DESC")->fetchAll();
$adminTitle = 'مدیریت اسلایدر';
include __DIR__ . '/_header.php';
?>
<div class="page-head"><div><h1>اسلایدر صفحه اصلی</h1><p>بنرهای قابل نمایش در صفحه اول سایت</p></div></div>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<form class="panel admin-form" method="post" enctype="multipart/form-data">
    <div class="form-grid">
        <label>عنوان <input name="title"></label>
        <label>زیرعنوان <input name="subtitle"></label>
        <label>لینک <input name="link_url" placeholder="?page=products"></label>
        <label>ترتیب <input type="number" name="sort_order" value="0"></label>
    </div>
    <label class="check-inline"><input type="checkbox" name="is_active" checked> فعال</label>
    <label class="upload-box">انتخاب عکس اسلایدر <input type="file" name="image" required accept="image/*"></label>
    <button class="admin-btn">افزودن اسلایدر</button>
</form>

<div class="slider-admin-grid">
<?php foreach ($items as $s): ?>
    <div class="slider-admin-card">
        <img src="<?= e(slider_image_url($s['image_path'])) ?>" alt="">
        <strong><?= e($s['title']) ?></strong>
        <span><?= e($s['subtitle']) ?></span>
        <div><span class="<?= $s['is_active'] ? 'badge green' : 'badge gray' ?>"><?= $s['is_active'] ? 'فعال' : 'غیرفعال' ?></span></div>
        <a class="danger-link" onclick="return confirm('حذف شود؟')" href="?delete=<?= (int)$s['id'] ?>">حذف</a>
    </div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
