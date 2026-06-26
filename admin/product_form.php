<?php
require_once __DIR__ . '/_init.php';
require_admin();

$id = (int)get('id', 0);
$product = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) redirect(BASE_URL . 'admin/products.php');
}

if (get('delete_image') && $id) {
    $imageId = (int)get('delete_image');
    $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id=? AND product_id=?");
    $stmt->execute([$imageId, $id]);
    $img = $stmt->fetch();
    if ($img) {
        $file = UPLOAD_PRODUCTS_DIR . $img['image_path'];
        if ($img['image_path'] && is_file($file)) @unlink($file);
        $pdo->prepare("DELETE FROM product_images WHERE id=?")->execute([$imageId]);
    }
    redirect(BASE_URL . 'admin/product_form.php?id=' . $id);
}

$error = '';

if (is_post()) {
    try {
        $categoryId = (int)post('category_id');
        $name = trim(post('name'));
        $slug = trim(post('slug'));
        $short = trim(post('short_description'));
        $desc = trim(post('description'));
        $price = (int)preg_replace('/\D+/', '', (string)post('price'));
        $oldPrice = (int)preg_replace('/\D+/', '', (string)post('old_price'));
        if ($oldPrice <= $price) $oldPrice = 0;
        $stock = (int)post('stock');
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $active = isset($_POST['is_active']) ? 1 : 0;

        if (!$name || !$slug) throw new RuntimeException('نام محصول و اسلاگ الزامی است.');

        if ($id) {
            $stmt = $pdo->prepare("UPDATE products SET category_id=?, name=?, slug=?, short_description=?, description=?, price=?, old_price=?, stock=?, is_featured=?, is_active=? WHERE id=?");
            $stmt->execute([$categoryId, $name, $slug, $short, $desc, $price, $oldPrice ?: null, $stock, $featured, $active, $id]);
            $productId = $id;
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, short_description, description, price, old_price, stock, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$categoryId, $name, $slug, $short, $desc, $price, $oldPrice ?: null, $stock, $featured, $active]);
            $productId = (int)$pdo->lastInsertId();
        }

        $main = upload_image($_FILES['main_image'] ?? [], UPLOAD_PRODUCTS_DIR);
        if ($main) {
            $pdo->prepare("UPDATE product_images SET is_main=0 WHERE product_id=?")->execute([$productId]);
            $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, 1, 0)")->execute([$productId, $main]);
        }

        if (!empty($_FILES['gallery']['name'][0])) {
            foreach ($_FILES['gallery']['name'] as $i => $fileName) {
                $file = [
                    'name' => $_FILES['gallery']['name'][$i],
                    'type' => $_FILES['gallery']['type'][$i],
                    'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
                    'error' => $_FILES['gallery']['error'][$i],
                    'size' => $_FILES['gallery']['size'][$i],
                ];
                $img = upload_image($file, UPLOAD_PRODUCTS_DIR);
                if ($img) {
                    $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, 0, ?)")
                        ->execute([$productId, $img, $i + 1]);
                }
            }
        }

        $pdo->prepare("DELETE FROM product_specs WHERE product_id=?")->execute([$productId]);
        foreach ($_POST['spec_key'] ?? [] as $i => $key) {
            $key = trim($key);
            $val = trim($_POST['spec_value'][$i] ?? '');
            if ($key && $val) {
                $pdo->prepare("INSERT INTO product_specs (product_id, spec_key, spec_value, sort_order) VALUES (?, ?, ?, ?)")
                    ->execute([$productId, $key, $val, $i + 1]);
            }
        }

        redirect(BASE_URL . 'admin/product_form.php?id=' . $productId . '&saved=1');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$cats = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();

$specs = [];
$images = [];
$mainImage = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM product_specs WHERE product_id=? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$id]);
    $specs = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id=? ORDER BY is_main DESC, sort_order ASC, id ASC");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();
    $mainImage = $images[0]['image_path'] ?? null;
}
$adminTitle = $id ? 'ویرایش محصول' : 'افزودن محصول';
include __DIR__ . '/_header.php';
?>
<div class="page-head">
    <div>
        <h1><?= $id ? 'ویرایش محصول' : 'افزودن محصول' ?></h1>
        <p>اطلاعات محصول، تصویر، گالری و مشخصات فنی را مدیریت کنید.</p>
    </div>
    <a class="admin-btn ghost" href="products.php">بازگشت</a>
