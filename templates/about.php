<?php
$pageTitle = 'درباره VaL3R ایران';
$content = page_content($pdo, 'about');
include __DIR__ . '/header.php';
?>

<section class="v5-about-hero">
    <div class="container v5-about-hero-grid">
        <div>
            <span class="v5-eyebrow">About VaL3R Iran</span>
            <h1>نمایندگی رسمی والر در ایران</h1>
            <p>VaL3R ایران با تمرکز بر معرفی و فروش محصولات لوازم شخصی برقی، تجربه‌ای روشن، قابل اعتماد و حرفه‌ای برای خرید آنلاین فراهم می‌کند.</p>
        </div>
        <div class="v5-about-brand-card">
            <strong>VaL3R</strong>
            <span>Official Representative Experience</span>
        </div>
    </div>
</section>

<section class="v5-section">
    <div class="container v5-about-grid">
        <div class="v5-about-card large">
            <span class="v5-eyebrow">Brand Story</span>
            <h2>سادگی، کیفیت و اعتماد</h2>
            <?php if ($content): ?>
                <p><?= nl2br(e($content)) ?></p>
            <?php else: ?>
                <p>هدف ما ایجاد یک تجربه خرید تمیز، سریع و قابل اعتماد برای محصولات VaL3R است؛ جایی که کاربر بدون پیچیدگی محصول مناسب خود را پیدا کند، سفارش دهد و وضعیت خرید خود را دنبال کند.</p>
            <?php endif; ?>
        </div>

        <div class="v5-about-card">
            <h3>تجربه خرید سریع</h3>
            <p>انتخاب محصول، افزودن به سبد و ثبت سفارش با کمترین مراحل و طراحی موبایل‌پسند.</p>
        </div>

        <div class="v5-about-card">
            <h3>تمرکز روی محصول</h3>
            <p>طراحی مینیمال و روشن باعث می‌شود محصول، توضیحات و قیمت در مرکز توجه کاربر باشند.</p>
        </div>

        <div class="v5-about-card">
            <h3>پشتیبانی و پیگیری</h3>
            <p>کاربران می‌توانند سفارش‌های خود را در پنل کاربری مشاهده و وضعیت سفارش را پیگیری کنند.</p>
        </div>
    </div>
</section>

<section class="v5-brand-story">
    <div class="container v5-story-card">
        <span>VaL3R Vision</span>
        <h2>فروشگاه رسمی، با تجربه‌ای شبیه برندهای جهانی.</h2>
        <p>ظاهر ساده، عملکرد سریع و مسیر خرید شفاف، سه اصل اصلی تجربه VaL3R ایران هستند.</p>
    </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
