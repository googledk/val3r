<?php
$pageTitle = 'VaL3R ایران | فروشگاه رسمی والر';
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 8")->fetchAll();
$featured = $pdo->query("SELECT * FROM products WHERE is_active = 1 AND is_featured = 1 ORDER BY created_at DESC LIMIT 4")->fetchAll();
$newProducts = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT 8")->fetchAll();
include __DIR__ . '/header.php';
?>


<?php
$homeSlides = [];
try {
    $homeSlides = $pdo->query("SELECT * FROM sliders WHERE is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT 6")->fetchAll();
} catch (Throwable $e) {
    $homeSlides = [];
}
if (!$homeSlides) {
    $homeSlides = [[
        'title' => 'قدرت، دقت و زیبایی در مراقبت شخصی روزانه.',
        'subtitle' => 'فروشگاه رسمی محصولات لوازم شخصی برقی VaL3R در ایران؛ طراحی مینیمال، خرید سریع و تجربه‌ای در سطح برندهای جهانی.',
        'button_text' => 'مشاهده محصولات',
        'button_link' => BASE_URL . '?page=products',
        'image_path' => null,
    ]];
}
?>
<section class="v84-home-slider" data-v84-slider>
    <div class="v84-slider-bg"></div>
    <div class="container v84-slider-shell">
        <div class="v84-slider-track">
            <?php foreach ($homeSlides as $i => $slide): ?>
                <?php
                    $title = $slide['title'] ?? 'VaL3R';
                    $subtitle = $slide['subtitle'] ?? ($slide['description'] ?? '');
                    $btnText = $slide['button_text'] ?? 'مشاهده محصولات';
                    $btnLink = $slide['button_link'] ?? (BASE_URL . '?page=products');
                    $img = $slide['image_path'] ?? null;
                ?>
                <article class="v84-slide <?= $i === 0 ? 'active' : '' ?>" data-v84-slide>
                    <div class="v84-slide-copy">
                        <span class="v5-eyebrow">Official VaL3R Iran</span>
                        <h1><?= e($title) ?></h1>
                        <?php if ($subtitle): ?><p><?= e($subtitle) ?></p><?php endif; ?>
                        <div class="v84-slide-actions">
                            <a class="v84-btn primary" href="<?= e($btnLink) ?>"><?= e($btnText ?: 'مشاهده محصولات') ?></a>
                            <a class="v84-btn ghost" href="<?= BASE_URL ?>?page=about">درباره برند</a>
                        </div>
                    </div>
                    <div class="v84-slide-visual">
                        <?php if ($img): ?>
                            <img src="<?= e(slider_image_url($img)) ?>" alt="<?= e($title) ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
                        <?php else: ?>
                            <div class="v84-fallback-product"><strong>VaL3R</strong><span>Premium Grooming</span></div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (count($homeSlides) > 1): ?>
            <div class="v84-slider-controls">
                <button type="button" data-v84-prev aria-label="اسلاید قبلی">‹</button>
                <div class="v84-slider-dots">
                    <?php foreach ($homeSlides as $i => $slide): ?>
                        <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" data-v84-dot="<?= $i ?>" aria-label="اسلاید <?= $i + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
                <button type="button" data-v84-next aria-label="اسلاید بعدی">›</button>
            </div>
            <div class="v84-slider-progress"><span data-v84-progress></span></div>
        <?php endif; ?>
    </div>
</section>

<section class="v5-strip">
    <div class="container v5-category-strip">
        <a class="active" href="<?= BASE_URL ?>?page=products">همه محصولات</a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL ?>?page=products&category=<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($featured): ?>
<section class="v5-section">
    <div class="container">
        <div class="v5-section-head">
            <div><span>Selected by VaL3R</span><h2>محصولات منتخب</h2></div>
            <a href="<?= BASE_URL ?>?page=products">همه محصولات</a>
        </div>
        <div class="v5-product-grid featured">
            <?php foreach ($featured as $p) render_product_card($pdo, $p); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="v5-section soft">
    <div class="container">
        <div class="v5-section-head">
            <div><span>New Arrivals</span><h2>جدیدترین محصولات</h2></div>
            <a href="<?= BASE_URL ?>?page=products">مشاهده فروشگاه</a>
        </div>
        <div class="v5-product-grid">
            <?php foreach ($newProducts as $p) render_product_card($pdo, $p); ?>
        </div>
    </div>
</section>

<section class="v5-brand-story">
    <div class="container v5-story-card">
        <span>VaL3R Iran</span>
        <h2>فروشگاه رسمی، ساده‌تر از همیشه.</h2>
        <p>تمرکز ما روی تجربه خرید سریع، روشن، قابل اعتماد و حرفه‌ای است؛ جایی که محصول در مرکز توجه قرار دارد.</p>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
