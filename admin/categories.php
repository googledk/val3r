<?php
require_once __DIR__ . '/_init.php';
require_admin();

if (is_post()) {
    $id = (int)post('id', 0);
    $name = trim(post('name'));
    $slug = trim(post('slug'));
    $sort = (int)post('sort_order', 0);
    $active = isset($_POST['is_active']) ? 1 : 0;

    if ($name && $slug) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $slug, $sort, $active, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, sort_order, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $sort, $active]);
        }
    }
    redirect(BASE_URL . 'admin/categories.php');
}

if (get('delete')) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
    $stmt->execute([(int)get('delete')]);
    redirect(BASE_URL . 'admin/categories.php');
}

$edit = null;
if (get('edit')) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([(int)get('edit')]);
    $edit = $stmt->fetch();
}

$cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, id DESC")->fetchAll();
$adminTitle = 'مدیریت دسته‌بندی‌ها';
include __DIR__ . '/_header.php';
?>
<div class="page-head"><div><h1>دسته‌بندی‌ها</h1><p>ساخت و ویرایش دسته‌بندی محصولات</p></div></div>

<form class="panel admin-form" method="post">
    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
    <div class="form-grid">
        <label>نام <input name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
        <label>اسلاگ انگلیسی <input name="slug" required value="<?= e($edit['slug'] ?? '') ?>"></label>
        <label>ترتیب <input type="number" name="sort_order" value="<?= e($edit['sort_order'] ?? 0) ?>"></label>
    </div>
    <label class="check-inline"><input type="checkbox" name="is_active" <?= !isset($edit) || !empty($edit['is_active']) ? 'checked' : '' ?>> فعال</label>
    <button class="admin-btn"><?= $edit ? 'ذخیره ویرایش' : 'افزودن دسته‌بندی' ?></button>
</form>

<div class="table-wrap">
<table><tr><th>نام</th><th>اسلاگ</th><th>ترتیب</th><th>وضعیت</th><th>عملیات</th></tr>
<?php foreach ($cats as $c): ?>
<tr>
<td><?= e($c['name']) ?></td><td><?= e($c['slug']) ?></td><td><?= (int)$c['sort_order'] ?></td>
<td><?= $c['is_active'] ? '<span class="badge green">فعال</span>' : '<span class="badge gray">غیرفعال</span>' ?></td>
<td class="actions"><a href="?edit=<?= (int)$c['id'] ?>">ویرایش</a> <a class="danger-link" onclick="return confirm('حذف شود؟')" href="?delete=<?= (int)$c['id'] ?>">حذف</a></td>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
