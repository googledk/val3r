<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../classes/DigikalaImporter.php';
require_admin();

$error = '';
$success = '';
$preview = null;

$cats = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();

function admin_unique_slug(PDO $pdo, string $slug): string {
    $base = $slug ?: ('product-' . time());
    $slug = $base;
    $i = 2;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) return $slug;
        $slug = $base . '-' . $i;
        $i++;
    }
}

if (is_post() && post('action') === 'fetch') {
    try {
        $dkp = DigikalaImporter::extractDkp(post('digikala_url'));
        $preview = DigikalaImporter::fetchProduct($dkp);
        $_SESSION['dk_import_preview'] = $preview;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

if (is_post() && post('action') === 'save') {
    try {
        $data = $_SESSION['dk_import_preview'] ?? null;
        if (!$data) {
            throw new RuntimeException('اطلاعات پیش‌نمایش پیدا نشد. دوباره لینک را بررسی کن.');
        }

        $categoryId = (int)post('category_id');
        $name = trim(post('name'));
        $slug = admin_unique_slug($pdo, trim(post('slug')));
        $short = trim(post('short_description'));
        $desc = trim(post('description'));
        $price = (int)preg_replace('/\D+/', '', (string)post('price'));
        $oldPrice = (int)preg_replace('/\D+/', '', (string)post('old_price'));
        if ($oldPrice <= $price) $oldPrice = 0;
        $stock = (int)post('stock');
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $active = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') throw new RuntimeException('نام محصول الزامی است.');

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO products (category_id, name, slug, short_description, description, price, old_price, stock, is_featured, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$categoryId, $name, $slug, $short, $desc, $price, $oldPrice ?: null, $stock, $featured, $active]);
        $productId = (int)$pdo->lastInsertId();

        $selectedImages = $_POST['images'] ?? [];
        $selectedImages = array_values(array_filter($selectedImages, fn($u) => is_string($u) && str_starts_with($u, 'http')));

        $mainImageUrl = trim((string)($_POST['main_image_url'] ?? ''));
        if ($mainImageUrl !== '' && in_array($mainImageUrl, $selectedImages, true)) {
            $selectedImages = array_values(array_unique(array_merge([$mainImageUrl], $selectedImages)));
        } else {
            $selectedImages = array_values(array_unique($selectedImages));
        }

        $sort = 0;
        foreach ($selectedImages as $imgUrl) {
            $file = DigikalaImporter::downloadImage($imgUrl, UPLOAD_PRODUCTS_DIR);
            if ($file) {
                $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?)")
                    ->execute([$productId, $file, $sort === 0 ? 1 : 0, $sort]);
                $sort++;
            }
        }

        foreach ($_POST['spec_key'] ?? [] as $i => $key) {
            $key = trim((string)$key);
            $value = trim((string)($_POST['spec_value'][$i] ?? ''));
            if ($key !== '' && $value !== '') {
                $pdo->prepare("INSERT INTO product_specs (product_id, spec_key, spec_value, sort_order) VALUES (?, ?, ?, ?)")
                    ->execute([$productId, $key, $value, $i + 1]);
            }
        }

        $pdo->commit();

        unset($_SESSION['dk_import_preview']);
        redirect(BASE_URL . 'admin/product_form.php?id=' . $productId . '&saved=1');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
        $preview = $_SESSION['dk_import_preview'] ?? null;
    }
}

$adminTitle = 'ایمپورت از دیجی‌کالا';
include __DIR__ . '/_header.php';
?>

<div class="page-head">
    <div>
        <h1>ایمپورت محصول از دیجی‌کالا</h1>
        <p>لینک محصول یا کد DKP را وارد کن تا عنوان، توضیحات، مشخصات فنی و تصاویر آماده ورود شوند.</p>
    </div>
    <a class="admin-btn ghost" href="products.php">محصولات</a>
</div>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>

<section class="panel dk-import-panel">
    <h2>مرحله ۱: دریافت اطلاعات</h2>
    <form method="post" class="dk-fetch-form">
        <input type="hidden" name="action" value="fetch">
        <label>لینک محصول دیجی‌کالا یا DKP
            <input name="digikala_url" placeholder="https://www.digikala.com/product/dkp-123456/ یا 123456" value="<?= e(post('digikala_url')) ?>">
        </label>
        <button class="admin-btn" type="submit">خواندن اطلاعات محصول</button>
    </form>
