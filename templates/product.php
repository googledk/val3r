<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    $pageTitle = 'محصول پیدا نشد';
    $cleanDescription = clean_product_description($product['description'] ?? '');

include __DIR__ . '/header.php';
    echo '<section class="v5-page-hero"><div class="container"><h1>محصول پیدا نشد</h1><p>این محصول در دسترس نیست.</p></div></section>';
    include __DIR__ . '/footer.php';
    exit;
}

$pageTitle = $product['name'] . ' | VaL3R ایران';

$imagesStmt = $pdo->prepare("
    SELECT image_path
    FROM product_images
    WHERE product_id = ?
    ORDER BY is_main DESC, sort_order ASC, id ASC
");
$imagesStmt->execute([$id]);
$images = $imagesStmt->fetchAll();

$specStmt = $pdo->prepare("
    SELECT spec_key, spec_value
    FROM product_specs
    WHERE product_id = ?
    ORDER BY sort_order ASC, id ASC
");
$specStmt->execute([$id]);
$specs = $specStmt->fetchAll();

$mainImage = $images[0]['image_path'] ?? null;
$productCartQty = (int)(cart_items()[(int)$product['id']] ?? 0);
$related = get_related_products($pdo, (int)$product['id'], $product['category_id'] ? (int)$product['category_id'] : null, 4);

$cleanDescription = clean_product_description($product['description'] ?? '');

include __DIR__ . '/header.php';
?>

<section class="v5-product-page">
    <div class="container v5-product-layout">
        <div class="v5-product-gallery">
            <div class="v5-product-main-image">
                <img class="main-product-image" src="<?= e(product_image_url($mainImage)) ?>" alt="<?= e($product['name']) ?>">
            </div>

            <div class="v5-product-thumbs">
                <img src="<?= e(product_image_url($mainImage)) ?>" alt="<?= e($product['name']) ?>" data-thumb>
                <?php foreach ($images as $img): ?>
                    <?php if (($img['image_path'] ?? '') !== $mainImage): ?>
                        <img src="<?= e(product_image_url($img['image_path'])) ?>" alt="<?= e($product['name']) ?>" data-thumb>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="v5-product-info v75-product-info">
            <?php $productDiscount = discount_percent($product['old_price'] ?? 0, $product['price'] ?? 0); ?>

            <div class="v75-product-topline">
                <span class="v5-eyebrow"><?= e($product['category_name'] ?? 'VaL3R Product') ?></span>
                <?php if ($productDiscount > 0): ?>
                    <b class="v75-discount-pill"><?= $productDiscount ?>٪ تخفیف</b>
                <?php endif; ?>
            </div>

            <h1><?= e($product['name']) ?></h1>

            <div class="v75-buy-card">
                <div class="v75-price-row">
                    <div>
                        <span class="v75-price-label">قیمت نهایی</span>
                        <?= price_block($product['old_price'] ?? 0, $product['price'] ?? 0, 'product') ?>
                    </div>
                    <?php if ((int)$product['stock'] > 0): ?>
                        <span class="v75-stock ok">آماده ارسال</span>
                    <?php else: ?>
                        <span class="v75-stock no">ناموجود</span>
                    <?php endif; ?>
                </div>

                <div class="v75-benefits">
                    <div>
                        <strong>اصالت کالا</strong>
                        <span>تضمین کیفیت</span>
                    </div>
                    <div>
                        <strong>ثبت آنلاین</strong>
                        <span>سریع و امن</span>
                    </div>
                    <div>
                        <strong>پشتیبانی</strong>
                        <span>پیگیری سفارش</span>
                    </div>
                </div>
            </div>

            <?php if ((int)$product['stock'] > 0): ?>
                <form class="v5-product-cart-form" method="post" action="<?= BASE_URL ?>ajax/cart.php?action=add" data-product-cart-form data-cart-url="<?= BASE_URL ?>?page=cart">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="set_qty" value="<?= $productCartQty > 0 ? '1' : '0' ?>" data-set-qty>
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

                    <div class="product-qty-box" data-product-qty-box <?= $productCartQty > 0 ? '' : 'hidden' ?>>
                        <button type="button" data-product-qty-minus aria-label="کم کردن تعداد">−</button>
                        <input type="number" name="quantity" value="<?= max(1, $productCartQty ?: 1) ?>" min="1" max="<?= max(1, (int)$product['stock']) ?>" data-product-qty>
                        <button type="button" data-product-qty-plus aria-label="زیاد کردن تعداد">+</button>
                    </div>

                    <button class="product-remove-btn" type="button" data-product-remove-btn <?= $productCartQty > 0 ? '' : 'hidden' ?> aria-label="حذف از سبد خرید">×</button>

                    <button class="v5-product-add-btn" type="submit" data-product-add-btn <?= $productCartQty > 0 ? 'data-added="1"' : '' ?>>
                        <?= $productCartQty > 0 ? 'ادامه خرید' : 'افزودن به سبد خرید' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="v5-section soft">
    <div class="container v5-product-details">
        <div>
            <span class="v5-eyebrow">Description</span>
            <h2>توضیحات محصول</h2>
            <div class="v5-product-description-text">
                <?php foreach (preg_split("/
{2,}/", $cleanDescription) as $paragraph): ?>
                    <?php $paragraph = trim($paragraph); if ($paragraph === '') continue; ?>
                    <p><?= nl2br(e($paragraph)) ?></p>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <span class="v5-eyebrow">Specifications</span>
            <h2>مشخصات فنی</h2>
            <?php if ($specs): ?>
                <div class="v5-specs v76-specs">
                    <?php foreach ($specs as $spec): ?>
                        <?php
                            $specValueRaw = trim((string)$spec['spec_value']);
                            $specValues = preg_split('/\r\n|\r|\n|،\s*/u', $specValueRaw);
                            $specValues = array_values(array_filter(array_map('trim', $specValues)));
                        ?>
                        <div class="v76-spec-row">
                            <strong><?= e($spec['spec_key']) ?></strong>
                            <span>
                                <?php if (count($specValues) > 1): ?>
                                    <?php foreach ($specValues as $v): ?>
                                        <b><?= e($v) ?></b>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?= e($specValueRaw) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>هنوز مشخصات فنی برای این محصول ثبت نشده است.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($related): ?>
<section class="v5-section">
    <div class="container">
        <div class="v5-section-head">
            <div><span>Related Products</span><h2>محصولات مرتبط</h2></div>
            <a href="<?= BASE_URL ?>?page=products">همه محصولات</a>
        </div>
        <div class="v5-product-grid">
            <?php foreach ($related as $p): render_product_card($pdo, $p); endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