</div>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if (get('saved')): ?><div class="alert success">محصول با موفقیت ذخیره شد.</div><?php endif; ?>

<form class="product-editor" method="post" enctype="multipart/form-data">
    <section class="panel editor-main">
        <h2>اطلاعات اصلی</h2>
        <div class="form-grid">
            <label>دسته‌بندی
                <select name="category_id">
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (($product['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>نام محصول <input name="name" required value="<?= e($product['name'] ?? '') ?>"></label>
            <label>اسلاگ انگلیسی <input name="slug" required value="<?= e($product['slug'] ?? '') ?>"></label>
            <label>قیمت قبل تخفیف <input class="money-input" inputmode="numeric" name="old_price" value="<?= !empty($product['old_price']) ? number_format((int)$product['old_price']) : '' ?>"></label>
            <label>قیمت فروش تومان <input class="money-input" inputmode="numeric" name="price" value="<?= number_format((int)($product['price'] ?? 0)) ?>"></label>
            <label>موجودی <input type="number" name="stock" value="<?= e($product['stock'] ?? 0) ?>"></label>
            <label>توضیح کوتاه <input name="short_description" value="<?= e($product['short_description'] ?? '') ?>"></label>
        </div>
        <label>توضیحات کامل
            <textarea name="description" rows="8"><?= e($product['description'] ?? '') ?></textarea>
        </label>
        <div class="check-row">
            <label><input type="checkbox" name="is_featured" <?= !empty($product['is_featured']) ? 'checked' : '' ?>> محصول منتخب</label>
            <label><input type="checkbox" name="is_active" <?= !isset($product) || !empty($product['is_active']) ? 'checked' : '' ?>> فعال</label>
        </div>
    </section>

    <aside class="panel editor-side">
        <h2>تصویر محصول</h2>
        <img id="mainPreview" class="preview-image" src="<?= e(product_image_url($mainImage)) ?>" alt="">
        <label class="upload-box">انتخاب عکس اصلی
            <input type="file" name="main_image" accept="image/*" data-preview-input="#mainPreview">
        </label>
        <label class="upload-box">افزودن گالری
            <input type="file" name="gallery[]" accept="image/*" multiple>
        </label>
    </aside>

    <?php if ($images): ?>
    <section class="panel full">
        <h2>گالری تصاویر</h2>
        <div class="image-grid">
            <?php foreach ($images as $img): ?>
                <div>
                    <img src="<?= e(product_image_url($img['image_path'])) ?>" alt="">
                    <a class="danger-link" onclick="return confirm('عکس حذف شود؟')" href="?id=<?= $id ?>&delete_image=<?= (int)$img['id'] ?>">حذف</a>
                    <?= $img['is_main'] ? '<span class="badge green">اصلی</span>' : '' ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="panel full">
        <div class="panel-head">
            <h2>مشخصات فنی</h2>
            <button type="button" class="admin-btn ghost" onclick="addSpec()">افزودن مشخصه</button>
        </div>
        <div id="specs">
            <?php $specs = $specs ?: [['spec_key'=>'','spec_value'=>'']]; foreach ($specs as $s): ?>
                <div class="spec-line">
                    <input name="spec_key[]" placeholder="عنوان مشخصه" value="<?= e($s['spec_key']) ?>">
                    <input name="spec_value[]" placeholder="مقدار" value="<?= e($s['spec_value']) ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="editor-actions full">
        <button class="admin-btn big" type="submit">ذخیره محصول</button>
        <?php if ($id): ?><a class="admin-btn ghost" target="_blank" href="<?= BASE_URL ?>?page=product&id=<?= $id ?>">نمایش محصول</a><?php endif; ?>
    </section>
</form>

<script>
function addSpec(){
    document.getElementById('specs').insertAdjacentHTML('beforeend',
        '<div class="spec-line"><input name="spec_key[]" placeholder="عنوان مشخصه"><input name="spec_value[]" placeholder="مقدار"></div>'
    );
}

function formatAdminMoneyInputs(){
    document.querySelectorAll('.money-input').forEach(input => {
        input.addEventListener('input', () => {
            const raw = input.value.replace(/\D/g, '');
            input.value = raw ? Number(raw).toLocaleString('en-US') : '';
        });
    });
}
formatAdminMoneyInputs();
</script>
<?php include __DIR__ . '/_footer.php'; ?>