</section>

<?php if ($preview): ?>
<form method="post" class="dk-preview-form">
    <input type="hidden" name="action" value="save">

    <section class="panel dk-product-review-panel">
        <div class="dk-review-head">
            <div>
                <span>Step 2</span>
                <h2>مرحله ۲: بررسی و ویرایش قبل از ذخیره</h2>
                <p>اطلاعات محصول را مرتب کن، قیمت‌ها را بررسی کن و بعد ذخیره کن.</p>
            </div>
            <a class="admin-btn ghost" target="_blank" href="<?= e($preview['source_url']) ?>">مشاهده در دیجی‌کالا</a>
        </div>

        <div class="dk-product-editor-grid">
            <div class="dk-editor-main">
                <label>نام محصول
                    <input name="name" required value="<?= e($preview['title']) ?>">
                </label>

                <label>توضیح کوتاه
                    <textarea name="short_description" rows="4"><?= e($preview['short_description']) ?></textarea>
                </label>

                <label>توضیحات کامل
                    <textarea name="description" rows="9"><?= e($preview['description']) ?></textarea>
                </label>
            </div>

            <aside class="dk-editor-side">
                <label>دسته‌بندی
                    <select name="category_id">
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>اسلاگ
                    <input name="slug" required value="<?= e($preview['slug']) ?>">
                </label>

                <div class="dk-price-grid">
                    <label>قیمت قبل تخفیف
                        <input class="money-input" inputmode="numeric" name="old_price" value="<?= $preview['old_price'] ? number_format((int)$preview['old_price']) : '' ?>" placeholder="مثلاً 1,900,000">
                    </label>

                    <label>قیمت فروش
                        <input class="money-input" inputmode="numeric" name="price" value="<?= number_format((int)$preview['price']) ?>" placeholder="مثلاً 1,490,000">
                    </label>
                </div>

                <label>موجودی
                    <input type="number" name="stock" value="<?= (int)$preview['stock'] ?>">
                </label>

                <div class="dk-discount-preview" data-discount-preview>
                    <span>پیش‌نمایش قیمت</span>
                    <del data-old-price-preview></del>
                    <strong data-price-preview></strong>
                    <b data-discount-percent></b>
                </div>

                <div class="check-row dk-checks">
                    <label><input type="checkbox" name="is_featured"> محصول منتخب</label>
                    <label><input type="checkbox" name="is_active" checked> فعال</label>
                </div>
            </aside>
        </div>
    </section>

    <section class="panel full dk-modern-images-panel">
        <div class="panel-head">
            <div>
                <h2>تصاویر محصول</h2>
                <p>تصاویر به صورت بندانگشتی نمایش داده می‌شوند، اما هنگام ذخیره با کیفیت اصلی داخل سایت دانلود می‌شوند.</p>
            </div>
            <div class="dk-image-tools">
                <button type="button" class="admin-btn ghost" onclick="dkSelectAllImages()">انتخاب همه</button>
                <button type="button" class="admin-btn ghost" onclick="dkClearImages()">لغو انتخاب</button>
            </div>
        </div>

        <?php if (!$preview['images']): ?>
            <div class="alert error">تصویری از دیجی‌کالا دریافت نشد.</div>
        <?php else: ?>
            <input type="hidden" name="main_image_url" id="dkMainImageUrl" value="<?= e($preview['images'][0] ?? '') ?>">

            <div class="dk-gallery-help">
                <strong>انتخاب تصویر اصلی:</strong>
                روی دایره «اصلی» کلیک کن. تصاویر تیک‌دار وارد گالری می‌شوند.
            </div>

            <div class="dk-image-grid modern" id="dkImageGrid">
                <?php foreach ($preview['images'] as $i => $img): ?>
                    <div class="dk-image-card <?= $i === 0 ? 'is-main' : '' ?>" data-image-card data-image-url="<?= e($img) ?>">
                        <label class="dk-check">
                            <input type="checkbox" name="images[]" value="<?= e($img) ?>" <?= $i < 8 ? 'checked' : '' ?>>
                            <span></span>
                        </label>

                        <button type="button" class="dk-main-btn" onclick="dkSetMainImage(this)" title="انتخاب تصویر اصلی">اصلی</button>

                        <div class="dk-thumb-box">
                            <img src="<?= e($img) ?>" alt="" loading="lazy">
                        </div>

                        <div class="dk-card-footer">
                            <strong><?= $i === 0 ? 'تصویر اصلی' : 'تصویر گالری' ?></strong>
                            <small>کیفیت اصلی ذخیره می‌شود</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel full dk-specs-panel">
        <div class="panel-head">
            <div>
                <h2>مشخصات فنی</h2>
                <p>مشخصات را قبل از ذخیره بررسی کن. هر موردی که نمی‌خواهی را حذف کن.</p>
            </div>
            <button type="button" class="admin-btn ghost" onclick="addDkSpec()">افزودن مشخصه</button>
        </div>

        <div id="dkSpecs" class="dk-specs-list">
            <?php foreach ($preview['specs'] as $s): ?>
                <?php
                    $specKey = trim($s['key'] ?? '');
                    if ($specKey === 'کد دیجی‌کالا') continue;
                ?>
                <div class="dk-spec-row">
                    <input name="spec_key[]" placeholder="عنوان مشخصه" value="<?= e($specKey) ?>">
                    <textarea name="spec_value[]" placeholder="مقدار" rows="3"><?= e($s['value']) ?></textarea>
                    <button type="button" onclick="this.closest('.dk-spec-row').remove()">حذف</button>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="editor-actions full">
        <button class="admin-btn big" type="submit">ذخیره محصول در سایت</button>
        <a class="admin-btn ghost" href="import_digikala.php">شروع دوباره</a>
    </section>
