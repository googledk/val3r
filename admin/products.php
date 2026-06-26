<?php
require_once __DIR__ . '/_init.php';
require_admin();

if (get('delete')) {
    $id = (int)get('delete');
    $imgs = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id=?");
    $imgs->execute([$id]);
    foreach ($imgs->fetchAll() as $img) {
        $file = UPLOAD_PRODUCTS_DIR . $img['image_path'];
        if ($img['image_path'] && is_file($file)) @unlink($file);
    }
    $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
    $stmt->execute([$id]);
    redirect(BASE_URL . 'admin/products.php');
}

$q = trim(get('q'));
$params = [];
$sql = "SELECT p.*, c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE 1";
if ($q !== '') {
    $sql .= " AND (p.name LIKE ? OR p.slug LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$adminTitle = 'مدیریت محصولات';
include __DIR__ . '/_header.php';
?>
<div class="page-head">
    <div>
        <h1>محصولات</h1>
        <p>افزودن، ویرایش، حذف و مدیریت موجودی محصولات VaL3R</p>
    </div>
    <a class="admin-btn" href="product_form.php">افزودن محصول</a>
</div>

<form class="admin-search" method="get">
    <input name="q" value="<?= e($q) ?>" placeholder="جستجوی نام یا اسلاگ محصول...">
    <button>جستجو</button>
</form>

<div class="table-wrap">
<table>
<tr>
    <th>تصویر</th><th>نام</th><th>دسته</th><th>قیمت</th><th>موجودی</th><th>وضعیت</th><th>عملیات</th>
</tr>
<?php foreach ($products as $p): $img = get_primary_image($pdo, (int)$p['id']); ?>
<tr>
<td><img class="admin-thumb" src="<?= e(product_image_url($img)) ?>" alt=""></td>
<td><strong><?= e($p['name']) ?></strong><small><?= e($p['slug']) ?></small></td>
<td><?= e($p['category_name'] ?? '-') ?></td>
<td><?= money($p['price']) ?></td>
<td><span class="<?= (int)$p['stock'] <= 3 ? 'badge red' : 'badge' ?>"><?= (int)$p['stock'] ?></span></td>
<td><?= $p['is_active'] ? '<span class="badge green">فعال</span>' : '<span class="badge gray">غیرفعال</span>' ?></td>
<td class="actions">
    <a href="<?= BASE_URL ?>?page=product&id=<?= (int)$p['id'] ?>" target="_blank">نمایش</a>
    <a href="product_form.php?id=<?= (int)$p['id'] ?>">ویرایش</a>
    <a class="danger-link" onclick="return confirm('محصول حذف شود؟')" href="?delete=<?= (int)$p['id'] ?>">حذف</a>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