</form>

<script>
function addDkSpec(){
    document.getElementById('dkSpecs').insertAdjacentHTML('beforeend',
        '<div class="dk-spec-row"><input name="spec_key[]" placeholder="عنوان مشخصه"><textarea name="spec_value[]" placeholder="مقدار" rows="3"></textarea><button type="button" onclick="this.closest(\\'.dk-spec-row\\').remove()">حذف</button></div>'
    );
}

function dkSetMainImage(btn){
    const card = btn.closest('[data-image-card]');
    const grid = document.getElementById('dkImageGrid');
    if (!card || !grid) return;

    grid.querySelectorAll('[data-image-card]').forEach(el => el.classList.remove('is-main'));
    card.classList.add('is-main');

    const checkbox = card.querySelector('input[type="checkbox"]');
    if (checkbox) checkbox.checked = true;

    const hidden = document.getElementById('dkMainImageUrl');
    if (hidden) hidden.value = card.dataset.imageUrl || '';
}

function dkSelectAllImages(){
    document.querySelectorAll('#dkImageGrid input[type="checkbox"]').forEach(ch => ch.checked = true);
}

function dkClearImages(){
    document.querySelectorAll('#dkImageGrid input[type="checkbox"]').forEach(ch => {
        const card = ch.closest('[data-image-card]');
        ch.checked = card && card.classList.contains('is-main');
    });
}

document.querySelectorAll('.money-input').forEach(input => {
    input.addEventListener('input', () => {
        const raw = input.value.replace(/\D/g, '');
        input.value = raw ? Number(raw).toLocaleString('en-US') : '';
        dkUpdatePricePreview();
    });
});

function dkMoneyValue(selector){
    const el = document.querySelector(selector);
    if (!el) return 0;
    return Number((el.value || '').replace(/\D/g, '')) || 0;
}

function dkUpdatePricePreview(){
    const oldPrice = dkMoneyValue('input[name="old_price"]');
    const price = dkMoneyValue('input[name="price"]');
    const oldEl = document.querySelector('[data-old-price-preview]');
    const priceEl = document.querySelector('[data-price-preview]');
    const percentEl = document.querySelector('[data-discount-percent]');

    if (priceEl) priceEl.textContent = price ? price.toLocaleString('en-US') + ' تومان' : '';
    if (oldEl) {
        oldEl.textContent = oldPrice > price ? oldPrice.toLocaleString('en-US') + ' تومان' : '';
        oldEl.style.display = oldPrice > price ? 'block' : 'none';
    }
    if (percentEl) {
        const percent = oldPrice > price && price > 0 ? Math.round(((oldPrice - price) / oldPrice) * 100) : 0;
        percentEl.textContent = percent ? percent + '٪ تخفیف' : '';
        percentEl.style.display = percent ? 'inline-flex' : 'none';
    }
}

dkUpdatePricePreview();

</script>
<?php endif; ?>

<?php include __DIR__ . '/_footer.php'; ?>
